<?php declare(strict_types = 1);

namespace CI\Builds\Tests;

use CI;


interface IStatusPublicator
{

	public function publish(BuildRequest $buildRequest);

}
