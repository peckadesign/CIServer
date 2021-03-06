<?php declare(strict_types = 1);

namespace CI\DashBoard\Presenters;

trait TSecuredPresenter
{

	public function checkRequirements($element)
	{
		if ( ! $this->user->loggedIn) {
			$this->redirect(':DashBoard:Login:default', ['backLink' => $this->storeRequest()]);
		}
	}

}
