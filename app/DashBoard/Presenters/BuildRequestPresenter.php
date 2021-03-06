<?php declare(strict_types = 1);

namespace CI\DashBoard\Presenters;

use CI;

class BuildRequestPresenter extends BasePresenter
{

	/**
	 * @var CI\Builds\Tests\BuildRequestsRepository
	 */
	private $buildRequests;

	/**
	 * @var CI\Builds\Tests\BuildRequest
	 */
	private $buildRequest;

	/**
	 * @var CI\DashBoard\Controls\PublishBuildRequestStatus\IFactory
	 */
	private $publishBuildRequestStatusFactory;

	/**
	 * @var CI\Monolog\Handlers\CommitLogLocator
	 */
	private $commitLogLocator;

	/**
	 * @var string
	 */
	private $outputDirectory;


	public function __construct(
		string $outputDirectory,
		CI\Builds\Tests\BuildRequestsRepository $buildRequests,
		CI\DashBoard\Controls\PublishBuildRequestStatus\IFactory $publishBuildRequestStatusFactory,
		CI\Monolog\Handlers\CommitLogLocator $commitLogLocator
	) {
		$this->outputDirectory = $outputDirectory;
		$this->buildRequests = $buildRequests;
		$this->publishBuildRequestStatusFactory = $publishBuildRequestStatusFactory;
		$this->commitLogLocator = $commitLogLocator;
	}


	public function actionDefault(int $id)
	{
		$this->buildRequest = $this->buildRequests->getById($id);

		if ( ! $this->buildRequest) {
			$this->error();
		}
	}


	public function renderDefault(int $id)
	{
		$this->template->buildRequest = $this->buildRequest;
		$outputFile = $this->commitLogLocator->getFilePath('runTests', $this->buildRequest->commit);
		$this->template->output = file_exists($outputFile) ? file_get_contents($outputFile) : NULL;
	}


	protected function createComponentPublishBuildRequestStatus(): CI\DashBoard\Controls\PublishBuildRequestStatus\Control
	{
		return $this->publishBuildRequestStatusFactory->create($this->buildRequest);
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
