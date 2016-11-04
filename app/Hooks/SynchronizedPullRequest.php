<?php

namespace CI\Hooks;

class SynchronizedPullRequest extends PullRequest
{

	public function __construct()
	{
		parent::__construct();
		$this->type = self::TYPE_SYNCHRONIZED;
	}

}
