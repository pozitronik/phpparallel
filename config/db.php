<?php
declare(strict_types = 1);

use yii\db\Connection;

return [
	'class' => Connection::class,
	'dsn' => 'pgsql:host=postgres;port=5432;dbname=roadrunner',
	'username' => 'root',
	'password' => 'password',
	'enableSchemaCache' => false,
];