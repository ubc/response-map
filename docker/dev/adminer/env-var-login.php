<?php
/** 
 * Fill login info from env var, makes it so that when we use it with docker
 * compose, we don't need to type in the login info manually.
 *
 * Use: Copy to Adminer plugins directory
 */
class EnvVarLogin {  
  private $driver;  
  private $server;
  private $user;
  private $pass;
  private $db;
    
  public function __construct() {
      $this->driver = $this->getEnvWithDefault('DB_DRIVER');
      $this->server = $this->getEnvWithDefault('DB_HOST');
      $this->user = $this->getEnvWithDefault('DB_USERNAME');
      $this->pass = $this->getEnvWithDefault('DB_PASSWORD');
      $this->db = $this->getEnvWithDefault('DB_NAME');

      # for some reason, adminer calls the mysql driver 'server' in login form
      if ($this->driver == 'mysql') $this->driver = 'server';
  }

  private function getEnvWithDefault($varName, $default='')
  {
      $tmp = getenv($varName);
      if ($tmp) return $tmp;
      return $default;
  }


  function loginFormField($name, $heading, $value) {
      if ($name == 'driver') {
          $value = str_replace('selected', "", $value);
          $value = str_replace("value=\"$this->driver\"",
              "value=\"$this->driver\" selected", $value);
      }
      elseif ($name == 'server') {
          $value = str_replace('value=""', "value=\"$this->server\"", $value);
      }
      elseif ($name == 'username') {
          $value = str_replace('value=""', "value=\"$this->user\"", $value);
      }
      elseif ($name == 'password') {
          $value = str_replace('type="password"', "value=\"$this->pass\"", $value);
      }
      elseif ($name == 'db') {
          $value = str_replace('value=""', "value=\"$this->db\"", $value);
      }
      #if(!defined('STDERR')) define('STDERR', fopen('php://stderr', 'wb'));
      #fwrite(STDERR, "------------ $name \n");
      #fwrite(STDERR, "------------ $heading \n");
      #fwrite(STDERR, "------------ $value \n");
      return $heading . $value;
  }
}
