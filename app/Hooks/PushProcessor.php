<?php declare(strict_types=1);

namespace CI\Hooks;

use CI;


class PushProcessor
{

	use \Nette\SmartObject;

	/**
	 * @var \Kdyby\RabbitMq\IProducer
	 */
	private $pushProducer;

	/**
	 * @var CI\Builds\CreateTestServer\CreateTestServersRepository
	 */
	private $createTestServersRepository;


	public function __construct(
		\Kdyby\RabbitMq\IProducer $pushProducer,
		CI\Builds\CreateTestServer\CreateTestServersRepository $createTestServersRepository
	) {
		$this->pushProducer = $pushProducer;
		$this->createTestServersRepository = $createTestServersRepository;
	}


	public function process(array $hookJson): void
	{
		if (empty($hookJson['repository']['name']) || empty($hookJson['ref'])) {
			throw new UnKnownHookException();
		}

		$repositoryName = $hookJson['repository']['name'];
		$branchName = $hookJson['ref'];

		$conditions = [
			'branchName' => $branchName,
			'this->repository->name' => $repositoryName,
		];
		$createTestServer = $this->createTestServersRepository->getBy($conditions);

		if ( ! $createTestServer) {
			$this->pushProducer->publish(\Nette\Utils\Json::encode(['repositoryName' => $repositoryName, 'branchName' => $branchName]));
		}
	}
}
