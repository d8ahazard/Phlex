<?php

class JsonConfig extends ArrayObject {

    protected $fileName;
    protected $header;
    protected $secure;
    protected $cache;

    public $data;

    public function __construct($filename, $secure=true)
    {
        $this->fileName = $filename;
        $this->header = ";<?php die('Access denied');?>";
        $this->secure = $secure;

        $this->data = [];

        if (file_exists($filename)) {
            $this->read();
        }
    }

    public function set($section, $data, $selector=null, $search=null, $new=false) {
        write_log("Trying to set data for $section with sel of $selector and sea of $search: ".json_encode($data),"ALERT");
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

    protected function read() {
        $data = file_get_contents($this->fileName);
        if ($data) {
            $data = str_replace($this->header, "", $data);
            $data = trim($data) ? json_decode($data, true) : [];
        }
        if (!$data) {
            write_log("Error reading data.","WARN");
            $data = [];
        }
        $this->data = $data;
    }

    protected function save() {
        $data = json_encode($this->data,JSON_PRETTY_PRINT);
        $output = $this->header . PHP_EOL . $data;
        $success = file_put_contents($this->fileName,$output,LOCK_EX);
        if (!$success) write_log("Save " . ($success ? 'was' : 'was not') . ' successful.',($success ? "INFO": "ALERT"));
        return $success;
    }

}