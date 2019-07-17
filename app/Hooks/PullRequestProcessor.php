<?php declare(strict_types=1);

namespace CI\Hooks;

class PullRequestProcessor
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
	 * @var \Kdyby\RabbitMq\IProducer
	 */
	private $synchronizedPullRequestProducer;

	/**
	 * @var \Kdyby\RabbitMq\IProducer
	 */
	private $closedPullRequestProducer;

	/**
	 * @var \CI\GitHub\RepositoryFacade
	 */
	private $repositoryFacade;


	public function __construct(
		\Kdyby\RabbitMq\IProducer $openedPullRequestProducer,
		\Kdyby\RabbitMq\IProducer $synchronizedPullRequestProducer,
		\Kdyby\RabbitMq\IProducer $closedPullRequestProducer,
		PullRequestsRepository $pullRequestRepository,
		\CI\GitHub\RepositoryFacade $repositoryFacade
	) {
		$this->openedPullRequestProducer = $openedPullRequestProducer;
		$this->synchronizedPullRequestProducer = $synchronizedPullRequestProducer;
		$this->pullRequestsRepository = $pullRequestRepository;
		$this->closedPullRequestProducer = $closedPullRequestProducer;
		$this->repositoryFacade = $repositoryFacade;
	}


	public function process(array $hookJson): PullRequest
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

		$repository = $this->repositoryFacade->getRepository($hookJson['repository']['name']);

		$hook->repository = $repository;
		$hook->hook = \Nette\Utils\Json::encode($hookJson);
		$this->pullRequestsRepository->persistAndFlush($hook, TRUE);

		$producer->publish($hook->id);

		return $hook;
	}
}
