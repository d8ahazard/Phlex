<?php

namespace digitalhigh;
require_once dirname(__FILE__). "/JsonXmlElement.php";
use JsonXmlElement;
class multiCurl
{
    private $urls;
    private $timeout;
    private $files;

    /**
     * multiCurl constructor.
     * @param $urls
     * @param int $timeout
     * @param string | bool $filePath - If specified, try to save data to a file.
     */
    function __construct($urls, $timeout=10, $filePath=false) {
        $this->urls = $urls;
        $this->timeout = $timeout;
        $this->files = $filePath;
    }

    function process() {
        $urls = $this->urls;
        $timeout = $this->timeout;
        $files = $this->files;
        $mh = curl_multi_init();
        $ch = $res = [];
        foreach($urls as $i => $item) {
            if (is_array($item)) {
                $url = $item[0];
                $header = $item[1];
            } else {
                $url = $item;
                $header = false;
            }
            write_log("URL: $url");
            $ch[$i] = curl_init($url);
            curl_setopt($ch[$i], CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch[$i],CURLOPT_CONNECTTIMEOUT,$timeout);

            if ($header) {
                //write_log("We have headers: ".json_encode($header));
                curl_setopt($ch[$i],CURLOPT_HTTPHEADER,$header);
            }
            if ($files) {
                curl_setopt($ch[$i], CURLOPT_BINARYTRANSFER, true);
                curl_setopt($ch[$i], CURLOPT_FOLLOWLOCATION, 0);
            }
            curl_multi_add_handle($mh, $ch[$i]);
        }

        // Start performing the request
        do {
            $execReturnValue = curl_multi_exec($mh, $runningHandles);
        } while ($execReturnValue == CURLM_CALL_MULTI_PERFORM);

        // Loop and continue processing the request
        while ($runningHandles && $execReturnValue == CURLM_OK) {
            if (curl_multi_select($mh) != -1) {
                usleep(100);
            }

            do {
                $execReturnValue = curl_multi_exec($mh, $runningHandles);
            } while ($execReturnValue == CURLM_CALL_MULTI_PERFORM);
        }

        // Check for any errors
        if ($execReturnValue != CURLM_OK) {
            write_log("Curl multi read error $execReturnValue!", "ERROR");
        }

        // Extract the content
        foreach($urls as $i => $url) {
            // Check for errors
            $curlError = curl_error($ch[$i]);
            if($curlError == "") {
                $res[$i] = curl_multi_getcontent($ch[$i]);
            } else {
                write_log("Error handling curl '$curlError' for url: $url","ERROR");
            }
            // Remove and close the handle
            curl_multi_remove_handle($mh, $ch[$i]);
            curl_close($ch[$i]);
        }
        // Clean up the curl_multi handle
        curl_multi_close($mh);
        //write_log("Res: ".json_encode($res));
        $results = [];
        foreach ($res as $url => $response) {
            if ($files) {
                $filePath = $files . "/" . rand(1000,10000);
                write_log("Trying to save data from url '$url' to $filePath");
                file_put_contents($filePath, $response);
                $results["$url"] = $filePath;
            } else {
                $data = $json = $xml = false;
                try {
                    $data = json_decode($response, true);
                    if (json_last_error()!==JSON_ERROR_NONE) {
                        write_log("This is not JSON");
                    }
                    $xml = simplexml_load_string($response);
                    if ($xml !== false) {
                        $xml = new JsonXmlElement($response);
                        $data = $xml->asArray();
                    }
                } catch (\Exception $e) {
                    //write_log("Exception: $e");
                }
                $response = $data ? $data : $response;
                $results["$url"] = $response;
            }
        }
        unset($mh);
        unset($ch);
        return $results;
    }
}