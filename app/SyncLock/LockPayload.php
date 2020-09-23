<?php declare(strict_types = 1);

namespace CI\SyncLock;

class LockPayload implements \JsonSerializable
{

	/**
	 * @var bool
	 */
	private $locked;

	/**
	 * @var int
	 */
	private $timestamp;


	public function __construct(
		bool $locked,
		int $timestamp
	)
	{
		$this->locked = $locked;
		$this->timestamp = $timestamp;
	}


	public function isLocked(): bool
	{
		return $this->locked;
	}


	public function getTimestamp(): int
	{
		return $this->timestamp;
	}


	public function jsonSerialize(): array
	{
		return [
			'locked' => $this->isLocked(),
			'timestamp' => $this->getTimestamp(),
		];
	}


	public static function createFromStdClass(\stdClass $payload): self
	{
		return new self($payload->locked, $payload->timestamp);
	}
}
