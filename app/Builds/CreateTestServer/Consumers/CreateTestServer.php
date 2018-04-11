<?php declare(strict_types=1);

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

	/**
	 * @var \CI\Process\ProcessRunner
	 */
	private $processRunner;


	public function __construct(
		\Monolog\Logger $logger,
		\CI\Builds\CreateTestServer\CreateTestServersRepository $createTestServersRepository,
		\CI\Builds\CreateTestServer\StatusPublicator $statusPublicator,
		\Kdyby\Github\Client $gitHub,
		\CI\User\UsersRepository $usersRepository,
		\CI\Orm\Orm $orm,
		\CI\Builds\CreateTestServer\BuildLocator $buildLocator,
		\Kdyby\Clock\IDateTimeProvider $dateTimeProvider,
		\CI\Process\ProcessRunner $processRunner
	) {
		$this->logger = $logger;
		$this->createTestServersRepository = $createTestServersRepository;
		$this->statusPublicator = $statusPublicator;
		$this->gitHub = $gitHub;
		$this->usersRepository = $usersRepository;
		$this->orm = $orm;
		$this->buildLocator = $buildLocator;
		$this->dateTimeProvider = $dateTimeProvider;
		$this->processRunner = $processRunner;
	}


	public function addOnBuildReady(\CI\Builds\IOnBuildReady $onBuildReady)
	{
		$this->onBuildReady[] = $onBuildReady;
	}


	public function process(\PhpAmqpLib\Message\AMQPMessage $message)
	{
		$this->orm->clearIdentityMapAndCaches(\CI\Orm\Orm::I_KNOW_WHAT_I_AM_DOING);

		$loggingContext = [];

		$this->logger->addDebug(sprintf('Přijatá data jsou: %s', $message->getBody()), $loggingContext);

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

			$cwd = $this->buildLocator->getPath($build->repository->name, $build->pullRequestNumber);

			$this->processRunner->runProcess($this->logger, $cwd, 'OLD_DIR=`pwd` && cd .. && rm -rf $OLD_DIR && cp -RP --preserve=all staging $OLD_DIR', $loggingContext);

			chdir($cwd);

			$this->processRunner->runProcess($this->logger, $cwd, 'test -d temp/ && git clean -xdf temp/ || true', $loggingContext);
			$this->processRunner->runProcess($this->logger, $cwd, 'test -d log/ && git clean -xdf log/ || true', $loggingContext);
			$this->processRunner->runProcess($this->logger, $cwd, 'git reset origin/master --hard', $loggingContext);
			$this->processRunner->runProcess($this->logger, $cwd, 'git clean -fx composer.lock', $loggingContext);

			$this->processRunner->runProcess($this->logger, $cwd, 'git fetch --prune', $loggingContext);
			$this->processRunner->runProcess($this->logger, $cwd, 'git checkout ' . $build->branchName, $loggingContext);
			$currentCommit = $this->processRunner->runProcess($this->logger, $cwd, 'git rev-parse HEAD', $loggingContext);
			$this->processRunner->runProcess($this->logger, $cwd, 'test -d temp/ && chmod -R 0777 temp/ || true', $loggingContext);
			$this->processRunner->runProcess($this->logger, $cwd, 'test -d log/ && chmod -R 0777 log/ || true', $loggingContext);

			$defaultLocalNeonPath = $cwd . '/../local.neon';
			$testName = 'test' . $build->pullRequestNumber;
			if (is_readable($defaultLocalNeonPath)) {
				if ($databaseFiles) {
					$cmd = sprintf('sed "s/testX/%s/" < %s > %s/app/config/local.neon', $testName, $defaultLocalNeonPath, $cwd);
				} else {
					$cmd = sprintf('sed "s/testX/%s/" < %s > %s/app/config/local.neon', 'staging', $defaultLocalNeonPath, $cwd);
				}
				$this->processRunner->runProcess($this->logger, $cwd, $cmd, $loggingContext);
			}


			if (is_readable('Makefile') && ($content = file_get_contents('Makefile')) && strpos($content, 'clean:') !== FALSE) {
				try {
					$this->processRunner->runProcess($this->logger, $cwd, 'make clean', $loggingContext);
				} catch (\Symfony\Component\Process\Exception\RuntimeException $e) {
					$this->logger->addWarning($e, $loggingContext);
					$success = FALSE;
				}
			}

			if (is_readable('Makefile') && ($content = file_get_contents('Makefile')) && strpos($content, 'build-staging:') !== FALSE) {
				try {
					$this->processRunner->runProcess($this->logger, $cwd, 'HOME=/home/' . get_current_user() . ' make build-staging', $loggingContext);
				} catch (\Symfony\Component\Process\Exception\RuntimeException $e) {
					$this->logger->addWarning($e, $loggingContext);
					$success = FALSE;
				}
			}

			/** @var \CI\Builds\IOnBuildReady $onBuildReady */
			foreach ($this->onBuildReady as $onBuildReady) {
				try {
					$onBuildReady->buildReady($this->logger, $build->repository, $build, $currentCommit);
				} catch (\Throwable $e) {
					$this->logger->addWarning($e);
				}
			}

			if (is_readable('Makefile') && ($content = file_get_contents('Makefile')) && strpos($content, 'build-staging-front:') !== FALSE) {
				try {
					$this->processRunner->runProcess($this->logger, $cwd, 'HOME=/home/' . get_current_user() . ' make build-staging-front', $loggingContext);
				} catch (\Symfony\Component\Process\Exception\RuntimeException $e) {
					$this->logger->addWarning($e, $loggingContext);
					$success = FALSE;
				}
			}

			$this->logger->addInfo('Vytvoření testovacího serveru "' . $cwd . '" dokončeno', $loggingContext);

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

}
