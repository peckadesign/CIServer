<?php declare(strict_types = 1);

namespace CI\Hooks\Consumers;

class Push implements \Kdyby\RabbitMq\IConsumer
{

	/**
	 * @var \Monolog\Logger
	 */
	private $logger;

	/**
	 * @var array|\CI\Builds\IOnBuildReady
	 */
	private $onBuildReady = [];

	/**
	 * @var array|\CI\Builds\IOnBuildReady
	 */
	private $onBuildFrontReady = [];

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

	/**
	 * @var \Kdyby\Clock\IDateTimeProvider
	 */
	private $dateTimeProvider;

	/**
	 * @var \CI\Builds\CreateTestServer\StatusPublicator
	 */
	private $statusPublicator;

	/**
	 * @var \Kdyby\RabbitMq\IProducer
	 */
	private $pushProducer;

	/**
	 * @var \Kdyby\RabbitMq\IProducer
	 */
	private $createTestServerProducer;

	/**
	 * @var \CI\SyncLock\PushLockFactory
	 */
	private $pushLockFactory;


	public function __construct(
		\Monolog\Logger $logger,
		\CI\Builds\CreateTestServer\CreateTestServersRepository $createTestServersRepository,
		\CI\GitHub\RepositoriesRepository $repositoriesRepository,
		\CI\Process\ProcessRunner $processRunner,
		\Kdyby\Clock\IDateTimeProvider $dateTimeProvider,
		\CI\Builds\CreateTestServer\StatusPublicator $statusPublicator,
		\Kdyby\RabbitMq\IProducer $pushProducer,
		\Kdyby\RabbitMq\IProducer $createTestServerProducer,
		\CI\SyncLock\PushLockFactory $pushLockFactory
	) {
		$this->logger = $logger;
		$this->createTestServersRepository = $createTestServersRepository;
		$this->repositoriesRepository = $repositoriesRepository;
		$this->processRunner = $processRunner;
		$this->dateTimeProvider = $dateTimeProvider;
		$this->statusPublicator = $statusPublicator;
		$this->pushProducer = $pushProducer;
		$this->createTestServerProducer = $createTestServerProducer;
		$this->pushLockFactory = $pushLockFactory;
	}


	public function addOnBuildReady(\CI\Builds\IOnBuildReady $onBuildReady)
	{
		$this->onBuildReady[] = $onBuildReady;
	}


	public function addOnBuildFrontReady(\CI\Builds\IOnBuildReady $onBuildFrontReady)
	{
		$this->onBuildFrontReady[] = $onBuildFrontReady;
	}


	public function process(\PhpAmqpLib\Message\AMQPMessage $message)
	{
		$loggingContext = [];

		try {
			$this->logger->addDebug('Přijatá data jsou: ' . $message->getBody(), $loggingContext);
			$hookJson = \Nette\Utils\Json::decode($message->getBody(), \Nette\Utils\Json::FORCE_ARRAY);
		} catch (\Nette\Utils\JsonException $e) {
			return self::MSG_REJECT;
		}

		$repositoryName = strtolower($hookJson['repositoryName']);
		$pullRequestNumber = $hookJson['pullRequestNumber'] ?? NULL;
		$branchName = $hookJson['branchName'] ?? NULL;
		if ($branchName && ($slashPosition = strrpos($branchName, '/')) !== FALSE) {
			$branchName = substr($branchName, $slashPosition + 1);
		}

		$conditions = [
			'name' => $repositoryName,
		];
		$repository = $this->repositoriesRepository->getBy($conditions);

		if ( ! $repository) {
			$this->logger->addWarning('Nebyl nalezen repozitář', $loggingContext);

			return self::MSG_REJECT;
		}

		$conditions = [
			'repository' => $repository,
		];
		if ($pullRequestNumber) {
			$conditions['pullRequestNumber'] = $pullRequestNumber;
		} elseif ($branchName) {
			$conditions['branchName'] = $branchName;
		} else {
			$this->logger->addWarning('Nebyla předána data pro bližší identifikaci testovacího sestavení', $loggingContext);

			return self::MSG_REJECT;
		}

		$build = $this->createTestServersRepository->getBy($conditions);

		if ( ! $branchName && $build) {
			$branchName = $branchName = $build->branchName;
		}

		if ( ! $branchName) {
			$this->logger->addError('Není možné dohledat název větve', $loggingContext);

			return self::MSG_REJECT;
		}

		if ($build && $build->closed) {
			$this->logger->addInfo('PR aktualizované větve je už zavřený, nebude se aktualizovat', $loggingContext);

			return self::MSG_REJECT;
		}

		$this->logger->addInfo('Aktualizovaná větev je "' . $branchName . '"', $loggingContext);

		if ($build) {
			$build->updateStart = $this->dateTimeProvider->getDateTime();
			$build->updateFinish = NULL;
			$build = $this->createTestServersRepository->persistAndFlush($build);
			$this->statusPublicator->publish($build);
		}

		$e = NULL;
		set_error_handler(function ($errno, $errstr) use (&$e) {
			throw new \Exception($errstr, $errno);
		});

		try {

			$repositoryPath = '/var/www';

			chdir($repositoryPath);

			if ( ! is_readable($repositoryName)) {
				$this->logger->addNotice(sprintf('Repozitář "%s" nebyl na serveru nalezen', $repositoryName), $loggingContext);

				if ($build) {
					$this->logger->addNotice(sprintf('Do fronty bude zařazen nový pokus o sestavení repositáře "%s" pro větev "%s"', $repositoryName, $branchName), $loggingContext);
					$this->createTestServerProducer->publish($build->id);
					return self::MSG_ACK;
				}

				return self::MSG_REJECT;
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
				if ( ! is_readable($file)) {
					return FALSE;
				}
				if ( ! is_dir($file . '/.git')) {
					return FALSE;
				}

				return TRUE;
			};
			$instances = array_filter($instances, $cb);

			if ( ! count($instances)) {
				$this->logger->addNotice('Nebyly nalezeny žádné instance repozitáře', $loggingContext);

				return self::MSG_REJECT;
			}

			if ($build) {
				$build->success = TRUE;
			}

			$isLocked = FALSE;

			foreach ($instances as $instanceDirectory) {
				$this->logger->addInfo(sprintf('Byla nalezena instance "%s"', $instanceDirectory), $loggingContext);

				chdir($instanceDirectory);

				$cwd = sprintf("%s/%s/%s", $repositoryPath, $repositoryName, $instanceDirectory);
				$lockFile = $cwd . '/push.lock';
				$pushLock = $this->pushLockFactory->create($lockFile);

				if ( ! $pushLock->checkAndLock(new \DateTimeImmutable())) {
					$this->logger->addInfo(sprintf('Instance "%s" se již zpracovává paralelně', $instanceDirectory), $loggingContext);
					$isLocked = TRUE;
					break;
				} else {
					$this->logger->addInfo(sprintf('Pro instanci "%s" byl vytvořen zámek', $instanceDirectory), $loggingContext);
				}

				try {
					try {
						$currentBranch = $this->processRunner->runProcess($this->logger, $cwd, 'git symbolic-ref --short HEAD', $loggingContext);
						$this->logger->addInfo('Větev instance je "' . $currentBranch . '"', $loggingContext);
					} catch (\Exception $e) {
						throw new \Exception('Nepodařilo na získat název aktuální větve: ' . $e->getMessage(), $e->getCode(), $e);
					}

					if ($branchName !== $currentBranch) {
						throw new \CI\Hooks\SkipException('Do změn nepřišla aktuální větev');
					}

					$this->processRunner->runProcess($this->logger, $cwd, 'git fetch --prune 2>&1', $loggingContext);

					$this->processRunner->runProcess($this->logger, $cwd, 'git reset origin/' . $currentBranch . ' --hard 2>&1', $loggingContext);

					$currentCommit = $this->processRunner->runProcess($this->logger, $cwd, 'git rev-parse HEAD 2>&1', $loggingContext);
					$loggingContext = array_merge($loggingContext, ['commit' => $currentCommit]);

					$this->processRunner->runProcess($this->logger, $cwd, 'git clean -fx composer.lock 2>&1', $loggingContext);

					if (is_readable('Makefile') && ($content = file_get_contents('Makefile')) && strpos($content, 'clean-cache:') !== FALSE && strpos($content, 'build-staging:') !== FALSE) {
						$this->processRunner->runProcess($this->logger, $cwd, 'make clean-cache', $loggingContext);
						$this->processRunner->runProcess($this->logger, $cwd, 'HOME=/home/' . get_current_user() . ' COMPOSE_INTERACTIVE_NO_CLI=1 make build-staging', $loggingContext);
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

					if (is_readable('Makefile') && ($content = file_get_contents('Makefile')) && strpos($content, 'build-staging-front:') !== FALSE) {
						$this->processRunner->runProcess($this->logger, $cwd, 'HOME=/home/' . get_current_user() . ' COMPOSE_INTERACTIVE_NO_CLI=1 make build-staging-front', $loggingContext);
					}

					/** @var \CI\Builds\IOnBuildReady $onBuildReady */
					foreach ($this->onBuildFrontReady as $onBuildReady) {
						$onBuildReady->buildReady($this->logger, $repository, $build, $currentCommit);
					}

					$this->logger->addInfo('Aktualizace instance "' . $instanceDirectory . '" dokončena', $loggingContext);
				} catch (\CI\Hooks\SkipException $e) {
					$this->logger->addInfo($e->getMessage());
					continue;
				} catch (\Throwable $e) {
					if ( ! $e instanceof \Symfony\Component\Process\Exception\RuntimeException) {
						$this->logger->addError($e->getMessage(), $loggingContext);
					}
					if ($build) {
						$build->success = FALSE;
						$build = $this->createTestServersRepository->persist($build);
					}
					continue;
				} finally {
					$this->logger->addInfo(sprintf('Bude odebrán zámek "%s"', $lockFile), $loggingContext);
					$releaseResult = $pushLock->releaseLock();

					if ($releaseResult) {
						$this->logger->addInfo(\sprintf('Byl odebrán zámek "%s"', $lockFile), $loggingContext);
					} else {
						$this->logger->addInfo(\sprintf('Nepodařilo se odebrat zámek "%s"', $lockFile), $loggingContext);
					}

					$changed = \chdir('..');
					if ($changed) {
						$this->logger->addInfo('Proběhl návrat do výchozího adresáře projektu', $loggingContext);
					} else {
						$this->logger->addError('Nepodařil se návrat do výchozího adresáře', $loggingContext);
					}
				}
			}

			if ($isLocked) {
				$this->logger->addInfo('Zpracovávaná zpráva je už zpracovávána jiným conusmerem, zařadí se znovu na konec fronty', $loggingContext);
				\sleep(5);
				$this->pushProducer->publish($message->getBody());

				return self::MSG_ACK;
			}
		} catch (\Exception $e) {
			$this->logger->addError('Došlo k chybě při zpracování zprávy, bude vrácena', $loggingContext);
			$this->logger->addError($e->getMessage(), $loggingContext);
			if ($build) {
				$build->updateStart = NULL;
				$build->success = FALSE;
				$build = $this->createTestServersRepository->persistAndFlush($build);
				$this->statusPublicator->publish($build);
			}

			return self::MSG_REJECT;
		}

		if ($build) {
			if ($build->success) {
				$build->updateFinish = $this->dateTimeProvider->getDateTime();
			} else {
				$build->updateStart = NULL;
				$build->updateFinish = NULL;
			}
			$build = $this->createTestServersRepository->persistAndFlush($build);
			$this->statusPublicator->publish($build);
		}

		$this->logger->addInfo('Zpracování zprávy dokončeno', $loggingContext);

		return self::MSG_ACK;
	}

}
