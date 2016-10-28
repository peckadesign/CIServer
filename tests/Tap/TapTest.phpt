<?php

namespace CITests\Tap;

include __DIR__ . '/../bootstrap.php';

use CI;
use Tester;


class TapTest extends Tester\TestCase
{

	public function getTestData()
	{
		return [
			[
				file_get_contents(__DIR__ . '/AllFailed.tap'), 0, 4,
			],
			[
				file_get_contents(__DIR__ . '/AllSucceded.tap'), 4, 0,
			],
			[
				file_get_contents(__DIR__ . '/Mixed.tap'), 1, 2,
			],
		];
	}

	/**
	 * @dataProvider getTestData
	 */
	public function testCommon(string $input, int $succeeded, int $failed)
	{
		$tap = new CI\Tap\Tap($input);
		Tester\Assert::equal($succeeded, $tap->getSucceeded());
		Tester\Assert::equal($failed, $tap->getFailed());
	}
}


(new TapTest())->run();
