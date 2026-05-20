<?php

// Copy this file to credentials.php and fill in your database details.
// credentials.php is listed in .gitignore — do not commit real credentials.
$PdoFish_options = [
	'username' => 'username',
	'database' => 'database_name',
	'password' => 'db_password',    // optional, defaults to blank
	'type'     => 'mysql',          // optional, defaults to mysql
	'charset'  => 'utf8',           // optional, defaults to utf8
	'host'     => 'localhost',      // optional, defaults to localhost
	'port'     => '3306',           // optional, defaults to 3306
	'model_path' => '/path/to/models/', // optional, defaults to null
];
