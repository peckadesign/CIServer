<?php declare(strict_types = 1);

namespace CI\Builds\Tests;

use CI;
use Kdyby;
use Nette;


class StatusPublicator implements IStatusPublicator
{

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
		$link = $this->linkGenerator->link('DashBoard:BuildRequest:', [$buildRequest->id]);

		if ( ! $buildRequest->start) {
			$state = 'pending';
			$message = 'Čeká se na spuštění';
		} elseif ( ! $buildRequest->finish) {
			$state = 'pending';
			$message = sprintf(
				'Testy běží od %s',
				CI\Utils\Helpers::dateTime($buildRequest->start)
			);
		} elseif ($buildRequest->failed) {
			$state = 'failure';
			$message = 'Past vedle pasti';
			$includeResults = TRUE;
			$link = $this->linkGenerator->link('DashBoard:BuildRequest:output', [$buildRequest->commit]);
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
				CI\Utils\Helpers::plural((int) $buildRequest->succeeded, 'Prošlo', 'Prošel', 'Prošly'),
				$buildRequest->succeeded,
				CI\Utils\Helpers::plural((int) $buildRequest->failed, 'selhalo', 'selhal', 'selhaly'),
				$buildRequest->failed
			);
		}

		$this->statusPublicator->publish(
			$buildRequest->repository,
			$buildRequest->commit,
			$state,
			$message,
			'Testy',
			$link
		);
	}
}
