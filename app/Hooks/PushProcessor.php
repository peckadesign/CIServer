<?php declare(strict_types = 1);

namespace CI\Hooks;

class PushProcessor
{

	use \Nette\SmartObject;

	/**
	 * @var \Kdyby\RabbitMq\IProducer
	 */
	private $pushProducer;

	/**
	 * @var \CI\GitHub\RepositoryFacade
	 */
	private $repositoryFacade;


	public function __construct(
		\Kdyby\RabbitMq\IProducer $pushProducer,
		\CI\GitHub\RepositoryFacade $repositoryFacade
	) {
		$this->pushProducer = $pushProducer;
		$this->repositoryFacade = $repositoryFacade;
	}


	public function process(array $hookJson): void
	{
		if (empty($hookJson['repository']['name']) || empty($hookJson['ref'])) {
			throw new UnKnownHookException();
		}

		$created = (bool) $hookJson['created'];
		$deleted = (bool) $hookJson['deleted'];

		$repository = $this->repositoryFacade->getRepository($hookJson['repository']['name']);
		$branchName = substr($hookJson['ref'], 11);

		if ($created || $deleted) {
			return;
		}

		$this->pushProducer->publish(\Nette\Utils\Json::encode(['repositoryName' => $repository->name, 'branchName' => $branchName]));
	}
}
