<?php declare(strict_types=1);

namespace CI\GitHub;

/**
 * @property int $id {primary}
 * @property string $name
 * @property \Nextras\Orm\Relationships\OneHasMany|PullRequest[] $pullRequests {1:m PullRequest::$repository}
 * @property \Nextras\Orm\Relationships\OneHasMany|\CI\Builds\Tests\BuildRequest[] $buildRequests {1:m \CI\Builds\Tests\BuildRequest::$repository}
 * @property \Nextras\Orm\Relationships\OneHasMany|\CI\Builds\CreateTestServer\CreateTestServer[] $createTestServer {1:m \CI\Builds\CreateTestServer\CreateTestServer::$repository}
 * @property \Nextras\Orm\Relationships\OneHasMany|\CI\Hooks\PullRequest[] $pullRequest {1:m \CI\Hooks\PullRequest::$repository}
 */
class Repository extends \Nextras\Orm\Entity\Entity
{

}
