<?php declare(strict_types = 1);

namespace CI\Hooks;

use CI;


class PushProcessor
{

	use \Nette\SmartObject;

	/**
	 * @var \Kdyby\RabbitMq\IProducer
	 */
	private $pushProducer;


	public function __construct(
		\Kdyby\RabbitMq\IProducer $pushProducer
	)
	{
		$this->pushProducer = $pushProducer;
	}


	public function process(array $hookJson)
	{
		$repositoryName = $hookJson['repository']['name'];
		$branchName = $hookJson['ref'];

		$this->pushProducer->publish(\Nette\Utils\Json::encode(['repositoryName' => $repositoryName, 'branchName' => $branchName]));

		return TRUE;
	}
}
