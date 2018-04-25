<?php

class JsonConfig extends ArrayObject {

    protected $fileName;
    protected $header;
    protected $secure;

    public $sections;

    public function __construct($filename, $secure=true)
    {
        $this->fileName = $filename;
        $this->header = ";<?php die('Access denied');?>";
        $this->secure = $secure;

        $this->sections = [];

        if (file_exists($filename)) {
            $this->read();
        }
    }

    public function read() {
        $data = file_get_contents($this->fileName);
        $data = str_replace($this->header,"",$data);
        $data = json_decode($data,true);
        if (!$data) {
            write_log("Error reading data.");
            throw new InvalidArgumentException("Unable to read specified file.");
        }
        $this->sections = $data ? $data : [];
    }

    public function save() {
        $data = json_encode($this->sections,JSON_PRETTY_PRINT);
        $output = $this->header . PHP_EOL . $data;
        file_put_contents($this->fileName,$output,LOCK_EX);
    }

    public function getSection($key, $default) {
        return $this->sections["$key"] ?? $default;
    }

    public function setSection($key,$data) {
        $this->sections["$key"] = $data;
        $this->save();
    }

    public function setValue($section, $key, $data) {
        $this->sections["$section"]["$key"] = $data;
        $this->save();
    }

    public function getValue($section, $key, $default) {
        return $this->sections["$section"]["$key"] ?? $default;
    }

    public function deleteSection($key) {
        if (isset($this->section["$key"])) unset($this->sections["$key"]);
        $this->save();
    }

    public function deleteValue($section, $key) {
        if (isset($this->section["$section"]["$key"])) unset($this->sections["$section"]["$key"]);
        $this->save();
    }

    public function findSection($key=null, $value=null) {
        if ($key == null && $value == null) return false;
        $sections = [];
        foreach ($this->sections as $name => $data) {
            foreach ($data as $dataKey => $dataValue) {
                if ($key == null) {
                    if ($dataValue == $value) $sections["$name"] = $data;
                } else if ($value == null) {
                    if ($dataKey == $key) $sections["$name"] = $data;
                } else {
                    if ($dataValue == $value && $dataKey == $key) $sections["$name"] = $data;
                }
            }
        }
        return count($sections) ? $sections : false;
    }

}