<?php

namespace CI\Hooks\Consumers;

class Push implements \Kdyby\RabbitMq\IConsumer
{

	/**
	 * @var \Monolog\Logger
	 */
	private $logger;

	/**
	 * @var \Kdyby\RabbitMq\IProducer
	 */
	private $runTestsProducer;


	public function __construct(
		\Monolog\Logger $logger,
		\Kdyby\RabbitMq\IProducer $runTestsProducer
	) {
		$this->logger = $logger;
		$this->runTestsProducer = $runTestsProducer;
	}


	public function process(\PhpAmqpLib\Message\AMQPMessage $message)
	{
		try {
			$hookJson = \Nette\Utils\Json::decode($message->getBody(), \Nette\Utils\Json::FORCE_ARRAY);
		} catch (\Nette\Utils\JsonException $e) {
			return self::MSG_REJECT;
		}

		$repositoryName = strtolower($hookJson['repositoryName']);
		$branchName = $hookJson['branchName'];

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

					if (is_readable('Makefile') && ($content = file_get_contents('Makefile')) && strpos($content, 'clean-cache:') !== FALSE && strpos($content, 'build-staging:') !== FALSE) {
						$this->runProcess('make clean-cache');
						$this->runProcess('HOME=/home/' . get_current_user() . ' make build-staging');
					} else {
						if (is_readable('temp/cache')) {
							$this->runProcess('git clean -dfX temp/cache');
						} elseif (is_readable('temp')) {
							$this->runProcess('git clean -dfX temp');
						}
					}

					if (is_readable('Makefile') && ($content = file_get_contents('Makefile')) && strpos($content, 'run-tests:') !== FALSE) {
						$publishData = \Nette\Utils\Json::encode(['repositoryName' => $repositoryName, 'instanceDirectory' => $instanceDirectory]);
						$this->logger->addInfo('Instance obsahuje testy, budou spuštěny: ' . $publishData);
						$this->runTestsProducer->publish($publishData);
					}

					$this->logger->addInfo('Aktualizace instance ' . $instanceDirectory . ' dokončena');

				} catch(\CI\Hooks\SkipException $e) {
					$this->logger->addInfo($e->getMessage());
					continue;
				} catch (\Exception $e) {
					$this->logger->addError($e->getMessage());
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


	private function runProcess(string $cmd) : string
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
