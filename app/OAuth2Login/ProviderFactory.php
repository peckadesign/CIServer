<?php declare(strict_types = 1);

namespace CI\OAuth2Login;

final class ProviderFactory
{

	/**
	 * @var \Nette\Application\LinkGenerator
	 */
	private $linkGenerator;

	/**
	 * @var \Nette\Http\IRequest
	 */
	private $request;

	/**
	 * @var string
	 */
	private $oauth2ProviderUrl;

	/**
	 * @var string
	 */
	private $clientId;

	/**
	 * @var string
	 */
	private $clientSecret;


	public function __construct(
		\Nette\Application\LinkGenerator $linkGenerator,
		\Nette\Http\IRequest $request,
		string $oauth2ProviderUrl,
		string $clientId,
		string $clientSecret
	)
	{
		$this->linkGenerator = $linkGenerator;
		$this->request = $request;
		$this->oauth2ProviderUrl = $oauth2ProviderUrl;
		$this->clientId = $clientId;
		$this->clientSecret = $clientSecret;
	}


	public function create(): \League\OAuth2\Client\Provider\AbstractProvider
	{
		$redirectUrl = new \Nette\Http\Url($this->linkGenerator->link('OAuth2Login:Login:OAuth2:authorize'));
		$redirectUrl->setHost($this->request->getUrl()->getHost());
		$redirectUrl->setScheme($this->request->getUrl()->getScheme());

		return new PeckaNotesProvider([
			'clientId' => $this->clientId,
			'clientSecret' => $this->clientSecret,
			'redirectUri' => (string) $redirectUrl,
			'urlAuthorize' => $this->oauth2ProviderUrl . '/oauth2/authorize',
			'urlAccessToken' => $this->oauth2ProviderUrl . '/oauth2/access-token',
			'urlResourceOwnerDetails' => $this->oauth2ProviderUrl . '/oauth2/resource',
		]);
	}

}
