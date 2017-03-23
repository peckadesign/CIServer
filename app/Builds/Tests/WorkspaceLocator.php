<?php declare(strict_types = 1);

namespace CI\Builds\Tests;

use CI;


class WorkspaceLocator implements IWorkspaceLocator
{

	/**
	 * @var string
	 */
	private $appDir;


	public function __construct(string $appDir)
	{
		$this->appDir = $appDir;
	}


	public function getPath(BuildRequest $buildRequest): string
	{
		return $this->appDir . '/../repositories/' . $buildRequest->repository->name;
	}
}
