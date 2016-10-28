<?php

namespace CI\DashBoard\Controls\PublishBuildRequestStatus;

use CI;


interface IFactory
{

	public function create(CI\Builds\Tests\BuildRequest $buildRequest) : Control;

}
