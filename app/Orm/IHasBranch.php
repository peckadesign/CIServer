<?php

namespace CI\Orm;

interface IHasBranch
{

	public function getBranchName() : string;


	public function getBranchRepository() : \CI\GitHub\Repository;

}
