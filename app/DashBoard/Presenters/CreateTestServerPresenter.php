<?php

namespace CI\DashBoard\Presenters;

class CreateTestServerPresenter extends BasePresenter
{

	use TSecuredPresenter;

	/**
	 * @var \CI\Builds\CreateTestServer\CreateTestServer
	 */
	private $createTestServer;

	/**
	 * @var \CI\Builds\CreateTestServer\CreateTestServersRepository
	 */
	private $createTestServersRepository;

	/**
	 * @var \CI\DashBoard\Controls\PublishCreateTestServerStatus\IFactory
	 */
	private $publishCreateTestServerStatusFactory;

	/**
	 * @var \CI\DashBoard\Controls\RerunCreateTestServer\IFactory
	 */
	private $rerunCreateTestServer;


	public function __construct(
		\CI\Builds\CreateTestServer\CreateTestServersRepository $createTestServersRepository,
		\CI\DashBoard\Controls\PublishCreateTestServerStatus\IFactory $publishCreateTestServerStatusFactory,
		\CI\DashBoard\Controls\RerunCreateTestServer\IFactory $rerunCreateTestServer
	) {

		$this->createTestServersRepository = $createTestServersRepository;
		$this->publishCreateTestServerStatusFactory = $publishCreateTestServerStatusFactory;
		$this->rerunCreateTestServer = $rerunCreateTestServer;
	}


	public function actionDefault(int $id)
	{
		$this->createTestServer = $this->createTestServersRepository->getById($id);

		if ( ! $this->createTestServer) {
			$this->error();
		}
	}


	public function renderDefault(int $id)
	{
		$this->template->createTestServer = $this->createTestServer;
	}


	protected function createComponentPublishCreateTestServerStatus() : \CI\DashBoard\Controls\PublishCreateTestServerStatus\Control
	{
		return $this->publishCreateTestServerStatusFactory->create($this->createTestServer);
	}


	protected function createComponentRerunCreateTestServer() : \CI\DashBoard\Controls\RerunCreateTestServer\Control
	{
		return $this->rerunCreateTestServer->create($this->createTestServer);
	}

}
