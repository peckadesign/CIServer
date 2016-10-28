<?php

namespace CI\DashBoard\Controls\RerunCreateTestServer;

class Control extends \Nette\Application\UI\Control
{

	/**
	 * @var \Kdyby\RabbitMq\IProducer
	 */
	private $createTestServerProducer;

	/**
	 * @var \CI\Builds\CreateTestServer\CreateTestServer
	 */
	private $createTestServer;


	public function __construct(
		\CI\Builds\CreateTestServer\CreateTestServer $createTestServer,
		\Kdyby\RabbitMq\IProducer $createTestServerProducer
	) {

		$this->createTestServerProducer = $createTestServerProducer;
		$this->createTestServer = $createTestServer;
	}


	public function handlePublish()
	{
		$this->createTestServerProducer->publish($this->createTestServer->id);
		$this->getPresenter()->flashMessage('Bylo naplánováno opětovné sestavení', \CI\DashBoard\Presenters\BasePresenter::FLASH_MESSAGE_SUCCESS);
		$this->redirect('this');
	}

}
