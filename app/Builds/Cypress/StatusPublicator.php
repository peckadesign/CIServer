<?php declare(strict_types = 1);

namespace CI\Builds\Cypress;

class StatusPublicator
{

	/**
	 * @var \Nette\Application\LinkGenerator
	 */
	private $linkGenerator;

	/**
	 * @var \CI\GitHub\StatusPublicator
	 */
	private $statusPublicator;


	public function __construct(
		\Nette\Application\LinkGenerator $linkGenerator,
		\CI\GitHub\StatusPublicator $statusPublicator
	) {
		$this->linkGenerator = $linkGenerator;
		$this->statusPublicator = $statusPublicator;
	}


	/**
	 * @throws \CI\Exception
	 */
	public function publish(\CI\GitHub\Repository $repository, string $commit, \CI\Cypress\Cypress $cypress)
	{
		if ( ! $cypress->getErrors()) {
			$message = 'Bez chyb';
			$state = 'success';
		} else {
			$message = sprintf(
				'Nalezeno: %d %s.',
				$cypress->getErrors(),
				\CI\Utils\Helpers::plural($cypress->getErrors(), 'chyb', 'chyba', 'chyby')
			);

			$state = 'error';
		}

		$this->statusPublicator->publish(
			$repository,
			$commit,
			$state,
			$message,
			'Cypress',
			$this->linkGenerator->link('DashBoard:Cypress:output', [$commit])
		);
	}
}
