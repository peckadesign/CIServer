<?php declare(strict_types = 1);

namespace CI\DashBoard\Presenters;

use CI;


class HomePagePresenter extends BasePresenter
{

	/**
	 * @var CI\GitHub\RepositoriesRepository
	 */
	private $repositories;

	/**
	 * @var \Kdyby\Monolog\Logger
	 */
	private $logger;


	public function __construct(
		CI\GitHub\RepositoriesRepository $repositories,
		\Kdyby\Monolog\Logger $logger
	) {
		parent::__construct();

		$this->repositories = $repositories;
		$this->logger = $logger;
	}


	public function renderDefault()
	{
		$this->template->repositories = $this->repositories->findAll();
		$logger = $this->logger->channel('runTests');
		$logger->addInfo('ahoj', ['commit' => '8d0d4fdc0975d962256f0de2603621ec148f82db']);
	}
}
