<?php

namespace CITests\Build\StatusPublicator;

use CI;
use Tester;


class PublishTest extends Tester\TestCase
{

	public function testCommon()
	{
		$buildRequest = new CI\GitHub\BuildRequest();
	}
}


(new PublishTest())->run();
