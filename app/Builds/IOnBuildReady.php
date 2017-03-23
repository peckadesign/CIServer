<?php declare(strict_types = 1);

namespace CI\Builds;

interface IOnBuildReady
{

	public function buildReady(
		\Monolog\Logger $logger,
		\CI\Builds\CreateTestServer\CreateTestServer $createTestServer,
		string $commit
	);

}
