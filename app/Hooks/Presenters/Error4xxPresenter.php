<?php declare(strict_types = 1);

namespace CI\Hooks\Presenters;

use Nette;


class Error4xxPresenter extends Nette\Application\UI\Presenter
{

	public function startup()
	{
		parent::startup();
		if ( ! $this->getRequest()->isMethod(Nette\Application\Request::FORWARD)) {
			$this->error();
		}
	}


	public function renderDefault(Nette\Application\BadRequestException $exception)
	{
		$this->sendResponse(new Nette\Application\Responses\TextResponse($exception->getMessage()));
	}

}
