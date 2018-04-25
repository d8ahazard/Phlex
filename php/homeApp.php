<?php

require_once dirname(__FILE__) . '/vendor/autoload.php';
require_once dirname(__FILE__). '/util.php';
require_once dirname(__FILE__) . "/JsonConfig.php";
use Cz\Git\GitRepository;

if (!function_exists('checkFiles')) {
    function checkFiles()
    {
        if (isset($_SESSION['webApp'])) return [];
        $messages = [];
        $extensions = [
            'curl',
            'xml'
        ];

        $logDir = file_build_path(dirname(__FILE__), "..", "logs");
        $logPath = file_build_path($logDir, "Phlex.log.php");
        $rwDir = file_build_path(dirname(__FILE__), "..", "rw");
        $errorLogPath = file_build_path($logDir, "Phlex_error.log.php");
        $updateLogPath = file_build_path($logDir, "Phlex_update.log.php");
        $configFile = file_build_path($rwDir, "config.ini.php");
        $cmdPath = file_build_path($rwDir, "commands.php");

        $files = [
            $logPath,
            $errorLogPath,
            $updateLogPath,
            $configFile,
            $cmdPath
        ];

        $secureString = "'; <?php die('Access denied'); ?>";
        if (!file_exists($logDir)) {
            if (!mkdir($logDir, 0777, true)) {
                $message = "Unable to create log folder directory, please check permissions and try again.";
                $error = [
                    'title' => 'Permission error.',
                    'message' => $message,
                    'url' => false
                ];
                array_push($messages, $error);
            }
        }
        if (!file_exists($rwDir)) {
            if (!mkdir($rwDir, 0777, true)) {
                $message = "Unable to create secure storage directory, please check permissions and try again.";
                $error = [
                    'title' => 'Permission error.',
                    'message' => $message,
                    'url' => false
                ];
                array_push($messages, $error);
            }
        }
        foreach ($files as $file) {
            if (!file_exists($file)) {
                mkdir(dirname($file), 0777, true);
                touch($file);
                chmod($file, 0777);
                file_put_contents($file, $secureString);
            }
            if ((file_exists($file) && (!is_writable(dirname($file)) || !is_writable($file))) || !is_writable(dirname($file))) { // If file exists, check both file and directory writeable, else check that the directory is writeable.
                $message = 'Either the file ' . $file . ' and/or it\'s parent directory is not writable by the PHP process. Check the permissions & ownership and try again.';
                $url = '';
                if (PHP_SHLIB_SUFFIX === "so") { //Check for POSIX systems.
                    $message .= "  Current permission mode of " . $file . " is " . decoct(fileperms($file) & 0777);
                    $message .= "  Current owner of " . $file . " is " . posix_getpwuid(fileowner($file))['name'];
                    $message .= "  Refer to the README on instructions how to change permissions on the aforementioned files.";
                    $url = 'http://www.computernetworkingnotes.com/ubuntu-12-04-tips-and-tricks/how-to-fix-permission-of-htdocs-in-ubuntu.html';
                } else if (PHP_SHLIB_SUFFIX === "dll") {
                    $message .= "  Detected Windows system, refer to guides on how to set appropriate permissions."; //Can't get fileowner in a trivial manner.
                    $url = 'https://stackoverflow.com/questions/32017161/xampp-on-windows-8-1-cant-edit-files-in-htdocs';
                }
                write_log($message, "ERROR");
                $error = [
                    'title' => 'File error.',
                    'message' => $message,
                    'url' => $url
                ];
                array_push($messages, $error);
            }
        }

        foreach ($extensions as $extension) {
            if (!extension_loaded($extension)) {
                $message = "The " . $extension . " PHP extension, which is required for Phlex to work correctly, is not loaded." . " Please enable it in php.ini, restart your webserver, and then reload this page to continue.";
                write_log($message, "ERROR");
                $url = "http://php.net/manual/en/book.$extension.php";
                $error = [
                    'title' => 'PHP Extension not loaded.',
                    'message' => $message,
                    'url' => $url
                ];
                array_push($messages, $error);
            }
        }


        //$testMessage = ['title'=>'Test message.','message'=>"This is a test of the emergency alert system. If this were a real emergency, you'd be screwed.",'url'=>'https://www.google.com'];
        //array_push($messages,$testMessage);
        return $messages;
    }
}

function checkGit() {
    if (isset($_SESSION['hasGit'])) {
        return $_SESSION['hasGit'];
    } else {
        exec("git", $lines);
        $hasGit = ((preg_match("/git help/", implode(" ", $lines))) && (file_exists(dirname(__FILE__) . '/../.git')));
        writeSession('hasGit',$hasGit);
    }
    return $hasGit;
}

function readConfig($section, $key, $default) {
    $configFile = file_build_path(dirname(__FILE__), "..","rw","config.ini.php");
    $config = new JsonConfig($configFile);
    return ($config->getValue($section,$key,$default));
}

function saveConfig($section, $key, $value) {
    $configFile = file_build_path(dirname(__FILE__), "..","rw","config.ini.php");
    $config = new JsonConfig($configFile);
    $config->setValue($section, $key, $value);
}

if (!function_exists('checkSetDeviceID')) {
    function checkSetDeviceID() {
        $deviceID = readConfig('general', 'deviceID', false);
        if (!$deviceID) {
            $deviceID = randomToken(12);
            saveConfig("general", "deviceID", $deviceID);
        }

        return $deviceID;
    }
}

if (!function_exists("checkSSL")) {
    function checkSSL() {
        return readConfig('general','forceSsl',false);
    }
}

if (!function_exists("checkUpdates")) {
    function checkUpdates($install = false) {
        write_log("Function fired." . ($install ? " Install requested." : ""));
        $installed = $pp = $result = false;
        $html = '';
        $autoUpdate = $_SESSION['autoUpdate'];
        write_log("Auto Update is " . ($autoUpdate ? " on " : "off"));
        if (checkGit()) {
            write_log("This is a repo and GIT is available, checking for updates.");
            try {
                $repo = new GitRepository(dirname(__FILE__));
                if ($repo) {
                    $repo->fetch();
                    $revision = $repo->getRev();
                    if ($revision !== readConfig('general','revision','foo')) saveConfig('general','revision',$revision);
                    $branch = $repo->getCurrentBranchName();
                    $result = $repo->readLog($branch);
                    write_log("ReadLog result for branch $branch: ".json_encode($result));
                    $logHistory = readUpdate();
                    if (count($logHistory)) $installed = $logHistory[0]['installed'] ?? false;
                    $header = '<div class="cardHeader">
                        Current revision: ' . substr($revision, 0, 7) . '<br>
                        ' . ($installed ? "Last Update: " . $installed : '') . '
                    </div>';
                    // This never works right when you're developing the app...
                    if (1 == 2) {
                        $html = $header . '<div class="cardHeader">
                            Status: ERROR: Local file conflicts exist.<br><br>
                        </div><br>';
                        write_log("LOCAL CHANGES DETECTED.", "ERROR");
                        return $html;
                    }

                    if (count($result)) {
                        $log = $result;
                        writeSession('updateAvailable', count($log));
                        $html = parseUpdateLog($log);
                        $html = $header . '<div class="cardHeader">
                            Status: ' . count($log) . ' commit(s) behind.<br><br>
                            Missing Update(s):' . $html . '</div>';
                        if (($install) || ($autoUpdate)) {
                            write_log("Updating from repository - " . ($install ? 'Manually triggered.' : 'Automatically triggered.'), "INFO",false,true);
                            $repo->pull('origin');
                            //write_log("Pull result: ".$result);
                            logUpdate($log);

                        }
                    } else {
                        write_log("No changes detected.");
                        writeSession('updateAvailable','',true);
                        if (count($logHistory)) {
                            $html = parseUpdateLog($logHistory[0]['commits']);
                            $installed = $logHistory[0]['installed'];
                        } else {
                            $html = parseUpdateLog($repo->readLog("", $branch, 0,true));
                        }
                        $html = $header . '<div class="cardHeader">
                            Status: Up-to-date<br><br>
                            ' . ($installed ? "Installed: " . $installed : '') . '
                        Last Update:' . $html . '</div><br>';
                    }
                } else {
                    write_log("Couldn't initialize git.", "ERROR");
                }
            } catch (\Cz\Git\GitException $e) {
                write_log("An exception has occurred: " . $e, "ERROR");
            }
        } else {
            write_log("Doesn't appear to be a cloned repository or git not available.", "INFO");
        }
        return $html;
    }
}

if (!function_exists("deviceName")) {
    function deviceName() {
        return "Flex TV (Home)";
    }
}

if (!function_exists("fetchBackground")) {
    function fetchBackground() {
        $path = "https://img.phlexchat.com";

        $code = 'var elem = document.createElement("img");'.PHP_EOL.
            'elem.setAttribute("src", "'.$path.'");'.PHP_EOL.
            'elem.className += "fade-in bg bgLoaded";'.PHP_EOL.
            'document.getElementById("bgwrap").appendChild(elem);'.PHP_EOL;
        return $code;
    }
}

if (!function_exists('fetchCommands')) {
    function fetchCommands() {
        $filename = file_build_path(dirname(__FILE__), "..","rw","commands.php");
        $handle = fopen($filename, "r");
        //Read first line, but do nothing with it
        fgets($handle);
        $contents = '';
        //now read the rest of the file line by line, and explode data
        while (!feof($handle)) {
            $contents .= fgets($handle);
        }
        return json_decode($contents,true);
    }
}

if (!function_exists('fetchDeviceCache')) {
    function fetchDeviceCache() {
        $list = readConfig($_SESSION['apiToken'],'dlist',[]);
        $data = [
            "plexServerId" => readConfig($_SESSION['apiToken'], 'plexServerId', ""),
            "plexDvrId" => readConfig($_SESSION['apiToken'], 'plexDvrId', ""),
            "plexClientId" => readConfig($_SESSION['apiToken'], 'plexClientId', "")
        ];
        if ($data) {
            writeSessionArray($data);
            $list = json_decode(base64_decode($list), true);
        }
        return $list;
    }
}

if (!function_exists('fetchUser')) {
    function fetchUser($userData) {
        $configFile = file_build_path(dirname(__FILE__), "..","rw","config.ini.php");
        $config = new JsonConfig($configFile);
        $email = $userData['plexEmail'];
        $userData = $config->findSection('plexEmail',$email);
        if ($userData) {
            foreach($userData as $token => $data) {
                $data['apiToken'] = $token;
                $userData = $data;
                break;
            }
        }
        return $userData;
    }
}

if (!function_exists('fetchUserData')) {
    function fetchUserData() {
        $configFile = file_build_path(dirname(__FILE__), "..","rw","config.ini.php");
        $config = new JsonConfig($configFile);
        write_log("Loading config from $configFile");
        $sections = $config->sections;
        write_log("Parsed sections: ".json_encode($sections));
        $noNewUsers = $config->getValue('general','noNewUsers',false);
        $data = $_SESSION['apiToken'] ?? false;
        if ($data) {
            $data = $config->getSection($data,[]);
        }
        if (count($data)) {
            $data['noNewUsers'] = $noNewUsers;
        }
        return $data;
    }
}

if (!function_exists("formatLog")) {
    function formatLog($logData) {
        $authString = "'; <?php die('Access denied'); ?>" . PHP_EOL;
        $logData = str_replace($authString, "", $logData);
        $lines = array_reverse(explode("\n", $logData));
        $JSON = false;
        $records = [];
        foreach ($lines as $line) {
            $sections = explode(" - ", $line);
            preg_match_all("/\[([^\]]*)\]/", $sections[0], $matches);
            $params = $matches[0];
            if (count($sections) >= 2) {
                $message = trim($sections[1]);
                $message = preg_replace('~\{(?:[^{}]|(?R))*\}~', '', $message);
                if ($message !== trim($sections[1])) $JSON = true;
                if ($JSON) $JSON = str_replace($message, "", trim($sections[1]));
                if (count($params) >= 3) {
                    $record = [
                        'time' => substr($params[0], 1, -1),
                        'level' => substr($params[1], 1, -1),
                        'caller' => substr($params[2], 1, -1),
                        'message' => $message
                    ];
                    if ($JSON) $record['JSON'] = trim($JSON);
                    array_push($records, $record);
                }
            }
        }
        return json_encode($records);
    }
}

function firstUser() {
    $configFile = file_build_path(dirname(__FILE__), "..","rw","config.ini.php");
    $config = new JsonConfig($configFile);
    $general = $config->getSection('general',false);
    return is_array($general);
}

if (!function_exists('isWebApp')) {
    function isWebApp() {
        return false;
    }
}

if (!function_exists('logCommand')) {
    function logCommand($resultObject) {
        if (isset($_GET['noLog'])) {
            write_log("UI command, not logging.");
            return;
        }
        $resultObject = (!is_array($resultObject)) ? json_decode($resultObject,true) : $resultObject;
        $resultObject['timecode'] = date_timestamp_get(new DateTime($resultObject['timestamp']));
        $commands = $_SESSION['newCommand'] ?? [];
        array_push($commands,$resultObject);
        writeSession('newCommand',$commands);
        if (isset($_GET['say'])) echo json_encode($resultObject);
        // Check for our JSON file and make sure we can access it
        $filename= file_build_path(dirname(__FILE__), "..","rw","commands.php");
        $json_a = fetchCommands();
        if (empty($json_a)) $json_a = [];
        // Append our newest command to the beginning
        array_unshift($json_a, $resultObject);
        // If we have more than 10 commands, remove one.
        if (count($json_a) >= 11) array_pop($json_a);
        // Triple-check we can write, write JSON to file
        if (!$handle = fopen($filename, 'wa+')) die;
        $cache_new = "'; <?php die('Access denied'); ?>" . PHP_EOL;
        $cache_new .= json_encode($json_a, JSON_PRETTY_PRINT);
        if (fwrite($handle, $cache_new) === FALSE) die;
        fclose($handle);
    }
}

if (!function_exists('newUser')) {
    function newUser($user) {
        $userName = $user['plexUserName'];
        $apiToken = randomToken(21);
        write_log("Creating and saving $userName as a new user.", "INFO");
        $user['apiToken'] = $apiToken;
        $_SESSION['apiToken'] = $apiToken;

        $defaults = [
            'returnItems' => '6',
            'rescanTime' => '6',
            'couchUri' => 'http://localhost',
            'sonarrUri' => 'http://localhost',
            'sickUri' => 'http://localhost',
            'radarrUri' => 'http://localhost',
            'plexDvrResolution' => '0',
            'plexDvrNewAirings' => 'true',
            'plexDvrStartOffsetMinutes' => '2',
            'plexDvrEndOffsetMinutes' => '2',
            'appLanguage' => 'en',
            'searchAccuracy' => '70',
            'darkTheme' => true,
            'hasPlugin' => false,
            'notifyUpdate' => false,
            'masterUser' =>firstUser()
        ];

        $userData = array_merge($defaults, $user);
        updateUserPreferenceArray($userData);
        return $user;
    }
}

function parseUpdateLog($log) {
    $html = '';
    foreach ($log as $commit) {
        $html .= '
                            <div class="panel panel-primary">
                                <div class="panel-heading cardHeader">
                                    <div class="panel-title">' . $commit['shortHead'] . ' - ' . $commit['date'] . '</div>
                                </div>
                                <div class="panel-body cardHeader">
                                    <b>' . $commit['subject'] . '</b><br>' . $commit['body'] . '
                                </div>
                            </div>';
    }
    return $html;
}

if (!function_exists('popCommand')) {
    function popCommand($id) {
        write_log("Popping ID of " . $id);
        $filename= file_build_path(dirname(__FILE__), "..","rw","commands.php");
        // Check for our JSON file and make sure we can access it
        $json_a = fetchCommands();
        // Read contents into an array
        $json_b = [];
        foreach ($json_a as $command) {
            if (strtotime($command['timestamp']) !== strtotime($id)) {
                array_push($json_b, $command);
            }
        }
        // Triple-check we can write, write JSON to file
        if (!$handle = fopen($filename, 'wa+')) die;
        $cache_new = "'; <?php die('Access denied'); ?>" . PHP_EOL . json_encode($json_b, JSON_PRETTY_PRINT);
        if (fwrite($handle, $cache_new) === FALSE) write_log("Error popping command!","ERROR",false,true);
        fclose($handle);
    }
}

function readUpdate() {
    $log = false;
    $filename = file_build_path(dirname(__FILE__), "..",'logs', "Phlex_update.log.php");
    if (file_exists($filename)) {
        $authString = "'; <?php die('Access denied'); ?>".PHP_EOL;
        $file = file_get_contents($filename);
        $file = str_replace($authString,"",$file);
        $log = json_decode($file, true) ?? [];
    }
    return $log;
}

if (!function_exists('setDefaults')) {
    function setDefaults() {
        ini_set("log_errors", 1);
        ini_set('max_execution_time', 300);
        error_reporting(E_ERROR);
        $errorLogPath = file_build_path(dirname(__FILE__),'..', 'logs', "Phlex_error.log.php");
        ini_set("error_log", $errorLogPath);
        date_default_timezone_set((date_default_timezone_get() ? date_default_timezone_get() : "America/Chicago"));
    }
}

function tailFile($filename, $lines = 50, $buffer = 4096) {
    if (!is_file($filename)) {
        return false;
    }
    $f = fopen($filename, "rb");
    if (!$f) {
        return false;
    }
    fseek($f, -1, SEEK_END);
    if (fread($f, 1) != "\n") $lines -= 1;
    $output = '';
    while (ftell($f) > 0 && $lines >= 0) {
        $seek = min(ftell($f), $buffer);
        fseek($f, -$seek, SEEK_CUR);
        $output = ($chunk = fread($f, $seek)) . $output;
        fseek($f, -mb_strlen($chunk, '8bit'), SEEK_CUR);
        $lines -= substr_count($chunk, "\n");
    }

    while ($lines++ < 0) {
        // Find first newline and remove all text before that
        $output = substr($output, strpos($output, "\n") + 1);
    }
    fclose($f);
    $output = str_replace("'; <?php die('Access denied'); ?>" . PHP_EOL, "", $output);
    return $output;
}

if (!function_exists('updateUserPreference')) {
    function updateUserPreference($key, $value) {
        $apiToken = $_SESSION['apiToken'] ?? false;
        $section = ($key=='noNewUsers') ? 'general' : $apiToken;

        if (trim($apiToken)) {
            write_log("Updating session value and saving $key as $value", "INFO");
            $master = $_SESSION['masterUser'] ?? false;
            if ($key === 'noNewUsers' && !$master) {
                write_log("Error, someone's trying to change things they shouldn't be.","ERROR");
            } else {
                writeSession($key, $value);
                saveConfig($section,$key,$value);
            }
        } else {
            write_log("No session username, can't save value.");
        }

    }
}

if (!function_exists('updateUserPreferenceArray')) {
    function updateUserPreferenceArray($array) {
        foreach($array as $key=>$value) {
            updateUserPreference($key,$value);
        }
    }
}

if (!function_exists('verifyApiToken')) {
    function verifyApiToken($apiToken) {
        $caller = getCaller("verifyApiToken");
        if (trim($apiToken)) {
            $configFile = file_build_path(dirname(__FILE__), "..","rw","config.ini.php");
            $config = new JsonConfig($configFile);
            $data = $config->getSection($apiToken,false);
        } else {
            write_log("Invalid token specified.", "ERROR");
            $data = false;
        }

        return $data;
    }
}

if (!function_exists('verifyPlexToken')) {
    function verifyPlexToken($token) {
        $user = $userData = false;
        $url = "https://plex.tv/users/account?X-Plex-Token=$token";
        $result = curlGet($url);
        $data = xmlToJson($result);
        if ($data) {
            write_log("Received userdata from Plex: " . json_encode($data));
            $userData = [
                'plexUserName' => $data['title'] ?? $data['username'],
                'plexEmail' => $data['email'],
                'plexAvatar' => $data['thumb'],
                'plexPassUser' => ($data['roles']['role']['id'] == "plexpass") ? "1" : "0",
                'appLanguage' => $data['profile_settings']['default_subtitle_language'],
                'plexToken' => $data['authToken']
            ];
        }
        if ($userData) {
            write_log("Recieved valid user data.", "INFO");
            //writeSessionArray($userData);
            $user = fetchUser($userData);
            if (!$user) {
                $webApp = $_SESSION['webApp'] ?? false;
                if (!$webApp) {
                    $noNewUsers = readConfig('general','noNewUsers',false);
                } else $noNewUsers = false;
                $user = $noNewUsers ? false : newUser($userData);
                if (!$user) return "Not allowed.";
            }
        }

        if ($user) {
            $_SESSION['apiToken'] = $user['apiToken'];
            updateUserPreferenceArray($user);
        }
        return $user;
    }
}

if (!function_exists('webAddress')) {
    function webAddress() {
        return $_SESSION['publicAddress'];
    }
}


