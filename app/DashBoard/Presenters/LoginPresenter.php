<?php declare(strict_types = 1);

namespace CI\DashBoard\Presenters;

final class LoginPresenter extends \Nette\Application\UI\Presenter
{

	/**
	 * @var \Kdyby\Github\Client
	 */
	private $github;

	/**
	 * @var string
	 * @persistent
	 */
	public $backLink;

	/**
	 * @var \CI\User\UsersRepository
	 */
	private $users;

	/**
	 * @var \CI\OAuth2Login\PeckaNotesProvider
	 */
	private $authProvider;

	/**
	 * @var \CI\OAuth2Login\StateStorage
	 */
	private $stateStorage;

	/**
	 * @var \CI\OAuth2Login\Login\BackLinkStorage
	 */
	private $backLinkStorage;


	public function __construct(
		\Kdyby\Github\Client $gitHub,
		\CI\User\UsersRepository $users,
		\CI\OAuth2Login\PeckaNotesProvider $authProvider,
		\CI\OAuth2Login\StateStorage $stateStorage,
		\CI\OAuth2Login\Login\BackLinkStorage $backLinkStorage
	) {
		parent::__construct();
		$this->github = $gitHub;
		$this->users = $users;
		$this->authProvider = $authProvider;
		$this->stateStorage = $stateStorage;
		$this->backLinkStorage = $backLinkStorage;
	}


	public function actionDefault(): void
	{
		$authorizationUrl = $this->authProvider->getAuthorizationUrl();

		$this->stateStorage->saveState($this->authProvider->getState());
		$this->backLinkStorage->saveBackLink($this->backLink);

		$this->redirectUrl($authorizationUrl);
	}

}
