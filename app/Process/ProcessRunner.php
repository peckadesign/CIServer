<?php declare(strict_types=1);

namespace CI\Process;

class ProcessRunner
{

	public function runProcess(\Monolog\Logger $logger, string $cwd, string $cmd, array $loggingContext = []): string
	{
		$logger->addInfo('> ' . trim($cmd), $loggingContext);

		try {
			\Nette\Utils\FileSystem::createDir($cwd, 755);
		} catch (\Nette\IOException $e) {

			$loggingContext->addError($e->getMessage(), $loggingContext);

			throw $e;
		}

		$process = new \Symfony\Component\Process\Process($cmd, $cwd, NULL, NULL, NULL);
		try {
			$cb = function (string $type, string $buffer) use ($logger, $loggingContext) {
				if ($type === \Symfony\Component\Process\Process::ERR) {
					$logger->addError($buffer, $loggingContext);
				} else {
					$logger->addInfo($buffer, $loggingContext);
				}
			};
			$process->mustRun($cb);

			return trim($process->getOutput());
		} catch (\Symfony\Component\Process\Exception\RuntimeException $e) {
			$logger->addError($e->getMessage(), $loggingContext);

			throw $e;
		}
	}
}
