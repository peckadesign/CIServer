<?php declare(strict_types = 1);

namespace CI\Monolog\Handlers;

use Kdyby;
use Nette;


/**
 * Handler exportuje zprávy do souborů, kdy pro každý den zakládá nový soubor a pro každý měsíc nový adresář
 *
 *  - Výsledná cesta je %logDir%/názevKanálu/YYYY-MM/YYYY-MM-DD-názevKanálu.log
 *  - Adresář pro uložení souboru se vytváří automaticky
 */
class DayFileHandler extends Kdyby\Monolog\Handler\FallbackNetteHandler
{

	/**
	 * @var string
	 */
	private $logDir;

	/**
	 * @var Kdyby\Clock\IDateTimeProvider
	 */
	private $dateTimeProvider;


	public function __construct($appName, $logDir, $expandNewlines = FALSE, Kdyby\Clock\IDateTimeProvider $dateTimeProvider)
	{
		parent::__construct($appName, $logDir, $expandNewlines);

		$this->logDir = $logDir;
		$this->dateTimeProvider = $dateTimeProvider;
	}


	protected function write(array $record)
	{
		$record['filename'] = $record['filename'] . '/' . $this->dateTimeProvider->getDateTime()->format('Y-m') . '/' . $this->dateTimeProvider->getDateTime()->format('Y-m-d') . '-' . $record['filename'];

		$logDirectory = dirname($this->logDir . '/' . strtolower($record['filename']));
		Nette\Utils\FileSystem::createDir($logDirectory);

		parent::write($record);
	}

}
