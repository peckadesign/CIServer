<?php declare(strict_types = 1);

namespace CI\GitHub;

class RepositoryFacade
{

	/**
	 * @var RepositoriesRepository
	 */
	private $repositoriesRepository;


	public function __construct(
		RepositoriesRepository $repositoriesRepository
	) {
		$this->repositoriesRepository = $repositoriesRepository;
	}


	public function getRepository(string $name): Repository
	{
		$repositoryName = $name;
		$conditions = [
			'name' => $repositoryName,
		];
		$repository = $this->repositoriesRepository->getBy($conditions);

		if ( ! $repository) {
			$repository = new \CI\GitHub\Repository();
			$repository->name = $repositoryName;
			$repository = $this->repositoriesRepository->persist($repository);
		}

		if ( ! $repository) {
			throw new \CI\Exception('Nepodařilo se založit repozitář');
		}

		return $repository;
	}
}
