<?php declare(strict_types = 1);

namespace CI\User;

use Nette;
use Nextras;


/**
 * @property int $id {primary}
 * @property int|null $gitHubId
 * @property string $gitHubName
 * @property string $gitHubToken
 * @property bool $systemUser {default FALSE}
 * @property string|null $oauth2token
 * @property string $email
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
