<?php

namespace CI\Builds\Tests\Commands;

use CI;
use Symfony;


class BuildCommand extends Symfony\Component\Console\Command\Command
{

	/**
	 * @var CI\Builds\Tests\Builder
	 */
	private $builder;

	/**
	 * @var CI\Builds\Tests\BuildRequestsRepository
	 */
	private $buildRequestsRepository;


	public function __construct(
		CI\Builds\Tests\BuildRequestsRepository $buildRequestsRepository,
		CI\Builds\Tests\Builder $builder
	) {
		parent::__construct();

		$this->builder = $builder;
		$this->buildRequestsRepository = $buildRequestsRepository;
	}


	protected function configure()
	{
		$this->setName('ci:build:build');
	}


	protected function execute(Symfony\Component\Console\Input\InputInterface $input, Symfony\Component\Console\Output\OutputInterface $output)
	{
		$conditions = [
			'start' => NULL,
		];
		$buildRequest = $this->buildRequestsRepository->getBy($conditions);

		if ( ! $buildRequest) {
			$output->writeln('Žádný požadavek na build nebyl nalezen.');
		}

		$this->builder->build($buildRequest);

		$output->writeln($buildRequest->succeeded);
		$output->writeln($buildRequest->failed);
	}

}
