<?php

namespace CI\Builds\Tests;

use CI;
use Kdyby;
use Nette;


class StatusPublicator implements IStatusPublicator
{

	const DATE_TIME_FORMAT = 'j. n. Y H:i:s';

	/**
	 * @var Nette\Application\LinkGenerator
	 */
	private $linkGenerator;


	/**
	 * @var CI\GitHub\StatusPublicator
	 */
	private $statusPublicator;


	public function __construct(
		Nette\Application\LinkGenerator $linkGenerator,
		CI\GitHub\StatusPublicator $statusPublicator
	) {
		$this->linkGenerator = $linkGenerator;
		$this->statusPublicator = $statusPublicator;
	}


	/**
	 * @throws CI\Exception
	 */
	public function publish(BuildRequest $buildRequest)
	{
		$includeResults = FALSE;

		if ( ! $buildRequest->start) {
			$state = 'pending';
			$message = 'Čeká se na spuštění testů.';
		} elseif ( ! $buildRequest->finish) {
			$state = 'pending';
			$message = sprintf(
				'Čeká se na dokončení spuštěných testů, běží od %s.',
				$buildRequest->start->format(self::DATE_TIME_FORMAT)
			);
		} elseif ($buildRequest->failed) {
			$state = 'failure';
			$message = 'Past vedle pasti';
			$includeResults = TRUE;
		} elseif ($buildRequest->succeeded) {
			$state = 'success';
			$message = 'Funguje';
			$includeResults = TRUE;
		} else {
			$state = 'success';
			$message = 'Žádné testy nespuštěny';
		}

		if ($includeResults) {
			$message = sprintf(
				'%s. %s %u, %s %u.',
				$message,
				'Prošlo',
				$buildRequest->succeeded,
				'selhalo',
				$buildRequest->failed
			);
		}

		$this->statusPublicator->publish(
			$buildRequest->repository,
			$buildRequest->commit,
			$state,
			$message,
			'Automatické testy',
			$this->linkGenerator->link('DashBoard:BuildRequest:', [$buildRequest->id])
		);
	}
}
