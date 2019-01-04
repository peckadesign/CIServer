<?php declare(strict_types = 1);

namespace CI\DashBoard\Presenters;

use CI;
use Kdyby;

class UserPresenter extends BasePresenter
{

	/**
	 * @var CI\User\UsersRepository
	 */
	private $usersRepository;

	/**
	 * @var Kdyby\Github\Client
	 */
	private $gitHub;


	public function __construct(
		CI\User\UsersRepository $usersRepository,
		\Kdyby\Github\Client $gitHub
	) {
		parent::__construct();

		$this->usersRepository = $usersRepository;
		$this->gitHub = $gitHub;
	}


	public function renderDefault()
	{
		$this->template->users = $this->usersRepository->findAll();
	}


	public function handleSetAsSystemUser($id)
	{
		$user = $this->usersRepository->getById($id);

		$user->systemUser = 1;

		$this->usersRepository->persistAndFlush($user);

		$this->redirect('this');
	}


	protected function createComponentGitHubLogin() : \Kdyby\Github\UI\LoginDialog
	{
		$dialog = new Kdyby\Github\UI\LoginDialog($this->gitHub);

		$dialog->onResponse[] = function (Kdyby\Github\UI\LoginDialog $dialog) {
			/** @var Kdyby\Github\Client $gitHub */
			$gitHub = $dialog->getClient();

			if ( ! $gitHub->getUser()) {
				$this->flashMessage("Sorry bro, github authentication failed.");

				return;
			}

			try {
				$me = $gitHub->api('/user');

				$user = $this->usersRepository->getById($this->getUser()->getId());
				$user->gitHubToken = $gitHub->getAccessToken();
				$this->usersRepository->persistAndFlush($user);

				$this->getUser()->login($user);
			} catch (Kdyby\Github\ApiException $e) {

				\Tracy\Debugger::log($e, 'github');
				$this->flashMessage("Sorry bro, github authentication failed hard.");
			}

			$this->redirect('this');
		};

		return $dialog;
	}

}
