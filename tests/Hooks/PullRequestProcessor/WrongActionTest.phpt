<?php

namespace CITests\Hooks\PullRequestProcessor;

use CI;
use Tester;


include __DIR__ . '/../../bootstrap.php';


class WrongActionTest extends Tester\TestCase
{

	public function getTestWrongActionData()
	{
		return [
			[
				"assigned",
			], [
				"unassigned",
			], [
				"labeled",
			], [
				"unlabeled",
			], [
				"opened",
			], [
				"edited",
			], [
				"closed",
			], [
				"reopened",
			], [
				"synchronize",
			],

		];
	}


	/**
	 * @dataProvider getTestWrongActionData
	 * @param string $action
	 */
	public function testWrongAction(string $action)
	{
		$pullRequestProcessor = new CI\Hooks\PullRequestProcessor();

		$data = [
			'action' => $action,
		];


		Tester\Assert::false($pullRequestProcessor->process($data));
	}
}


(new WrongActionTest())->run();
