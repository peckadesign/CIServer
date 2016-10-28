<?php

namespace CI\User;

use Nette;
use Nextras;


/**
 * @property int $id {primary}
 * @property int $gitHubId
 * @property string $gitHubName
 * @property string $gitHubToken
 * @property bool $systemUser {default FALSE}
 */
class User extends Nextras\Orm\Entity\Entity implements Nette\Security\IIdentity
{

	public function getId() : int
	{
		return $this->id;
	}


	public function getRoles() : array
	{
		return [];
	}
}
