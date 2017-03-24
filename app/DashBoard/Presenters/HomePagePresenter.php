<?php declare(strict_types = 1);

namespace CI\DashBoard\Presenters;

use CI;


class HomePagePresenter extends BasePresenter
{

	/**
	 * @var CI\GitHub\RepositoriesRepository
	 */
	private $repositories;


	public function __construct(
		CI\GitHub\RepositoriesRepository $repositories,
		\Kdyby\Monolog\Logger $logger
	) {
		parent::__construct();

		$this->repositories = $repositories;
	}


	public function renderDefault()
	{
		$this->template->repositories = $this->repositories->findAll();
	}
}
