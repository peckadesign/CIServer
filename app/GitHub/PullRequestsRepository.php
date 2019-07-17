<?php declare(strict_types = 1);

namespace CI\GitHub;

use Nextras;


class PullRequestsRepository extends Nextras\Orm\Repository\Repository
{

	public static function getEntityClassNames(): array
	{
		return [
			PullRequest::class,
		];
	}
}
