<?php

namespace CI\Builds\Tests;

use CI;


interface IWorkspaceLocator
{

	public function getPath(BuildRequest $buildRequest);
}
