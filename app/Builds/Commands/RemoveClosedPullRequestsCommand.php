<?php declare(strict_types = 1);

namespace CI\Builds\Commands;

final class RemoveClosedPullRequestsCommand extends \Symfony\Component\Console\Command\Command
{

	/**
	 * @var \CI\Builds\CreateTestServer\CreateTestServersRepository
	 */
	private $createTestServersRepository;

	/**
	 * @var \CI\Builds\RemoveBuild
	 */
	private $removeBuild;


	public function __construct(
		\CI\Builds\CreateTestServer\CreateTestServersRepository $createTestServersRepository,
		\CI\Builds\RemoveBuild $removeBuild
	) {
		parent::__construct();
		$this->createTestServersRepository = $createTestServersRepository;
		$this->removeBuild = $removeBuild;
	}


	protected function configure()
	{
		parent::configure();

		$this->setName('ci:builds:remove-closed-pull-requests');
		$this->setDescription('Smaže již zavřené PR, které nejsou smazané');
	}


	protected function execute(
		\Symfony\Component\Console\Input\InputInterface $input,
		\Symfony\Component\Console\Output\OutputInterface $output
	): int {
		$conditions = [
			'closed' => TRUE,
		];
		$builds = $this->createTestServersRepository->findBy($conditions);

		$progress = NULL;
		if ($output->isVerbose()) {
			$progress = new \Symfony\Component\Console\Helper\ProgressBar($output, \count($builds));
		}

		foreach ($builds as $build) {
			$progress && $progress->advance();

			if ( ! $build->pullRequestNumber) {
				continue;
			}
			$this->removeBuild->remove($build->repository, $build->pullRequestNumber);
		}

		$progress && $progress->finish();
		$progress && $output->writeln('');

		return 0;
	}

}
