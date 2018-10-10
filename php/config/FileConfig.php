<?php
namespace digitalhigh;
use Filebase\Database;

require_once dirname(__FILE__) . "/ConfigException.php";
require_once dirname(__FILE__) . '/../vendor/autoload.php';

class FileConfig {

    protected $connection;

	/**
	 * Filebase DB constructor.
	 * @param $dbPath
	 */
    public function __construct($dbPath)
	{
		$db = new Database([
			'path' => $dbPath
		]);
		$this->connection = $db;
	}


    /**
     * @return bool
     */
    public function isValid()
    {
        return ($this->connection ? true : false);
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

	    $doc = false;
        $db = $this->connection;
	    if ($db) {
		    $table = $db->table($section);
		    if (count($selectors)) {
			    $doc = $table->where($selectors)->get()->first();
		    } else {
			    $doc = $table->getAll()->toArray();
		    }
		    foreach($data as $key => $value) {
			    $doc->$key = $value;
		    }
		    $doc->save();
	    }

    }

    /**
     * @param string $section
     * @param bool | array $keys
     * @param bool | string $selector
     * @param bool | string $search
     * @return array|bool
     */
    public function get($section, $keys=false, $selector=false, $search=false) {
        $db = $this->connection;
	    if ($section !== 'general') write_log("GET CALLED: ".json_encode(['sec'=>$section,'keys'=>$keys, 'sel'=>$selector, 'ser'=>$search]),"INFO",false,false,true);
	    $return = $selectors = [];

        if ($selector && $search) {
		    if (is_array($selector) && is_array($search)) {
			    $selectors = array_combine($selector, $search);
		    } else if (is_string($selector) && is_string($search)) {
			    $selectors = [$selector=>$search];
		    }
	    }

        if ($db) {
        	$table = $db->table($section);
        	if ($selectors) {
        		$rows = $table->where($selectors)->get()->first()->toArray();
	        } else {
        		$rows = $table->getAll();
	        }
        }
        if ($section !== 'general') write_log("Returning: ".json_encode($rows),"INFO",false,false,true);
        return $rows;
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