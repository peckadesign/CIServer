<?php

namespace CI\Builds\CreateTestServer\Consumers;

class CreateTestServer implements \Kdyby\RabbitMq\IConsumer
{

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

	/**
	 * @var \Kdyby\RabbitMq\IProducer
	 */
	private $runPhpCsProducer;


	public function __construct(
		\Monolog\Logger $logger,
		\CI\Builds\CreateTestServer\CreateTestServersRepository $createTestServersRepository,
		\CI\Builds\CreateTestServer\StatusPublicator $statusPublicator,
		\Kdyby\Github\Client $gitHub,
		\CI\User\UsersRepository $usersRepository,
		\Kdyby\RabbitMq\IProducer $runTestsProducer,
		\Kdyby\RabbitMq\IProducer $runPhpCsProducer
	) {
		$this->logger = $logger;
		$this->createTestServersRepository = $createTestServersRepository;
		$this->statusPublicator = $statusPublicator;
		$this->gitHub = $gitHub;
		$this->usersRepository = $usersRepository;
		$this->runTestsProducer = $runTestsProducer;
		$this->runPhpCsProducer = $runPhpCsProducer;
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

			$this->runProcess($build, 'OLD_DIR=`pwd` && cd .. && rm -rf $OLD_DIR && cp -RpP staging $OLD_DIR');

			$this->runProcess($build, 'test -d temp/ && git clean -xdf temp/ || true');
			$this->runProcess($build, 'test -d log/ && git clean -xdf log/ || true');
			$this->runProcess($build, 'git reset origin/master --hard');

			$this->runProcess($build, 'git fetch --prune');
			$this->runProcess($build, 'git checkout ' . $build->branchName);
			$this->runProcess($build, 'test -d temp/ && chmod -R 0777 temp/ || true');
			$this->runProcess($build, 'test -d log/ && chmod -R 0777 log/ || true');

			$this->runProcess($build, 'test -f Makefile && cat Makefile | grep -q "clean:" && make clean || true');
			$this->runProcess($build, 'test -f Makefile && cat Makefile | grep -q "build-staging:" && HOME=/home/' . get_current_user() . ' make build-staging || true');

			$defaultLocalNeonPath = '/var/www/' . strtolower($build->repository->name) . '/local.neon';
			if (is_readable($defaultLocalNeonPath)) {
				$testName = 'test' . $build->pullRequestNumber;
				if ($databaseFiles) {
					$cmd = sprintf('sed "s/testX/%s/" < %s > /var/www/%s/%s/app/config/local.neon', $testName, $defaultLocalNeonPath, strtolower($build->repository->name), $testName);
				} else {
					$cmd = sprintf('sed "s/testX/%s/" < %s > /var/www/%s/%s/app/config/local.neon', 'staging', $defaultLocalNeonPath, strtolower($build->repository->name), $testName);
				}
				$this->runProcess($build, $cmd);
			}

			chdir('/var/www/' . strtolower($build->repository->name) . '/' . $testName);

			if (is_readable('Makefile') && ($content = file_get_contents('Makefile')) && strpos($content, 'run-tests:') !== FALSE) {
				$this->logger->addInfo('Instance obsahuje testy, budou spuštěny');
				$this->runTestsProducer->publish(\Nette\Utils\Json::encode(['repositoryName' => strtolower($build->repository->name), 'instanceDirectory' => $testName]));
			}

			if (is_readable('Makefile') && ($content = file_get_contents('Makefile')) && strpos($content, 'cs:') !== FALSE) {
				$publishData = \Nette\Utils\Json::encode(['repositoryName' => strtolower($build->repository->name), 'instanceDirectory' => $testName]);
				$this->logger->addInfo('Instance obsahuje coding standard, bude spuštěn: ' . $publishData);
				$this->runPhpCsProducer->publish($publishData);
			}

			try {
				$client = new \GuzzleHttp\Client();
				$testUrl = 'http://' . strtolower($build->repository->name) . '.' . $testName . '.peckadesign.com';
				$response = $client->request('GET', $testUrl);
				$build->output .= PHP_EOL . $testUrl . ': ' . $response->getStatusCode() . PHP_EOL;

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

		return self::MSG_ACK;
	}


	private function runProcess(\CI\Builds\CreateTestServer\CreateTestServer $build, string $cmd)
	{
		$build->output .= '> ' . trim($cmd) . "\n";

		$cwd = '/var/www/' . strtolower($build->repository->name) . '/' . 'test' . $build->pullRequestNumber;

		try {
			\Nette\Utils\FileSystem::createDir($cwd, 755);
		} catch (\Nette\IOException $e) {
			$build->output .= $e->getMessage();
			$this->createTestServersRepository->persistAndFlush($build);

			$this->logger->addError($e->getMessage());

			throw $e;
		}

		$process = new \Symfony\Component\Process\Process($cmd, $cwd, NULL, NULL, NULL);
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
