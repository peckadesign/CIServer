<?php declare(strict_types = 1);

namespace CI\DashBoard\Presenters;

final class LoginPresenter extends \Nette\Application\UI\Presenter
{

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

	private \Psr\Log\LoggerInterface $logger;


	public function __construct(
		\CI\User\UsersRepository $users,
		\CI\OAuth2Login\PeckaNotesProvider $authProvider,
		\CI\OAuth2Login\StateStorage $stateStorage,
		\CI\OAuth2Login\Login\BackLinkStorage $backLinkStorage,
		\Psr\Log\LoggerInterface $logger
	) {
		parent::__construct();
		$this->users = $users;
		$this->authProvider = $authProvider;
		$this->stateStorage = $stateStorage;
		$this->backLinkStorage = $backLinkStorage;
		$this->logger = $logger;
	}


	public function actionDefault(): void
	{
		$authorizationUrl = $this->authProvider->getAuthorizationUrl();

		$this->stateStorage->saveState($this->authProvider->getState());
		$this->backLinkStorage->saveBackLink($this->backLink);

		$this->logger->debug('Dojde k přesměrování pro přihlášení s GitHubem: $authorizationUrl = ' . $authorizationUrl . ', $state = ' . $this->authProvider->getState());

		$this->redirectUrl($authorizationUrl);
	}

}
