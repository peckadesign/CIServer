<?php

namespace CI\GitHub;

class StatusPublicator
{

	const DATE_TIME_FORMAT = 'j. n. Y H:i:s';

	const STATUS_PENDING = 'pending';
	const STATUS_SUCCESS = 'success';
	const STATUS_FAILURE = 'failure';

	/**
	 * @var \Kdyby\Github\Client
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
		\Kdyby\Github\Client $gitHubClient,
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

		$this->gitHubClient->setAccessToken($systemUser->gitHubToken);

		try {
			$this->gitHubClient->post(
				'/repos/peckadesign/' . $repository->name . '/statuses/' . $commit,
				[],
				\Nette\Utils\Json::encode($body),
				['Content-Type' => 'application/json']
			);
		} catch (\Kdyby\Github\ApiException $e) {
			$this->logger->addError($e);
			throw new \CI\Exception($e->getMessage(), $e->getCode(), $e);
		}
	}
}
