<?php declare(strict_types = 1);

namespace CI\Orm;

use CI;
use Nextras;


/**
 * @property-read CI\GitHub\RepositoriesRepository $repositories
 * @property-read CI\GitHub\PullRequestsRepository $pullRequests
 * @property-read CI\Builds\Tests\BuildRequestsRepository $buildRequests
 * @property-read CI\User\UsersRepository $users
 * @property-read CI\Hooks\PullRequestsRepository $pullRequestsHooks
 * @property-read CI\Builds\CreateTestServer\CreateTestServersRepository $createTestServers
 */
class Orm extends Nextras\Orm\Model\Model
{

}
