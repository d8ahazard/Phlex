<?php

namespace digitalhigh;

require_once dirname(__FILE__) . "/DbConfig.php";
require_once dirname(__FILE__) . "/JsonConfig.php";
require_once dirname(__FILE__) . "/ConfigException.php";

class appConfig
{
    public $ConfigObject;


    /**
     * Config constructor.
     * @param string $configFile
	 * @param $type
     * @throws ConfigException
     */
	public function __construct($configFile, $type) {
		$configObject = false;
		if (!file_exists($configFile) || !is_readable($configFile)) {
		   throw new ConfigException("Invalid config file specified.");
		}
		$configFile = realpath($configFile);
		if ($type === 'db') {
			$config = str_replace("'; <?php die('Access denied'); ?>", "", file_get_contents($configFile));
			$config = json_decode($config, true);
			if ($config) {
			   $user = $config['username'] ?? false;
			   $pass = $config['password'] ?? false;
						$dbName = $config['database'] ?? false;
			   if (!$user || !$pass || !$dbName) {
			       $words = [];
			       if (!$user) array_push($words, "'username'");
			       if (!$pass) array_push($words,"'password'");
			       if (!$dbName) array_push($words,"'dbname'");
			       $strings = join(", ",$words);
			       throw new ConfigException("Missing config param(s) $strings");
			   } else {
					$configObject = new DbConfig($config);
			   }
			}
		} else {
		   $configObject = new JsonConfig($configFile);
		}
       $this->ConfigObject = $configObject;
    }
}