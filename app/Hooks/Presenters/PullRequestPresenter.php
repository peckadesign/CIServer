<?php

namespace CI\Hooks\Presenters;

class PullRequestPresenter extends \Nette\Application\UI\Presenter
{

	/**
	 * @var \CI\Hooks\PullRequestProcessor
	 */
	private $pullRequestProcessor;


	public function __construct(
		\CI\Hooks\PullRequestProcessor $pullRequestProcessor
	) {
		$this->pullRequestProcessor = $pullRequestProcessor;
	}


	public function actionDefault()
	{
		$input = file_get_contents('php://input');
		$json = [];

		if ($input) {
			try {
				$json = \Nette\Utils\Json::decode($input, \Nette\Utils\Json::FORCE_ARRAY);
			} catch (\Nette\Utils\JsonException $e) {
				throw new \InvalidArgumentException('Předaná data nebylo možné přečíst', $e->getCode(), $e);
			}
		}

		if ( ! $input || ! $json) {
			$this->error('Předaný hook neobsahuje data', \Nette\Http\IResponse::S400_BAD_REQUEST);
		}

		try {
			$hook = $this->pullRequestProcessor->process($json);
			$this->sendResponse(new \Nette\Application\Responses\TextResponse('Hook přijat pod ID ' . $hook->id));
		} catch (\CI\Hooks\UnKnownHookException $e) {
			$this->error('Předaný hook není podporován', \Nette\Http\IResponse::S200_OK);
		}
	}
}
