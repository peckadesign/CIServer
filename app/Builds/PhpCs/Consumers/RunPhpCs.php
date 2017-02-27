<?php

namespace CI\Builds\PhpCs\Consumers;

class RunPhpCs implements \Kdyby\RabbitMq\IConsumer
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
		\CI\Builds\PhpCs\StatusPublicator $statusPublicator,
		\CI\GitHub\RepositoriesRepository $repositoriesRepository
	) {
		$this->logger = $logger;
		$this->dateTimeProvider = $dateTimeProvider;
		$this->statusPublicator = $statusPublicator;
		$this->repositoriesRepository = $repositoriesRepository;
	}


	public function process(\PhpAmqpLib\Message\AMQPMessage $message)
	{
		try {
			$messageJson = \Nette\Utils\Json::decode($message->getBody(), \Nette\Utils\Json::FORCE_ARRAY);
		} catch (\Nette\Utils\JsonException $e) {
			$this->logger->addNotice('Přijatá data nejsou platná: ' . $e->getMessage());
			return self::MSG_REJECT;
		}

		$repositoryName = $messageJson['repositoryName'];
		$repositoryDirName = strtolower($messageJson['repositoryName']);
		$instanceDirectory = $messageJson['instanceDirectory'];

		$this->logger->addInfo('Bude spuštěna PHP CS pro repozitář "' . $repositoryName . '" a instanci "' . $instanceDirectory . '"');

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
			$output = file_get_contents($instancePath . '/output.log');
			if ( ! $output) {
				throw new \CI\Exception('Nepodařilo se dohledat výstup kontroly coding standardů');
			}

			$phpCs = new \CI\PhpCs\PhpCs($output);

			$this->statusPublicator->publish($repository, $currentCommit, $phpCs);

		} catch (\Exception $e) {
			$this->logger->addError($e);
		} finally {
			if (file_exists($instancePath . '/output.log')) {
				try {
					\Nette\Utils\FileSystem::delete($instancePath . '/output.log');
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
