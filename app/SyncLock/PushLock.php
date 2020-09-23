<?php declare(strict_types = 1);

namespace CI\SyncLock;

class PushLock
{

	/**
	 * @var string
	 */
	private $path;

	/**
	 * @var \DateInterval
	 */
	private $lockExpiration;


	public function __construct(
		string $path,
		\DateInterval $lockExpiration
	)
	{
		$this->path = $path;
		$this->lockExpiration = $lockExpiration;
	}

	public function isLocked(): bool
	{
		try {
			$this->getLock();
		} catch (\CI\SyncLock\Exception\LockNotFoundException $e) {
			return FALSE;
		}

		try {
			$lockPayload = $this->getPayload();
		} catch (\Nette\Utils\JsonException $e) {
			return FALSE;
		}

		if ( ! $lockPayload->isLocked()) {
			return FALSE;
		}

		try {
			return $this->isLockValid();
		} catch (\Nette\Utils\JsonException $e) {
			return FALSE;
		}
	}


	public function checkAndLock(\DateTimeImmutable $now): bool
	{
		if ($this->isLocked()) {
			return FALSE;
		}

		try {
			$filePointer = $this->getLock();
		} catch (\CI\SyncLock\Exception\LockNotFoundException $exception) {
			$this->createLock($now);

			return TRUE;
		}

		$writeResult = $this->safeWrite($filePointer, TRUE, $this->getExpireTime($now));
		\fclose($filePointer);

		return $writeResult;
	}


	public function releaseLock(): bool
	{
		try {
			$filePointer = $this->getLock();
		} catch (\CI\SyncLock\Exception\LockNotFoundException $e) {
			return TRUE;
		}

		$writeResult = $this->safeWrite($filePointer, FALSE, \time());
		\fclose($filePointer);

		return $writeResult;
	}


	/**
	 * @throws \CI\SyncLock\Exception\LockNotFoundException
	 */
	private function getLock()
	{
		if ( ! \is_readable($this->path)) {
			throw new \CI\SyncLock\Exception\LockNotFoundException();
		}

		$filePointer = \fopen($this->path, 'r+');

		if ($filePointer === FALSE) {
			throw new \CI\SyncLock\Exception\LockNotFoundException();
		}

		return $filePointer;
	}


	/**
	 * @throws \Nette\Utils\JsonException
	 */
	private function createLock(\DateTimeImmutable $now): void
	{
		\Nette\Utils\FileSystem::write(
			$this->path,
			\Nette\Utils\Json::encode(new LockPayload(TRUE, $this->getExpireTime($now)))
		);
	}


	/**
	 * @throws \Nette\Utils\JsonException
	 */
	private function isLockValid(): bool
	{
		$lockPayload = $this->getPayload();
		$lockDateTime = \DateTimeImmutable::createFromFormat('U', (string) $lockPayload->getTimestamp());
		$now = new \DateTimeImmutable();

		return $lockDateTime > $now;
	}


	/**
	 * @throws \Nette\Utils\JsonException
	 */
	private function getPayload(): LockPayload
	{
		$payload = \file_get_contents($this->path);
		$lockPayload = \Nette\Utils\Json::decode($payload);

		return LockPayload::createFromStdClass($lockPayload);
	}


	private function safeWrite($filePointer, bool $locked, int $timestamp): bool
	{
		if ( ! \flock($filePointer, \LOCK_EX)) {
			return FALSE;
		}

		\ftruncate($filePointer, 0);
		\fwrite(
			$filePointer,
			\Nette\Utils\Json::encode(
				new LockPayload($locked, $timestamp)
			)
		);
		\flock($filePointer, \LOCK_UN);

		return TRUE;
	}


	private function getExpireTime(\DateTimeImmutable $now): int
	{
		return $now->add($this->lockExpiration)->getTimestamp();
	}
}
