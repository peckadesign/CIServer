<?php

namespace CI\Hooks;

class PullRequestsMapper extends \Nextras\Orm\Mapper\Mapper
{

	public function getTableName()
	{
		return 'pull_requests_hooks';
	}

}