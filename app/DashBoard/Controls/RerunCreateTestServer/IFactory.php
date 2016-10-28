<?php

namespace CI\DashBoard\Controls\RerunCreateTestServer;

interface IFactory
{

	public function create(\CI\Builds\CreateTestServer\CreateTestServer $createTestServer) : Control;
}
