<?php

namespace CI\Builds\CreateTestServer;

/**
 * @method CreateTestServer getBy(array $conds)
 * @method CreateTestServer getById(int $id)
 */
class CreateTestServersRepository extends \Nextras\Orm\Repository\Repository
{

	public static function getEntityClassNames()
	{
		return [
			CreateTestServer::class,
		];
	}
}
