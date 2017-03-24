<?php declare(strict_types = 1);

namespace CI\Builds;

class BuiltCommit implements \JsonSerializable
{

	/**
	 * @var int
	 */
	private $buildId;

	/**
	 * @var string
	 */
	private $commit;


	public function __construct(
		int $buildId,
		string $commit
	) {
		$this->buildId = $buildId;
		$this->commit = $commit;
	}


	public function getBuildId(): int
	{
		return $this->buildId;
	}


	public function getCommit(): string
	{
		return $this->commit;
	}


	public function jsonSerialize(): array
	{
		return ['buildId' => $this->buildId, 'commit' => $this->commit];
	}


	/**
	 * @throws \Nette\Utils\JsonException
	 */
	public static function fromJson(string $jsonString): self
	{
		$json = \Nette\Utils\Json::decode($jsonString, \Nette\Utils\Json::FORCE_ARRAY);

		return new self((int) $json['buildId'], (string) $json['commit']);
	}
}
