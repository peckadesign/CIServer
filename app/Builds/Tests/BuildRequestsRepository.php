<?php

namespace CI\Builds\Tests;

use Nextras;


/**
 * @method BuildRequest getBy(array $conds)
 */
class BuildRequestsRepository extends Nextras\Orm\Repository\Repository
{

	public static function getEntityClassNames()
	{
		return [
			BuildRequest::class,
		];
	}
}
