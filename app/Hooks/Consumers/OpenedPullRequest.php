<?php

namespace CI\Hooks\Consumers;

class OpenedPullRequest implements \Kdyby\RabbitMq\IConsumer
{

	/**
	 * @var \CI\Hooks\PullRequestsRepository
	 */
	private $pullRequestRepository;

	/**
	 * @var \CI\Builds\CreateTestServer\CreateTestServersRepository
	 */
	private $createTestServerRepository;

	/**
	 * @var \Kdyby\RabbitMq\IProducer
	 */
	private $createTestServerProducer;

	/**
	 * @var \CI\Builds\CreateTestServer\StatusPublicator
	 */
	private $statusPublicator;


	public function __construct(
		\Kdyby\RabbitMq\IProducer $createTestServerProducer,
		\CI\Hooks\PullRequestsRepository $pullRequestRepository,
		\CI\Builds\CreateTestServer\CreateTestServersRepository $createTestServerRepository,
		\CI\Builds\CreateTestServer\StatusPublicator $statusPublicator
	) {
		$this->pullRequestRepository = $pullRequestRepository;
		$this->createTestServerRepository = $createTestServerRepository;
		$this->createTestServerProducer = $createTestServerProducer;

		$this->statusPublicator = $statusPublicator;
	}


	public function process(\PhpAmqpLib\Message\AMQPMessage $message)
	{
		$hookId = $message->getBody();
		$hook = $this->pullRequestRepository->getById($hookId);

		if ( ! $hook) {
			return self::MSG_REJECT;
		}

		$createTestServer = new \CI\Builds\CreateTestServer\CreateTestServer();
		$createTestServer->pullRequestNumber = $hook->pullRequestNumber;
		$createTestServer->branchName = $hook->branchName;
		$createTestServer->commit = $hook->commit;
		$createTestServer->repository = $hook->repository;
		$this->createTestServerRepository->persistAndFlush($createTestServer);

		$this->createTestServerProducer->publish($createTestServer->id);
		$this->statusPublicator->publish($createTestServer);

		return self::MSG_ACK;
	}
}
