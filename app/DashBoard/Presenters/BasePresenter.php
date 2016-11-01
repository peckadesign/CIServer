<?php

namespace CI\DashBoard\Presenters;

use CI;
use Nette;


abstract class BasePresenter extends Nette\Application\UI\Presenter
{

	use TSecuredPresenter;

	const FLASH_MESSAGE_SUCCESS = 'success';
	const FLASH_MESSAGE_INFO = 'info';
	const FLASH_MESSAGE_WARNING = 'warning';
	const FLASH_MESSAGE_ERROR = 'danger';

	/**
	 * @var CI\DashBoard\Controls\RecentBuildRequests\IFactory
	 */
	private $recentBuildRequestsControlFactory;

	/**
	 * @var CI\DashBoard\Controls\Logout\IFactory
	 */
	private $logoutControlFactory;

	/**
	 * @var CI\DashBoard\Controls\RecentCreateTestServer\IFactory
	 */
	private $recentCreateTestServerFactory;

	/**
	 * @var CI\User\UsersRepository
	 */
	private $usersRepository;

	/**
	 * @var CI\DashBoard\Controls\RabbitMQStatus\IFactory
	 */
	private $rabbitMqStatusControlFactory;


	public function injectServices(
		CI\DashBoard\Controls\RecentBuildRequests\IFactory $recentBuildRequestControlFactory,
		CI\DashBoard\Controls\Logout\IFactory $logoutControlFactory,
		CI\DashBoard\Controls\RecentCreateTestServer\IFactory $recentCreateTestServerFactory,
		CI\User\UsersRepository $usersRepository,
		CI\DashBoard\Controls\RabbitMQStatus\IFactory $rabbitMqStatusControlFactory
	) {
		$this->recentBuildRequestsControlFactory = $recentBuildRequestControlFactory;
		$this->logoutControlFactory = $logoutControlFactory;
		$this->recentCreateTestServerFactory = $recentCreateTestServerFactory;
		$this->usersRepository = $usersRepository;
		$this->rabbitMqStatusControlFactory = $rabbitMqStatusControlFactory;
	}


	protected function createComponentRecentBuildRequests() : CI\DashBoard\Controls\RecentBuildRequests\Control
	{
		return $this->recentBuildRequestsControlFactory->create();
	}


	protected function createComponentLogout() : CI\DashBoard\Controls\Logout\Control
	{
		return $this->logoutControlFactory->create();
	}


	protected function createComponentRecentCreateTestServers() : CI\DashBoard\Controls\RecentCreateTestServer\Control
	{
		return $this->recentCreateTestServerFactory->create();
	}


	protected function createComponentRabbitMqStatus() : CI\DashBoard\Controls\RabbitMQStatus\Control
	{
		return $this->rabbitMqStatusControlFactory->create();
	}


	protected function createTemplate()
	{
		$template = parent::createTemplate();

		$template->addFilter('dateTime', function (\DateTime $s) {
			return $s->format('j. n. Y H:i:s');
		});

		return $template;
	}


	protected function beforeRender()
	{
		parent::beforeRender();

		$conditions = [
			'systemUser' => TRUE,
		];
		$systemUser = $this->usersRepository->getBy($conditions);
		if ( ! $systemUser) {
			$this->flashMessage('Není nastaven systémový uživatel', self::FLASH_MESSAGE_ERROR);
		}
	}

}
