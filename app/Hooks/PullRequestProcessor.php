<?php

namespace CI\Hooks;

use CI;
use Nette;


class PullRequestProcessor
{

	use Nette\SmartObject;

	const ACTION_SYNCHRONIZED = 'synchronized';
	const ACTION_OPENED = 'opened';

	/**
	 * @var \CI\Queue\IQueue
	 */
	private $queue;

	/**
	 * @var CI\Orm\Orm
	 */
	private $orm;


	public function __construct(
		CI\Orm\Orm $orm
//		CI\Queue\IQueue $queue
	) {
//		$this->queue = $queue;
		$this->orm = $orm;
	}


	public function process(array $input)
	{

		if ( ! in_array($input['action'], [self::ACTION_OPENED, self::ACTION_SYNCHRONIZED])) {
			return FALSE;
		}

		$repositoryName = $input['repository']['name'];
		$pullRequestNumber = $input['number'];

		$conditions = [
			'name' => $repositoryName,
		];
		/** @var CI\GitHub\Repository $repository */
		$repository = $this->orm->repositories->getBy($conditions);

		if ( ! $repository) {
			$repository = new CI\GitHub\Repository();
			$repository->name = $repositoryName;
			$this->orm->repositories->persistAndFlush($repository);
		}

		$conditions = [
			'number' => $pullRequestNumber,
		];
		/** @var CI\GitHub\PullRequest $pullRequest */
		$pullRequest = $this->orm->pullRequests->getBy($conditions);

		if ( ! $pullRequest) {
			$pullRequest = new CI\GitHub\PullRequest();
			$pullRequest->number = $pullRequestNumber;
			$pullRequest->repository = $repository;
			$this->orm->pullRequests->persistAndFlush($pullRequest);
		}

		//		$this->queue->enQueue($repositoryName, $input['number']);

		return TRUE;
	}
}
