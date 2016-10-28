<?php

namespace CITests\Hooks\PullRequestProcessor;

use CI;


class QueueStub implements CI\Queue\IQueue
{

	/**
	 * @var int
	 */
	public $pullRequestNumber;

	/**
	 * @var string
	 */
	public $repositoryName;


	public function enQueue(CI\GitHub\Repository $repository, CI\GitHub\PullRequest $pullRequest)
	{

	}
}
