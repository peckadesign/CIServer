<?php

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


	public function __construct(
		\Monolog\Logger $logger,
		\Kdyby\Clock\IDateTimeProvider $dateTimeProvider,
		\CI\Builds\Tests\BuildRequestsRepository $buildRequestsRepository,
		\CI\Builds\Tests\StatusPublicator $statusPublicator,
		\CI\GitHub\RepositoriesRepository $repositoriesRepository
	) {
		$this->logger = $logger;
		$this->dateTimeProvider = $dateTimeProvider;
		$this->buildRequestsRepository = $buildRequestsRepository;
		$this->statusPublicator = $statusPublicator;
		$this->repositoriesRepository = $repositoriesRepository;
	}


	public function process(\PhpAmqpLib\Message\AMQPMessage $message)
	{
		try {
			$messageJson = \Nette\Utils\Json::decode($message->getBody(), \Nette\Utils\Json::FORCE_ARRAY);
		} catch (\Nette\Utils\JsonException $e) {
			return self::MSG_REJECT;
		}

		$repositoryName = $messageJson['repositoryName'];
		$repositoryDirName = strtolower($messageJson['repositoryName']);
		$instanceDirectory = $messageJson['instanceDirectory'];

		$this->logger->addInfo('Budou spuštěny testy pro repozitář "' . $repositoryName . '" a instanci "' . $instanceDirectory . '"');

		$e = NULL;
		set_error_handler(function ($errno, $errstr) use (&$e) {
			throw new \Exception($errstr, $errno);
		});

		$success = FALSE;
		$buildRequest = NULL;

		try {
			$instancePath = '/var/www/' . $repositoryDirName . '/' . $instanceDirectory;

			if ( ! is_readable($instancePath)) {
				throw new \Exception('Instance nebyla na serveru nalezena');
			}

			chdir($instancePath);

			if ( ! is_readable('Makefile') || ! ($content = file_get_contents('Makefile')) || strpos($content, 'run-tests:') === FALSE) {
				$this->logger->addNotice('Instance neobsahuje příkaz pro spuštění testů');
				return self::MSG_REJECT;
			}

			$currentCommit = $this->runProcess('git rev-parse HEAD');
			$currentBranch = $this->runProcess('git rev-parse --abbrev-ref HEAD');

			$conditions = [
				'name' => $repositoryName,
			];
			$repository = $this->repositoriesRepository->getBy($conditions);
			if ( ! $repository) {
				$repository = new \CI\GitHub\Repository();
				$repository->name = $repositoryName;
				$repository = $this->repositoriesRepository->persistAndFlush($repository);
			}

			$conditions = [
				'repository' => $repository,
				'commit' => $currentCommit,
			];
			$buildRequest = $this->buildRequestsRepository->getBy($conditions);
			if ( ! $buildRequest) {
				$buildRequest = new \CI\Builds\Tests\BuildRequest();
				$buildRequest->repository = $repository;
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

			$this->runProcess('HOME=/home/' . get_current_user() . ' make run-tests');
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
			$buildRequest->finish = $this->dateTimeProvider->getDateTime();
			$buildRequest = $this->buildRequestsRepository->persistAndFlush($buildRequest);
		} finally {
			if (file_exists($instancePath . '/output.tap')) {
				try {
					\Nette\Utils\FileSystem::delete($instancePath . '/output.tap');
				} catch (\Nette\IOException $e) {
					$this->logger->addError($e);
				}
			}
		}

		if ($buildRequest) {
			$this->statusPublicator->publish($buildRequest);
		}

		if ($success) {
			return self::MSG_ACK;
		} else {
			return self::MSG_REJECT;
		}
	}


	private function runProcess(string $cmd): string
	{
		$this->logger->addInfo($cmd);
		$process = new \Symfony\Component\Process\Process($cmd, NULL, NULL, NULL, NULL);
		try {
			$cb = function (string $type, string $buffer) {
				if ($type === \Symfony\Component\Process\Process::ERR) {
					$this->logger->addError($buffer);
				} else {
					$this->logger->addInfo($buffer);
				}
			};
			$process->mustRun($cb);

			return trim($process->getOutput());
		} catch (\Symfony\Component\Process\Exception\RuntimeException $e) {
			$this->logger->addError($e->getMessage());

			throw $e;
		}
	}
}
