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
	 * @var \League\OAuth2\Client\Provider\Github
	 */
	private $gitHub;

	/**
	 * @var string
	 * @persistent
	 */
	public $backLink;


	public function __construct(
		CI\User\UsersRepository $usersRepository,
		\League\OAuth2\Client\Provider\Github $gitHub
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


	public function handleGitHub(): void
	{
		if ($this->user->isLoggedIn()) {
			$authUrl = $this->gitHub->getAuthorizationUrl(['state' => $this->storeRequest()]);
			$this->redirectUrl($authUrl);
		}
	}

}
