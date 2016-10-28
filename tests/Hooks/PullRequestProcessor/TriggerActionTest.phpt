<?php

namespace CITests\Hooks\PullRequestProcessor;

use CI;
use Tester;


include __DIR__ . '/../../bootstrap.php';


class TriggerActionTest extends Tester\TestCase
{

	public function getTestWrongActionData()
	{
		return [
			[
				CI\Hooks\PullRequestProcessor::ACTION_OPENED,
			], [
				CI\Hooks\PullRequestProcessor::ACTION_SYNCHRONIZED,
			],

		];
	}


	/**
	 * @dataProvider getTestWrongActionData
	 * @param string $action
	 */
	public function testTriggerAction(string $action)
	{
		$queue = new QueueStub();

		$testMapper = new \Nextras\Orm\TestHelper\TestMapper();
		$repositoriesRepository = new CI\GitHub\RepositoriesRepository($testMapper);


		$pullRequestProcessor = new CI\Hooks\PullRequestProcessor($repositoriesRepository, $queue);

		$pullRequestNumber = rand(1, 100);
		$repositoryName = 'repositoryName' . rand(1, 100);

		$data = [
			'action' => $action,
			'number' => $pullRequestNumber,
			'repository' => [
				'name' => $repositoryName,
			],
		];

		Tester\Assert::true($pullRequestProcessor->process($data));
		Tester\Assert::equal($pullRequestNumber, $queue->pullRequestNumber);
		Tester\Assert::equal($repositoryName, $queue->repositoryName);
	}
}


(new TriggerActionTest())->run();
