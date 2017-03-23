<?php declare(strict_types = 1);

namespace CI\User;

use CI;
use Nextras;


/**
 * @method User getById($id)
 * @method User getBy(array $conds)
 */
class UsersRepository extends Nextras\Orm\Repository\Repository
{

	public static function getEntityClassNames()
	{
		return [
			User::class,
		];
	}
}
