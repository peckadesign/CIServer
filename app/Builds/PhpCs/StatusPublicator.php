<?php

namespace CI\Builds\PhpCs;

use CI;
use Kdyby;
use Nette;


class StatusPublicator
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
	public function publish(CI\GitHub\Repository $repository, string $commit, CI\PhpCs\PhpCs $phpCs)
	{
		if ( ! $phpCs->getWarnings() && ! $phpCs->getWarnings()) {
			$message = 'Bez chyb';
			$state = 'success';
		} else {
			$message = sprintf(
				'Nalezeno %d chyb a %d varování.',
				$phpCs->getErrors(),
				$phpCs->getWarnings()
			);

			$state = 'error';
		}

		$this->statusPublicator->publish(
			$repository,
			$commit,
			$state,
			$message,
			'Coding standard'
		);
	}
}
