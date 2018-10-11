<?php

namespace digitalhigh;

require_once dirname(__FILE__) . "/DbConfig.php";
require_once dirname(__FILE__) . "/FileConfig.php";
require_once dirname(__FILE__) . "/ConfigException.php";


class appConfig {
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
				$config = @parse_ini_file($configFile);
				if ($config) {
					$user = $config['username'] ?? false;
					$pass = $config['password'] ?? false;
					$dbName = $config['dbname'] ?? false;
					if (!$user || !$pass || !$dbName) {
						$words = [];
						if (!$user) array_push($words, "'username'");
						if (!$pass) array_push($words, "'password'");
						if (!$dbName) array_push($words, "'dbname'");
						$strings = join(", ", $words);
						throw new ConfigException("Missing config param(s) $strings");
					} else {
						$configObject = new DbConfig($configFile);
					}
				}
			} else {
				$configObject = new FileConfig($configFile);
			}

		if ($configObject) {
			$this->ConfigObject = $configObject;
		} else {
			throw new ConfigException("Unable to create config object!!");
		}
	}
}