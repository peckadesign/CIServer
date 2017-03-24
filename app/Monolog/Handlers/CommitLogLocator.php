<?php declare(strict_types = 1);

namespace CI\Monolog\Handlers;

class CommitLogLocator
{

	/**
	 * @var string
	 */
	private $logDir;


	public function __construct(
		string $logDir
	) {
		$this->logDir = $logDir;
	}


	public function getFile(string $channel, string $commit): string
	{
		return $channel . '/' . $commit;
	}


	public function getFilePath(string $channel, string $commit): string
	{
		return strtolower($this->logDir . '/' . $this->getFile($channel, $commit) . '.log');
	}
}
