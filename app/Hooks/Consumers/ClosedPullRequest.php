<?php declare(strict_types=1);

namespace CI\Hooks\Consumers;

final class ClosedPullRequest implements \Kdyby\RabbitMq\IConsumer
{

	/**
	 * @var \CI\Hooks\PullRequestsRepository
	 */
	private $pullRequestsRepository;

	/**
	 * @var \CI\Builds\CreateTestServer\CreateTestServersRepository
	 */
	private $createTestServersRepository;

	/**
	 * @var \CI\Builds\RemoveBuild
	 */
	private $removeBuild;


	public function __construct(
		\CI\Hooks\PullRequestsRepository $pullRequestsRepository,
		\CI\Builds\CreateTestServer\CreateTestServersRepository $createTestServersRepository,
		\CI\Builds\RemoveBuild $removeBuild
	) {
		$this->pullRequestsRepository = $pullRequestsRepository;
		$this->createTestServersRepository = $createTestServersRepository;
		$this->removeBuild = $removeBuild;
	}


	public function process(\PhpAmqpLib\Message\AMQPMessage $message)
	{
		$hookId = (int) $message->getBody();
		/** @var \CI\Hooks\PullRequest $hook */
		$hook = $this->pullRequestsRepository->getById($hookId);

		if ( ! $hook) {
			return self::MSG_REJECT;
		}

		$conditions = [
			'repository' => $hook->repository,
			'pullRequestNumber' => $hook->pullRequestNumber,
		];
		/** @var \CI\Builds\CreateTestServer\CreateTestServer $build */
		$build = $this->createTestServersRepository->getBy($conditions);
		if ($build) {
			$build->closed = TRUE;
			$this->createTestServersRepository->persistAndFlush($build);
		}

		return self::MSG_ACK;
	}



}
