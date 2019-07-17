<?php declare(strict_types = 1);

namespace CI\Builds\CreateTestServer;

/**
 * @method CreateTestServer getBy(array $conds)
 * @method CreateTestServer getById(int $id)
 * @method \Nextras\Orm\Collection\ICollection|CreateTestServer[] findBy(array $conds)
 */
class CreateTestServersRepository extends \Nextras\Orm\Repository\Repository
{

	public static function getEntityClassNames(): array
	{
		return [
			CreateTestServer::class,
		];
	}
}
