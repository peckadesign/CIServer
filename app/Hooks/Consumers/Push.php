<?php declare(strict_types = 1);

namespace CI\Hooks\Consumers;

class Push implements \Kdyby\RabbitMq\IConsumer
{

	/**
	 * @var \Monolog\Logger
	 */
	private $logger;

	/**
	 * @var \CI\Orm\Orm
	 */
	private $orm;

	/**
	 * @var array|\CI\Builds\IOnBuildReady
	 */
	private $onBuildReady = [];

	/**
	 * @var \CI\Builds\CreateTestServer\CreateTestServersRepository
	 */
	private $createTestServersRepository;

	/**
	 * @var \CI\GitHub\RepositoriesRepository
	 */
	private $repositoriesRepository;


	public function __construct(
		\Monolog\Logger $logger,
		\CI\Orm\Orm $orm,
		\CI\Builds\CreateTestServer\CreateTestServersRepository $createTestServersRepository,
		\CI\GitHub\RepositoriesRepository $repositoriesRepository
	) {
		$this->logger = $logger;
		$this->orm = $orm;
		$this->createTestServersRepository = $createTestServersRepository;
		$this->repositoriesRepository = $repositoriesRepository;
	}


	public function addOnBuildReady(\CI\Builds\IOnBuildReady $onBuildReady)
	{
		$this->onBuildReady[] = $onBuildReady;
	}


	public function process(\PhpAmqpLib\Message\AMQPMessage $message)
	{
		$this->orm->clearIdentityMapAndCaches(\CI\Orm\Orm::I_KNOW_WHAT_I_AM_DOING);

		try {
			$hookJson = \Nette\Utils\Json::decode($message->getBody(), \Nette\Utils\Json::FORCE_ARRAY);
		} catch (\Nette\Utils\JsonException $e) {
			return self::MSG_REJECT;
		}

		$repositoryName = strtolower($hookJson['repositoryName']);
		$branchName = $hookJson['branchName'];

		$conditions = [
			'name' => $repositoryName,
		];
		$repository = $this->repositoriesRepository->getBy($conditions);

		$conditions = [
			'repository' => $repository,
			'branchName' => $branchName,
		];
		$build = $this->createTestServersRepository->getBy($conditions);

		$this->logger->addInfo('Aktualizovaná větev je "' . $branchName . '"');

		$e = NULL;
		set_error_handler(function ($errno, $errstr) use (&$e) {
			throw new \Exception($errstr, $errno);
		});

		try {

			$repositoryPath = '/var/www';

			chdir($repositoryPath);

			if ( ! is_readable($repositoryName)) {
				throw new \Exception('Repozitář nebyl na serveru nalezen');
			}

			chdir($repositoryName);

			$instances = scandir('.');
			$cb = function ($file) {
				return $file !== '.' && $file !== '..';
			};
			$instances = array_filter($instances, $cb);

			$cb = function ($file) {
				if ( ! is_dir($file)) {
					return FALSE;
				}
				if ( ! is_dir($file . '/.git')) {
					return FALSE;
				}

				return TRUE;
			};
			$instances = array_filter($instances, $cb);

			if ( ! count($instances)) {
				throw new \Exception('Nebyly nalezeny žádné instance repozitáře');
			}

			foreach ($instances as $instanceDirectory) {
				try {
					$this->logger->addInfo('Byla nalezena instance ' . $instanceDirectory);

					chdir($instanceDirectory);

					try {
						$currentBranch = $this->runProcess('git symbolic-ref --short HEAD');
						$this->logger->addInfo('Větev instance je "' . $currentBranch . '"');
					} catch (\Exception $e) {
						throw new \Exception('Nepodařilo na získat název aktuální větve', $e->getCode(), $e);
					}

					if ($branchName !== 'refs/heads/' . $currentBranch) {
						throw new \CI\Hooks\SkipException('Do změn nepřišla aktuální větev');
					}

					$this->runProcess('git fetch --prune');

					$this->runProcess('git reset origin/' . $currentBranch . ' --hard');

					$currentCommit = $this->runProcess('git rev-parse HEAD');

					$this->runProcess('git clean -fx composer.lock', $currentCommit);

					if (is_readable('Makefile') && ($content = file_get_contents('Makefile')) && strpos($content, 'clean-cache:') !== FALSE && strpos($content, 'build-staging:') !== FALSE) {
						$this->runProcess('make clean-cache', $currentCommit);
						$this->runProcess('HOME=/home/' . get_current_user() . ' make build-staging', $currentCommit);
					} else {
						if (is_readable('temp/cache')) {
							$this->runProcess('git clean -dfX temp/cache', $currentCommit);
						} elseif (is_readable('temp')) {
							$this->runProcess('git clean -dfX temp', $currentCommit);
						}
					}

					/** @var \CI\Builds\IOnBuildReady $onBuildReady */
					foreach ($this->onBuildReady as $onBuildReady) {
						$onBuildReady->buildReady($this->logger, $build, $currentCommit);
					}

					$this->logger->addInfo('Aktualizace instance ' . $instanceDirectory . ' dokončena', ['commit' => $currentCommit]);
				} catch (\CI\Hooks\SkipException $e) {
					$this->logger->addInfo($e->getMessage());
					continue;
				} catch (\Exception $e) {
					$this->logger->addError($e->getMessage(), isset($currentCommit) ? ['commit' => $currentCommit] : []);
					continue;
				} finally {
					chdir('..');
				}
			}
		} catch (\Exception $e) {
			$this->logger->addError($e->getMessage());

			return self::MSG_REJECT;
		}

		return self::MSG_ACK;
	}


	private function runProcess(string $cmd, string $commit = NULL): string
	{
		$this->logger->addInfo($cmd, $commit ? ['commit' => $commit] : []);
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
