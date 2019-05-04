<?php declare(strict_types=1);

namespace CI\Builds\Tests\Consumers;

class RunTests implements \Kdyby\RabbitMq\IConsumer
{

	/**
	 * @var \Monolog\Logger
	 */
	private $logger;

	/**
	 * @var \Kdyby\Clock\IDateTimeProvider
	 */
	private $dateTimeProvider;

	/**
	 * @var \CI\Builds\Tests\BuildRequestsRepository
	 */
	private $buildRequestsRepository;

	/**
	 * @var \CI\Builds\Tests\StatusPublicator
	 */
	private $statusPublicator;

	/**
	 * @var \CI\GitHub\RepositoriesRepository
	 */
	private $repositoriesRepository;

	/**
	 * @var \CI\Builds\CreateTestServer\CreateTestServersRepository
	 */
	private $createTestServersRepository;

	/**
	 * @var \CI\Builds\CreateTestServer\BuildLocator
	 */
	private $buildLocator;

	/**
	 * @var \CI\Process\ProcessRunner
	 */
	private $processRunner;

	/**
	 * @var string
	 */
	private $logDirectory;


	public function __construct(
		string $logDirectory,
		\Monolog\Logger $logger,
		\Kdyby\Clock\IDateTimeProvider $dateTimeProvider,
		\CI\Builds\Tests\BuildRequestsRepository $buildRequestsRepository,
		\CI\Builds\Tests\StatusPublicator $statusPublicator,
		\CI\GitHub\RepositoriesRepository $repositoriesRepository,
		\CI\Builds\CreateTestServer\CreateTestServersRepository $createTestServersRepository,
		\CI\Builds\CreateTestServer\BuildLocator $buildLocator,
		\CI\Process\ProcessRunner $processRunner
	) {
		$this->logDirectory = $logDirectory;
		$this->logger = $logger;
		$this->dateTimeProvider = $dateTimeProvider;
		$this->buildRequestsRepository = $buildRequestsRepository;
		$this->statusPublicator = $statusPublicator;
		$this->repositoriesRepository = $repositoriesRepository;
		$this->createTestServersRepository = $createTestServersRepository;
		$this->buildLocator = $buildLocator;
		$this->processRunner = $processRunner;
	}


	public function process(\PhpAmqpLib\Message\AMQPMessage $message)
	{
		$loggingContext = [];

		try {
			$this->logger->addDebug('Přijatá data jsou: ' . $message->getBody(), $loggingContext);
			$builtCommit = \CI\Builds\BuiltCommit::fromJson($message->getBody());
		} catch (\Nette\Utils\JsonException $e) {
			$this->logger->addNotice('Přijatá data nejsou platná: ' . $e->getMessage(), $loggingContext);

			return self::MSG_REJECT;
		}

		$repository = $this->repositoriesRepository->getById($builtCommit->getRepositoryId());

		if ($builtCommit->getBuildId()) {
			$build = $this->createTestServersRepository->getById($builtCommit->getBuildId());
		} else {
			$build = NULL;
		}

		$loggingContext = array_merge($loggingContext, ['commit' => $builtCommit->getCommit()]);

		$this->logger->addInfo(
			sprintf(
				'Spouští se testy pro repozitář "%s" a commit "%s"',
				$repository->name,
				$builtCommit->getCommit()
			),
			$loggingContext
		);

		$e = NULL;
		set_error_handler(function ($errno, $errstr) use (&$e) {
			throw new \Exception($errstr, $errno);
		});

		$success = FALSE;
		$buildRequest = NULL;

		$instancePath = $this->buildLocator->getPath($repository->name, $build ? $build->pullRequestNumber : NULL);
		$this->logger->addInfo('Cesta instance je ' . $instancePath, ['commit' => $builtCommit->getCommit()]);
		try {

			if ( ! is_readable($instancePath)) {
				$this->logger->addNotice('Instance nebyla na serveru nalezena', $loggingContext);

				return self::MSG_REJECT;
			}

			chdir($instancePath);

			$currentBranch = $this->processRunner->runProcess($this->logger, $instancePath,'git rev-parse --abbrev-ref HEAD', $loggingContext);

			$conditions = [
				'repository' => $repository,
				'commit' => $builtCommit->getCommit(),
			];
			$buildRequest = $this->buildRequestsRepository->getBy($conditions);
			if ( ! $buildRequest) {
				$buildRequest = new \CI\Builds\Tests\BuildRequest();
				$buildRequest->repository = $repository;
				$buildRequest->commit = $builtCommit->getCommit();
			}
			$buildRequest->start = $this->dateTimeProvider->getDateTime();
			$buildRequest->finish = NULL;
			$buildRequest->succeeded = NULL;
			$buildRequest->failed = NULL;
			$buildRequest->branchName = $currentBranch;
			$buildRequest = $this->buildRequestsRepository->persistAndFlush($buildRequest);

			try {
				$this->statusPublicator->publish($buildRequest);
			} catch (\CI\Exception $e) {
				$this->logger->addError($e->getMessage(), $loggingContext);
			}

			$this->processRunner->runProcess($this->logger, $instancePath, 'HOME=/home/' . get_current_user() . ' COMPOSE_INTERACTIVE_NO_CLI=1 make run-tests', $loggingContext);
			$outputFilename = $instancePath . '/output.tap';
			$tapOutput = file_get_contents($outputFilename);
			if ( ! $tapOutput) {
				throw new \CI\Exception('Nepodařilo se dohledat výstup testů');
			}

			\Nette\Utils\FileSystem::copy(
				$outputFilename,
				sprintf(
					'%s/%s.cs',
					$this->logDirectory,
					$builtCommit->getCommit()
				)
			);

			$tap = new \CI\Tap\Tap($tapOutput);

			$buildRequest->succeeded = $tap->getSucceeded();
			$buildRequest->failed = $tap->getFailed();
			$buildRequest->finish = $this->dateTimeProvider->getDateTime();
			$this->buildRequestsRepository->persistAndFlush($buildRequest);

			$success = TRUE;
		} catch (\Exception $e) {
			$this->logger->addError($e);
			if ($buildRequest) {
				$buildRequest->finish = $this->dateTimeProvider->getDateTime();
				$buildRequest = $this->buildRequestsRepository->persistAndFlush($buildRequest);
			}
		} finally {
			if (file_exists($instancePath . '/output.tap')) {
				try {
					\Nette\Utils\FileSystem::delete($instancePath . '/output.tap');
				} catch (\Nette\IOException $e) {
					$this->logger->addError($e->getMessage(), $loggingContext);
				}
			}
		}

		if ($buildRequest) {
			try {
				$this->statusPublicator->publish($buildRequest);
			} catch (\CI\Exception $e) {
				$this->logger->addError($e->getMessage(), $loggingContext);
			}
		}

		if ($success) {
			$this->logger->addInfo('Požadavek byl úspěšný');
		} else {
			$this->logger->addNotice('Požadavek skončil chybou');
		}
		return self::MSG_ACK;
	}

}
