<?php

namespace CI\DashBoard\Presenters;

class PhpCsPresenter extends BasePresenter
{

	/**
	 * @var string
	 */
	private $outputDirectory;


	public function __construct(
		string $outputDirectory
	) {
		$this->outputDirectory = $outputDirectory;
	}


	public function actionOutput(string $id = NULL)
	{
		$filename = sprintf("%s/%s.cs", $this->outputDirectory, $id);
		if ( ! is_readable($filename)) {
			$this->error();
		}

		$this->getHttpResponse()->setContentType('text/plain');
		$this->sendResponse(new \Nette\Application\Responses\TextResponse(file_get_contents($filename)));
	}
}
