<?php declare(strict_types = 1);

namespace CI\Router;

use CI;
use Nette;


class RouterFactory
{

	use Nette\SmartObject;

	/**
	 * @var string
	 */
	private $wwwDir;

	/**
	 * @var Nette\Caching\IStorage
	 */
	private $storage;


	public function __construct(
		$wwwDir,
		Nette\Caching\IStorage $storage
	) {
		$this->wwwDir = $wwwDir;
		$this->storage = $storage;
	}


	public function createRouter() : Nette\Application\IRouter
	{
		$router = new Nette\Application\Routers\RouteList();

		$metadata = [
			'module' => 'DashBoard',
			'presenter' => 'HomePage',
			'action' => 'default',
		];
		$router[] = new Nette\Application\Routers\Route('<module>/<presenter>/<action>[/<id>]', $metadata);

		$metadata = [
			NULL => [
				Nette\Application\Routers\Route::FILTER_OUT => function (array $parameters) {
					if (isset($parameters['hasBranch']) && ($hasBranch = $parameters['hasBranch']) && $hasBranch instanceof CI\Orm\IHasBranch) {
						$parameters = [];
						$parameters['repository'] = $hasBranch->getBranchRepository()->name;
						$parameters['branch'] = $hasBranch->getBranchName();
					}

					return $parameters;
				},
			],
		];
		$router[] = new Nette\Application\Routers\Route('https://github.com/peckadesign/<repository [a-zA-Z0-9/]+>/tree/<branch>', $metadata);

		$router[] = new \Nette\Application\Routers\Route('oauth2/<action>', [
			'module' => 'OAuth2Login:Login',
			'presenter' => 'OAuth2',
		]);

		$metadata = [
			NULL => [
				Nette\Application\Routers\Route::FILTER_OUT => function (array $parameters) {
					if (isset($parameters['createTestServer']) && ($build = $parameters['createTestServer']) && $build instanceof CI\Builds\CreateTestServer\CreateTestServer) {
						$parameters = [];
						$parameters['repository'] = $build->repository->name;
						$parameters['pullRequestNumber'] = $build->pullRequestNumber;
					}

					return $parameters;
				},
			],
		];
		$router[] = new Nette\Application\Routers\Route('https://test<pullRequestNumber [0-9]+>.<repository [a-zA-Z0-9/\-]+>.peckadesign.com', $metadata);

		return $router;
	}

}
