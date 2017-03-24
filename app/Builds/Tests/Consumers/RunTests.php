<?php declare(strict_types = 1);

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


	public function __construct(
		\Monolog\Logger $logger,
		\Kdyby\Clock\IDateTimeProvider $dateTimeProvider,
		\CI\Builds\Tests\BuildRequestsRepository $buildRequestsRepository,
		\CI\Builds\Tests\StatusPublicator $statusPublicator,
		\CI\GitHub\RepositoriesRepository $repositoriesRepository,
		\CI\Builds\CreateTestServer\CreateTestServersRepository $createTestServersRepository,
		\CI\Orm\Orm $orm,
		\CI\Builds\CreateTestServer\BuildLocator $buildLocator
	) {
		$this->logger = $logger;
		$this->dateTimeProvider = $dateTimeProvider;
		$this->buildRequestsRepository = $buildRequestsRepository;
		$this->statusPublicator = $statusPublicator;
		$this->repositoriesRepository = $repositoriesRepository;
		$this->createTestServersRepository = $createTestServersRepository;
		$this->orm = $orm;
		$this->buildLocator = $buildLocator;
	}


	public function process(\PhpAmqpLib\Message\AMQPMessage $message)
	{
		$this->orm->clearIdentityMapAndCaches(\CI\Orm\Orm::I_KNOW_WHAT_I_AM_DOING);

		try {
			$this->logger->addDebug('Přijatá data jsou: ' . $message->getBody());
			$builtCommit = \CI\Builds\BuiltCommit::fromJson($message->getBody());
		} catch (\Nette\Utils\JsonException $e) {
			$this->logger->addNotice('Přijatá data nejsou platná: ' . $e->getMessage());

			return self::MSG_REJECT;
		}

		$build = $this->createTestServersRepository->getById($builtCommit->getBuildId());

		$this->logger->addInfo(
			sprintf(
				'Spouští se testy pro repozitář "%s" a PR "#%s"',
				$build->repository->name,
				$build->pullRequestNumber
			),
			[
				'commit' => $builtCommit->getCommit(),
			]
		);

		$e = NULL;
		set_error_handler(function ($errno, $errstr) use (&$e) {
			throw new \Exception($errstr, $errno);
		});

		$success = FALSE;
		$buildRequest = NULL;

		$instancePath = $this->buildLocator->getPath($build->repository->name, $build->pullRequestNumber);
		try {

			if ( ! is_readable($instancePath)) {
				$this->logger->addNotice('Instance nebyla na serveru nalezena', ['commit' => $builtCommit->getCommit()]);

				//return self::MSG_REJECT;
			}

			chdir($instancePath);

			$currentCommit = $this->runProcess('git rev-parse HEAD');
			$currentBranch = $this->runProcess('git rev-parse --abbrev-ref HEAD', $currentCommit);

			$conditions = [
				'repository' => $build->repository,
				'commit' => $currentCommit,
			];
			$buildRequest = $this->buildRequestsRepository->getBy($conditions);
			if ( ! $buildRequest) {
				$buildRequest = new \CI\Builds\Tests\BuildRequest();
				$buildRequest->repository = $build->repository;
				$buildRequest->commit = $currentCommit;
			}
			$buildRequest->start = $this->dateTimeProvider->getDateTime();
			$buildRequest->finish = NULL;
			$buildRequest->output = NULL;
			$buildRequest->succeeded = NULL;
			$buildRequest->failed = NULL;
			$buildRequest->branchName = $currentBranch;
			$buildRequest = $this->buildRequestsRepository->persistAndFlush($buildRequest);

			$this->statusPublicator->publish($buildRequest);

			$this->runProcess('HOME=/home/' . get_current_user() . ' make run-tests', $currentCommit);
			$tapOutput = file_get_contents($instancePath . '/output.tap');
			if ( ! $tapOutput) {
				throw new \CI\Exception('Nepodařilo se dohledat výstup testů');
			}

			$tap = new \CI\Tap\Tap($tapOutput);

			$buildRequest->succeeded = $tap->getSucceeded();
			$buildRequest->failed = $tap->getFailed();
			$buildRequest->output = $tapOutput;
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
					$this->logger->addError($e, isset($currentCommit) ? ['commit' => $currentCommit] : []);
				}
			}
		}

		if ($buildRequest) {
			$this->statusPublicator->publish($buildRequest);
		}

		if ($success) {
			return self::MSG_ACK;
		} else {
			return self::MSG_REJECT_REQUEUE;
		}
	}


	private function runProcess(string $cmd, string $commit = NULL): string
	{
		$this->logger->addInfo($cmd);
		$process = new \Symfony\Component\Process\Process($cmd, NULL, NULL, NULL, NULL);
		try {
			$cb = function (string $type, string $buffer) use ($commit) {
				if ($type === \Symfony\Component\Process\Process::ERR) {
					$this->logger->addError($buffer, $commit ? ['commit' => $commit] : []);
				} else {
					$this->logger->addInfo($buffer, $commit ? ['commit' => $commit] : []);
				}
			};
			$process->mustRun($cb);

			return trim($process->getOutput());
		} catch (\Symfony\Component\Process\Exception\RuntimeException $e) {
			$this->logger->addError($e->getMessage(), $commit ? ['commit' => $commit] : []);

			throw $e;
		}
	}
}
