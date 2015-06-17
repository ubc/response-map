<?php
class Config
{
	public $host = '127.0.0.1';
	public $database_name = 'response_map';
	public $database_user = 'yLe7Uq3FPmeQb0NbvuXj';
	public $database_pass = 'PMRxdcgDlFz7Rh30jDy9';
	public $adminpassword = '19VZ7mDyYsHWs9iU3AWm';
	public $google_key = 'Tk2BBlyShqZNQ9G8KoeS';
}

// environment variables map with Config properties
$envs = array(
	'HOST' => 'host',
	'DB_NAME' => 'database_name',
	'DB_USERNAME' => 'database_user',
	'DB_PASSWORD' => 'database_pass',
	'ADMIN_PASSWORD' => 'adminpassword',
	'GOOGLE_KEY' => 'google_key',
);

// include config.php if exists
include_once('config.php');

// initialize the configuration
$config = new Config();

// load environment variables and/or config variables
// environment variable takes precedence over config variables
foreach ($envs as $k => $v) {
	$value = getenv($k);
	// if environment variable is set
	if ($value !== false) {
		$config->$v = $value;
		// if variable is set in config.php
	} else if (isset($$v)) {
		$config->$v = $$v;
	}
}

// Establish a connection to the database
$conn = mysqli_connect($config->host, $config->database_user, $config->database_pass, $config->database_name);


