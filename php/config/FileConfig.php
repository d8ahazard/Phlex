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
	    if ($tableName !== 'general') write_log("GET CALLED: ".json_encode(['table'=>$tableName,'rows'=>$columns, 'sel'=>$selectors]),"INFO",false,false,true);

	    $db = new Database($path);
	    $table = $db->table($tableName);
	    switch($tableName) {
	    	// Userdata rows are named using apiToken as suggested
		    case 'userdata':
		    		if ($columns) {
		    			$results = $table->where($selectors)->select($columns)->results();
				    } else {
					    $results = $table->where($selectors)->results();
				    }
		    		// I also need to re-append the apiToken value to the returned data
			    break;
		    // General rows are named after the setting value...I think I've got this one
		    case 'general':
		    	if ($columns) {
		    		if (is_string($columns)) $columns = [$columns];
		    		foreach($columns as $row) {
		    			$results[] = $table->get($row);
				    }
			    } else {
		    		// If no selector - return everything
				    $results = [];
		    		$results = $table->getAll(false);
			    }
			    break;
	        // Search through array of command history. Always looked up by timestamp + user apiToken
		    default:
		    	$results = $table->where($selectors)->results();

	    }
        write_log("Returning: ".json_encode($results),"INFO",false,false,true);
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