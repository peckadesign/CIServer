<?php declare(strict_types = 1);

namespace CI\Hooks;

class OpenedPullRequest extends PullRequest
{

	public function __construct()
	{
		parent::__construct();
		$this->type = static::TYPE_OPENED;
	}

}
