<?php declare(strict_types=1);

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

	/**
	 * @var \CI\Builds\CreateTestServer\CreateTestServersRepository
	 */
	private $createTestServersRepository;


	public function __construct(
		string $binDir,
		\Monolog\Logger $logger,
		\CI\Hooks\PullRequestsRepository $pullRequestsRepository,
		\CI\Builds\CreateTestServer\CreateTestServersRepository $createTestServersRepository
	) {
		$this->binDir = $binDir;
		$this->logger = $logger;
		$this->pullRequestsRepository = $pullRequestsRepository;
		$this->createTestServersRepository = $createTestServersRepository;
	}


	public function process(\PhpAmqpLib\Message\AMQPMessage $message)
	{
		$hookId = (int) $message->getBody();
		/** @var \CI\Hooks\PullRequest $hook */
		$hook = $this->pullRequestsRepository->getById($hookId);

		if ( ! $hook) {
			return self::MSG_REJECT;
		}

		$testServerPath = '/var/www/' . strtolower($hook->repository->name) . '/test' . $hook->pullRequestNumber;
		if (is_dir($testServerPath)) {
			$this->logger->addInfo('Proběhne smazání adresáře ' . $testServerPath);
			try {
				\Nette\Utils\FileSystem::delete($testServerPath);
			} catch (\Nette\IOException $e) {
				$this->logger->addError($e);

				return self::MSG_REJECT_REQUEUE;
			}
		} else {
			$this->logger->addNotice('Adresář projektu už neexistuje ' . $testServerPath);
		}

		$dbNameFile = '/var/www/' . strtolower($hook->repository->name) . '/dbname.cnf';
		if (is_readable($dbNameFile)) {
			$dbName = file_get_contents($dbNameFile);
			$dbName = str_replace('testX', 'test' . $hook->pullRequestNumber, $dbName);
			$this->logger->addInfo('Proběhne smazání databáze ' . $dbName);

			$cmd = sprintf('mysql --defaults-extra-file=/var/www/%s/mysql.cnf -e "DROP DATABASE IF EXISTS %s;"', strtolower($hook->repository->name), $dbName);
			$this->runProcess($cmd);
		}

		$conditions = [
			'repository' => $hook->repository,
			'pullRequestNumber' => $hook->pullRequestNumber,
		];
		/** @var \CI\Builds\CreateTestServer\CreateTestServer $build */
		$build = $this->createTestServersRepository->getBy($conditions);
		if ($build) {
			$build->closed = TRUE;
			$this->createTestServersRepository->persistAndFlush($build);
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
