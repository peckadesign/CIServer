<?php

namespace Build\Builder;

require __DIR__ . '/../../bootstrap.php';

use CI;
use Nette;
use Nextras;
use Symfony;
use Tester;


class BuildTest extends Tester\TestCase
{

	private $workspaceDir;


	public function __construct()
	{
		$this->workspaceDir = __DIR__ . '/workspace/' . getmypid();
		Nette\Utils\FileSystem::createDir($this->workspaceDir);
		Tester\Helpers::purge($this->workspaceDir);
	}


	protected function setUp()
	{
		parent::setUp();

		$process = new Symfony\Component\Process\Process('cp -R testRepository/* ' . $this->workspaceDir, __DIR__);
		$process->mustRun();

		$process = new Symfony\Component\Process\Process('git init', $this->workspaceDir);
		$process->mustRun();

		$process = new Symfony\Component\Process\Process('git add composer.json tests/ && git commit -m "Initial commit"', $this->workspaceDir);
		$process->mustRun();
	}


	public function testOne()
	{
		$workspaceLocator = new class($this->workspaceDir) implements CI\Build\IWorkspaceLocator
		{

			private $workspaceDir;


			public function __construct(
				$workspaceDir
			) {
				$this->workspaceDir = $workspaceDir;
			}


			public function getPath(CI\GitHub\BuildRequest $buildRequest)
			{
				return $this->workspaceDir;
			}
		};

		$statusPublicator = new class implements CI\Build\IStatusPublicator
		{

			/**
			 * @var CI\GitHub\BuildRequest
			 */
			public $buildRequest;


			public function publish(CI\GitHub\BuildRequest $buildRequest)
			{
				$this->buildRequest = $this->buildRequest;
			}
		};

		$process = new Symfony\Component\Process\Process('git log -n 1 --format="%H"', $this->workspaceDir);
		$process->mustRun();
		$commit = $process->getOutput();

		$testMapper = new Nextras\Orm\TestHelper\TestMapper();
		$buildRequstsRepository = new CI\Builds\Tests\BuildRequestsRepository($testMapper);

		$buildRequst = new CI\Builds\Tests\BuildRequest();
		$buildRequst->commit = $commit;

		$builder = new CI\Builds\Tests\Builder($workspaceLocator, $buildRequstsRepository, $statusPublicator);
		$builder->build($buildRequst);
	}


	protected function tearDown()
	{
		parent::tearDown();

		Nette\Utils\FileSystem::delete($this->workspaceDir);
	}

}


(new BuildTest())->run();
