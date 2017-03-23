<?php declare(strict_types = 1);

namespace CI\DashBoard\Controls\PublishCreateTestServerStatus;

use CI;


interface IFactory
{

	public function create(CI\Builds\CreateTestServer\CreateTestServer $createTestServer) : Control;

}
