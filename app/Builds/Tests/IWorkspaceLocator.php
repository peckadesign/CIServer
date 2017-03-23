<?php declare(strict_types = 1);

namespace CI\Builds\Tests;

use CI;


interface IWorkspaceLocator
{

	public function getPath(BuildRequest $buildRequest);
}
