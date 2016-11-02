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

	/**
	 * @var SynchronizedPullRequestsRepository
	 */
	private $synchronizedPullRequestsRepository;

	/**
	 * @var \Kdyby\RabbitMq\IProducer
	 */
	private $synchronizedPullRequestProducer;


	public function __construct(
		\Kdyby\RabbitMq\IProducer $openedPullRequestProducer,
		\Kdyby\RabbitMq\IProducer $synchronizedPullRequestProducer,
		OpenedPullRequestsRepository $openedPullRequestRepository,
		\CI\GitHub\RepositoriesRepository $repositoriesRepository,
		SynchronizedPullRequestsRepository $synchronizedPullRequestsRepository
	) {
		$this->openedPullRequestProducer = $openedPullRequestProducer;
		$this->synchronizedPullRequestProducer = $synchronizedPullRequestProducer;
		$this->openedPullRequestRepository = $openedPullRequestRepository;
		$this->repositoriesRepository = $repositoriesRepository;
		$this->synchronizedPullRequestsRepository = $synchronizedPullRequestsRepository;
	}


	public function process(array $hookJson)
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

		} elseif (isset($hookJson['pull_request']) && $hookJson['action'] === 'synchronize') {
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

			$hook = new SynchronizedPullRequest();
			$hook->repository = $repository;
			$hook->hook = \Nette\Utils\Json::encode($hookJson);
			$this->synchronizedPullRequestsRepository->persistAndFlush($hook);

			$this->synchronizedPullRequestProducer->publish($hook->id);

			return $hook;
		}

		throw new UnKnownHookException();
	}
}
