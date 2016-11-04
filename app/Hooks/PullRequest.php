<?php

namespace CI\Hooks;

/**
 * @property int $id {primary}
 * @property string $hook
 * @property int $pullRequestNumber
 * @property string $branchName
 * @property string $commit
 * @property \CI\GitHub\Repository $repository {m:1 \CI\GitHub\Repository::$pullRequest}
 * @property int $type {enum self::TYPE_*}
 */
abstract class PullRequest extends \Nextras\Orm\Entity\Entity
{

	const TYPE_SYNCHRONIZED = 0;
	const TYPE_CLOSED = 1;
	const TYPE_OPENED = 2;

	public function setterHook(string $value)
	{
		$valueJson = \Nette\Utils\Json::decode($value, \Nette\Utils\Json::FORCE_ARRAY);
		$pullRequestNumber = $valueJson['pull_request']['number'];
		$branchName = $valueJson['pull_request']['head']['ref'];
		$commit = $valueJson['pull_request']['head']['sha'];

		$this->pullRequestNumber = $pullRequestNumber;
		$this->branchName = $branchName;
		$this->commit = $commit;

		return $value;
	}
}
