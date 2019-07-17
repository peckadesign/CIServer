<?php declare(strict_types = 1);

namespace CI\GitHub;

class StatusPublicator
{

	const STATUS_PENDING = 'pending';
	const STATUS_SUCCESS = 'success';
	const STATUS_FAILURE = 'failure';

	/**
	 * @var \League\OAuth2\Client\Provider\Github
	 */
	private $gitHubClient;

	/**
	 * @var \CI\User\UsersRepository
	 */
	private $usersRepository;

	/**
	 * @var \Monolog\Logger
	 */
	private $logger;


	public function __construct(
		\League\OAuth2\Client\Provider\Github $gitHubClient,
		\CI\User\UsersRepository $usersRepository,
		\Monolog\Logger $logger
	) {
		$this->gitHubClient = $gitHubClient;
		$this->usersRepository = $usersRepository;
		$this->logger = $logger;
	}


	public function publish(
		Repository $repository,
		string $commit,
		string $state,
		string $description,
		string $context,
		string $link = NULL
	) {
		$conditions = [
			'systemUser' => TRUE,
		];
		$systemUser = $this->usersRepository->getBy($conditions);

		if ( ! $systemUser) {
			throw new \CI\Exception('Nebyl nalezen systémový uživatel');
		}

		$body = [
			'state' => $state,
			'description' => $description,
			'context' => 'Pecka CI' . ' / ' . $context,
		];

		if ($link) {
			$body['target_url'] = $link;
		}

		$this->logger->addInfo(
			sprintf('Pro commit %s je nastavován status "%s" a odkaz "%s"', $commit, $body['description'], $link)
		);

		try {
			$request = $this->gitHubClient->getAuthenticatedRequest(
				'POST',
				$this->gitHubClient->apiDomain . '/repos/peckadesign/' . $repository->name . '/statuses/' . $commit,
				$systemUser->gitHubToken,
				['headers' => [['Content-Type' => 'application/json']], 'body' => \Nette\Utils\Json::encode($body)]
			);
			$this->gitHubClient->getParsedResponse($request);
		} catch (\Throwable $e) {
			$this->logger->addError($e->getMessage());
		}
	}
}
