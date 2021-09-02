<?php

// PdoFish setup options
// if you specify a model path, PdoFish will setup all of your models
$PdoFish_options = [
	'username' => 'username',
	'database' => 'database_name',  
	'password' => 'db_password',    // optional, defaults to blank 
  	'type' => 'mysql',              // optional, defaults to mysql
	'charset' => 'utf8',            // optional, defaults to utf8
	'host' => "localhost",          // optional, defaults to localhost
	'port' => '3306',               // optional, defaults to 3306
	'model_path'=>'/pathtomodels/',	// optional, defaults to null 
];

