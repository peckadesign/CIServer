<?php declare(strict_types = 1);

namespace CI\Builds\PhpCs;

class PublishPhpCs implements \CI\Builds\IOnBuildReady
{

	/**
	 * @var \CI\Builds\CreateTestServer\BuildLocator
	 */
	private $buildLocator;

	/**
	 * @var \Kdyby\RabbitMq\IProducer
	 */
	private $producer;


	public function __construct(
		\CI\Builds\CreateTestServer\BuildLocator $buildLocator,
		\Kdyby\RabbitMq\IProducer $producer

	) {
		$this->buildLocator = $buildLocator;
		$this->producer = $producer;
	}


	public function buildReady(
		\Monolog\Logger $logger,
		\CI\Builds\CreateTestServer\CreateTestServer $createTestServer,
		string $commit
	) {
		$cwd = $this->buildLocator->getPath($createTestServer->repository->name, $createTestServer->pullRequestNumber);

		if (1 || is_readable($cwd . '/Makefile') && ($content = file_get_contents($cwd . '/Makefile')) && strpos($content, 'cs:') !== FALSE) {
			$builtCommit = new \CI\Builds\BuiltCommit($createTestServer->id, $commit);
			$publishData = \Nette\Utils\Json::encode($builtCommit);
			$logger->addInfo('Sestavení obsahuje coding standard, bude spuštěn: ' . $publishData, ['commit' => $commit]);
			$this->producer->publish($publishData);
		} else {
			$logger->addInfo('Sestavení neobsahuje coding standard', ['commit' => $commit]);
		}
	}
}
