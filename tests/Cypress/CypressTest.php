<?php

namespace CITests\Cypress;

include __DIR__ . '/../bootstrap.php';


class CypressTest extends \Tester\TestCase
{

	public function getTestData()
	{
		return [
			[
				file_get_contents(__DIR__ . '/errors.txt'), 3,
			],
			[
				file_get_contents(__DIR__ . '/noErrors.txt'), 0,
			],
		];
	}


	/**
	 * @dataProvider getTestData
	 */
	public function testCommon(string $input, int $errors)
	{
		$tap = new \CI\Cypress\Cypress($input);
		\Tester\Assert::equal($errors, $tap->getErrors());
	}
}


(new CypressTest())->run();
