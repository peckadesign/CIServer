<?php declare(strict_types = 1);

namespace CI\Builds\CreateTestServer;

/**
 * @property int $id {primary}
 * @property int|NULL $pullRequestNumber
 * @property string $branchName
 * @property string|NULL $commit
 * @property int|NULL $succeeded
 * @property int|NULL $failed
 * @property \DateTimeImmutable|NULL $start
 * @property \DateTimeImmutable|NULL $finish
 * @property bool $success {default FALSE}
 * @property bool $closed {default FALSE}
 * @property \DateTimeImmutable|NULL $updateStart
 * @property \DateTimeImmutable|NULL $updateFinish
 * @property \CI\GitHub\Repository $repository {m:1 \CI\GitHub\Repository::$createTestServer}
 */
class CreateTestServer extends \Nextras\Orm\Entity\Entity implements
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
