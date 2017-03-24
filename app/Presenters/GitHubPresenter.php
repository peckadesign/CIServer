<?php declare(strict_types = 1);

namespace CI\Presenters;

use CI;
use Nette;


class GitHubPresenter extends Nette\Application\UI\Presenter
{

	public function actionBranch(CI\Orm\IHasBranch $hasBranch)
	{
	}


	public function actionPullRequest()
	{

	}
}
