<?php declare(strict_types=1);

namespace CI\DI;

use CI;
use Nette;


class Extension extends Nette\DI\CompilerExtension
{

	public function beforeCompile()
	{
		$builder = $this->getContainerBuilder();
		$userStorageDefinitionName = $builder->getByType('Nette\Security\IUserStorage') ?: 'nette.userStorage';
		$builder
			->getDefinition($userStorageDefinitionName)
			->setFactory(CI\User\UserStorage::class)
		;
	}

}
