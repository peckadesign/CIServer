<?php

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
		$this->template->createTestServers = $this->createTestServersRepository->findAll();
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
