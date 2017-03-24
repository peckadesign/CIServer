<?php declare(strict_types = 1);

namespace CI\DashBoard\Controls\RecentCreateTestServer;


class Control extends \Nette\Application\UI\Control
{

	/**
	 * @var \CI\Builds\CreateTestServer\CreateTestServersRepository
	 */
	private $createTestServersRepository;


	public function __construct(
		\CI\Builds\CreateTestServer\CreateTestServersRepository $createTestServersRepository
	) {

		$this->createTestServersRepository = $createTestServersRepository;
	}


	public function render()
	{
		$this->template->setFile(__DIR__ . '/Control.latte');
		$this->template->createTestServers = $this->createTestServersRepository->findAll()->orderBy('start', \Nextras\Orm\Collection\ICollection::DESC)->limitBy(5);
		$this->template->render();
	}

}
