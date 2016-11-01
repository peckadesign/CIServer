<?php

namespace CI\DashBoard\Presenters;

use CI;


class BuildRequestPresenter extends BasePresenter
{

	/**
	 * @var CI\Builds\Tests\BuildRequestsRepository
	 */
	private $buildRequests;

	/**
	 * @var CI\Builds\Tests\BuildRequest
	 */
	private $buildRequest;

	/**
	 * @var CI\DashBoard\Controls\PublishBuildRequestStatus\IFactory
	 */
	private $publishBuildRequestStatusFactory;


	public function __construct(
		CI\Builds\Tests\BuildRequestsRepository $buildRequests,
		CI\DashBoard\Controls\PublishBuildRequestStatus\IFactory $publishBuildRequestStatusFactory
	) {
		$this->buildRequests = $buildRequests;
		$this->publishBuildRequestStatusFactory = $publishBuildRequestStatusFactory;
	}


	public function actionDefault(int $id)
	{
		$this->buildRequest = $this->buildRequests->getById($id);

		if ( ! $this->buildRequest) {
			$this->error();
		}
	}


	public function renderDefault(int $id)
	{
		$this->template->buildRequest = $this->buildRequest;
	}


	protected function createComponentPublishBuildRequestStatus() : CI\DashBoard\Controls\PublishBuildRequestStatus\Control
	{
		return $this->publishBuildRequestStatusFactory->create($this->buildRequest);
	}
}
