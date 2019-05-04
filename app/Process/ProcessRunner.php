<?php declare(strict_types = 1);

namespace CI\Process;

class ProcessRunner
{

	public function runProcess(\Monolog\Logger $logger, string $cwd, string $cmd, array $loggingContext = []): string
	{
		$logger->addDebug('> ' . trim($cmd), $loggingContext);

		try {
			\Nette\Utils\FileSystem::createDir($cwd, 755);
		} catch (\Nette\IOException $e) {

			$cb = static function (string $message) use ($logger, $loggingContext) {
				$logger->addError($message, $loggingContext);
			};
			$this->logMultiline($e->getMessage(), $cb);

			throw $e;
		}

		$process = \Symfony\Component\Process\Process::fromShellCommandline($cmd, $cwd, NULL, NULL, NULL);
		try {
			$cb = function (string $type, string $buffer) use ($logger, $loggingContext) {
				if ($type === \Symfony\Component\Process\Process::ERR) {
					$cb = static function (string $message) use ($logger, $loggingContext) {
						$logger->addError($message, $loggingContext);
					};
					$this->logMultiline($buffer, $cb);
				} else {
					$cb = static function (string $message) use ($logger, $loggingContext) {
						$logger->addDebug($message, $loggingContext);
					};
					$this->logMultiline($buffer, $cb);
				}
			};
			$process->mustRun($cb);

			return trim($process->getOutput());
		} catch (\Symfony\Component\Process\Exception\RuntimeException $e) {
			$cb = static function (string $message) use ($logger, $loggingContext) {
				$logger->addError($message, $loggingContext);
			};
			$this->logMultiline($e->getMessage(), $cb);

			throw $e;
		}
	}


	private function logMultiline(string $message, callable $loggingCallback): void
	{
		$messageLines = \explode("\n", \trim($message));
		\array_map($loggingCallback, $messageLines);
	}

}
