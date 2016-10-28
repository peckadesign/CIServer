<?php

namespace CI\Builds\Tests;

use CI;


interface IStatusPublicator
{

	public function publish(BuildRequest $buildRequest);

}
