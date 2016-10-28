<?php

namespace CI\Hooks;

/**
 * @method OpenedPullRequest getById(int $id)
 */
class OpenedPullRequestsRepository extends \Nextras\Orm\Repository\Repository
{

	public static function getEntityClassNames()
	{
		return [
			OpenedPullRequest::class,
		];
	}
}
