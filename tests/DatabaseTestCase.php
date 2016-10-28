<?php

namespace CITests;

use Nextras;
use Symfony;
use Tester;


abstract class DatabaseTestCase extends Tester\TestCase
{

	/**
	 * @var string
	 */
	private $dbName;

	/**
	 * @var Nextras\Dbal\Connection
	 */
	private $connection;


	protected function setUp()
	{
		$config = [
			'driver' => 'mysqli',
			'host' => 'localhost',
			'user' => 'root',
			'connectionTz' => '+02:00',
		];

		$this->dbName = 'testDB' . getmypid();
		$this->connection = new Nextras\Dbal\Connection($config);
		$this->connection->query('DROP DATABASE IF EXISTS ' . $this->dbName);
		$this->connection->query('CREATE DATABASE ' . $this->dbName);
		$this->connection->query('USE ' . $this->dbName);

		$process = new Symfony\Component\Process\Process('php --DB_NAME=' . $this->dbName . ' migration.php structures');
		$process->mustRun();

		parent::setUp();
	}


	protected function getConnection()
	{
		return $this->connection;
	}

	protected function tearDown()
	{
		$this->connection->query('DROP DATABASE ' . $this->dbName);

		parent::tearDown();
	}

}
