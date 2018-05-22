<?php
namespace digitalhigh;
require_once dirname(__FILE__) . "/ConfigException.php";
use ArrayObject;

class JsonConfig extends ArrayObject {

    protected $fileName;
    protected $header;
    protected $secure;

    public $data;

    /**
     * JsonConfig constructor.
     * @param $filename - The file to use.
     * @param bool $secure - Whether or not to add a "Access denied flag" to the file. (Needs to be .php)
     * @throws ConfigException - Pukes all over you if the file can't be written to.
     */
    public function __construct($filename, $secure=true)
    {
        $this->fileName = $filename;
        $this->header = "'; <?php die('Access denied'); ?>";
        $this->secure = $secure;
        $data = $this->read();
        if (!is_array($data)) {
            write_log("Error, dying to protect config.");
            die();
        } else {
            $this->data = $data;
        }

    }

    /**
     * @param $section
     * @param $data
     * @param null $selector
     * @param null $search
     * @param bool $new
     * @throws ConfigException
     */
    public function set($section, $data, $selector=null, $search=null, $new=false) {
        $temp = $this->data[$section] ?? [];
        $pushed = false;
        if (count($temp) && !$new) {
            foreach ($temp as &$record) {
                $update = false;
                if ($selector && $search) {
                    if (($record[$selector] ?? 'zxc') == $search) {
                        $update = true;
                    }
                } else {
                    $update = true;
                }
                if ($update) {
                    $pushed = true;
                    foreach ($data as $key => $value) {
                        $record[$key] = $value;
                    }
                }
            }
        }

        if (!$pushed || $new) {
            array_push($temp,$data);
        }
        $this->data[$section] = $temp;
        $this->save();
    }

    /**
     * @param $section
     * @param bool $keys
     * @param null $selector
     * @param null $search
     * @return array|mixed
     */
    public function get($section, $keys=false, $selector=null, $search=null) {
        $data = $this->data[$section] ?? [];
        if ($data) {
            if ($selector && $search) {
                $results = [];
                foreach($data as $record) {
                    if (isset($record[$selector]) && $record[$selector] == $search) {
                        array_push($results,$record);
                    }
                }
                $data = $results;
            }
            if ($keys) {
                if (is_string($keys)) $keys = [$keys];
                $temp = [];
                foreach($data as $record) {
                    $item = [];
                    foreach($keys as $key) {
                        if (isset($record[$key])) {
                            $item[$key] = $record[$key];
                        }
                    }
                    if (count($item)) {
                        array_push($temp,$item);
                    }
                }
                $data = $temp;
            }
        }
        return $data;
    }

    /**
     * @param $section
     * @param null $selectors
     * @param null $values
     * @throws ConfigException
     */
    public function delete($section, $selectors=null, $values=null) {
        $sectionData = $this->data[$section] ?? false;
        if ($sectionData) {
            if ($selectors && $values) {
                if (is_string($selectors)) {
                    $selector = $selectors;
                    $value = $values;
                    $i = 0;
                    foreach ($sectionData as $record) {
                        $check = $record[$selector] ?? 'foo';
                        if ($check == $value) unset($sectionData[$i]);
                        $i++;
                    }
                } else {
                    $j = 0;
                    foreach($selectors as $selector) {
                        $i = 0;
                        $value = $values[$i];
                        foreach ($sectionData as $record) {
                            $check = $record[$selector] ?? 'foo';
                            if ($check == $value) unset($sectionData[$i]);
                            $i++;
                        }
                        $j++;
                    }
                }
                $this->data[$section] = $sectionData;
            } else {
                write_log("Unsetting a whole section because you told me to.","ALERT");
                unset($this->data[$section]);
            }
            $this->save();
        }
    }


    /**
     * @return bool|mixed|string
     * @throws ConfigException
     */
    protected function read() {
        $path = $this->fileName;
        if (!file_exists($path) || !is_readable($path)) {
            throw new ConfigException("Error accessing file, it should exist already...");
        }

        $file = fopen($path, 'r');
        $locked = flock($file,LOCK_SH);
        if (!$locked) {
            $i = 0;
            while(!$locked && $i < 10) {
                $locked = flock($file,LOCK_SH);
                write_log("Unable to get file lock, looping.","WARN");
                usleep(100);
                $i++;
            }
        }

        if ($locked) {
            $data = fread($file, filesize($path));
        } else {
            write_log("Unable to get file lock!!","ALERT");
            $data = false;
        }
        flock($file,LOCK_UN);
        fclose($file);
        if ($data) {
            $data = str_replace($this->header, "", $data);
            if (trim($data) === "") {
                write_log("Blank file, creating array.","WARN");
                $data = [];
            } else {
                $data = json_decode($data,true);
                if (!$data) {
                    write_log("JSON Decode failed!!","ALERT");
                    rename($path,$path.".bk");
                    $data = [];
                }
            }
        } else {
            write_log("Unable to read from specified file.","ERROR");
        }
        return $data;
    }

    /**
     * @return mixed
     * @throws ConfigException
     */
    protected function save() {
        $data = json_encode($this->data,JSON_PRETTY_PRINT);
        $output = $this->header . PHP_EOL . $data;
        $i = 0;
        do {
            $result = $this->write($output);
            $i++;
        } while (!$result && $i >=5);

        if (!$result) {
            write_log("Can't save file!","ERROR");
            throw New ConfigException("Error saving file, this is bad!!");
        }
        return $result;
    }

    protected function write($contents) {
        $result = false;
        $path = $this->fileName;
        $result = file_put_contents($path,$contents,LOCK_EX);
        return $result !== false;
    }

}