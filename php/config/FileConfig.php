<?php
namespace digitalhigh;

require_once dirname(__FILE__) . "/ConfigException.php";
require_once dirname(__FILE__) . '/../vendor/autoload.php';

use Filebase\Database;
use Filebase\Query\Builder;


class FileConfig extends Database {

    protected $path;

	/**
	 * Filebase DB constructor.
	 * @param $dbPath
	 */
    public function __construct($dbPath)
	{
		$this->path = ['path'=>$dbPath];
	}


    /**
     * @return bool
     */
    public function isValid()
    {
        return ($this->path ? true : false);
    }



    /**
     * @param $section
     * @param array $data
     * @param array | bool $selectors
     */
    public function set($section, $data, $selectors=false) {
	    write_log("SET CALLED for $section: ".json_encode(['data'=>$data, 'sel'=>$selectors]),"INFO",false,false,true);
	    $path = $this->path;
        $db = new Database($path);
        $table = $db->table($section);
        $apiToken = $selectors['apiToken'] ?? false;
        $name = $selectors['name'] ?? false;
        $record = false;
        if ($section === 'userdata') {
			$record = $table->get($apiToken);
        }

        if ($section === 'general') {
			$record = $table->get($name);
        }

        if ($section === 'commands') {
			$record = $table->get(uniqid());
        }
        if ($record) {
        	write_log("We have a valid record, appending data.","INFO",false,false,true);
	        foreach ($data as $key => $value) {
		        $record->$key = $value;
	        }
	        $record->save();
        }
    }

	/**
	 * @param $tableName
	 * @param bool | array $columns - The rows to select
	 * @param array|bool $selectors - Optional array of key, value pairs to be used as WHERE selectors
	 * @return array|bool
	 */
    public function get($tableName, $columns=false, $selectors=false) {
	    $path = $this->path;
		$results = false;
	    //write_log("GET CALLED for $tableName: ".json_encode(['rows'=>$columns, 'sel'=>$selectors]),"INFO",false,false,true);
		if (is_string($columns)) $columns = [$columns];
	    $table = (new Database($path))->table($tableName);
	    $builder = new Builder($table);
	    $apiToken = $selectors['apiToken'] ?? false;
	    $email = $selectors['plexEmail'] ?? false;
	    $prefName = $selectors['name'] ?? false;

	    if ($tableName === 'userdata') {
	    	if ($apiToken) {
	    		$results = [$table->get($apiToken)->toArray()];
		    } else if ($email) {
			    $results = $builder->where('plexEmail', "==", $email)->get()->results();
			    $results = $this->resultsToArray($results);
		    } else {
			    $results = $table->getAll();
			    $results = $this->resultsToArray($results);
		    }
	    }

	    if ($tableName === 'general') {
	    	if ($prefName) {
			    if ($prefName && $columns) {
				    $columns = [$prefName];
			    }
			    if ($columns) {
				    if (is_string($columns)) $columns = [$columns];
				    foreach ($columns as $row) {
					    $data = $table->get($row)->toArray();
					    $results = $data['value'] ?? null;
				    }
				    $results = [$results];
			    }
		    } else {
			    $results = $table->getAll();
			    $results = $this->resultsToArray($results);
		    }
	    }

	    if ($tableName === 'commands') {
			$results = $builder->where('apiToken', '==', $apiToken)->get()->results();
		    $results = $this->resultsToArray($results);
	    }

	    if (is_array($columns) && $tableName !== 'general') {
		    foreach($results as &$row) {
			    if (is_array($row)) {
			    	$row = array_intersect_key($row,array_flip($columns));
			    } else {
			    	write_log("THIS IS NOT AN ARRAY, DUDE.","ERROR",false,false,true);
			    }
		    }
	    }
		return $results;
    }

	/**
	 * @param $table
	 * @param array $selectors
	 * @return bool
	 */
    public function delete($table, $selectors) {
    	$db = new Database(($this->path));
	    $deleted = $db->table($table)->where($selectors)->get->first()->delete();
	    return $deleted;
    }


    private function resultsToArray($results) {
    	$temp = [];
    	foreach($results as $record) {
        	$class = get_class($record);
        	if ($class === "Filebase\Document") {
		        $data = $record->toArray();
		        $temp[] = $data;
	        }
        }
	    return count($temp) ? $temp : $results;
    }
}