<?php

namespace CI\GitHub;

use Nextras;


class RepositoriesRepository extends Nextras\Orm\Repository\Repository
{

	public static function getEntityClassNames()
	{
		return [
			Repository::class,
		];
	}
}
