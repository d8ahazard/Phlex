<?php
require_once dirname(__FILE__). '/util.php';
require_once dirname(__FILE__) . '/vendor/autoload.php';
$useDb = file_exists(dirname(__FILE__) . "/db.conf.php");
require_once dirname(__FILE__) . ($useDb ? '/DbConfig.php' : '/JsonConfig.php');

use Cz\Git\GitRepository;
$isWebapp = isWebApp();
$_SESSION['webApp'] = $isWebapp;
$GLOBALS['webApp'] = $isWebapp;

$publicAddress = serverAddress();
$_SESSION['appAddress'] = $publicAddress;
$_SESSION['publicAddress'] = $publicAddress;

function updateUserPreference($key, $value) {
    setPreference('userdata',[$key=>$value],'apiToken',$_SESSION['apiToken']);
}

function updateUserPreferenceArray($data) {
    setPreference('userdata',$data,'apiToken',$_SESSION['apiToken']);
}

function setPreference($section, $data, $selector=null, $search=null, $new=false) {
    $useDb = file_exists(dirname(__FILE__) . "/db.conf.php");
    $configFile = file_build_path(dirname(__FILE__), "..","rw","config.ini.php");
    $config = $useDb ? new \digitalhigh\DbConfig() : new JsonConfig($configFile);
    $config->set($section, $data, $selector, $search, $new);
}

function getPreference($section, $keys=false, $default=false, $selector=null, $search=null,$single=true) {
    $useDb = file_exists(dirname(__FILE__) . "/db.conf.php");
    $configFile = file_build_path(dirname(__FILE__), "..","rw","config.ini.php");
    $config = $useDb ? new \digitalhigh\DbConfig() : new JsonConfig($configFile);
    $data = $config->get($section, $keys, $selector, $search);
    $ignore = false;
    //if ($section == 'general') write_log("Raw data: ".json_encode($data));
    if ($keys) {
        if (is_string($keys)) {
            $data = $data[0][$keys] ?? $default;
            $ignore = true;
        }
    }
    if (empty($data) && !$ignore) {
        $data = $default;
    }
    if ($single && !is_string($data))  $data = (count($data) == 1) ? $data[0] : $data;
    //if ($section == 'commands') write_log("Outgoing data: ".json_encode($data));
    return $data;
}

function deleteData($section, $selector=null, $value=null) {
    $useDb = file_exists(dirname(__FILE__) . "/db.conf.php");
    $configFile = file_build_path(dirname(__FILE__), "..","rw","config.ini.php");
    $config = $useDb ? new \digitalhigh\DbConfig() : new JsonConfig($configFile);
    $config->delete($section, $selector, $value);
}

function checkDefaults() {
    // OG Stuff
    ini_set("log_errors", 1);
    ini_set('max_execution_time', 300);
    error_reporting(E_ERROR);
    $errorLogPath = file_build_path(dirname(__FILE__),'..', 'logs', 'Phlex_error.log.php');
    ini_set("error_log", $errorLogPath);
    date_default_timezone_set((date_default_timezone_get() ? date_default_timezone_get() : "America/Chicago"));

    // Loading from General
    $defaults = getPreference('general',false,false);
    write_log("Received from get: ".json_encode($defaults));
    if (!$defaults) {
        write_log("Creating default values!","ALERT");
        $defaults = [
            'deviceId' => randomToken(12),
            'forceSSL' => false,
            'isWebApp' => false,
            'noNewUsers' => false,
            'deviceName' => "Flex TV (Home)",
            'publicAddress' => currentAddress(),
            'revision' => '000'
        ];
        foreach($defaults as $key=>$value) {
            $data = ['name'=>$key, 'value'=>$value];
            setPreference('general',$data,"name",$key);
        }
    }
    return $defaults;
}

function checkSetDeviceID() {
    $deviceId = getPreference('general','value','foo','name','deviceId');
    return $deviceId;
}

function checkSSL() {
    $forceSSL = getPreference('general', 'value',false,'name','forceSSL');
    return $forceSSL;
}

function isWebApp() {
    $isWebApp = getPreference('general','value',false,'name','isWebApp');
    return $isWebApp;
}

function currentAddress() {
    $url = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
    if (strpos($url,"?") !== false) $url = strtok($url,'?');
    write_log("URL: $url");
    $url = str_replace("index.php", "", $url);
    write_log("URL: $url");
    return $url;
}

function serverAddress() {
    //write_log("function fired: ".json_encode(getSessionData()),"ALERT");
    $loggedIn = isset($_SESSION['apiToken']);
    $section = ($loggedIn) ? 'userdata' : 'general';
    $key = ($loggedIn) ? 'publicAddress' : 'value';
    $selector = ($loggedIn) ? 'apiToken' : 'name';
    $search = ($loggedIn) ? $_SESSION['apiToken'] : 'publicAddress';
    //write_log("Getting sec $section key $key sel $selector sear $search");
    $serverAddress = getPreference($section, $key, 'http://localhost', $selector, $search);
    //write_log("Response: ".json_encode($serverAddress));
    return $serverAddress;
}

function fetchCommands() {
    $commands = getPreference('commands',['data','stamp'],[],'apiToken',$_SESSION['apiToken'],false);
    $out = [];
    foreach($commands as $command) {
        if (isset($command['data'])) {
            $data = json_decode($command['data'],true);
            $data['stamp'] = $command['stamp'];
            array_push($out,$data);
        }
    }
    return $out;
}

#TODO: Should we be writing session here?
function fetchDeviceCache() {
    $list = [];
    $keys = ['dlist','plexServerId','plexDvrId','plexClientId'];
    $cache = getPreference('userdata',$keys,false,'apiToken', $_SESSION['apiToken']);
    if (is_array($cache) && count($cache)) {
        $list = json_decode(base64_decode($cache['dlist']),true);
        unset($cache['dlist']);
        writeSessionArray($cache);
    }
    return $list;
}

function fetchUser($userData) {
    $email = $userData['plexEmail'];
    $keys = ['plexUserName', 'plexEmail','apiToken','plexAvatar','plexPassUser','plexToken','apiToken','appLanguage'];
    $data = getPreference('userdata',$keys,false,'plexEmail',$email);
    write_log("Fetched, data: ".json_encode($data),"ALERT");
    return $data;
}

function fetchUserData() {
    $temp = getPreference('userdata',false,false,'apiToken',$_SESSION['apiToken']);
    $data = [];
    foreach($temp as $key => $value) {
        if (isJSON($value)) $value = json_decode($value,true);
        $data[$key] = $value;
    }
    write_log("Fetched, data: ".json_encode($data),"ALERT");
    return $data;

}

function logCommand($resultObject) {
    if (isset($_GET['noLog'])) {
        write_log("UI command, not logging.");
        return;
    }
    $resultObject = (!is_array($resultObject)) ? json_decode($resultObject,true) : $resultObject;
    $resultObject['timecode'] = date_timestamp_get(new DateTime($resultObject['timestamp']));
    $commands = $_SESSION['newCommand'] ?? [];
    array_push($commands,$resultObject);
    writeSession('newCommand', $commands);
    if (isset($_GET['say'])) echo json_encode($resultObject);

    $apiToken = $_SESSION['apiToken'];
    unset($resultObject['media']);
    unset($resultObject['meta']);
    $data = json_encode($resultObject);
    if (trim($apiToken) && trim($data)) {
        #TODO: Verify that the commands are in the right order here
        $rows = getPreference('commands','stamp',[],'apiToken',$apiToken,false);
        write_log("Retrieved rows and stuff: ".json_encode($rows),"ALERT");
        if (is_array($rows)) $rows = array_reverse($rows);
        $i = 1;
        $stamps = [];
        foreach ($rows as $row) {
            if ($i >=20) {
                array_push($stamps,$row['stamp']);
            }
            $i++;
        }
        if (count($stamps)) {
            foreach ($stamps as $stamp) {
                deleteData('commands',['apiToken','stamp'],[$apiToken,$stamp]);
            }
        }
        $now = date("Y-m-d h:m:s");
        setPreference('commands',['stamp'=>$now,'apiToken'=>$apiToken, 'data'=>$data],null,null, true);
    } else {
        write_log("No token or data, skipping log.","WARNING");
    }
}

function firstUser() {
    $data = getPreference('userdata',false,[]);
    return (is_array($data) && count($data)) ? false : true;
}

function newUser($user) {
    write_log("Function fired.");
    $userName = $user['plexUserName'];
    $apiToken = randomToken(21);
    write_log("Creating and saving $userName as a new user.","INFO");
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
        'appLanguage' => getLocale(),
        'searchAccuracy' => '70',
        'darkTheme' => true,
        'hasPlugin' => false,
        'notifyUpdate' => false,
        'masterUser' => firstUser(),
        'publicAddress' => currentAddress(),
        'autoUpdate' => false,
        'notifyUpdate' => true
    ];
    $user = array_merge($user,$defaults);
    setPreference('userdata',$user,'apiToken',$apiToken);
    return $user;
}

function popCommand($id) {
    $commands = getPreference('commands','stamp',[],'apiToken',$_SESSION['apiToken']);
    if (is_array($commands) && count($commands)) foreach ($commands as $command) {
        $stamp = $command['stamp'];
        if ($id == $stamp) deleteData('commands','apiToken',$_SESSION['apiToken']);
    }
}

function verifyApiToken($apiToken) {
    $data = false;
    if (trim($apiToken)) {
        $keys = ['plexUserName', 'plexEmail','apiToken','plexAvatar','plexPassUser','plexToken','apiToken','appLanguage'];
        $data = getPreference('userdata',$keys,false,'apiToken',$apiToken);
    }
    if (!$data) {
        write_log("ERROR, api token $apiToken not recognized, called by ".getCaller(), "ERROR");
        dumpRequest();
    }
    return $data;
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

function checkFiles() {
    if (isWebApp()) return [];
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

function checkUpdates($install = false) {
    if (isWebApp()) return false;
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
                $current = getPreference('general', 'value','foo','name','revision');
                if ($revision !== $current) {
                    setPreference('general',['name'=>'revision','value'=>$revision],'name','revision');
                }
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

function deviceName() {
    $app = isWebApp() ? 'Web' : 'Home';
    return "Flex TV ($app)";
}


function isJSON($string){
    return is_string($string) && is_array(json_decode($string, true)) && (json_last_error() == JSON_ERROR_NONE) ? true : false;
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



function verifyPlexToken($token) {
	$user = $userData = false;
	$url = "https://plex.tv/users/account?X-Plex-Token=$token";
	$result = curlGet($url);
	$data = xmlToJson($result);
	if ($data) {
		write_log("Received userdata from Plex: ".json_encode($data),"INFO",false,true);
		$userData = [
			'plexUserName' => $data['title'] ?? $data['username'],
			'plexEmail' => $data['email'],
			'plexAvatar' => $data['thumb'],
			'plexPassUser' => ($data['roles']['role']['id'] == "plexpass"),
			'plexToken' => $data['authToken']
		];
	}
	if ($userData) {
		write_log("Recieved valid user data.","INFO");
		$user = fetchUser($userData);
		if (!$user) {
			write_log("User fetch failed, tring to create new.");
			$user = newUser($userData);
		}
	}

	if ($user) {
		write_log("We have the user, should be setting token here...");
		$_SESSION['apiToken'] = $user['apiToken'];
		write_log("Session token: ".$_SESSION['apiToken']);
		updateUserPreferenceArray($userData);
	}
	return $user;
}

function webAddress() {
    return serverAddress();
}


function fetchBackground() {
    $isWebApp = isWebApp();
    if ($isWebApp) {
        $dir = dirname(__FILE__) . "/../backgrounds/";
        $images = glob($dir . '*.{jpg}', GLOB_BRACE);
        $image = $images[array_rand($images)];
        $path = pathinfo($image);
        $path = "https://img.phlexchat.com/bg/" . $path['filename'] . "." . $path['extension'];
    } else {
        $path = "https://img.phlexchat.com";
    }
    $elem = $isWebApp ? 'elem.setAttribute("id","ov");'.PHP_EOL : '';
    $code = 'var elem = document.createElement("img");'.PHP_EOL.
        'elem.setAttribute("src", "'.$path.'");'.PHP_EOL.
        'elem.className += "fade-in bg bgLoaded";'.PHP_EOL.
        $elem .
        'document.getElementById("bgwrap").appendChild(elem);'.PHP_EOL;
    return $code;
}
