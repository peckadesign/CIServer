<?php

namespace CI\Hooks\Consumers;

class ClosedPullRequest implements \Kdyby\RabbitMq\IConsumer
{

	/**
	 * @var \CI\Hooks\PullRequestsRepository
	 */
	private $pullRequestsRepository;

	/**
	 * @var \Monolog\Logger
	 */
	private $logger;

	/**
	 * @var string
	 */
	private $binDir;


	public function __construct(
		string $binDir,
		\Monolog\Logger $logger,
		\CI\Hooks\PullRequestsRepository $pullRequestsRepository,
		\CI\Builds\CreateTestServer\CreateTestServersRepository $createTestServersRepository
	) {
		$this->binDir = $binDir;
		$this->logger = $logger;
		$this->pullRequestsRepository = $pullRequestsRepository;
	}


	public function process(\PhpAmqpLib\Message\AMQPMessage $message)
	{
		$hookId = $message->getBody();
		$hook = $this->pullRequestsRepository->getById($hookId);

		if ( ! $hook) {
			return self::MSG_REJECT;
		}

		$testServerPath = '/var/www/' . $hook->repository->name . '/test' . $hook->pullRequestNumber;
		if (is_dir($testServerPath)) {
			$this->logger->addInfo('Proběhne smazání adresáře ' . $testServerPath);
			\Nette\Utils\FileSystem::delete($testServerPath);
		} else {
			$this->logger->addNotice('Adresář projektu už neexistuje ' . $testServerPath);
		}

		$dbNameFile = '/var/www/' . $hook->repository->name . '/dbname.cnf';
		if (is_readable($dbNameFile)) {
			$dbName = file_get_contents($dbNameFile);
			$this->logger->addInfo('Proběhne smazání databáze ' . $dbName);
			$dbName = str_replace('testX', 'test' . $hook->pullRequestNumber, $dbName);

			$cmd = sprintf('mysqladmin --defaults-extra-file=/var/www/%s/mysql.cnf --force drop %s', $hook->repository->name, $dbName);
			$this->runProcess($cmd);
		}

		return self::MSG_ACK;
	}


	private function runProcess(string $cmd)
	{
		$process = new \Symfony\Component\Process\Process($cmd, $this->binDir, NULL, NULL, NULL);
		try {
			$cb = function (string $type, string $buffer) {
				if ($type === \Symfony\Component\Process\Process::ERR) {
					$this->logger->addError($buffer);
				} else {
					$this->logger->addInfo($buffer);
				}
			};
			$process->mustRun($cb);
		} catch (\Symfony\Component\Process\Exception\RuntimeException $e) {
			$this->logger->addError($e->getMessage());

			throw $e;
		}
	}
}
