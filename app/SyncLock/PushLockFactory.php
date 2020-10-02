<?php declare(strict_types = 1);

namespace CI\SyncLock;

class PushLockFactory
{
	public function create(string $path): PushLock
	{
		return new PushLock($path, new \DateInterval("PT5M"));
	}
}
