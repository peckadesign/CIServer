<?php

namespace AppTest;

use Tester;

class OneTest extends Tester\TestCase
{

	public function testOne()
	{
		Tester\Assert::true(TRUE);
	}
}

(new OneTest())->run();
