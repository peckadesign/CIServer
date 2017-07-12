<?php declare(strict_types = 1);

namespace CI\Hooks\Consumers;

class SynchronizedPullRequest implements \Kdyby\RabbitMq\IConsumer
{

	/**
	 * @var \CI\Hooks\PullRequestsRepository
	 */
	private $pullRequestsRepository;

	/**
	 * @var \CI\Builds\CreateTestServer\StatusPublicator
	 */
	private $statusPublicator;

	/**
	 * @var \CI\Builds\CreateTestServer\CreateTestServersRepository
	 */
	private $createTestServersRepository;

	/**
	 * @var \Kdyby\RabbitMq\IProducer
	 */
	private $createTestServerProducer;

	/**
	 * @var \CI\Orm\Orm
	 */
	private $orm;

	/**
	 * @var \Kdyby\RabbitMq\IProducer
	 */
	private $pushProducer;


	public function __construct(
		\Kdyby\RabbitMq\IProducer $createTestServerProducer,
		\Kdyby\RabbitMq\IProducer $pushProducer,
		\CI\Hooks\PullRequestsRepository $pullRequestsRepository,
		\CI\Builds\CreateTestServer\CreateTestServersRepository $createTestServersRepository,
		\CI\Builds\CreateTestServer\StatusPublicator $statusPublicator,
		\CI\Orm\Orm $orm
	) {
		$this->createTestServerProducer = $createTestServerProducer;
		$this->statusPublicator = $statusPublicator;
		$this->createTestServersRepository = $createTestServersRepository;
		$this->pullRequestsRepository = $pullRequestsRepository;
		$this->orm = $orm;
		$this->pushProducer = $pushProducer;
	}


	public function process(\PhpAmqpLib\Message\AMQPMessage $message)
	{
		$this->orm->clearIdentityMapAndCaches(\CI\Orm\Orm::I_KNOW_WHAT_I_AM_DOING);

		$hookId = (int) $message->getBody();
		$hook = $this->pullRequestsRepository->getById($hookId);

		if ( ! $hook) {
			return self::MSG_REJECT;
		}

		$conditions = [
			'repository' => $hook->repository,
			'pullRequestNumber' => $hook->pullRequestNumber,
		];
		$build = $this->createTestServersRepository->getBy($conditions);

		if ( ! $build) {
			$build = new \CI\Builds\CreateTestServer\CreateTestServer();
			$build->pullRequestNumber = $hook->pullRequestNumber;
			$build->branchName = $hook->branchName;
			$build->commit = $hook->commit;
			$build->repository = $hook->repository;
			$this->createTestServersRepository->persistAndFlush($build);

			$this->createTestServerProducer->publish($build->id);
		} else {
			$build->commit = $hook->commit;
			$build = $this->createTestServersRepository->persistAndFlush($build);
		}
		$this->statusPublicator->publish($build);

		return self::MSG_ACK;
	}
}
