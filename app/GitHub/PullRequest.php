<?php

namespace CI\GitHub;

use Nextras;

/**
 * @property int $id {primary}
 * @property int $number
 * @property Repository $repository {m:1 Repository::$pullRequests}
 */
class PullRequest extends Nextras\Orm\Entity\Entity
{

}
