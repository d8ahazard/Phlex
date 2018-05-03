<?php

class JsonConfig extends ArrayObject {

    protected $fileName;
    protected $header;
    protected $secure;

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
        if ($section == 'commands') write_log("Section data: ".json_encode($data),"ALERT");
        if ($data) {
            if ($section == 'commands') write_log("Got data: ".json_encode($data),"ALERT");
            if ($selector && $search) {
                $results = [];
                foreach($data as $record) {
                    if (isset($record[$selector]) && $record[$selector] == $search) {
                        if ($section == 'commands') write_log("Matching data: ".json_encode($record),"ALERT");
                        array_push($results,$record);
                    }
                }
                $data = $results;
            }
            if ($section == 'commands') write_log("Prekey data: ".json_encode($data),"ALERT");
            if ($keys) {
                if (is_string($keys)) $keys = [$keys];
                $temp = [];
                foreach($data as $record) {
                    $item = [];
                    foreach($keys as $key) {
                        if (isset($record[$key])) {
                            if ($section == 'commands') write_log("Key match for $key, pushing.","ALERT");
                            $item[$key] = $record[$key];
                        }
                    }
                    if (count($item)) {
                        if ($section == 'commands') write_log("Pushing item: ".json_encode($item),"ALERT");
                        array_push($temp,$item);
                    }
                }
                $data = $temp;
            }
        } else {
            if ($section == 'commands') write_log("NO DATA!!","ALERT");
        }
        if ($section == 'commands') write_log("Section data: ".json_encode($data),"ALERT");
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
                write_log("Unsetting a whole section because you told me to.","WARN");
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
        write_log("Trying to save here...");
        $data = json_encode($this->data,JSON_PRETTY_PRINT);
        write_log("Data to write: ".json_encode($this->data));
        $output = $this->header . PHP_EOL . $data;
        $success = file_put_contents($this->fileName,$output,LOCK_EX);
        write_log("Save " . ($success ? 'was' : 'was not') . ' successful.');
        return $success;
    }

}