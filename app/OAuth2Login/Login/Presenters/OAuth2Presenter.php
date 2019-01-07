<?php declare(strict_types = 1);

namespace CI\OAuth2Login\Login\Presenters;

final class OAuth2Presenter extends \Nette\Application\UI\Presenter
{

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

	/**
	 * @var \CI\User\UsersRepository
	 */
	private $usersRepository;


	public function __construct(
		\CI\OAuth2Login\PeckaNotesProvider $authProvider,
		\CI\OAuth2Login\StateStorage $stateStorage,
		\CI\OAuth2Login\Login\BackLinkStorage $backLinkStorage,
		\CI\User\UsersRepository $usersRepository
	) {
		parent::__construct();
		$this->authProvider = $authProvider;
		$this->stateStorage = $stateStorage;
		$this->backLinkStorage = $backLinkStorage;
		$this->usersRepository = $usersRepository;
	}


	public function actionAuthorize(?string $code = NULL, ?string $state = NULL): void
	{
		$this->stateStorage->validateState($state) || $this->error('Požadavek není validní, zkuste se přihlásit znovu.', \Nette\Http\IResponse::S403_FORBIDDEN);

		try {
			$accessToken = $this->authProvider->getAccessToken('authorization_code', [
				'code' => $code,
			]);

			/** @var \CI\OAuth2Login\User $peckanotesUser */
			$peckanotesUser = $this->authProvider->getResourceOwner($accessToken);

			$options = [
				'email' => $peckanotesUser->getId(),
			];
			$user = $this->usersRepository->getBy($options);

			if ( ! $user) {
				$user = new \CI\User\User();
				$user->email = $peckanotesUser->getId();
				$user->gitHubName = $peckanotesUser->getFirstName() . ' ' . $peckanotesUser->getLastName();
			}

			$user->oauth2token = \Nette\Utils\Json::encode($accessToken);
			$this->usersRepository->persistAndFlush($user);
			$this->user->login($user);
		} catch (\League\OAuth2\Client\Provider\Exception\IdentityProviderException $e) {
			throw $e;
		}

		$backLink = $this->backLinkStorage->getBackLink();
		if ($backLink) {
			$this->restoreRequest($backLink);
			$this->redirectUrl($backLink);
		} else {
			$this->redirect(':DashBoard:HomePage');
		}
	}

}
