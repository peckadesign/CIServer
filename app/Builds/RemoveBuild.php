<?php declare(strict_types = 1);

namespace CI\Builds;

final class RemoveBuild
{

	/**
	 * @var string
	 */
	private $binDir;

	/**
	 * @var \Monolog\Logger
	 */
	private $logger;


	public function __construct(
		string $binDir,
		\Monolog\Logger $logger
	) {
		$this->binDir = $binDir;
		$this->logger = $logger;
	}


	public function remove(\CI\GitHub\Repository $repository, int $pullRequestNumber): bool
	{
		$testServerPath = '/var/www/' . strtolower($repository->name) . '/test' . $pullRequestNumber;
		if (is_dir($testServerPath)) {
			$this->logger->addInfo('Proběhne smazání adresáře ' . $testServerPath);
			try {
				\Nette\Utils\FileSystem::delete($testServerPath);
			} catch (\Nette\IOException $e) {
				$this->logger->addError($e);

				return FALSE;
			}
		} else {
			$this->logger->addNotice('Adresář projektu už neexistuje ' . $testServerPath);
		}

		$dbNameFile = '/var/www/' . strtolower($repository->name) . '/dbname.cnf';
		if (is_readable($dbNameFile)) {
			$dbName = file_get_contents($dbNameFile);
			$dbName = str_replace('testX', 'test' . $pullRequestNumber, $dbName);
			$this->logger->addInfo('Proběhne smazání databáze ' . $dbName);

			$cmd = sprintf('mysql --defaults-extra-file=/var/www/%s/mysql.cnf -e "DROP DATABASE IF EXISTS %s;"', strtolower($repository->name), $dbName);
			$this->runProcess($cmd);
		}

		return TRUE;
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
