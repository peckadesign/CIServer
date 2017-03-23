<?php declare(strict_types = 1);

namespace CI\DashBoard\Controls\PublishBuildRequestStatus;

use CI;


interface IFactory
{

	public function create(CI\Builds\Tests\BuildRequest $buildRequest) : Control;

}
