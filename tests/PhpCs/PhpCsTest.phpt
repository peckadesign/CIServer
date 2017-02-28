<?php

namespace CITests\PhpCs;

include __DIR__ . '/../bootstrap.php';


class PhpCsTest extends \Tester\TestCase
{

	public function getTestData()
	{
		return [
			[
				file_get_contents(__DIR__ . '/OnlyWarnings.txt'), 0, 2,
			],
			[
				file_get_contents(__DIR__ . '/OnlyErrors.txt'), 5, 0,
			],
		];
	}


	/**
	 * @dataProvider getTestData
	 */
	public function testCommon(string $input, int $errors, int $warnings)
	{
		$tap = new \CI\PhpCs\PhpCs($input);
		\Tester\Assert::equal($errors, $tap->getErrors());
		\Tester\Assert::equal($warnings, $tap->getWarnings());
	}
}


(new PhpCsTest())->run();
