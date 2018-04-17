<?php

namespace digitalhigh;
require_once dirname(__FILE__). "/JsonXmlElement.php";
use JsonXmlElement;
class multiCurl
{
    private $urls;
    private $timeout;
    function __construct($urls, $timeout=10) {
        $this->urls = $urls;
        $this->timeout = $timeout;
    }
    function process() {
        write_log("Function fired!!");
        $urls = $this->urls;
        $timeout = $this->timeout;
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
            //curl_setopt($ch[$i],CURLOPT_TIMEOUT,$timeout);
            if ($header) {
                //write_log("We have headers: ".json_encode($header));
                curl_setopt($ch[$i],CURLOPT_HTTPHEADER,$header);
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
            $json = $xml = false;
            try {
                $json = json_decode($response,true);
                if (!is_array($json)) {
                    $json = new JsonXmlElement($response);
                    $json = $json->asArray();
                }
            } catch (\Exception $e) {
                //write_log("Exception: $e");
            }
            if (is_array($json)) $response = $json;
            $results["$url"] = $response;
        }
        unset($mh);
        unset($ch);
        return $results;
    }
}