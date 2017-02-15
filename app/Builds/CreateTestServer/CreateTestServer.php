<?php

namespace CI\Builds\CreateTestServer;

/**
 * @property int $id {primary}
 * @property int $pullRequestNumber
 * @property string $branchName
 * @property string $commit
 * @property int|NULL $succeeded
 * @property int|NULL $failed
 * @property string|NULL $output
 * @property \DateTime|NULL $start
 * @property \DateTime|NULL $finish
 * @property bool $success {default FALSE}
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
