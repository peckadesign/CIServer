<?php declare(strict_types = 1);

namespace CI\Builds\Cypress;

class PublishCypress implements \CI\Builds\IOnBuildReady
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
		\CI\GitHub\Repository $repository,
		?\CI\Builds\CreateTestServer\CreateTestServer $createTestServer,
		string $commit
	) {
		$cwd = $this->buildLocator->getPath($repository->name, $createTestServer ? $createTestServer->pullRequestNumber : NULL);

		if (is_readable($cwd . '/Makefile') && ($content = file_get_contents($cwd . '/Makefile')) && \preg_match('~\ncypress:~', $content)) {
			$builtCommit = new \CI\Builds\BuiltCommit($repository->id, $createTestServer ? $createTestServer->id : NULL, $commit);
			$publishData = \Nette\Utils\Json::encode($builtCommit);
			$logger->addInfo('Sestavení obsahuje Cypress, bude spuštěn: ' . $publishData, ['commit' => $commit]);
			$this->producer->publish($publishData);
		} else {
			$logger->addInfo('Sestavení neobsahuje Cypress', ['commit' => $commit]);
		}
	}
}
