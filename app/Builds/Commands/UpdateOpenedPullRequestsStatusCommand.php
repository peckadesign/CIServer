<?php declare(strict_types = 1);

namespace CI\Builds\Commands;

final class UpdateOpenedPullRequestsStatusCommand extends \Symfony\Component\Console\Command\Command
{

	/**
	 * @var \CI\Builds\CreateTestServer\CreateTestServersRepository
	 */
	private $createTestServersRepository;

	/**
	 * @var \Kdyby\Github\Client
	 */
	private $gitHub;

	/**
	 * @var \CI\User\UsersRepository
	 */
	private $usersRepository;


	public function __construct(
		\Kdyby\Github\Client $gitHub,
		\CI\User\UsersRepository $usersRepository,
		\CI\Builds\CreateTestServer\CreateTestServersRepository $createTestServersRepository
	) {
		parent::__construct();
		$this->gitHub = $gitHub;
		$this->usersRepository = $usersRepository;
		$this->createTestServersRepository = $createTestServersRepository;
	}


	protected function configure()
	{
		parent::configure();

		$this->setName('ci:github:update-opened-pull-requests-status');
		$this->setDescription('Ověří, zda jsou ještě všechny PR označené jako otevřené stále otevřené');
	}


	protected function execute(
		\Symfony\Component\Console\Input\InputInterface $input,
		\Symfony\Component\Console\Output\OutputInterface $output
	): int {

		$conditions = [
			'systemUser' => TRUE,
		];
		$systemUser = $this->usersRepository->getBy($conditions);

		$conditions = [
			'closed' => FALSE,
		];
		$builds = $this->createTestServersRepository->findBy($conditions);
		$this->gitHub->setAccessToken($systemUser->gitHubToken);

		$progress = NULL;
		if ($output->isVerbose()) {
			$progress = new \Symfony\Component\Console\Helper\ProgressBar($output, \count($builds));
		}

		foreach ($builds as $build) {
			$progress && $progress->advance();
			$pullRequest = $this->gitHub->api('/repos/peckadesign/' . $build->repository->name . '/pulls/' . $build->pullRequestNumber);
			if ($pullRequest['state'] === 'open') {
				continue;
			}
			$build->closed = TRUE;
			$this->createTestServersRepository->persist($build);
		}
		$progress && $progress->finish();
		$progress && $output->writeln('');
		$this->createTestServersRepository->flush();

		return 0;
	}

}
