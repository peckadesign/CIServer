<?php declare(strict_types = 1);

namespace CI\Monolog\Handlers;

use Kdyby;
use Nette;


class CommitHandler extends Kdyby\Monolog\Handler\FallbackNetteHandler
{

	/**
	 * @var string
	 */
	private $logDir;

	/**
	 * @var CommitLogLocator
	 */
	private $commitLogLocator;


	public function __construct($appName, $logDir, $expandNewlines = FALSE, CommitLogLocator $commitLogLocator)
	{
		parent::__construct($appName, $logDir, $expandNewlines);

		$this->logDir = $logDir;
		$this->commitLogLocator = $commitLogLocator;
	}


	public function isHandling(array $record): bool
	{
		if ( ! isset($record['context']['commit'])) {
			return FALSE;
		}

		return parent::isHandling($record);
	}


	protected function write(array $record)
	{
		$record['filename'] = $this->commitLogLocator->getFile($record['filename'], $record['context']['commit']);

		$logDirectory = dirname($this->logDir . '/' . strtolower($record['filename']));
		Nette\Utils\FileSystem::createDir($logDirectory);

		parent::write($record);
	}

}
