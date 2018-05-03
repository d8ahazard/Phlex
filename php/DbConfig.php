<?php
namespace digitalhigh;
use mysqli;
require_once(dirname(__FILE__)."/util.php");
class DbConfig {


	public function __construct()
	{
		$this->connection = $this->connect();
		if ($this->connection === false) {
			write_log("Error connecting to db.");
		}
		return ($this->connection !== false);
	}

	protected $connection;

	protected function connect() {
		$config = parse_ini_file('db.conf.php');
		$mysqli = new mysqli('localhost',$config['username'],$config['password'],$config['dbname']);
		if ($mysqli->connect_errno) {
			return false;
		}


		/* check if server is alive */
		if ($mysqli->ping()) {
			return $mysqli;
		} else {
			return false;
		}
	}
	
	public function disconnect() {
		
		// Try and connect to the database
		if($this->connection) {
			$this->connection->close();
		}
	}

	public function set($section, $data, $selector=null, $search=null) {
        write_log("Called by ".getCaller("set"));
        $keys = $strings = $values = [];
        $result = false;
        foreach ($data as $key => $value) {
            if (is_array($value)) $value = json_encode($value);
            $quoted = $this->quote($value);
            array_push($keys, $key);
            array_push($values, $value);
            array_push($strings, "$key=$quoted");
        }

        if ($selector && $search) {
            $search = $this->quote($search);
            array_push($keys, $selector);
            array_push($values, $search);
            array_push($string, "$selector=$search");
        }

        $strings = join(", ",$strings);
        $keys = join(", ",$keys);
        $values = join(", ",$values);
        $query = "INSERT INTO $section ($keys) VALUES ($values) ON DUPLICATE KEY UPDATE $strings";
        write_log("Constructed query: ".$query);
        //$result = $this->query($query);
        if ($result) {
            write_log("Record saved successfully.","INFO");
        } else{
            write_log("Error saving record.","ERROR");
        }
    }

    public function get($section, $keys=false, $selector=null, $search=null) {
        write_log("Called by ".getCaller("get"));
        if (is_string($keys)) $keys = [$keys];
        $keys = $keys ? "*" : join(", ",$keys);
        $query = "SELECT $keys FROM $section";
        if ($selector && $search) $query .= " WHERE $selector LIKE ".$this->quote($search);
        write_log("Constructed query is '$query'");
        $data = $this->select($query);
        write_log("Retrieved data: ".json_encode($data));
        return (count($data) == 1) ? $data[0] : $data;
    }

    public function delete($section, $selector=null, $value=null) {
	    $result = false;
        write_log("Called by ".getCaller("delete"));
        $query = "DELETE from $section";
        if ($selector && $value) {
            if (is_string($selector)) {
                $query .= " WHERE $selector LIKE " . $this->quote($value);
            } else {
                $i = 0;
                $strings = [];
                foreach ($selector as $sel) {
                    array_push($strings,"$sel LIKE ".$this->quote($value[$i]));
                    $i++;
                }
                $query .= " WHERE " . join(" AND ",$strings);
            }
        }
        write_log("Constructed query is '$query'");
        //$result = $this->query($query);
        return $result;
    }
	
	/**
	* Query the database
	*
	* @param string $query - The query string
	* @return mixed $result - The result of the mysqli::query() function 
	*/
	
	public function query($query) {

		// Query the database
		$result = $this->connection -> query($query);
        if (!$result) {
            $error = $this->connection -> error;
            write_log("Query error: ".$error,"ERROR");
            return false;
        }
		return $result;
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
	    $value = ltrim($value,"'");
	    $value = rtrim($value,"'");
		$escaped = $this-> connection -> real_escape_string($value);
		return "'$escaped'";
	}
	
	public function escape($value) {
		return $this -> connection -> real_escape_string($value);
	}
}