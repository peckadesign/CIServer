<?php

namespace CI\Builds\CreateTestServer;

class StatusPublicator
{

	use \Nette\SmartObject;

	/**
	 * @var \CI\GitHub\StatusPublicator
	 */
	private $statusPublicator;

	/**
	 * @var \Nette\Application\LinkGenerator
	 */
	private $linkGenerator;


	public function __construct(
		\CI\GitHub\StatusPublicator $statusPublicator,
		\Nette\Application\LinkGenerator $linkGenerator
	) {

		$this->statusPublicator = $statusPublicator;
		$this->linkGenerator = $linkGenerator;
	}


	public function publish(\CI\Builds\CreateTestServer\CreateTestServer $createTestServer)
	{
		if ($createTestServer->finish) {
			if ($createTestServer->success) {
				$description = 'Byl vytvořen ' . \CI\Utils\Helpers::dateTime($createTestServer->finish);
				$status = \CI\GitHub\StatusPublicator::STATUS_SUCCESS;
			} else {
				$description = 'Byl vytvořen ' . \CI\Utils\Helpers::dateTime($createTestServer->finish);
				$status = \CI\GitHub\StatusPublicator::STATUS_SUCCESS;
			}
		} elseif ($createTestServer->start) {
			$description = 'Probíhá příprava od ' . \CI\Utils\Helpers::dateTime($createTestServer->start);
			$status = \CI\GitHub\StatusPublicator::STATUS_PENDING;
		} else {
			$description = 'Je v plánu';
			$status = \CI\GitHub\StatusPublicator::STATUS_PENDING;
		}

		$this->statusPublicator->publish(
			$createTestServer->repository,
			$createTestServer->commit,
			$status,
			$description,
			'Testovací server',
			$status === \CI\GitHub\StatusPublicator::STATUS_SUCCESS
				?
				$this->linkGenerator->link('TestServer:', [$createTestServer])
				:
				$this->linkGenerator->link('DashBoard:CreateTestServer:default', [$createTestServer->id])
		);
	}
}
