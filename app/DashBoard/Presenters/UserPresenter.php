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


	public function __construct(
		CI\User\UsersRepository $usersRepository
	) {
		parent::__construct();

		$this->usersRepository = $usersRepository;
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
}
