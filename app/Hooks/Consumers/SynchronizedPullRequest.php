<?php

namespace CI\Hooks\Consumers;

class SynchronizedPullRequest implements \Kdyby\RabbitMq\IConsumer
{

	/**
	 * @var \CI\Hooks\SynchronizedPullRequestsRepository
	 */
	private $synchronizedPullRequestsRepository;

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


	public function __construct(
		\Kdyby\RabbitMq\IProducer $createTestServerProducer,
		\CI\Hooks\SynchronizedPullRequestsRepository $synchronizedPullRequestsRepository,
		\CI\Builds\CreateTestServer\CreateTestServersRepository $createTestServersRepository,
		\CI\Builds\CreateTestServer\StatusPublicator $statusPublicator
	) {
		$this->createTestServerProducer = $createTestServerProducer;
		$this->synchronizedPullRequestsRepository = $synchronizedPullRequestsRepository;
		$this->statusPublicator = $statusPublicator;
		$this->createTestServersRepository = $createTestServersRepository;
	}


	public function process(\PhpAmqpLib\Message\AMQPMessage $message)
	{
		$hookId = $message->getBody();
		$hook = $this->synchronizedPullRequestsRepository->getById($hookId);

		$conditions = [
			'repository' => $hook->repository,
			'branchName' => $hook->branchName,
		];
		$build = $this->createTestServersRepository->getBy($conditions);

		if ( ! $build) {
			$createTestServer = new \CI\Builds\CreateTestServer\CreateTestServer();
			$createTestServer->pullRequestNumber = $hook->pullRequestNumber;
			$createTestServer->branchName = $hook->branchName;
			$createTestServer->commit = $hook->commit;
			$createTestServer->repository = $hook->repository;
			$this->createTestServersRepository->persistAndFlush($createTestServer);

			$this->createTestServerProducer->publish($createTestServer->id);
			$this->statusPublicator->publish($build);
		} else {
			$this->statusPublicator->publish($build);
		}

		return self::MSG_ACK;
	}
}
