<?php declare(strict_types = 1);

namespace CI\Builds\CreateTestServer;

class BuildLocator
{

	public function getPath(string $repositoryName, int $pullRequestNumber = NULL): string
	{
		if ($pullRequestNumber) {
			return sprintf('/var/www/%s/test%d', strtolower($repositoryName), $pullRequestNumber);
		} else {
			return sprintf('/var/www/%s/staging', strtolower($repositoryName));
		}
	}
}
