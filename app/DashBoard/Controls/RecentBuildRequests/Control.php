<?php

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
		$this->template->buildRequests = $this->buildRequestsRepository->findAll();
		$this->template->render();
	}


	protected function createTemplate()
	{
		$template = parent::createTemplate();

		$template->addFilter('dateTime', function (\DateTime $s) {
			return $s->format('j. n. Y H:i:s');
		});

		return $template;
	}
}
