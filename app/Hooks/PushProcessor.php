<?php

namespace CI\Hooks;

use CI;


class PushProcessor
{

	use \Nette\SmartObject;

	/**
	 * @var CI\Queue\IQueue
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

		$repositoryName = $input['repository']['full_name'];
		$commit = $input['after'];
		$refs = explode('/', $input['ref']);
		$branch = end($refs);

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
			'commit' => $commit,
		];
		/** @var CI\GitHub\BuildRequest $buildRequest */
		$buildRequest = $this->orm->buildRequests->getBy($conditions);
		if ( ! $buildRequest) {
			$buildRequest = new CI\GitHub\BuildRequest();
			$buildRequest->commit = $commit;
			$buildRequest->branch = $branch;
			$buildRequest->repository = $repository;
			$this->orm->buildRequests->persistAndFlush($buildRequest);
		}

		//		$this->queue->enQueue($repositoryName, $input['number']);

		return TRUE;
	}
}
