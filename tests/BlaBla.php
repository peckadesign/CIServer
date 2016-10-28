<?php

namespace CITests;

include __DIR__ . '/bootstrap.php';

use Tester;


class BlaBla extends DatabaseTestCase
{

	public function testOne()
	{
		Tester\Assert::true(TRUE);
	}
}


(new BlaBla())->run();
