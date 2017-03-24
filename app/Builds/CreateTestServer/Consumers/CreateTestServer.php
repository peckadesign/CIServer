<?php declare(strict_types = 1);

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
	 * @var \CI\Orm\Orm
	 */
	private $orm;

	/**
	 * @var array|\CI\Builds\IOnBuildReady
	 */
	private $onBuildReady = [];

	/**
	 * @var \CI\Builds\CreateTestServer\BuildLocator
	 */
	private $buildLocator;

	/**
	 * @var \Kdyby\Clock\IDateTimeProvider
	 */
	private $dateTimeProvider;


	public function __construct(
		\Monolog\Logger $logger,
		\CI\Builds\CreateTestServer\CreateTestServersRepository $createTestServersRepository,
		\CI\Builds\CreateTestServer\StatusPublicator $statusPublicator,
		\Kdyby\Github\Client $gitHub,
		\CI\User\UsersRepository $usersRepository,
		\CI\Orm\Orm $orm,
		\CI\Builds\CreateTestServer\BuildLocator $buildLocator,
		\Kdyby\Clock\IDateTimeProvider $dateTimeProvider
	) {
		$this->logger = $logger;
		$this->createTestServersRepository = $createTestServersRepository;
		$this->statusPublicator = $statusPublicator;
		$this->gitHub = $gitHub;
		$this->usersRepository = $usersRepository;
		$this->orm = $orm;
		$this->buildLocator = $buildLocator;
		$this->dateTimeProvider = $dateTimeProvider;
	}


	public function addOnBuildReady(\CI\Builds\IOnBuildReady $onBuildReady)
	{
		$this->onBuildReady[] = $onBuildReady;
	}


	public function process(\PhpAmqpLib\Message\AMQPMessage $message)
	{
		$this->orm->clearIdentityMapAndCaches(\CI\Orm\Orm::I_KNOW_WHAT_I_AM_DOING);

		$hookId = (int) $message->getBody();
		$build = $this->createTestServersRepository->getById($hookId);

		if ( ! $build) {
			return self::MSG_REJECT;
		}

		$conditions = [
			'systemUser' => TRUE,
		];
		$systemUser = $this->usersRepository->getBy($conditions);

		$build->start = $this->dateTimeProvider->getDateTime();
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

			$this->runProcess($build, 'OLD_DIR=`pwd` && cd .. && rm -rf $OLD_DIR && cp -RP --preserve=all staging $OLD_DIR');

			$this->runProcess($build, 'test -d temp/ && git clean -xdf temp/ || true');
			$this->runProcess($build, 'test -d log/ && git clean -xdf log/ || true');
			$this->runProcess($build, 'git reset origin/master --hard');
			$this->runProcess($build, 'git clean -fx composer.lock');

			$this->runProcess($build, 'git fetch --prune');
			$this->runProcess($build, 'git checkout ' . $build->branchName);
			$currentCommit = $this->runProcess($build, 'git rev-parse HEAD');
			$this->runProcess($build, 'test -d temp/ && chmod -R 0777 temp/ || true');
			$this->runProcess($build, 'test -d log/ && chmod -R 0777 log/ || true');

			$this->runProcess($build, 'test -f Makefile && cat Makefile | grep -q "clean:" && make clean || true');
			$this->runProcess($build, 'test -f Makefile && cat Makefile | grep -q "build-staging:" && HOME=/home/' . get_current_user() . ' make build-staging || true');

			$defaultLocalNeonPath = '/var/www/' . strtolower($build->repository->name) . '/local.neon';
			$testName = 'test' . $build->pullRequestNumber;
			if (is_readable($defaultLocalNeonPath)) {
				if ($databaseFiles) {
					$cmd = sprintf('sed "s/testX/%s/" < %s > /var/www/%s/%s/app/config/local.neon', $testName, $defaultLocalNeonPath, strtolower($build->repository->name), $testName);
				} else {
					$cmd = sprintf('sed "s/testX/%s/" < %s > /var/www/%s/%s/app/config/local.neon', 'staging', $defaultLocalNeonPath, strtolower($build->repository->name), $testName);
				}
				$this->runProcess($build, $cmd);
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

			/** @var \CI\Builds\IOnBuildReady $onBuildReady */
			foreach ($this->onBuildReady as $onBuildReady) {
				try {
					$onBuildReady->buildReady($this->logger, $build, $currentCommit);
				} catch (\Throwable $e) {
					$this->logger->addWarning($e);
				}
			}

		} catch (\Symfony\Component\Process\Exception\RuntimeException $e) {
			$success = FALSE;
		} finally {
			$build->finish = $this->dateTimeProvider->getDateTime();
			$build->success = $success;
			$this->createTestServersRepository->persistAndFlush($build);
		}

		$this->statusPublicator->publish($build);

		return self::MSG_ACK;
	}


	private function runProcess(\CI\Builds\CreateTestServer\CreateTestServer $build, string $cmd): string
	{
		$build->output .= '> ' . trim($cmd) . "\n";
		$this->logger->addInfo('> ' . trim($cmd), ['commit' => $build->commit]);

		$cwd = $this->buildLocator->getPath($build->repository->name, $build->pullRequestNumber);

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
				$this->logger->addInfo(trim($buffer));
			};
			$process->mustRun($cb);

			return $process->getOutput();
		} catch (\Symfony\Component\Process\Exception\RuntimeException $e) {
			$build->output .= $e->getMessage();
			$this->createTestServersRepository->persistAndFlush($build);

			$this->logger->addError($e->getMessage());

			throw $e;
		}
	}
}
