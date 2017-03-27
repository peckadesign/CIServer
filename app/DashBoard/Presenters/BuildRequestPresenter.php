<?php declare(strict_types = 1);

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

	/**
	 * @var CI\Monolog\Handlers\CommitLogLocator
	 */
	private $commitLogLocator;


	public function __construct(
		CI\Builds\Tests\BuildRequestsRepository $buildRequests,
		CI\DashBoard\Controls\PublishBuildRequestStatus\IFactory $publishBuildRequestStatusFactory,
		CI\Monolog\Handlers\CommitLogLocator $commitLogLocator
	) {
		$this->buildRequests = $buildRequests;
		$this->publishBuildRequestStatusFactory = $publishBuildRequestStatusFactory;
		$this->commitLogLocator = $commitLogLocator;
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
		$this->template->output = file_get_contents($this->commitLogLocator->getFilePath('runTests', $this->buildRequest->commit));
	}


	protected function createComponentPublishBuildRequestStatus() : CI\DashBoard\Controls\PublishBuildRequestStatus\Control
	{
		return $this->publishBuildRequestStatusFactory->create($this->buildRequest);
	}
}
