<?php
    require_once dirname(__FILE__) . '/vendor/autoload.php';
    require_once dirname(__FILE__) . '/cast/Chromecast.php';
    require_once dirname(__FILE__) . '/util.php';
    date_default_timezone_set("America/Chicago");
    ini_set("log_errors", 1);
    error_reporting(E_ERROR);
    $errfilename = 'Phlex_error.log';
    ini_set("error_log", $errfilename);
    $checkURL = $fetchCast = $post = $valid = false;
    if ( is_session_started() === FALSE ) {
        if (isset($argv[1])) {
            session_id($argv[1]);
        }
        session_start();
    }
    if (isset($argv[1])) {
        $config = new Config_Lite('config.ini.php');
        foreach ($config as $section => $setting) {
            if ($section != "general") {
                $testToken = $setting['apiToken'];
                if ($testToken == $argv[1]) {
                    write_log("API Token is a match, on to the wizard!");
                    $_SESSION['username'] = $setting['plexUserName'];
                    $valid = true;
                    break;
                }
            }
        }
    } else {
        echo "Please specify an API Token";
        die();
    }

    if (isset($argv[2])) {
        $params = explode("=",$argv[2]);
        if ($params[0] == 'CAST') $fetchCast = true;
        if ($params[0] == 'URL') $checkURL = $params[1];
    }

    if (isset($argv[3])) {
        $params = explode("=",$argv[3]);
        if ($params[0] == 'POST') $post = $params[1];
    }

    if ($valid) {
        if ($fetchCast) {
            $castDevices = fetchCastDevices();
            $i = 0;
            if ($castDevices) {
                foreach ($castDevices as $castDevice) {
                    foreach ($castDevice as $key => $value) {
                        $GLOBALS['config']->set('castDevice' . $i, $key, $value);
                    }
                    $i++;
                }
                saveConfig($GLOBALS['config']);
            }
        }
        if ($checkURL) {
            check_url($checkURL,$post);
        }
    }

    function fetchCastDevices() {
        if (!(isset($_GET['pollPlayer']))) write_log("Function fired.");
        $result = Chromecast::scan();
        $returns = array();
        if (!(isset($_GET['pollPlayer']))) write_log("Returns: ".json_encode($result));
        foreach ($result as $key=>$value) {
            $deviceOut = array();
            $nameString = preg_replace("/\._googlecast.*/","",$key);
            $nameArray = explode('-',$nameString);
            $id = array_pop($nameArray);
            $deviceOut['name'] = $value['friendlyname'];
            $deviceOut['product'] = 'cast';
            $deviceOut['id'] = $id;
            $deviceOut['token'] = 'none';
            $deviceOut['uri'] = "https://" . $value['ip'] . ":" . $value['port'];
            array_push($returns, $deviceOut);
        }
        if (json_encode($returns) == "[Error]") $returns = false;
        return $returns;
    }

    function check_url($url, $post=false) {
        $ch = curl_init($url);
        curl_setopt($ch,  CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_TIMEOUT,1);
        if ($post) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $_SESSION['plex_headers']);
        }
        /* Get the HTML or whatever is linked in $url. */
        $response = curl_exec($ch);

        /* Check for 404 (file not found). */
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        /* If the document has loaded successfully without any redirection or error */
        if ($httpCode >= 200 && $httpCode < 300) {
            write_log("Connection is valid: ".$url);
            return true;
        } else {
            write_log("Connection failed with error code ".$httpCode.": ".$url,"ERROR");
            return false;
        }
    }


