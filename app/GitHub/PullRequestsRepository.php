<?php

namespace CI\GitHub;

use Nextras;


class PullRequestsRepository extends Nextras\Orm\Repository\Repository
{

	public static function getEntityClassNames()
	{
		return [
			PullRequest::class,
		];
	}
}
