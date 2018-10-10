<?php
namespace digitalhigh;

require_once dirname(__FILE__) . "/ConfigException.php";
require_once dirname(__FILE__) . '/../vendor/autoload.php';

use Filebase\Database;
use Filebase\Table;
use Filebase\Query\Builder;
use Base\Support\Filesystem;


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
     * @param null $selector
     * @param null $search
     * @param bool $new
     */
    public function set($section, $data, $selector=null, $search=null, $new=false) {
	    $selectors = [];
	    if ($section !== 'general') write_log("SET CALLED: ".json_encode(['sec'=>$section,'data'=>$data, 'sel'=>$selector, 'ser'=>$search]),"INFO",false,false,true);
	    if ($search && $selector) {
		    if (is_array($search) && is_array($selector)) {
			    $selectors = array_combine($selector, $search);
		    } else if (is_string($search) && is_string($selector)) {
			    $selectors = [$selector => $search];
		    }
	    }

	    $path = $this->path;
        $db = new Database($path);
        $table = $db->table($section);


    }

	/**
	 * @param $tableName
	 * @param bool | array $columns - The rows to select
	 * @param array|bool $selectors - Optional array of key, value pairs to be used as WHERE selectors
	 * @return array|bool
	 */
    public function get($tableName, $columns=false, $selectors=false) {
	    $path = $this->path;
		$results = [];
	    //write_log("GET CALLED for $tableName: ".json_encode(['rows'=>$columns, 'sel'=>$selectors]),"INFO",false,false,true);
		if (is_string($columns)) $columns = [$columns];
	    $table = (new Database($path))->table($tableName);
	    $builder = new Builder($table);


	    switch($tableName) {
	    	// Userdata rows are named using apiToken as suggested
		    case 'userdata':
		    	    if ($selectors) {
			            foreach ($selectors as $key => $value) {
					        //write_log("Selecting by key $key and value $value", "INFO", false, false, true);
					        if ($key !== 'apiToken') {
						        $results = $builder->where($key, '==', $value)->get()->results();
						        //write_log("Raw query data: ".json_encode($results),"INFO",false,false,true);
						        $temp = [];
						        foreach($results as $record) {
							        $data = $record->toArray();
							        $temp[] = $data;
						        }
						        $userData = $temp;
					        } else {
						        $userData = [$table->get($value)->toArray()];
					        }
					        break;
				        }

			        } else {
				        $results = $table->getAll();
				        //write_log("Raw results: ".json_encode($results),"INFO",false,false,true);
				        $temp = [];
				        foreach($results as $record) {
					        $data = $record->toArray();
					        $temp[] = $data;
				        }
				        $userData = $temp;
			        }
			        $results = $userData;
			        //write_log("Here are some results: ".json_encode($results),"INFO",false,false,true);
		    		if (is_array($columns)) {
						foreach($results as &$row) {
							//write_log("Trying to find keys from the intersection of: ".json_encode($row, $columns), "INFO",false,false,true);
							$row = array_intersect_key($row,array_flip($columns));
							//write_log("")
						}
				    }
			    break;
		    // General rows are named after the setting value...I think I've got this one
		    case 'general':
		    	if (($selectors['name'] ?? false) && $columns) {
		    		$columns = [$selectors['name']];
			    }
		    	if ($columns) {
		    		if (is_string($columns)) $columns = [$columns];
		    		foreach($columns as $row) {
		    			$data = $table->get($row)->toArray();
		    			$results[$row] = $data;
				    }
				    $results = [$results];
			    } else {
		    		// If no selector - return everything
				    $data = $table->getAll();
				    foreach($data as $record) {
				    	$record = $record->toArray();
					    $key = $record['name'];
					    $value = $record['value'];
					    array_push($results,$record);
				    }
				    $results = [$results];
				    //write_log("Raw results: ".json_encode($results),"INFO",false,false,true);

			    }
			    break;
	        // Search through array of command history. Always looked up by timestamp + user apiToken
		    default:
			    $selKey = "";
			    $selValue = "";
			    foreach ($selectors as $key => $value) {
				    $selKey = $key;
				    $selValue = $value;
				    break;
			    }
			    $results = $builder->where($selKey, '==', $selValue)->get()->results();
			    //write_log("Raw query data: ".json_encode($results),"INFO",false,false,true);
			    $temp = [];

			    foreach($results as $record) {
				    $data = $record->toArray();
				    $temp[] = $data;
			    }
			    $results = $temp;
	    }

        //write_log("Returning: ".json_encode($results),"INFO",false,false,true);
        return $results;
    }

    /**
     * @param $section
     * @param null $selector
     * @param null $value
     * @return mixed
     */
    public function delete($section, $selector=null, $value=null) {
	    $db = $this->connection;
	    if ($db) {
		    $sectionData = $db->table($section)->getAll();
		    $newData = $selectors = [];
		    if ($selector && $value)
		    	if (is_string($selector)) {
		    		$selectors = [$selector=>$value];
			    } else {
		    		$selectors = array_combine($selector,$value);
			    }


	    }
	    return true;
    }
}