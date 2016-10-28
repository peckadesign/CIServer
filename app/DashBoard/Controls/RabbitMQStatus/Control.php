<?php

namespace CI\DashBoard\Controls\RabbitMQStatus;

class Control extends \Nette\Application\UI\Control
{

	/**
	 * @var string
	 */
	private $host;

	/**
	 * @var int
	 */
	private $port;

	/**
	 * @var string
	 */
	private $user;

	/**
	 * @var string
	 */
	private $password;

	/**
	 * @var string
	 */
	private $vhost;


	public function __construct(
		string $host,
		int $port,
		string $user,
		string $password,
		string $vhost
	) {

		$this->host = $host;
		$this->port = $port;
		$this->user = $user;
		$this->password = $password;
		$this->vhost = $vhost;
	}


	public function render()
	{
		$client = new \GuzzleHttp\Client();

		$res = $client->request('GET', 'http://' . $this->host . ':' . $this->port . '/api/consumers/' . $this->vhost, [
			'auth' => [$this->user, $this->password],
		]);
		if ($res->getStatusCode() !== 200) {
			return;
		}

		$consumersData = \Nette\Utils\Json::decode($res->getBody(), \Nette\Utils\Json::FORCE_ARRAY);
		$consumers = [];
		foreach($consumersData as $consumerData) {
			$consumers[$consumerData['queue']['name']][] = $consumerData;
		}

		$this->template->setFile(__DIR__ . '/Control.latte');
		$this->template->consumers = $consumers;
		$this->template->render();
	}

}
