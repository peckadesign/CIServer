<?php declare(strict_types = 1);

namespace CI\OAuth2Login;

/**
 * @method User getResourceOwner(\League\OAuth2\Client\Token\AccessToken $token)
 */
class PeckaNotesProvider extends \League\OAuth2\Client\Provider\GenericProvider
{

	/**
	 * @return User
	 */
	protected function createResourceOwner(array $response, \League\OAuth2\Client\Token\AccessToken $token)
	{
		return new User($response);
	}

}
