<?php

namespace CI\DashBoard\Controls\PublishCreateTestServerStatus;

class Control extends \Nette\Application\UI\Control
{

	/**
	 * @var \CI\Builds\CreateTestServer\StatusPublicator
	 */
	private $statusPublicator;

	/**
	 * @var \CI\Builds\CreateTestServer\CreateTestServer
	 */
	private $createTestServer;


	public function __construct(
		\CI\Builds\CreateTestServer\CreateTestServer $createTestServer,
		\CI\Builds\CreateTestServer\StatusPublicator $statusPublicator
	) {
		$this->createTestServer = $createTestServer;
		$this->statusPublicator = $statusPublicator;
	}


	public function handlePublish()
	{
		try {
			$this->statusPublicator->publish($this->createTestServer);
			$this->getPresenter()->flashMessage('Status byl publikován', \CI\DashBoard\Presenters\BasePresenter::FLASH_MESSAGE_SUCCESS);
			$this->redirect('this');
		} catch (\CI\Exception $e) {
			$this->getPresenter()->flashMessage($e->getMessage(), \CI\DashBoard\Presenters\BasePresenter::FLASH_MESSAGE_ERROR);
		}
	}
}
