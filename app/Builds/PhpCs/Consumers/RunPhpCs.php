<?php declare(strict_types=1);

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


	public function __construct(
		string $logDirectory,
		\Monolog\Logger $logger,
		\CI\Builds\PhpCs\StatusPublicator $statusPublicator,
		\CI\Builds\CreateTestServer\CreateTestServersRepository $createTestServersRepository,
		\CI\Orm\Orm $orm,
		\CI\Builds\CreateTestServer\BuildLocator $buildLocator
	) {
		$this->logger = $logger;
		$this->statusPublicator = $statusPublicator;
		$this->logDirectory = $logDirectory;
		$this->orm = $orm;
		$this->createTestServersRepository = $createTestServersRepository;
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
				'Spouští se PHP CS pro repozitář "%s" a větev "%s"',
				$build->repository->name,
				$build->branchName
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

		$instancePath = $this->buildLocator->getPath($build->repository->name, $build->pullRequestNumber);
		$this->logger->addInfo('Cesta instance je ' . $instancePath, ['commit' => $builtCommit->getCommit()]);
		try {
			if ( ! is_readable($instancePath)) {
				$this->logger->addNotice('Instance nebyla na serveru nalezena', ['commit' => $builtCommit->getCommit()]);

				return self::MSG_REJECT;
			}

			chdir($instancePath);

			$currentCommit = $this->runProcess('git rev-parse HEAD');

			$success = TRUE;

			$this->runProcess('HOME=/home/' . get_current_user() . ' make cs', $currentCommit);

			$outputFilename = $instancePath . '/output.cs';
			if ( ! is_readable($outputFilename) || ($output = file_get_contents($outputFilename)) === FALSE) {
				throw new \CI\Exception('Nepodařilo se dohledat výstup kontroly coding standardů');
			}

			\Nette\Utils\FileSystem::copy(
				$outputFilename,
				sprintf(
					'%s/%s.cs',
					$this->logDirectory,
					$currentCommit
				)
			);

			$phpCs = new \CI\PhpCs\PhpCs($output);

			$this->logger->addInfo(
				sprintf(
					'Výstup pro commit %s je %d chyb a %d varování.',
					$currentCommit,
					$phpCs->getErrors(),
					$phpCs->getWarnings()
				),
				array_merge(['commit' => $currentCommit])
			);

			$this->statusPublicator->publish($build->repository, $currentCommit, $phpCs);
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

		if ($success) {
			return self::MSG_ACK;
		} else {
			return self::MSG_REJECT_REQUEUE;
		}
	}


	private function runProcess(string $cmd, string $commit = NULL): string
	{
		$this->logger->addInfo('> ' . trim($cmd), $commit ? ['commit' => $commit] : []);

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
