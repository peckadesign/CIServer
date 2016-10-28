<?php

namespace CI\Hooks;

class GitHubProcessor
{

	use \Nette\SmartObject;

	/**
	 * @var \Kdyby\RabbitMq\IProducer
	 */
	private $openedPullRequestProducer;

	/**
	 * @var OpenedPullRequestsRepository
	 */
	private $openedPullRequestRepository;

	/**
	 * @var \CI\GitHub\RepositoriesRepository
	 */
	private $repositoriesRepository;


	public function __construct(
		\Kdyby\RabbitMq\IProducer $openedPullRequestProducer,
		OpenedPullRequestsRepository $openedPullRequestRepository,
		\CI\GitHub\RepositoriesRepository $repositoriesRepository
	) {
		$this->openedPullRequestProducer = $openedPullRequestProducer;

		$this->openedPullRequestRepository = $openedPullRequestRepository;
		$this->repositoriesRepository = $repositoriesRepository;
	}


	public function process(array $hookJson) : OpenedPullRequest
	{
		if (isset($hookJson['pull_request']) && $hookJson['action'] === 'opened') {
			$repositoryName = $hookJson['repository']['name'];
			$conditions = [
				'name' => $repositoryName,
			];
			$repository = $this->repositoriesRepository->getBy($conditions);

			if ( ! $repository) {
				$repository = new \CI\GitHub\Repository();
				$repository->name = $repositoryName;
				$this->repositoriesRepository->persistAndFlush($repository);
			}

			$hook = new OpenedPullRequest();
			$hook->repository = $repository;
			$hook->hook = \Nette\Utils\Json::encode($hookJson);
			$this->openedPullRequestRepository->persistAndFlush($hook);

			$this->openedPullRequestProducer->publish($hook->id);

			return $hook;
		}

		throw new UnKnownHookException();
	}
}
