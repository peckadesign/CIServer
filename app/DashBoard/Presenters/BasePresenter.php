<?php declare(strict_types = 1);

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
		CI\DashBoard\Controls\RecentCreateTestServer\IFactory $recentCreateTestServerFactory,
		CI\User\UsersRepository $usersRepository,
		CI\DashBoard\Controls\RabbitMQStatus\IFactory $rabbitMqStatusControlFactory
	) {
		$this->recentBuildRequestsControlFactory = $recentBuildRequestControlFactory;
		$this->recentCreateTestServerFactory = $recentCreateTestServerFactory;
		$this->usersRepository = $usersRepository;
		$this->rabbitMqStatusControlFactory = $rabbitMqStatusControlFactory;
	}


	protected function createComponentRecentBuildRequests() : CI\DashBoard\Controls\RecentBuildRequests\Control
	{
		return $this->recentBuildRequestsControlFactory->create();
	}


	protected function createComponentRecentCreateTestServers() : CI\DashBoard\Controls\RecentCreateTestServer\Control
	{
		return $this->recentCreateTestServerFactory->create();
	}


	protected function createComponentRabbitMqStatus() : CI\DashBoard\Controls\RabbitMQStatus\Control
	{
		return $this->rabbitMqStatusControlFactory->create();
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
