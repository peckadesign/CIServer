<?php

namespace CI\Hooks\Consumers;

class OpenedPullRequest implements \Kdyby\RabbitMq\IConsumer
{

	/**
	 * @var \CI\Hooks\OpenedPullRequestsRepository
	 */
	private $openedPullRequestRepository;

	/**
	 * @var \CI\Builds\CreateTestServer\CreateTestServersRepository
	 */
	private $createTestServerRepository;

	/**
	 * @var \Kdyby\RabbitMq\IProducer
	 */
	private $createTestServerProducer;


	public function __construct(
		\Kdyby\RabbitMq\IProducer $createTestServerProducer,
		\CI\Hooks\OpenedPullRequestsRepository $openedPullRequestRepository,
		\CI\Builds\CreateTestServer\CreateTestServersRepository $createTestServerRepository
	) {
		$this->openedPullRequestRepository = $openedPullRequestRepository;
		$this->createTestServerRepository = $createTestServerRepository;
		$this->createTestServerProducer = $createTestServerProducer;
	}


	public function process(\PhpAmqpLib\Message\AMQPMessage $message)
	{
		$hookId = $message->getBody();
		$hook = $this->openedPullRequestRepository->getById($hookId);

		$createTestServer = new \CI\Builds\CreateTestServer\CreateTestServer();
		$createTestServer->pullRequestNumber = $hook->pullRequestNumber;
		$createTestServer->branchName = $hook->branchName;
		$createTestServer->commit = $hook->commit;
		$createTestServer->repository = $hook->repository;
		$this->createTestServerRepository->persistAndFlush($createTestServer);

		$this->createTestServerProducer->publish($createTestServer->id);

		return self::MSG_ACK;
	}
}
