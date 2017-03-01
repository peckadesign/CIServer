<?php

namespace CI\Builds\PhpCs\Consumers;

class RunPhpCs implements \Kdyby\RabbitMq\IConsumer
{

	/**
	 * @var \Monolog\Logger
	 */
	private $logger;

	/**
	 * @var \CI\Builds\PhpCs\StatusPublicator
	 */
	private $statusPublicator;

	/**
	 * @var \CI\GitHub\RepositoriesRepository
	 */
	private $repositoriesRepository;

	/**
	 * @var string
	 */
	private $logDirectory;


	public function __construct(
		string $logDirectory,
		\Monolog\Logger $logger,
		\CI\Builds\PhpCs\StatusPublicator $statusPublicator,
		\CI\GitHub\RepositoriesRepository $repositoriesRepository
	) {
		$this->logger = $logger;
		$this->statusPublicator = $statusPublicator;
		$this->repositoriesRepository = $repositoriesRepository;
		$this->logDirectory = $logDirectory;
	}


	public function process(\PhpAmqpLib\Message\AMQPMessage $message)
	{
		try {
			$messageJson = \Nette\Utils\Json::decode($message->getBody(), \Nette\Utils\Json::FORCE_ARRAY);
			$this->logger->addNotice('Přijatá data jsou: ' . $message->getBody());
		} catch (\Nette\Utils\JsonException $e) {
			$this->logger->addNotice('Přijatá data nejsou platná: ' . $e->getMessage());
			return self::MSG_REJECT;
		}

		$repositoryName = $messageJson['repositoryName'];
		$repositoryDirName = strtolower($messageJson['repositoryName']);
		$instanceDirectory = $messageJson['instanceDirectory'];

		$this->logger->addInfo('Bude spuštěn PHP CS pro repozitář "' . $repositoryName . '" a instanci "' . $instanceDirectory . '"');

		$e = NULL;
		set_error_handler(function ($errno, $errstr) use (&$e) {
			throw new \Exception($errstr, $errno);
		});


		try {
			$instancePath = '/var/www/' . $repositoryDirName . '/' . $instanceDirectory;

			if ( ! is_readable($instancePath)) {
				throw new \Exception('Instance nebyla na serveru nalezena');
			}

			chdir($instancePath);

			if ( ! is_readable('Makefile') || ! ($content = file_get_contents('Makefile')) || strpos($content, 'cs:') === FALSE) {
				$this->logger->addNotice('Instance neobsahuje příkaz pro spuštění kontroly conding standardů');

				return self::MSG_REJECT;
			}

			$currentCommit = $this->runProcess('git rev-parse HEAD');

			$conditions = [
				'name' => $repositoryName,
			];
			$repository = $this->repositoriesRepository->getBy($conditions);
			if ( ! $repository) {
				$repository = new \CI\GitHub\Repository();
				$repository->name = $repositoryName;
				$repository = $this->repositoriesRepository->persistAndFlush($repository);
			}

			$this->runProcess('make cs');
			$output = file_get_contents($instancePath . '/output.cs');
			if ( ! $output) {
				throw new \CI\Exception('Nepodařilo se dohledat výstup kontroly coding standardů');
			}

			\Nette\Utils\FileSystem::copy(
				$instancePath . '/output.cs',
				sprintf(
					'%s/%s.cs',
					$this->logDirectory,
					$currentCommit
				)
			);

			$phpCs = new \CI\PhpCs\PhpCs($output);

			$this->logger->addInfo(sprintf('Výstup pro commit %s je %d chyb a %d varování.', $currentCommit, $phpCs->getErrors(), $phpCs->getWarnings()));

			$this->statusPublicator->publish($repository, $currentCommit, $phpCs);

		} catch (\Exception $e) {
			$this->logger->addError($e);
		} finally {
			if (file_exists($instancePath . '/output.cs')) {
				try {
					\Nette\Utils\FileSystem::delete($instancePath . '/output.cs');
				} catch (\Nette\IOException $e) {
					$this->logger->addError($e);
				}
			}
		}

		return self::MSG_ACK;
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
