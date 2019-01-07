<?php declare(strict_types = 1);

namespace CI\OAuth2Login\Login;

final class BackLinkStorage
{

	/**
	 * @var \Nette\Http\SessionSection|\stdClass
	 */
	private $sessionSection;


	public function __construct(\Nette\Http\SessionSection $sessionSection)
	{
		$this->sessionSection = $sessionSection;
	}


	public function getBackLink(): ?string
	{
		return $this->sessionSection->backLink;
	}


	public function saveBackLink(?string $backLink): void
	{
		$this->sessionSection->backLink = $backLink;
	}

}
