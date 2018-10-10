<?php
namespace digitalhigh;
require_once dirname(__FILE__) . "/ConfigException.php";
use mysqli;
class DbConfig {

    protected $connection;

    /**
     * DbConfig constructor.
     * @param string $configFile
     * @throws ConfigException
     */
    public function __construct($configFile)
	{
		$this->connection = $this->connect($configFile);
		if ($this->connection === false) {
		   throw new ConfigException("Error connecting to database!!");
		}
	}


    /**
     * @return bool
     */
    public function isValid()
    {
        return ($this->connection ? true : false);
    }


    /**
     * @param $configFile
     * @return bool|mysqli
     * @throws ConfigException
     */
    protected function connect($configFile) {
		$config = parse_ini_file($configFile);
		$host = $config['dburi'] ?? 'localhost';
		$mysqli = new mysqli($host,$config['username'],$config['password'],$config['dbname']);
		if ($mysqli->connect_errno) {
		    throw new ConfigException("ERROR CONNECTING: ".$mysqli->connect_errno);
		}

		/* check if server is alive */
		if ($mysqli->ping()) {
			return $mysqli;
		} else {
			return false;
		}
	}

    /**
     *
     */
    public function disconnect() {
		
		// Try and connect to the database
		if($this->connection) {
			$this->connection->close();
		}
	}

	/**
	 * @param string $table
	 * @param array $data
	 * @param bool | array $selectors
	 */
    public function set($table, $data, $selectors=false) {
	    $keys = $strings = $values = [];

	    if ($selectors) $data = array_merge($data,$selectors);
        foreach ($data as $key => $value) {
            if ($value === true) $value = "true";
            if ($value === false) $value = "false";
            if (is_array($value)) $value = json_encode($value);
            $quoted = $this->quote($value);
            if ($value === "true" || $value === "false") $quoted = $value;
            array_push($keys, $key);
            array_push($values, $quoted);
            array_push($strings, "$key=$quoted");
        }

        $strings = join(", ",$strings);
        $keys = join(", ",$keys);
        $values = join(", ",$values);
        $query = "INSERT INTO $table ($keys) VALUES ($values) ON DUPLICATE KEY UPDATE $strings";
        $result = $this->query($query);
        if (!$result) {
            trigger_error("Error saving record: ".$this->error(),E_USER_ERROR);
        }
    }

    /**
     * @param $section
     * @param bool $keys
     * @param bool | array $selector
     * @return array|bool
     */
    public function get($section, $keys=false, $selector=false) {
        if (is_string($keys)) $keys = [$keys];
        $keys = $keys ? join(", ",$keys) : "*";
        $query = "SELECT $keys FROM $section";
	    $value = reset($selector);
	    $key = key($selector);
	    if ($selector) $query .= " WHERE $key LIKE ".$this->quote($value);
        $data = $this->select($query);
        return $data;
    }

    /**
     * @param $section
     * @param array $selectors
     * @return mixed
     */
    public function delete($section, $selectors) {
        $strings = [];
        if (empty($selectors)) return false;
        foreach ($selectors as $key => $value) {
            array_push($strings, "$key LIKE " . $this->quote($value));
        }
        $query = "DELETE from $section WHERE " . join(" AND ",$strings);
        $result = $this->query($query);
        return $result;
    }
	
	/**
	* Query the database
	*
	* @param string $query - The query string
	* @return mixed $result - The result of the mysqli::query() function 
	*/
	
	public function query($query) {
        return $this->connection -> query($query);
	}
	
	/**
	* Fetch rows from the database (SELECT query)
	*
	* @param string $query
	* @return bool|array on failure|success
	*/
	
	public function select($query) {
        $rows = array();
		$result = $this-> connection -> query($query);
		if(($result === false) || (! is_object($result))) {
            $error = mysqli_error($this->connection);
            trigger_error("Possible select error: $error",E_USER_WARNING);
			return $result;
		}
		while ($row = $result -> fetch_assoc()) {
			$rows[] = $row;
		}
		return $rows;
	}
	
	/**
	* Fetch the last error from the database
	* 
	* @return string Database error message
	*/
	
	public function error() {
		return $this -> connection -> error;
	}
	
	/**
	* Quote and escape value for use in a database query
	*
	* @param string $value The value to be quoted and escaped
	* @return string The quoted and escaped string
	*/
	
	public function quote($value) {
	    if (is_string($value)) {
            $value = ltrim($value, "'");
            $value = rtrim($value, "'");
        }
        $escaped = $this->connection->real_escape_string($value);
	    if (is_string($value)) {
            $escaped = "'$escaped'";
        } else $escaped = $value;
		return $escaped;
	}

    /**
     * @param $value
     * @return string
     */
    public function escape($value) {
        if (is_string($value)) {
            $escaped = $this->connection->real_escape_string($value);
        } else $escaped = $value;
        return $escaped;
	}
}