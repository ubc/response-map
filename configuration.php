<?php
class Config
{
	public $host = '127.0.0.1';
	public $database_name = 'response_map';
	public $database_user = 'yLe7Uq3FPmeQb0NbvuXj';
	public $database_pass = 'PMRxdcgDlFz7Rh30jDy9';
	public $adminpassword = '19VZ7mDyYsHWs9iU3AWm';
	public $google_key = 'Tk2BBlyShqZNQ9G8KoeS';
	public $key = "LyhHemD3s6L6ezu24lxn";
	public $secret = "1KJ7R3IXhLzDDbJ7Rflj";
	public $database_host = "";
	public $database_port = "";

	// environment variables map with Config properties
	private $envs = array(
		'OPENSHIFT_MYSQL_DB_HOST' => 'database_host',
		'OPENSHIFT_MYSQL_DB_PORT' => 'database_port',
		'OPENSHIFT_APP_NAME' => 'database_name',
		'OPENSHIFT_MYSQL_DB_USERNAME' => 'database_user',
		'OPENSHIFT_MYSQL_DB_PASSWORD' => 'database_pass',
		'HOST' => 'host',
		'DB_NAME' => 'database_name',
		'DB_USERNAME' => 'database_user',
		'DB_PASSWORD' => 'database_pass',
		'ADMIN_PASSWORD' => 'adminpassword',
		'GOOGLE_KEY' => 'google_key',
		'OAUTH_CONSUMER_KEY' => 'key',
		'OAUTH_CONSUMER_SECRET' => 'secret',
	);

	function __construct() {
		// include config.php if exists
		include('config.php');
		foreach ($this->envs as $k => $v) {
			$value = getenv($k);
			// if environment variable is set
			if ($value !== false) {
				$this->$v = $value;
				// if variable is set in config.php
			} else if (isset($$v)) {
				$this->$v = $$v;
			}
		}
	}
}

// initialize the configuration
$config = new Config();

// Establish a connection to the database
$conn = mysqli_connect($config->host, $config->database_user, $config->database_pass, $config->database_name);


