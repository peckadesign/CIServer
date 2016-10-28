<?php

namespace CI\Builds\Tests;

use CI;
use Kdyby;
use Monolog;
use Nette;


class StatusPublicator implements IStatusPublicator
{

	const DATE_TIME_FORMAT = 'j. n. Y H:i:s';

	/**
	 * @var Kdyby\Github\Client
	 */
	private $gitHubClient;

	/**
	 * @var Nette\Application\LinkGenerator
	 */
	private $linkGenerator;

	/**
	 * @var CI\User\UsersRepository
	 */
	private $usersRepository;

	/**
	 * @var Monolog\Logger
	 */
	private $logger;


	public function __construct(
		Kdyby\Github\Client $gitHubClient,
		Nette\Application\LinkGenerator $linkGenerator,
		CI\User\UsersRepository $usersRepository,
		Monolog\Logger $logger
	) {
		$this->gitHubClient = $gitHubClient;
		$this->linkGenerator = $linkGenerator;
		$this->usersRepository = $usersRepository;
		$this->logger = $logger;
	}


	/**
	 * @throws CI\Exception
	 */
	public function publish(BuildRequest $buildRequest)
	{
		$conditions = [
			'systemUser' => TRUE,
		];
		$systemUser = $this->usersRepository->getBy($conditions);

		if ( ! $systemUser) {
			throw new CI\Exception('Nebyl nalezen systémový uživatel');
		}

		$includeResults = FALSE;

		if ( ! $buildRequest->start) {
			$state = 'pending';
			$message = 'Čeká se na spuštění testů.';
		} elseif ( ! $buildRequest->finish) {
			$state = 'pending';
			$message = sprintf(
				'Čeká se na dokončení spuštěných testů, běží od %s.',
				$buildRequest->start->format(self::DATE_TIME_FORMAT)
			);
		} elseif ($buildRequest->failed) {
			$state = 'failure';
			$message = 'Past vedle pasti';
			$includeResults = TRUE;
		} elseif ($buildRequest->succeeded) {
			$state = 'success';
			$message = 'Funguje';
			$includeResults = TRUE;
		} else {
			$state = 'success';
			$message = 'Žádné testy nespuštěny';
		}

		if ($includeResults) {
			$message = sprintf(
				'%s %s %u, %s %u.',
				$message,
				'Prošlo',
				$buildRequest->succeeded,
				'selhalo',
				$buildRequest->failed
			);
		}

		$body = [
			'state' => $state,
			'description' => $message,
			'context' => 'Pecka CI',
			'target_url' => $this->linkGenerator->link('DashBoard:BuildRequest:', [$buildRequest->id]),
		];

		$this->logger->addInfo(
			sprintf('Pro commit %s je nastavován status "%s"', $buildRequest->commit, $body['description'])
		);

		$this->gitHubClient->setAccessToken($systemUser->gitHubToken);

		try {
			$this->gitHubClient->post(
				'/repos/' . $buildRequest->repository->name . '/statuses/' . $buildRequest->commit,
				[],
				Nette\Utils\Json::encode($body),
				['Content-Type' => 'application/json']
			);
		} catch (Kdyby\Github\ApiException $e) {
			$this->logger->addError($e);
			throw new CI\Exception($e->getMessage(), $e->getCode(), $e);
		}
	}
}
