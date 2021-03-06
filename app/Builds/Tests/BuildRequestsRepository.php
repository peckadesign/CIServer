<?php declare(strict_types = 1);

namespace CI\Builds\Tests;

use Nextras;


/**
 * @method BuildRequest getBy(array $conds)
 */
class BuildRequestsRepository extends Nextras\Orm\Repository\Repository
{

	public static function getEntityClassNames(): array
	{
		return [
			BuildRequest::class,
		];
	}
}
