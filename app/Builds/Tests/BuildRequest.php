<?php declare(strict_types = 1);

namespace CI\Builds\Tests;

/**
 * @property int $id {primary}
 * @property string $commit
 * @property string $branchName
 * @property int|NULL $succeeded
 * @property int|NULL $failed
 * @property \DateTimeImmutable|NULL $start
 * @property \DateTimeImmutable|NULL $finish
 * @property \CI\GitHub\Repository $repository {m:1 \CI\GitHub\Repository::$buildRequests}
 */
class BuildRequest extends \Nextras\Orm\Entity\Entity implements
	\CI\Orm\IHasBranch
{

	public function getBranchName() : string
	{
		return $this->branchName;
	}


	public function getBranchRepository() : \CI\GitHub\Repository
	{
		return $this->repository;
	}
}
