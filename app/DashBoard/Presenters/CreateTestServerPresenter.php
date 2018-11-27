<?php declare(strict_types = 1);

namespace CI\DashBoard\Presenters;

class CreateTestServerPresenter extends BasePresenter
{

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
	 * @var \CI\DashBoard\Controls\CreateTestServerDataGrid\IFactory
	 */
	private $createTestServerDataGrid;


	public function __construct(
		\CI\Builds\CreateTestServer\CreateTestServersRepository $createTestServersRepository,
		\CI\DashBoard\Controls\PublishCreateTestServerStatus\IFactory $publishCreateTestServerStatusFactory,
		\CI\DashBoard\Controls\CreateTestServerDataGrid\IFactory $createTestServerDataGrid
	) {

		$this->createTestServersRepository = $createTestServersRepository;
		$this->publishCreateTestServerStatusFactory = $publishCreateTestServerStatusFactory;
		$this->createTestServerDataGrid = $createTestServerDataGrid;
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


	protected function createComponentCreateTestServerDataGrid(): \CI\DashBoard\Controls\CreateTestServerDataGrid\Control
	{
		return $this->createTestServerDataGrid->create();
	}

}
