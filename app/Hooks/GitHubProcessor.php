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
	 * @var PullRequestsRepository
	 */
	private $pullRequestsRepository;

	/**
	 * @var \CI\GitHub\RepositoriesRepository
	 */
	private $repositoriesRepository;

	/**
	 * @var \Kdyby\RabbitMq\IProducer
	 */
	private $synchronizedPullRequestProducer;

	/**
	 * @var \Kdyby\RabbitMq\IProducer
	 */
	private $closedPullRequestProducer;


	public function __construct(
		\Kdyby\RabbitMq\IProducer $openedPullRequestProducer,
		\Kdyby\RabbitMq\IProducer $synchronizedPullRequestProducer,
		\Kdyby\RabbitMq\IProducer $closedPullRequestProducer,
		PullRequestsRepository $pullRequestRepository,
		\CI\GitHub\RepositoriesRepository $repositoriesRepository
	) {
		$this->openedPullRequestProducer = $openedPullRequestProducer;
		$this->synchronizedPullRequestProducer = $synchronizedPullRequestProducer;
		$this->pullRequestsRepository = $pullRequestRepository;
		$this->repositoriesRepository = $repositoriesRepository;
		$this->closedPullRequestProducer = $closedPullRequestProducer;
	}


	public function process(array $hookJson) : PullRequest
	{
		if (isset($hookJson['pull_request']) && $hookJson['action'] === 'opened') {
			$hook = new OpenedPullRequest();
			$producer = $this->openedPullRequestProducer;
		} elseif (isset($hookJson['pull_request']) && $hookJson['action'] === 'synchronize') {
			$hook = new SynchronizedPullRequest();
			$producer = $this->synchronizedPullRequestProducer;
		} elseif (isset($hookJson['pull_request']) && $hookJson['action'] === 'closed') {
			$hook = new ClosedPullRequest();
			$producer = $this->closedPullRequestProducer;
		} else {
			throw new UnKnownHookException();
		}

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

		$hook->repository = $repository;
		$hook->hook = \Nette\Utils\Json::encode($hookJson);
		$this->pullRequestsRepository->persistAndFlush($hook);

		$producer->publish($hook->id);

		return $hook;
	}
}
