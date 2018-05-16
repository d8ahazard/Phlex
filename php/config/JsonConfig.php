<?php
namespace digitalhigh;
require_once dirname(__FILE__) . "/ConfigException.php";
use ArrayObject;

class JsonConfig extends ArrayObject {

    protected $fileName;
    protected $header;
    protected $secure;
    protected $cache;

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
        $this->header = ";<?php die('Access denied');?>";
        $this->secure = $secure;

        $this->data = [];
        $this->cache = [];

        if (!$this->validate()) throw new ConfigException("Error accessing specified config file.");

        $data = $this->read();
        if (is_array($data)) {
            $this->data = $data;
            $this->cache = $data;
        } else {
            throw new ConfigException("Error reading data from file.");
        }
    }

    /**
     * @return bool
     */
    public function isValid() {
        return ($this->read ? true : false);
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
        $data = $this->cache[$section] ?? [];
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
     * @return bool
     */
    protected function validate() {
        if (!file_exists($this->fileName)) {
            if (touch($this->fileName)) {
                if (chmod($this->fileName,0666)) {
                    $data = $this->header . PHP_EOL .json_encode([],JSON_PRETTY_PRINT);
                    if (file_put_contents($this->fileName,$data)) {
                        return true;
                    }
                }
            }
        } else {
            return is_writable($this->fileName);
        }
        return false;
    }

    /**
     * @return bool|mixed|string
     * @throws ConfigException
     */
    protected function read() {
        $path = $this->fileName;
        if (!file_exists($path)) {
            throw new ConfigException("Error accessing file, it should exist already...");
        }
        $file = fopen($path,'r');
        $data = fread($file,filesize($path));
        fclose($file);

        if ($data !== false) {
            $data = str_replace($this->header, "", $data);
            $data = json_decode($data,true);
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
            throw New ConfigException("Error saving file, this is bad!!");
        } else {
            $this->cache = $this->data;
        }
        return $result;
    }

    protected function write($contents) {
        $path = $this->fileName;
        $fp = fopen($path, 'w+');
        if(!flock($fp, LOCK_EX))
        {
            return false;
        }
        $result = fwrite($fp, $contents);
        flock($fp, LOCK_UN);
        fclose($fp);
        return $result !== false;
    }

}