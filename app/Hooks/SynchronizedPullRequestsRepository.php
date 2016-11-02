<?php

namespace CI\Hooks;

/**
 * @method SynchronizedPullRequest getById(int $id)
 */
class SynchronizedPullRequestsRepository extends \Nextras\Orm\Repository\Repository
{

	public static function getEntityClassNames()
	{
		return [
			SynchronizedPullRequest::class,
		];
	}
}
