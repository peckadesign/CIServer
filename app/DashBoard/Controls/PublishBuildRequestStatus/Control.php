<?php

namespace CI\DashBoard\Controls\PublishBuildRequestStatus;

use CI;
use Nette;


class Control extends Nette\Application\UI\Control
{

	/**
	 * @var CI\Builds\Tests\StatusPublicator
	 */
	private $statusPublicator;

	/**
	 * @var CI\Builds\Tests\BuildRequest
	 */
	private $buildRequest;


	public function __construct(
		CI\Builds\Tests\BuildRequest $buildRequest,
		CI\Builds\Tests\StatusPublicator $statusPublicator
	) {
		$this->statusPublicator = $statusPublicator;
		$this->buildRequest = $buildRequest;
	}


	public function handlePublish()
	{
		try {
			$this->statusPublicator->publish($this->buildRequest);
			$this->getPresenter()->flashMessage('Status byl publikován', CI\DashBoard\Presenters\BasePresenter::FLASH_MESSAGE_SUCCESS);
			$this->redirect('this');
		} catch (CI\Exception $e) {
			$this->getPresenter()->flashMessage($e->getMessage(), CI\DashBoard\Presenters\BasePresenter::FLASH_MESSAGE_ERROR);
		}
	}
}
