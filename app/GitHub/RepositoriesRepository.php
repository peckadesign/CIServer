<?php declare(strict_types = 1);

namespace CI\GitHub;

use Nextras;


/**
 * @method Repository getBy(array $conds)
 * @method Repository getById(int $id)
 */
class RepositoriesRepository extends Nextras\Orm\Repository\Repository
{

	public static function getEntityClassNames(): array
	{
		return [
			Repository::class,
		];
	}
}
