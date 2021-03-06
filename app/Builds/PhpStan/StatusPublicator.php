<?php declare(strict_types = 1);

namespace CI\Builds\PhpStan;

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

	private \Kdyby\Clock\IDateTimeProvider $dateTimeProvider;


	public function __construct(
		\Nette\Application\LinkGenerator $linkGenerator,
		\CI\GitHub\StatusPublicator $statusPublicator,
		\Kdyby\Clock\IDateTimeProvider $dateTimeProvider
	) {
		$this->linkGenerator = $linkGenerator;
		$this->statusPublicator = $statusPublicator;
		$this->dateTimeProvider = $dateTimeProvider;
	}


	/**
	 * @throws \CI\Exception
	 */
	public function publish(\CI\GitHub\Repository $repository, string $commit, ?\CI\PhpStan\PhpStan $phpStan)
	{
		if ($phpStan === NULL) {
			$message = \sprintf(
				'Běží od %s',
				\CI\Utils\Helpers::dateTime($this->dateTimeProvider->getDateTime())
			);
			$state = 'pending';
		} elseif ( ! $phpStan->getErrors()) {
			$message = 'Bez chyb';
			$state = 'success';
		} else {
			$message = sprintf(
				'Nalezeno: %d %s.',
				$phpStan->getErrors(),
				\CI\Utils\Helpers::plural($phpStan->getErrors(), 'chyb', 'chyba', 'chyby')
			);

			$state = 'error';
		}

		$this->statusPublicator->publish(
			$repository,
			$commit,
			$state,
			$message,
			'PHPStan',
			$this->linkGenerator->link('DashBoard:PhpStan:output', [$commit])
		);
	}
}
