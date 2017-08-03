<?php declare(strict_types = 1);

namespace CI\Builds\PhpStan\Consumers;

class PhpStan implements \Kdyby\RabbitMq\IConsumer
{

	/**
	 * @var \Monolog\Logger
	 */
	private $logger;

	/**
	 * @var \CI\Builds\PhpStan\StatusPublicator
	 */
	private $statusPublicator;

	/**
	 * @var string
	 */
	private $logDirectory;

	/**
	 * @var \CI\Orm\Orm
	 */
	private $orm;

	/**
	 * @var \CI\Builds\CreateTestServer\CreateTestServersRepository
	 */
	private $createTestServersRepository;

	/**
	 * @var \CI\Builds\CreateTestServer\BuildLocator
	 */
	private $buildLocator;

	/**
	 * @var \CI\GitHub\RepositoriesRepository
	 */
	private $repositoriesRepository;

	/**
	 * @var \CI\Process\ProcessRunner
	 */
	private $processRunner;


	public function __construct(
		string $logDirectory,
		\Monolog\Logger $logger,
		\CI\Builds\PhpStan\StatusPublicator $statusPublicator,
		\CI\Builds\CreateTestServer\CreateTestServersRepository $createTestServersRepository,
		\CI\GitHub\RepositoriesRepository $repositoriesRepository,
		\CI\Orm\Orm $orm,
		\CI\Builds\CreateTestServer\BuildLocator $buildLocator,
		\CI\Process\ProcessRunner $processRunner
	) {
		$this->logger = $logger;
		$this->statusPublicator = $statusPublicator;
		$this->logDirectory = $logDirectory;
		$this->orm = $orm;
		$this->createTestServersRepository = $createTestServersRepository;
		$this->buildLocator = $buildLocator;
		$this->repositoriesRepository = $repositoriesRepository;
		$this->processRunner = $processRunner;
	}


	public function process(\PhpAmqpLib\Message\AMQPMessage $message)
	{
		$this->orm->clearIdentityMapAndCaches(\CI\Orm\Orm::I_KNOW_WHAT_I_AM_DOING);

		$loggingContext = [];

		try {
			$this->logger->addDebug('Přijatá data jsou: ' . $message->getBody());
			$builtCommit = \CI\Builds\BuiltCommit::fromJson($message->getBody());
		} catch (\Nette\Utils\JsonException $e) {
			$this->logger->addNotice('Přijatá data nejsou platná: ' . $e->getMessage());

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
				'Spouští se PHPStan pro repozitář "%s" a commit "%s"',
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

		$instancePath = $this->buildLocator->getPath($repository->name, $build ? $build->pullRequestNumber : NULL);
		$this->logger->addInfo('Cesta instance je ' . $instancePath, $loggingContext);
		try {
			if ( ! is_readable($instancePath)) {
				$this->logger->addNotice('Instance nebyla na serveru nalezena', $loggingContext);

				return self::MSG_REJECT;
			}

			chdir($instancePath);

			$currentCommit = $this->processRunner->runProcess($this->logger, $instancePath, 'git rev-parse HEAD', $loggingContext);

			$success = TRUE;

			$this->processRunner->runProcess($this->logger, $instancePath, 'HOME=/home/' . get_current_user() . ' make phpstan', $loggingContext);

			$outputFilename = $instancePath . '/output.phpstan';
			if ( ! is_readable($outputFilename) || ($output = file_get_contents($outputFilename)) === FALSE) {
				throw new \CI\Exception('Nepodařilo se dohledat výstup kontroly PHPStanu');
			}

			\Nette\Utils\FileSystem::copy(
				$outputFilename,
				sprintf(
					'%s/%s.phpstan',
					$this->logDirectory,
					$currentCommit
				)
			);

			$phpStan = new \CI\PhpStan\PhpStan($output);

			$this->logger->addInfo(
				sprintf(
					'Výstup pro commit %s je %d chyb.',
					$currentCommit,
					$phpStan->getErrors()
				),
				$loggingContext
			);

			$this->statusPublicator->publish($repository, $currentCommit, $phpStan);
		} catch (\Exception $e) {
			$this->logger->addError($e);
		} finally {
			if (file_exists($instancePath . '/output.phpstan')) {
				try {
					\Nette\Utils\FileSystem::delete($instancePath . '/output.phpstan');
				} catch (\Nette\IOException $e) {
					$this->logger->addError($e, $loggingContext);
				}
			}
		}

		if ($success) {
			return self::MSG_ACK;
		} else {
			return self::MSG_REJECT_REQUEUE;
		}
	}

}
