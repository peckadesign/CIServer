<?php declare(strict_types = 1);

namespace CI\OAuth2Login;

final class StateStorage
{

	/**
	 * @var \Nette\Http\SessionSection|\stdClass
	 */
	private $sessionSection;


	public function __construct(\Nette\Http\SessionSection $sessionSection)
	{
		$this->sessionSection = $sessionSection;
	}


	public function saveState(string $state): void
	{
		$this->sessionSection->state = $state;
	}


	public function validateState(string $state): bool
	{
		return $this->sessionSection->state === $state;
	}

}
