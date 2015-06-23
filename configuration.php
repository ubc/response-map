<?php
class Config
{
	public $database_host = '127.0.0.1';
	public $database_port = 3306;
	public $database_name = 'response_map';
	public $database_user = 'rmap_user';
	public $database_pass = '';
	public $adminpassword = '19VZ7mDyYsHWs9iU3AWm';
	public $google_key = '';
	public $key = '["ltikey"]';
	public $secret = "";
	public $key_secret = '{}';


	// environment variables map with Config properties
	private $envs = array(
		'OPENSHIFT_MYSQL_DB_HOST' => 'database_host',
		'OPENSHIFT_MYSQL_DB_PORT' => 'database_port',
		'OPENSHIFT_APP_NAME' => 'database_name',
		'OPENSHIFT_MYSQL_DB_USERNAME' => 'database_user',
		'OPENSHIFT_MYSQL_DB_PASSWORD' => 'database_pass',
		'DB_HOST' => 'database_host',
		'DB_PORT' => 'database_port',
		'DB_NAME' => 'database_name',
		'DB_USERNAME' => 'database_user',
		'DB_PASSWORD' => 'database_pass',
		'ADMIN_PASSWORD' => 'adminpassword',
		'GOOGLE_KEY' => 'google_key',
		'OAUTH_CONSUMER_KEY' => 'key',
		'OAUTH_CONSUMER_SECRET' => 'secret',
		'OAUTH_CONSUMER' => 'key_secret',
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
$conn = mysqli_connect($config->database_host, $config->database_user, $config->database_pass, $config->database_name);


