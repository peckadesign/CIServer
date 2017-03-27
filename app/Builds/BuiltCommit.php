<?php declare(strict_types=1);

namespace CI\Builds;

class BuiltCommit implements \JsonSerializable
{

	/**
	 * @var int
	 */
	private $repositoryId;

	/**
	 * @var int|null
	 */
	private $buildId;

	/**
	 * @var string
	 */
	private $commit;


	public function __construct(
		int $repositoryId,
		?int $buildId,
		string $commit
	) {
		$this->repositoryId = $repositoryId;
		$this->buildId = $buildId;
		$this->commit = $commit;
	}


	public function getRepositoryId(): int
	{
		return $this->repositoryId;
	}


	public function getBuildId(): ?int
	{
		return $this->buildId;
	}


	public function getCommit(): string
	{
		return $this->commit;
	}


	public function jsonSerialize(): array
	{
		return ['repositoryId' => $this->repositoryId, 'buildId' => $this->buildId, 'commit' => $this->commit];
	}


	/**
	 * @throws \Nette\Utils\JsonException
	 */
	public static function fromJson(string $jsonString): self
	{
		$json = \Nette\Utils\Json::decode($jsonString, \Nette\Utils\Json::FORCE_ARRAY);

		if (empty($json['repositoryId']) || empty($json['commit'])) {
			throw new \Nette\Utils\JsonException('Zpráva neobsahuje povinná pole');
		}

		return new self((int) $json['repositoryId'], isset($json['buildId']) ? (int) $json['buildId'] : NULL, (string) $json['commit']);
	}
}
