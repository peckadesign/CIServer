<?php declare(strict_types = 1);

namespace CI\GitHub\Presenters;

final class LoginPresenter extends \Nette\Application\UI\Presenter
{

	/**
	 * @var \League\OAuth2\Client\Provider\Github
	 */
	private $github;

	/**
	 * @var \CI\User\UsersRepository
	 */
	private $users;


	public function __construct(
		\League\OAuth2\Client\Provider\Github $gitHub,
		\CI\User\UsersRepository $users
	) {
		parent::__construct();
		$this->github = $gitHub;
		$this->users = $users;
	}


	public function actionDefault(string $code, string $state): void
	{
		try {
			$token = $this->github->getAccessToken('authorization_code', [
				'code' => $code,
			]);

			/** @var \League\OAuth2\Client\Provider\GithubResourceOwner $gitHubUser */
			$gitHubUser = $this->github->getResourceOwner($token);

			$conditions = [
				'gitHubId' => $gitHubUser->getId(),
			];
			if ( ! $user = $this->users->getBy($conditions)) {
				$user = new \CI\User\User();
				$user->gitHubId = $gitHubUser->getId();
				$user->gitHubName = $gitHubUser->getName() ?: $gitHubUser->getNickname();
			}

			$user->gitHubToken = $token->getToken();
			$this->users->persistAndFlush($user);

			$this->getUser()->login($user);
			$this->flashMessage('Přihlášení přes GitHub proběhlo úspěšně', \CI\DashBoard\Presenters\BasePresenter::FLASH_MESSAGE_SUCCESS);
		} catch (\Throwable $e) {
			$this->flashMessage('Přihlášení přes GitHub selhalo', \CI\DashBoard\Presenters\BasePresenter::FLASH_MESSAGE_ERROR);
		}

		$this->redirect(':DashBoard:User:default');
	}

}
