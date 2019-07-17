<?php declare(strict_types = 1);

namespace CI\Hooks;

class PullRequestsMapper extends \Nextras\Orm\Mapper\Mapper
{

	public function getTableName(): string
	{
		return 'pull_requests_hooks';
	}

}
