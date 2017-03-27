<?php declare(strict_types=1);

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

	/**
	 * @var \CI\Process\ProcessRunner
	 */
	private $processRunner;


	public function __construct(
		\Monolog\Logger $logger,
		\CI\Orm\Orm $orm,
		\CI\Builds\CreateTestServer\CreateTestServersRepository $createTestServersRepository,
		\CI\GitHub\RepositoriesRepository $repositoriesRepository,
		\CI\Process\ProcessRunner $processRunner
	) {
		$this->logger = $logger;
		$this->orm = $orm;
		$this->createTestServersRepository = $createTestServersRepository;
		$this->repositoriesRepository = $repositoriesRepository;
		$this->processRunner = $processRunner;
	}


	public function addOnBuildReady(\CI\Builds\IOnBuildReady $onBuildReady)
	{
		$this->onBuildReady[] = $onBuildReady;
	}


	public function process(\PhpAmqpLib\Message\AMQPMessage $message)
	{
		$this->orm->clearIdentityMapAndCaches(\CI\Orm\Orm::I_KNOW_WHAT_I_AM_DOING);

		$loggingContext = [];

		try {
			$this->logger->addDebug('Přijatá data jsou: ' . $message->getBody(), $loggingContext);
			$hookJson = \Nette\Utils\Json::decode($message->getBody(), \Nette\Utils\Json::FORCE_ARRAY);
		} catch (\Nette\Utils\JsonException $e) {
			return self::MSG_REJECT;
		}

		$repositoryName = strtolower($hookJson['repositoryName']);
		$branchName = $hookJson['branchName'];
		if (($slashPosition = strrpos($branchName, '/')) !== FALSE) {
			$branchName = substr($branchName, $slashPosition + 1);
		}

		$conditions = [
			'name' => $repositoryName,
		];
		$repository = $this->repositoriesRepository->getBy($conditions);

		$conditions = [
			'repository' => $repository,
			'branchName' => $branchName,
		];
		$build = $this->createTestServersRepository->getBy($conditions);

		if ($build && $build->closed) {
			$this->logger->addInfo('PR aktualizované větve je už zavřený, nebude se aktualizovat', $loggingContext);

			return self::MSG_REJECT;
		}

		$this->logger->addInfo('Aktualizovaná větev je "' . $branchName . '"', $loggingContext);

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
					$this->logger->addInfo(sprintf('Byla nalezena instance "%s"', $instanceDirectory), $loggingContext);

					chdir($instanceDirectory);

					$cwd = sprintf("%s/%s/%s", $repositoryPath, $repositoryName, $instanceDirectory);

					try {
						$currentBranch = $this->processRunner->runProcess($this->logger, $cwd, 'git symbolic-ref --short HEAD', $loggingContext);
						$this->logger->addInfo('Větev instance je "' . $currentBranch . '"', $loggingContext);
					} catch (\Exception $e) {
						throw new \Exception('Nepodařilo na získat název aktuální větve', $e->getCode(), $e);
					}

					if ($branchName !== $currentBranch) {
						throw new \CI\Hooks\SkipException('Do změn nepřišla aktuální větev');
					}

					$this->processRunner->runProcess($this->logger, $cwd, 'git fetch --prune', $loggingContext);

					$this->processRunner->runProcess($this->logger, $cwd, 'git reset origin/' . $currentBranch . ' --hard', $loggingContext);

					$currentCommit = $this->processRunner->runProcess($this->logger, $cwd, 'git rev-parse HEAD', $loggingContext);
					$loggingContext = array_merge($loggingContext, ['commit' => $currentCommit]);

					$this->processRunner->runProcess($this->logger, $cwd, 'git clean -fx composer.lock', $loggingContext);

					if (is_readable('Makefile') && ($content = file_get_contents('Makefile')) && strpos($content, 'clean-cache:') !== FALSE && strpos($content, 'build-staging:') !== FALSE) {
						$this->processRunner->runProcess($this->logger, $cwd, 'make clean-cache', $loggingContext);
						$this->processRunner->runProcess($this->logger, $cwd, 'HOME=/home/' . get_current_user() . ' make build-staging', $loggingContext);
					} else {
						if (is_readable('temp/cache')) {
							$this->processRunner->runProcess($this->logger, $cwd, 'git clean -dfX temp/cache', $loggingContext);
						} elseif (is_readable('temp')) {
							$this->processRunner->runProcess($this->logger, $cwd, 'git clean -dfX temp', $loggingContext);
						}
					}

					/** @var \CI\Builds\IOnBuildReady $onBuildReady */
					foreach ($this->onBuildReady as $onBuildReady) {
						$onBuildReady->buildReady($this->logger, $repository, $build, $currentCommit);
					}

					$this->logger->addInfo('Aktualizace instance "' . $instanceDirectory . '" dokončena', $loggingContext);
				} catch (\CI\Hooks\SkipException $e) {
					$this->logger->addInfo($e->getMessage());
					continue;
				} catch (\Exception $e) {
					$this->logger->addError($e->getMessage(), $loggingContext);
					continue;
				} finally {
					chdir('..');
				}
			}
		} catch (\Exception $e) {
			$this->logger->addError($e->getMessage(), $loggingContext);

			return self::MSG_REJECT;
		}

		return self::MSG_ACK;
	}

}
