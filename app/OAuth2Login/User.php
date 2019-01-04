<?php declare(strict_types = 1);

namespace CI\OAuth2Login;

final class User implements \League\OAuth2\Client\Provider\ResourceOwnerInterface
{

	/**
	 * @var array
	 */
	private $response;


	public function __construct(array $response)
	{

		$this->response = $response;
	}


	public function getId(): string
	{
		return $this->response['email'];
	}


	public function getFirstName(): string
	{
		return $this->response['firstName'];
	}


	public function getLastName(): string
	{
		return $this->response['lastName'];
	}


	public function isActive(): bool
	{
		return (bool) $this->response['active'];
	}


	public function toArray(): array
	{
		return $this->response;
	}

}
