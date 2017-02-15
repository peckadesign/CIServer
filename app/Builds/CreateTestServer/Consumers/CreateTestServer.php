<?php

namespace CI\Builds\CreateTestServer\Consumers;

class CreateTestServer implements \Kdyby\RabbitMq\IConsumer
{

	/**
	 * @var string
	 */
	private $binDir;

	/**
	 * @var \Monolog\Logger
	 */
	private $logger;

	/**
	 * @var \CI\Builds\CreateTestServer\CreateTestServersRepository
	 */
	private $createTestServersRepository;

	/**
	 * @var \CI\Builds\CreateTestServer\StatusPublicator
	 */
	private $statusPublicator;

	/**
	 * @var \Kdyby\Github\Client
	 */
	private $gitHub;

	/**
	 * @var \CI\User\UsersRepository
	 */
	private $usersRepository;

	/**
	 * @var \Kdyby\RabbitMq\IProducer
	 */
	private $runTestsProducer;


	public function __construct(
		string $binDir,
		\Monolog\Logger $logger,
		\CI\Builds\CreateTestServer\CreateTestServersRepository $createTestServersRepository,
		\CI\Builds\CreateTestServer\StatusPublicator $statusPublicator,
		\Kdyby\Github\Client $gitHub,
		\CI\User\UsersRepository $usersRepository,
		\Kdyby\RabbitMq\IProducer $runTestsProducer
	) {
		$this->binDir = $binDir;
		$this->logger = $logger;
		$this->createTestServersRepository = $createTestServersRepository;
		$this->statusPublicator = $statusPublicator;
		$this->gitHub = $gitHub;
		$this->usersRepository = $usersRepository;
		$this->runTestsProducer = $runTestsProducer;
	}


	public function process(\PhpAmqpLib\Message\AMQPMessage $message)
	{
		$hookId = $message->getBody();
		$build = $this->createTestServersRepository->getById($hookId);

		if ( ! $build) {
			return self::MSG_REJECT;
		}

		$conditions = [
			'systemUser' => TRUE,
		];
		$systemUser = $this->usersRepository->getBy($conditions);

		$build->start = new \DateTime();
		$build->finish = NULL;
		$build->output = '';
		$this->createTestServersRepository->persistAndFlush($build);

		$this->statusPublicator->publish($build);

		$databaseFiles = [];
		try {
			$this->gitHub->setAccessToken($systemUser->gitHubToken);
			$pullRequestFiles = $this->gitHub->api('/repos/peckadesign/' . $build->repository->name . '/pulls/' . $build->pullRequestNumber . '/files');
			foreach ($pullRequestFiles as $pullRequestFile) {
				if (strpos($pullRequestFile->filename, '.sql') !== FALSE) {
					$databaseFiles[] = $pullRequestFile->filename;
				}
			}
		} catch (\Kdyby\Github\ApiException $e) {
			$this->logger->addWarning($e->getMessage());
		}

		try {
			$success = TRUE;
			$cmd = sprintf('./createTest.sh -r %s -i %d -b %s', strtolower($build->repository->name), $build->pullRequestNumber, $build->branchName);
			$this->runProcess($build, $cmd);

			$defaultLocalNeonPath = '/var/www/' . strtolower($build->repository->name) . '/local.neon';
			if (is_readable($defaultLocalNeonPath)) {
				$testName = 'test' . $build->pullRequestNumber;
				if ($databaseFiles) {
					$cmd = sprintf('sed "s/testX/%s/" < %s > /var/www/%s/%s/app/config/local.neon', $testName, $defaultLocalNeonPath, strtolower($build->repository->name), $testName);
				} else {
					$cmd = sprintf('sed "s/testX/%s/" < %s > /var/www/%s/%s/app/config/local.neon', 'staging', $defaultLocalNeonPath, strtolower($build->repository->name), $testName);
				}
			}

			$this->runProcess($build, $cmd);

			chdir('/var/www/' . strtolower($build->repository->name) . '/' . $testName);

			if (is_readable('Makefile') && ($content = file_get_contents('Makefile')) && strpos($content, 'run-tests:') !== FALSE) {
				$this->logger->addInfo('Instance obsahuje testy, budou spuštěny');
				$this->runTestsProducer->publish(\Nette\Utils\Json::encode(['repositoryName' => strtolower($build->repository->name), 'instanceDirectory' => $testName]));
			}

			try {
				$client = new \GuzzleHttp\Client();
				$response = $client->request('GET', 'http://' . strtolower($build->repository->name) . '.' . $testName . '.peckadesign.com');

				if ($response->getStatusCode() !== 200) {
					$success = FALSE;
				}
			} catch (\GuzzleHttp\Exception\RequestException $e) {
				$success = FALSE;
			}

		} catch (\Symfony\Component\Process\Exception\RuntimeException $e) {
			$success = FALSE;
		} finally {
			$build->finish = new \DateTime();
			$build->success = $success;
			$this->createTestServersRepository->persistAndFlush($build);
		}

		$this->statusPublicator->publish($build);

		if ($success) {
			return self::MSG_ACK;
		} else {
			return self::MSG_REJECT_REQUEUE;
		}
	}


	private function runProcess(\CI\Builds\CreateTestServer\CreateTestServer $build, string $cmd)
	{
		$build->output .= trim($cmd) . "\n";
		$env = [
			'HOME' => getenv('HOME'),
		];
		$process = new \Symfony\Component\Process\Process($cmd, $this->binDir, $env, NULL, NULL);
		try {
			$cb = function (string $type, string $buffer) use ($build) {
				$build->output .= $buffer;
				$this->createTestServersRepository->persistAndFlush($build);
			};
			$process->mustRun($cb);
		} catch (\Symfony\Component\Process\Exception\RuntimeException $e) {
			$build->output .= $e->getMessage();
			$this->createTestServersRepository->persistAndFlush($build);

			$this->logger->addError($e->getMessage());

			throw $e;
		}
	}
}
