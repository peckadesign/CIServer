<?php

namespace CI\Builds\Tests;

use CI;
use Kdyby;
use Symfony;


class Builder
{

	/**
	 * @var IWorkspaceLocator
	 */
	private $workspaceLocator;

	/**
	 * @var BuildRequestsRepository
	 */
	private $buildRequestsRepository;

	/**
	 * @var IStatusPublicator
	 */
	private $statusPublicator;

	/**
	 * @var Kdyby\Clock\IDateTimeProvider
	 */
	private $dateTimeProvider;


	public function __construct(
		IWorkspaceLocator $workspaceLocator,
		BuildRequestsRepository $buildRequestsRepository,
		IStatusPublicator $statusPublicator,
		Kdyby\Clock\IDateTimeProvider $dateTimeProvider
	) {
		$this->workspaceLocator = $workspaceLocator;
		$this->buildRequestsRepository = $buildRequestsRepository;
		$this->statusPublicator = $statusPublicator;
		$this->dateTimeProvider = $dateTimeProvider;
	}


	public function build(BuildRequest $buildRequest)
	{
		$buildRequest->start = $this->dateTimeProvider->getDateTime();
		$this->buildRequestsRepository->persistAndFlush($buildRequest);

		$this->statusPublicator->publish($buildRequest);

		$workspace = $this->workspaceLocator->getPath($buildRequest);

		$process = new Symfony\Component\Process\Process('git fetch --prune', $workspace);
		$process->mustRun();

		$process = new Symfony\Component\Process\Process('git checkout ' . $buildRequest->commit, $workspace);
		$process->mustRun();

		$process = new Symfony\Component\Process\Process('composer install --ignore-platform-reqs', $workspace);
		$process->mustRun();

		$process = new Symfony\Component\Process\Process('./vendor/bin/tester -p php -o tap tests/', $workspace);
		$process->run();

		$tapOutput = $process->getOutput();
		$tap = new CI\Tap\Tap($tapOutput);

		$buildRequest->succeeded = $tap->getSucceeded();
		$buildRequest->failed = $tap->getFailed();
		$buildRequest->output = $process->getOutput();
		$buildRequest->finish = $this->dateTimeProvider->getDateTime();

		$this->buildRequestsRepository->persistAndFlush($buildRequest);

		$this->statusPublicator->publish($buildRequest);
	}
}
