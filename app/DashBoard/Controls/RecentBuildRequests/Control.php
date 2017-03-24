<?php declare(strict_types = 1);

namespace CI\DashBoard\Controls\RecentBuildRequests;

class Control extends \Nette\Application\UI\Control
{

	/**
	 * @var \CI\Builds\Tests\BuildRequestsRepository
	 */
	private $buildRequestsRepository;


	public function __construct(
		\CI\Builds\Tests\BuildRequestsRepository $buildRequestsRepository
	) {

		$this->buildRequestsRepository = $buildRequestsRepository;
	}


	public function render()
	{
		$this->template->setFile(__DIR__ . '/Control.latte');
		$this->template->buildRequests = $this->buildRequestsRepository->findAll()->limitBy(5)->orderBy('start', \Nextras\Orm\Collection\ICollection::DESC);
		$this->template->render();
	}

}
