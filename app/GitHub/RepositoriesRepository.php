<?php

namespace CI\GitHub;

use Nextras;


/**
 * @method Repository getBy(array $conds)
 */
class RepositoriesRepository extends Nextras\Orm\Repository\Repository
{

	public static function getEntityClassNames()
	{
		return [
			Repository::class,
		];
	}
}
