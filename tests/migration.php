<?php

include __DIR__ . '/bootstrap.php';

$dbName = getenv('DB_NAME');

$config = [
	'driver' => 'mysqli',
	'host' => 'localhost',
	'user' => 'root',
	'dbname' => $dbName,
	'connectionTz' => '+02:00',
];
$connection = new Nextras\Dbal\Connection($config);
$dbal = new Nextras\Migrations\Bridges\NextrasDbal\NextrasAdapter($connection);
$driver = new Nextras\Migrations\Drivers\MySqlDriver($dbal);
$controller = new Nextras\Migrations\Controllers\ConsoleController($driver);

$baseDir = __DIR__ . '/../migrations';
$controller->addGroup('structures', $baseDir . '/structures');

$controller->run();
