<?php declare(strict_types=1);

namespace CI\Hooks;

class PushProcessor
{

	use \Nette\SmartObject;

	/**
	 * @var \Kdyby\RabbitMq\IProducer
	 */
	private $pushProducer;

	/**
	 * @var \CI\Builds\CreateTestServer\CreateTestServersRepository
	 */
	private $createTestServersRepository;

	/**
	 * @var \CI\GitHub\RepositoryFacade
	 */
	private $repositoryFacade;


	public function __construct(
		\Kdyby\RabbitMq\IProducer $pushProducer,
		\CI\Builds\CreateTestServer\CreateTestServersRepository $createTestServersRepository,
		\CI\GitHub\RepositoryFacade $repositoryFacade
	) {
		$this->pushProducer = $pushProducer;
		$this->createTestServersRepository = $createTestServersRepository;
		$this->repositoryFacade = $repositoryFacade;
	}


	public function process(array $hookJson): void
	{
		if (empty($hookJson['repository']['name']) || empty($hookJson['ref'])) {
			throw new UnKnownHookException();
		}

		$repository = $this->repositoryFacade->getRepository($hookJson['repository']['name']);
		$branchName = $hookJson['ref'];

		$conditions = [
			'branchName' => $branchName,
			'repository' => $repository,
		];
		$createTestServer = $this->createTestServersRepository->getBy($conditions);

		if ( ! $createTestServer) {
			$this->pushProducer->publish(\Nette\Utils\Json::encode(['repositoryName' => $repository->name, 'branchName' => $branchName]));
		}
	}
}
