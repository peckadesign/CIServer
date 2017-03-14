<?php

namespace CI\Builds\PhpCs;

use CI;
use Kdyby;
use Nette;


class StatusPublicator
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
	public function publish(CI\GitHub\Repository $repository, string $commit, CI\PhpCs\PhpCs $phpCs)
	{
		if ( ! $phpCs->getErrors() && ! $phpCs->getWarnings()) {
			$message = 'Bez chyb';
			$state = 'success';
		} else {
			$message = sprintf(
				'Nalezeno: %d %s a %d varování.',
				$phpCs->getErrors(),
				CI\Utils\Helpers::plural($phpCs->getErrors(), 'chyb', 'chyba', 'chyby'),
				$phpCs->getWarnings()
			);

			$state = 'error';
		}

		$this->statusPublicator->publish(
			$repository,
			$commit,
			$state,
			$message,
			'Coding standard',
			$this->linkGenerator->link('DashBoard:PhpCs:output', [$commit])
		);
	}
}
