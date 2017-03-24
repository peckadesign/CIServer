<?php declare(strict_types = 1);

namespace CI\Hooks;

/**
 * @method PullRequest getById(int $id)
 */
class PullRequestsRepository extends \Nextras\Orm\Repository\Repository
{

	public static function getEntityClassNames()
	{
		return [
			PullRequest::class,
			SynchronizedPullRequest::class,
			ClosedPullRequest::class,
			OpenedPullRequest::class,
		];
	}


	public function getEntityClassName(array $data)
	{
		if ( ! isset($data['type'])) {
			return parent::getEntityClassName($data);
		} else {
			switch ($data['type']) {
				case PullRequest::TYPE_SYNCHRONIZED:
					return SynchronizedPullRequest::class;

				case PullRequest::TYPE_CLOSED:
					return ClosedPullRequest::class;

				case PullRequest::TYPE_OPENED:
					return OpenedPullRequest::class;

				default:
					throw new \Nextras\Orm\InvalidStateException();
			}
		}
	}

}
