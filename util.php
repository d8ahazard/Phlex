<?PHP

require_once dirname(__FILE__) . "/webApp.php";
require_once dirname(__FILE__) . '/vendor/autoload.php';



function plexSignIn($token) {
	$url = "https://plex.tv/pins/$token.xml";
	$user = $token = false;
	$headers = headerRequestArray(plexHeaders());
	$result = curlGet($url,$headers);
	$data = $result ? flattenXML(new SimpleXMLElement($result)) : false;
	if ($data) {
		$token = $data['auth_token'] ?? false;
	}

	if ($token) {
		write_log("Received valid token lookup: ".json_encode($data),"INFO");
		$user = verifyPlexToken($token);
	}

	return $user;
}

if (!function_exists('fetchUser')) {
	function fetchUser($userData) {
		$configDir = file_build_path(dirname(__FILE__),'rw', "config.ini.php");
		$config = $GLOBALS['config'] ?? new Config_Lite($configDir, LOCK_EX);
		$email = $userData['plexEmail'];
		foreach ($config as $key => $section) {
			$userEmail = $section['plexEmail'] ?? false;
			if ($userEmail == $email) {
				write_log("Found an existing user: " . $section['plexUserName']);
				$userData['apiToken'] = $section['apiToken'];
				return $userData;
			}
		}
		return false;
	}
}

if (!function_exists('fetchUserData')) {
	function fetchUserData() {
		$configDir = file_build_path(dirname(__FILE__),'rw', "config.ini.php");
		$config = $GLOBALS['config'] ?? new Config_Lite($configDir, LOCK_EX);
		foreach ($config as $token => $data) {
			if ($token == $_SESSION['apiToken']) {
				return $data;
			}
		}
		return false;
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
			'plexDvrStartOffset' => '2',
			'plexDvrEndOffset' => '2',
			'appLanguage' => 'en',
			'searchAccuracy' => '70',
			'darkTheme' => 1,
			'hasPlugin' => 0
		];

		$userData = array_merge($defaults, $user);
		updateUserPreferenceArray($userData);
		return $user;
	}
}


if (!function_exists('updateUserPreferenceArray')) {
	function updateUserPreferenceArray($array) {
		$configDir = file_build_path(dirname(__FILE__),'rw', "config.ini.php");
		$config = $GLOBALS['config'] ?? new Config_Lite($configDir, LOCK_EX);
		$apiToken = $_SESSION['apiToken'] ?? false;
		if (trim($apiToken)) {
			write_log("Updating session and saved values with array: " . json_encode($array));
			foreach ($array as $key => $value) {
				$_SESSION[$key] = $value;
				$config->set($apiToken, $key, $value);
			}
			saveConfig($config);
		} else {
			write_log("No session username, can't save value.");
		}
	}
}

if (!function_exists('updateUserPreference')) {
	function updateUserPreference($key, $value) {
		$configDir = file_build_path(dirname(__FILE__),'rw', "config.ini.php");
		$config = $GLOBALS['config'] ?? new Config_Lite($configDir, LOCK_EX);
		$apiToken = $_SESSION['apiToken'] ?? false;
		if (trim($apiToken)) {
			write_log("Updating session value and saving $key as $value", "INFO");
			$_SESSION[$key] = $value;
			$config->set($apiToken, $key, $value);
			saveConfig($config);
		} else {
			write_log("No session username, can't save value.");
		}
	}
}

// Sign in, get a token if we need it
if (!function_exists('verifyPlexToken')) {
	function verifyPlexToken($token) {
		$user = $userData = false;
		$url = "https://plex.tv/users/account?X-Plex-Token=$token";
		$result = curlGet($url);
		$data = $result ? flattenXML(new SimpleXMLElement($result)) : false;
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
			$user = fetchUser($userData);
			if (!$user) $user = newUser($userData);
		}


		if ($user) {
			$_SESSION['apiToken'] = $user['apiToken'];
			updateUserPreferenceArray($user);
		}
		return $user;
	}
}

if (!function_exists('verifyApiToken')) {
	function verifyApiToken($apiToken) {
		$caller = getCaller("verifyApiToken");
		if (trim($apiToken)) {
			$configDir = file_build_path(dirname(__FILE__),'rw', "config.ini.php");
			$config = $GLOBALS['config'] ?? new Config_Lite($configDir);
			foreach ($config as $token => $data) {
				if (trim($token) == trim($apiToken)) {
					$userData = [
						'plexUserName' => $data['plexUserName'],
						'plexEmail' => $data['plexEmail'],
						'plexAvatar' => $data['plexAvatar'],
						'plexPassUser' => $data['plexPassUser'] ? 1 : 0,
						'appLanguage' => $data['appLanguage'],
						'apiToken' => $apiToken
					];
					foreach ($userData as $key => $value) {
						$_SESSION[$key] = $value;
					}
					return $userData;
				}
			}
			write_log("ERROR, api token $apiToken not recognized, called by $caller.", "ERROR");

		} else {
			write_log("Invalid token specified.", "ERROR");
		}

		return false;
	}
}

function cleanCommandString($string) {
	$string = trim(strtolower($string));
	$string = preg_replace("/ask Flex TV/", "", $string);
	$string = preg_replace("/tell Flex TV/", "", $string);
	$string = preg_replace("/Flex TV/", "", $string);
	$stringArray = explode(" ", $string);
	$stripIn = ["th", "nd", "rd", "by"];
	$stringArray = array_diff($stringArray, array_intersect($stringArray, $stripIn));
	foreach ($stringArray as &$word) {
		$word = preg_replace("/[^\w\']+|\'(?!\w)|(?<!\w)\'/", "", $word);
	}
	$result = implode(" ", $stringArray);
	return $result;
}

function TTS($text) {
	$words = substr($text, 0, 2000);
	write_log("Building speech for '$words'");
	$payload = [
		"engine"=>"Google",
		"data"=>[
			"text"=>$text,
			"voice"=>"en-US"
		]
	];
	$url = "https://soundoftext.com/api/sounds";
	$ch = curl_init($url);
	curl_setopt_array($ch, array(
		CURLOPT_POST => TRUE,
		CURLOPT_RETURNTRANSFER => TRUE,
		CURLOPT_HTTPHEADER => array(
			'Content-Type: application/json'
		),
		CURLOPT_POSTFIELDS => json_encode($payload)
	));

	$mp3 = curl_exec($ch);
	$data = json_decode($mp3,true);
	write_log("Response: ".json_encode($data));
	if (isset($data['success'])) {
		if ($data['success']) {
			$id = $data['id'];
			$url = "https://soundoftext.com/api/sounds/$id";
			$data = curlGet($url, null, 10);
			if ($data) {
				$response = json_decode($data, true);
				return $response['location'] ?? false;
			}
		}
	}
	return false;
}


function flattenXML($xml) {
	libxml_use_internal_errors(true);
	$return = [];
	if (is_string($xml)) {
		try {
			$xml = new SimpleXMLElement($xml);
		} catch (Exception $e) {
			write_log("PARSE ERROR: $e","ERROR");
			return false;
		}
	}
	if (!($xml instanceof SimpleXMLElement)) {
		return false;
	}
	$_value = trim((string)$xml);
	if (strlen($_value) == 0) {
		$_value = null;
	};
	if ($_value !== null) {
		$return = $_value;
	}
	$children = [];
	$first = true;
	foreach ($xml->children() as $elementName => $child) {
		$value = flattenXML($child);
		if (isset($children[$elementName])) {
			if ($first) {
				$temp = $children[$elementName];
				unset($children[$elementName]);
				$children[$elementName][] = $temp;
				$first = false;
			}
			$children[$elementName][] = $value;
		} else {
			$children[$elementName] = $value;
		}
	}
	if (count($children) > 0) {
		$return = array_merge($return, $children);
	}
	$attributes = [];
	foreach ($xml->attributes() as $name => $value) {
		$attributes[$name] = trim($value);
	}
	if (count($attributes) > 0) {
		$return = array_merge($return, $attributes);
	}
	if (empty($return)) $return = false;
	return $return;
}


// Generate a random token using the first available PHP function
function randomToken($length = 32) {
	write_log("Function fired.");
	if (!isset($length) || intval($length) <= 8) {
		$length = 32;
	}
	if (function_exists('openssl_random_pseudo_bytes')) {
		write_log("Generating using pseudo_random.");
		return bin2hex(openssl_random_pseudo_bytes($length));
	}
	// Keep this last, as there appear to be issues with random_bytes and Docker.
	if (function_exists('random_bytes')) {
		write_log("Generating using random_bytes.");
		return bin2hex(random_bytes($length));
	}
	return false;
}

// Generate a timestamp and return it
function timeStamp() {
	return date(DATE_RFC2822, time());
}

// Recursively filter empty keys from an array
// Returns filtered array.
function array_filter_recursive(array $array, callable $callback = null) {
	$array = is_callable($callback) ? array_filter($array, $callback) : array_filter($array);
	foreach ($array as &$value) {
		if (is_array($value)) {
			$value = call_user_func(__FUNCTION__, $value, $callback);
		}
	}

	return $array;
}

//Get the current protocol of the server
function serverProtocol() {
	return (((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443) ? 'https://' : 'http://');
}

//Get the relative path to $to in relation to where $from is
function getRelativePath($from, $to) {
	// some compatibility fixes for Windows paths
	$from = is_dir($from) ? rtrim($from, '\/') . '/' : $from;
	$to = is_dir($to) ? rtrim($to, '\/') . '/' : $to;
	$from = str_replace('\\', '/', $from);
	$to = str_replace('\\', '/', $to);

	$from = explode('/', $from);
	$to = explode('/', $to);
	$relPath = $to;

	foreach ($from as $depth => $dir) {
		// find first non-matching dir
		if ($dir === $to[$depth]) {
			// ignore this directory
			array_shift($relPath);
		} else {
			// get number of remaining dirs to $from
			$remaining = count($from) - $depth;
			if ($remaining > 1) {
				// add traversals up to first matching dir
				$padLength = (count($relPath) + $remaining - 1) * -1;
				$relPath = array_pad($relPath, $padLength, '..');
				break;
			} else {
				$relPath[0] = '/' . $relPath[0];
			}
		}
	}
	return implode('/', $relPath);
}

#TODO: Make sure webapp always uses the right URL
// Grab an image from a server and save it locally
function cacheImage($url, $image = false) {
	write_log("Function fired, caching " . $url);
	$path = $url;
	$cached_filename = false;
	try {
		$URL_REF = $_SESSION['publicAddress'] ?? fetchUrl(false);
		$cacheDir = file_build_path(dirname(__FILE__), "img", "cache");
		checkCache($cacheDir);
		if ($url) {
			$cached_filename = md5($url);
			$files = glob($cacheDir . '/*.{jpg,jpeg,png,gif}', GLOB_BRACE);
			$now = time();
			foreach ($files as $file) {
				$fileName = explode('.', basename($file));
				if ($fileName[0] == $cached_filename) {
					write_log("File is already cached.");
					$path = $URL_REF . getRelativePath(dirname(__FILE__), $file);
				} else {
					if (is_file($file)) {
						if ($now - filemtime($file) >= 60 * 60 * 24 * 5) { // 5 days
							unlink($file);
						}
					}
				}
			}
		}
		if ($image) {
			$cached_filename = md5($image);
		}
		if ((($path == $url) || ($image)) && ($cached_filename)) {
			write_log("Caching file.");
			if (!$image) $image = file_get_contents($url);
			if ($image) {
				write_log("Image retrieved successfully!");
				$tempName = file_build_path($cacheDir, $cached_filename);
				file_put_contents($tempName, $image);
				$imageData = getimagesize($tempName);
				$extension = image_type_to_extension($imageData[2]);
				if ($extension) {
					write_log("Extension detected successfully!");
					$filenameOut = file_build_path($cacheDir, $cached_filename . $extension);
					$result = file_put_contents($filenameOut, $image);
					if ($result) {
						rename($tempName, $filenameOut);
						$path = $URL_REF . getRelativePath(dirname(__FILE__), $filenameOut);
						write_log("Success, returning cached URL: " . $path);
					}
				} else {
					unset($tempName);
				}
			}
		}
	} catch (\Exception $e) {
		write_log('Exception: ' . $e->getMessage());
	}
	return $path;
}

function checkCache($cacheDir) {
	if (!file_exists($cacheDir)) {
		write_log("No cache directory found, creating.", "INFO");
		mkdir($cacheDir, 0777, true);
	}
}

function setStartUrl() {
	$fileOut = dirname(__FILE__) . "/manifest.json";
	$file = (file_exists($fileOut)) ? $fileOut : dirname(__FILE__) . "/manifest_template.json";
	$json = json_decode(file_get_contents($file), true);
	$url = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
	$url = parse_url($url);
	$url = $url['scheme']."://". $url['host'] . $url['path'];
	$url = str_replace("\api.php","",$url);

	if ($json['start_url'] !== $url) {
		$json['start_url'] = $url;
		file_put_contents($fileOut, json_encode($json, JSON_PRETTY_PRINT));
	}
}

function transcodeImage($path, $uri = "", $token = "",$full=false) {
	if (preg_match("/library/", $path)) {
		if ($uri) $server = $uri;
		$server = $server ?? $_SESSION['plexServerPublicUri'] ?? $_SESSION['plexServerUri'] ?? false;
		if ($token) $serverToken = $token;
		$token = $serverToken ?? $_SESSION['plexServerToken'];
		$size = $full ? 'width=1920&height=1920' : 'width=600&height=600';
		if ($server && $token) {
			return $server . "/photo/:/transcode?$size&minSize=1&url=" . urlencode($path) . "%3FX-Plex-Token%3D" . $token . "&X-Plex-Token=" . $token;
		}
	}
	write_log("Invalid image path, returning generic image.", "WARN");
	$path = 'https://phlexchat.com/img/avatar.png';
	return $path;
}

// Check if string is present in an array
function arrayContains($str, array $arr) {
	//write_log("Function Fired.");
	$result = array_intersect($arr, explode(" ", $str));
	if (count($result) == 1) $result = true;
	if (count($result) == 0) $result = false;
	return $result;
}

function initMCurl() {
	return JMathai\PhpMultiCurl\MultiCurl::getInstance();
}

// TODO: Set a system variable for timeout
// Fetch data from a URL using CURL
function curlGet($url, $headers = null, $timeout = 4) {
	$cert = getContent(file_build_path(dirname(__FILE__),"rw", "cacert.pem"), 'https://curl.haxx.se/ca/cacert.pem');
	if (!$cert) $cert = file_build_path(dirname(__FILE__), "cert", "cacert.pem");
	write_log("GET url $url","INFO","curlGet");
	$url = filter_var($url, FILTER_SANITIZE_URL);
	if (!filter_var($url, FILTER_VALIDATE_URL)) {
		write_log("URL $url is not valid.");
		return false;
	}
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
	curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
	curl_setopt($ch, CURLOPT_CAINFO, $cert);
	if ($headers !== null) curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	$result = curl_exec($ch);
	if (!curl_errno($ch)) {
		switch ($http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE)) {
			case 200:
				break;
			default:
				write_log('Unexpected HTTP code: ' . $http_code . ', URL: ' . $url, "ERROR");
				$result = false;
		}
	}
	curl_close($ch);
	return $result;
}


function curlPost($url, $content = false, $JSON = false, Array $headers = null) {
    write_log("POST url $url","INFO","curlPost");
	$url = filter_var($url, FILTER_SANITIZE_URL);
	if (!filter_var($url, FILTER_VALIDATE_URL)) {
		write_log("URL $url is not valid.");
		return false;
	}

	$cert = getContent(file_build_path(dirname(__FILE__), "cacert.pem"), 'https://curl.haxx.se/ca/cacert.pem');
	if (!$cert) $cert = file_build_path(dirname(__FILE__), "cert", "cacert.pem");
	$curl = curl_init($url);
	curl_setopt($curl, CURLOPT_HEADER, false);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl, CURLOPT_CAINFO, $cert);
	curl_setopt($curl, CURLOPT_POST, true);
	curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 4);
	curl_setopt($curl, CURLOPT_TIMEOUT, 3);
	if ($headers) {
		if ($JSON) {
			$headers = array_merge($headers, ["Content-type: application/json"]);
		}

		curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
	} else {
		if ($JSON) {
			$headers = ["Content-type: application/json"];
			curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
		}
	}
	if ($content) curl_setopt($curl, CURLOPT_POSTFIELDS, $content);
	$response = curl_exec($curl);
    if (!curl_errno($curl)) {
        switch ($http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE)) {
            case 200:
                break;
            default:
                write_log('Unexpected HTTP code: ' . $http_code . ', URL: ' . $url, "ERROR","curlPost");
                $response = false;
        }
    }
    curl_close($curl);
	return $response;
}

function curl201($url, $content = false, $JSON = false, Array $headers = null) {
	write_log("URL $url");
	$cert = getContent(file_build_path(dirname(__FILE__), "cacert.pem"), 'https://curl.haxx.se/ca/cacert.pem');
	if (!$cert) $cert = file_build_path(dirname(__FILE__), "cert", "cacert.pem");
	$curl = curl_init($url);
	curl_setopt($curl, CURLOPT_HEADER, false);
	curl_setopt($curl, CURLOPT_CAINFO, $cert);
	curl_setopt($curl, CURLOPT_POST, true);
	curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 4);
	curl_setopt($curl, CURLOPT_TIMEOUT, 3);
	curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
	if ($headers) {
		if ($JSON) {
			$headers = array_merge($headers, ["Content-type: application/json"]);
		}
		curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

	} else {
		if ($JSON) {
			$headers = ["Content-type: application/json"];
			curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
		}
	}
	if ($content) {
		curl_setopt($curl, CURLOPT_POSTFIELDS, $content);
	}
	$response = curl_exec($curl);
	if (!curl_errno($curl)) {
		switch ($http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE)) {
			case 201:
				break;
			default:
				write_log('Unexpected HTTP code: ' . $http_code . ', URL: ' . $url, "ERROR");
				$response = false;
		}
	}
	curl_close($curl);
	return $response;
}

// Write log information to $filename
// Auto rotates files larger than 2MB
if (!function_exists('write_log')) {
function write_log($text, $level = false, $caller = false, $force=false) {
	$log = file_build_path(dirname(__FILE__), 'logs', "Phlex.log.php");
	if (!file_exists($log)) {
		touch($log);
		chmod($log, 0666);
		$authString = "; <?php die('Access denied'); ?>".PHP_EOL;
		file_put_contents($log,$authString);
	}
	if (!file_exists($log)) return;
	if (!$level) $level = 'DEBUG';
	if ((isset($_GET['pollPlayer']) && !$force) || (trim($text) === "")) return;
	$caller = $caller ? getCaller($caller) : getCaller();
	$text = '[' . date(DATE_RFC2822) . '] [' . $level . '] [' . $caller . "] - " . trim($text) . PHP_EOL;
	if (filesize($log) > 1048576) {
		$oldLog = file_build_path(dirname(__FILE__),'logs',"Phlex.log.php.old");
		if (file_exists($oldLog)) unlink($oldLog);
		rename($log, $oldLog);
		touch($log);
		chmod($log, 0666);
		$authString = "; <?php die('Access denied'); ?>".PHP_EOL;
		file_put_contents($log,$authString);
	}
	if (!is_writable($log)) return;
	if (!$handle = fopen($log, 'a+')) return;
	if (fwrite($handle, $text) === FALSE) return;
	fclose($handle);
}
}


// Get the name of the function calling write_log
function getCaller($custom = "foo") {
	$trace = debug_backtrace();
	$useNext = false;
	$caller = false;
	//write_log("TRACE: ".print_r($trace,true),null,true);
	foreach ($trace as $event) {
		if ($useNext) {
			if (($event['function'] != 'require') && ($event['function'] != 'include')) {
				$caller .= "::" . $event['function'];
				break;
			}
		}
		if (($event['function'] == 'write_log') || ($event['function'] == 'doRequest') || ($event['function'] == $custom)) {
			$useNext = true;
			// Set our caller as the calling file until we get a function
			$file = pathinfo($event['file']);
			$caller = $file['filename'] . "." . $file['extension'];
		}
	}
	return $caller;
}

// Save the specified configuration file using CONFIG_LITE
function saveConfig(Config_Lite $inConfig) {
	$configFile = file_build_path(dirname(__FILE__), "config.ini.php");
	if (!is_writable($configFile)) write_log("Configuration file is NOT writeable.","ERROR");
	try {
		$inConfig->save();
	} catch (Config_Lite_Exception $e) {
		$msg = $e->getMessage();
		write_log("Error saving configuration: $msg", 'ERROR');
	}
	$configFile = file_build_path(dirname(__FILE__), "config.ini.php");
	$cache_new = "'; <?php die('Access denied'); ?>"; // Adds this to the top of the config so that PHP kills the execution if someone tries to request the config-file remotely.
	if (file_exists($configFile)) {
		$cache_new .= file_get_contents($configFile);
	} else {
		$fh = fopen($configFile, 'w') or write_log("Can't create config file!","ERROR");
	}
	if (!file_put_contents($configFile, $cache_new)) write_log("Config save failed!", "ERROR");

}

function isDomainAvailible($domain) {
	//check, if a valid url is provided
	if (!filter_var($domain, FILTER_VALIDATE_URL)) {
		return false;
	}

	//initialize curl
	$curlInit = curl_init($domain);
	curl_setopt($curlInit, CURLOPT_CONNECTTIMEOUT, 10);
	curl_setopt($curlInit, CURLOPT_HEADER, true);
	curl_setopt($curlInit, CURLOPT_NOBODY, true);
	curl_setopt($curlInit, CURLOPT_RETURNTRANSFER, true);

	//get answer
	$response = curl_exec($curlInit);

	curl_close($curlInit);

	if ($response) return true;

	return false;
}




// This should take our command objects and save them to the JSON file
// read by the webUI.
if (!function_exists('logCommand')) {
function logCommand($resultObject) {
	if (isset($_GET['noLog'])) {
		write_log("UI command, not logging.");
		return;
	}

	$_SESSION['newCommand'][] = json_decode($resultObject,true);
	$newCommand = json_decode($resultObject, true);
	$newCommand['timecode'] = date_timestamp_get(new DateTime($newCommand['timestamp']));
	if (isset($_GET['say'])) echo json_encode($newCommand);
	// Check for our JSON file and make sure we can access it
	$filename = file_build_path(dirname(__FILE__),"rw","commands.php");
	$jsondata = fetchCommands();
	$json_a = json_decode($jsondata);
	if (empty($json_a)) $json_a = [];
	// Append our newest command to the beginning
	array_unshift($json_a, $newCommand);
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

if (!function_exists('popCommand')) {
	function popCommand($id) {
		write_log("Popping ID of " . $id);
		$filename = file_build_path(dirname(__FILE__),"rw","commands.php");
		// Check for our JSON file and make sure we can access it
		$contents = fetchCommands();
		// Read contents into an array
		$jsondata = $contents;
		$json_a = json_decode($jsondata, true);
		$json_b = [];
		foreach ($json_a as $command) {
			if (strtotime($command['timestamp']) !== strtotime($id)) {
				array_push($json_b, $command);
			}
		}
		// Triple-check we can write, write JSON to file
		if (!$handle = fopen($filename, 'wa+')) die;
		$cache_new = "'; <?php die('Access denied'); ?>" . PHP_EOL . json_encode($json_b, JSON_PRETTY_PRINT);
		if (fwrite($handle, $cache_new) === FALSE) return false;
		fclose($handle);
	}
}

if (!function_exists('fetchCommands')) {
	function fetchCommands() {
		$filename = file_build_path(dirname(__FILE__),"rw","commands.php");
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

function plexHeaders() {
	$name = deviceName();
	$headers = [
		"X-Plex-Product"=>$name,
		"X-Plex-Version"=>"1.1.0",
		"X-Plex-Client-Identifier"=>checkSetDeviceID(),
		"X-Plex-Platform"=>"Web",
		"X-Plex-Platform-Version"=>"1.0.0",
		"X-Plex-Sync-Version"=>"2",
		"X-Plex-Device"=>$name,
		"X-Plex-Device-Name"=>"Phlex",
		"X-Plex-Device-Screen-Resolution"=>"1920x1080",
		"X-Plex-Provider-Version"=>"1.1"
	];
	if (isset($_SESSION['plexServerToken'])) $headers["X-Plex-Token"]=$_SESSION['plexServerToken'];
	return $headers;
}

function clientHeaders() {
	return array_merge(plexHeaders(),[
		'X-Plex-Target-Client-Identifier' => $_SESSION['plexClientId']
	]);
}

function headerQuery($headers) {
	$string = "";
	foreach($headers as $key => $val) {
		$string.="&".urlencode($key)."=".urlencode($val);
	}
	return $string;
}

function headerHtml() {
	$string = "<div id='X-Plex-Data' class='hidden'";
	foreach(plexHeaders() as $key => $value) {
		$string .= " data-$key='$value'";
	}
	$string .="></div>";
	return $string;
}

function headerRequestArray($headers) {
	$headerArray = [];
	foreach ($headers as $key => $val) {
		$headerArray[] = "$key:$val";
	}
	return $headerArray;
}

function logUpdate(array $log) {
	$configDir = file_build_path(dirname(__FILE__),'rw', "config.ini.php");
	$config = $GLOBALS['config'] ?? new Config_Lite($configDir, LOCK_EX);
	$filename = file_build_path(dirname(__FILE__), 'logs', "Phlex_update.log.php");
	$data['installed'] = date(DATE_RFC2822);
	$data['commits'] = $log;
	$config->set("general", "lastUpdate", $data['installed']);
	saveConfig($config);
	unset($_SESSION['updateAvailable']);
	if (!file_exists($filename)) {
		touch($filename);
		chmod($filename, 0666);
	}
	if (filesize($filename) > 2 * 1024 * 1024) {
		$filename2 = "$filename.old";
		if (file_exists($filename2)) unlink($filename2);
		rename($filename, $filename2);
		touch($filename);
		chmod($filename, 0666);
	}
	if (!is_writable($filename)) die;
	$json = json_decode(file_get_contents($filename), true) ?? [];
	array_unshift($json, $data);
	file_put_contents($filename, json_encode($json));
}

function readUpdate() {
	$log = false;
	$filename = file_build_path(dirname(__FILE__), 'logs', "Phlex_update.log.php");
	if (file_exists($filename)) {
		$authString = "'; <?php die('Access denied'); ?>".PHP_EOL;
		$file = file_get_contents($filename);
		$file = str_replace($authString,"",$file);
		$log = json_decode($file, true) ?? [];
	}
	return $log;
}

# TODO: Add web setter for this to be false
function protectURL($string) {
	if ($_SESSION['cleanLogs']) {
		$keys = parse_url($string);
		$parts = explode(".", $keys['host']);
		if (count($parts) >= 2) {
			$i = 0;
			foreach ($parts as $part) {
				if ($i != 0) {
					$parts[$i] = str_repeat("X", strlen($part));
				}
				$i++;
			}
			$cleaned = implode(".", $parts);
		} else {
			$cleaned = str_repeat("X", strlen($keys['host']));
		}
		$string = str_replace($keys['host'], $cleaned, $string);

		$cleaned = str_repeat("X", strlen($keys['host']));
		$string = str_replace($keys['host'], $cleaned, $string);
		$pairs = [];
		if ($keys['query']) {
			parse_str($keys['query'], $pairs);
			foreach ($pairs as $key => $value) {
				if ((preg_match("/token/", $key)) || (preg_match("/Token/", $key))) {
					$cleaned = str_repeat("X", strlen($value));
					$string = str_replace($value, $cleaned, $string);
				}
				if (preg_match("/address/", $key)) {
					$parts = explode(".", $value);
					write_log("Parts: " . json_encode($parts));
					if (count($parts) >= 2) {
						$i = 0;
						foreach ($parts as &$part) {
							if ($i <= count($parts) - 1) {
								$part = str_repeat("X", strlen($part));
							}
							$i++;
						}
						$cleaned = implode(".", $parts);
					} else {
						$cleaned = str_repeat("X", strlen($value));
					}
					$string = str_replace($value, $cleaned, $string);
				}
			}
		}
	}
	return $string;
}

// # TODO: Replace this with the more thorough method compareTitles()
function similarity($str1, $str2) {
	return similar_text($str1,$str2);
}

function compareTitles(string $search, string $check,$sendWeight = false) {
	$goal = $_SESSION['searchAccuracy'] ?? 70;
	$strength = similar_text($search,$check);
	$lev = levenshtein($search,$check);
	$len = strlen($search) > strlen($check) ? strlen($search) : strlen($check);
	$similarity = 100-(($lev/$len) * 100);
	$heavy = ($strength >= $goal || $similarity >= $goal);
	$substring = (stripos($search,$check) !== false || stripos($check,$search) !== false);
	if ($heavy || $substring) {
		$str = (strlen($search) == $len) ? $search : $check;
		$weight = ($strength > $similarity) ? $strength : $similarity;
		write_log("This meets criteria: $str");
		return $sendWeight ? $weight : $str;
	}
	return false;
}

// Check if we have a running session before trying to start one
function session_started() {
	if (php_sapi_name() !== 'cli') {
		if (version_compare(phpversion(), '5.4.0', '>=')) {
			return session_status() === PHP_SESSION_ACTIVE ? TRUE : FALSE;
		} else {
			return session_id() === '' ? FALSE : TRUE;
		}
	}
	return FALSE;
}

// Check the validity of a URL response
function check_url($url, $post=false, $device=false) {
	if (!$device) write_log("Checking URL: " . $url); else write_log("Checking URL for device $device");
	$certPath = file_build_path(dirname(__FILE__), "cert", "cacert.pem");
	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
	curl_setopt($ch, CURLOPT_TIMEOUT, 2);
	curl_setopt($ch, CURLOPT_CAINFO, $certPath);
	if ($post) {
		write_log("Using POST in check_url, instead of GET");
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $_SESSION['plex_headers']);
	}
	/* Get the HTML or whatever is linked in $url. */
	curl_exec($ch);
	/* Check for 404 (file not found). */
	$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	curl_close($ch);

	/* If the document has loaded successfully without any redirection or error */
	if ($httpCode >= 200 && $httpCode < 300) {
		write_log("Connection is valid: " . $url);
		return true;
	} else {
		write_log("Connection failed with error code " . $httpCode . ": " . $url, "ERROR");
		return false;
	}
}


// Build a path with OS-agnostic separator
function file_build_path(...$segments) {
	return join(DIRECTORY_SEPARATOR, $segments);
}

function buildSpeech(...$segments) {
	return join(" ", $segments);
}

if (!function_exists('checkSetDeviceID')) {
	function checkSetDeviceID() {
		$configDir = file_build_path(dirname(__FILE__),'rw', "config.ini.php");
		$config = $GLOBALS['config'] ?? new Config_Lite($configDir, LOCK_EX);
		$deviceID = $config->get('general', 'deviceID', false);
		if (!$deviceID) {
			$deviceID = randomToken(12);
			$config->set("general", "deviceID", $deviceID);
			saveConfig($config);
		}
		return $deviceID;
	}
}

if (!function_exists("deviceName")) {
	function deviceName() {
		return "Phlex Home";
	}
}



function updateDeviceSelection($type, $id) {
	write_log("I was called by ".getCaller("updateDeviceSelection"));
	if ($id != 'rescan') {
		if ($type === "Parent") {
			updateUserPreference('plexMasterId',$id);
			return scanDevices();
		}
		write_log("New $type selected, ID is $id");
		$devices = scanDevices(false);
		write_log("Gonna loop over this array: ".json_encode($devices));
		write_log("And the type array: ".json_encode($devices[$type]));
		foreach ($devices[$type] as $device) {
			if ($device['Id'] === $id) {
				write_log("Got it.");
				$temp = [];
				unset($device['Selected']);
				foreach ($device as $key => $value) {
					if ((($key !== 'Parent') || ($type === "Client")) && ($key !== 'Master') && ($key !== 'Selected')) $temp["plex$type$key"] = $value;
				}
				updateUserPreferenceArray($temp);
			}
		}
		write_log("Session data: " . json_encode(sessionData()));
	} else {
		$devices = scanDevices(true);
	}
	return $devices;
}


function setSelectedDevice($type,$id) {
	write_log("Function fired.");
	$list = $_SESSION['deviceList'] ?? [];
	$selected = false;
	$current = $_SESSION['plex'.$type."Id"] ?? "000";
//	if ($current == $id) {
//		write_log("Skipping because device is already selected.");
//		return $list;
//	}

	foreach($list[$type] as $device) {
		write_log("Comparing $id to ".$device['Id']);
		if (trim($id) === trim($device['Id'])) {
			$selected = $device;
		}
	}

	if (!$selected && count($list[$type])) {
		write_log("Unable to find selected device in list, defaulting to first item.","WARN");
		$selected = $list[$type][0];
	}

	if (is_array($selected)) {
		$new = $push = [];
		foreach($list[$type] as $device) {
			$device['Selected'] = ($device['Id'] === $id) ? "yes" : "no";
			array_push($new, $device);
		}
		$list[$type] = $new;
		write_log("Going to select ". $selected['Name']);
		foreach ($selected as $key=>$value) {
			$uc = ucfirst($key);
			if (($type === 'Server' || $type==='Dvr') && ($uc === "Parent" || $uc === "Selected" || $uc === "Master")) {
				write_log("Skipping attributes.");
			} else {
				$itemKey = "plex$type$uc";
				$push[$itemKey] = $value;
			}
		}
		$_SESSION['deviceList'] = $list;
		$push['dlist'] = base64_encode(json_encode($list));
		updateUserPreferenceArray($push);
	}
	return $list;
}

function fetchDirectory($id = 1) {
	if ($id == 1) return base64_decode("Y2QyMjlmNTU5NWZjYWEyNzI3MGI0NDU4OTIyOGE0OTI=");
	if ($id == 2) return base64_decode("Njk0Nzg2RjBBMkVCNEUwOQ==");
	if ($id == 3) return base64_decode("NjU2NTRmODIwZDQ2NDdhYjljZjdlZGRkZGJiYTZlMDI=");
	if ($id == 4) return base64_decode("MTk1MDAz");
	return false;
}

if (!function_exists('fetchDeviceCache')) {
	function fetchDeviceCache() {
		$configDir = file_build_path(dirname(__FILE__),'rw', "config.ini.php");
		$config = $GLOBALS['config'] ?? new Config_Lite($configDir, LOCK_EX);
		$list = $config->get($_SESSION['apiToken'], 'dlist', []);
		$list = json_decode(base64_decode($list), true);
		return $list;
	}
}

if (!function_exists('setDefaults')) {
	function setDefaults() {
		$configDir = file_build_path(dirname(__FILE__),'rw', "config.ini.php");
		if (!isset($_SESSION['webApp'])) $GLOBALS['config'] = new Config_Lite($configDir, LOCK_EX);
		ini_set("log_errors", 1);
		ini_set('max_execution_time', 300);
		error_reporting(E_ERROR);
		$errorLogPath = file_build_path(dirname(__FILE__), 'logs', "Phlex_error.log.php");
		ini_set("error_log", $errorLogPath);
		date_default_timezone_set((date_default_timezone_get() ? date_default_timezone_get() : "America/Chicago"));
	}
}



	function clearSession() {
		write_log("Function fired");
		foreach($_SESSION as $key=>$val) {
			unset($_SESSION[$key]);
		}
		if (!session_started()) session_start();
		if (isset($_SERVER['HTTP_COOKIE'])) {
			write_log("Cookies found, unsetting.");
			$cookies = explode(';', $_SERVER['HTTP_COOKIE']);
			foreach ($cookies as $cookie) {
				$parts = explode('=', $cookie);
				$name = trim($parts[0]);
				write_log("Cookie: " . $name);
				setcookie($name, '', time() - 1000);
				setcookie($name, '', time() - 1000, '/');
			}
		}
		foreach($_SESSION as $key=>$val) {
			unset($_SESSION[$key]);
		}
		session_start();
		session_unset();
		$has_session = session_status() == PHP_SESSION_ACTIVE;
		if ($has_session) session_destroy();
		session_write_close();
		setcookie(session_name(), '', 0, '/');
		session_regenerate_id(true);
	}

	function cleanUri($url) {
		$parsed = parse_url($url);
		if ($parsed) {
			$parsed['scheme'] = $parsed['scheme'] ?? 'http';
			$created = http_build_url($parsed);
			$parsed = $created;
		}
		$parsed = rtrim($parsed,"/");
		write_log("Cleaned URI: $parsed");
		return $parsed;
	}

// Shamelessly stolen from https://davidwalsh.name/php-cache-function
// But slightly updated to do what I needed it to do.

	function getContent($file, $url, $hours = 56, $fn = '', $fn_args = '') {
		$current_time = time();
		$expire_time = $hours * 60 * 60;
		$file_time = filemtime($file);
		if (file_exists($file) && ($current_time - $expire_time < $file_time)) {
			return $file;
		} else {
			$content = getUrl($url);
			if ($content) {
				if ($fn) {
					$content = $fn($content, $fn_args);
				}
				$content .= '<!-- cached:  ' . time() . '-->';
				file_put_contents($file, $content);
				write_log('Retrieved fresh from ' . $url, "INFO");
				if (file_exists($file)) return $file;
			}
			return false;
		}
	}


	function toBool($var) {
		if (!is_string($var)) return $var;
		switch (strtolower($var)) {
			case 'true':
				return true;
			case 'false':
				return false;
			default:
				return $var;
		}
	}

	if (!function_exists("checkSSL")) {
		function checkSSL() {
			$forceSSL = false;
			$configDir = file_build_path(dirname(__FILE__),'rw', "config.ini.php");
			if (file_exists($configDir)) {
				$config = $GLOBALS['config'] ?? new Config_Lite($configDir);
				$forceSSL = $config->getBool('general', 'forceSsl', false);
			}
			return $forceSSL;
		}
	}

	function checkSetLanguage($locale = false) {
		$locale = $locale ? $locale : getDefaultLocale();

		listLocales();
		if (file_exists(dirname(__FILE__) . "/lang/" . $locale . ".json")) {
			$langJSON = file_get_contents(dirname(__FILE__) . "/lang/" . $locale . ".json");
		} else {
			write_log("Couldn't find the selected locale, defaulting to 'Murica.");
			$langJSON = file_get_contents(dirname(__FILE__) . "/lang/en.json");
		}
		// This gets added automagically, ignore IDE warnings about it...
		return json_decode($langJSON, true);
	}

	function listLocales() {
		$dir = file_build_path(dirname(__FILE__), "lang");
		$list = "";
		if (is_dir($dir)) {
			if ($dh = opendir($dir)) {
				while (($file = readdir($dh)) !== false) {
					$name = trim(str_replace(".", "", trim($file)));
					if ($name) {
						$locale = str_replace("json", "", $name);
						$localeName = localeName($locale);
						$json = file_get_contents(file_build_path($dir, $file));
						$json = json_decode($json, true);
						if ($json) {
							$selected = ($_SESSION["appLanguage"] == $locale ? 'selected' : '');
							$list .= "<option data-value='$locale' id='$locale' $selected>$localeName</option>" . PHP_EOL;
						}
					}
				}
				closedir($dh);
			}
		}
		return $list;
	}


	function getDefaultLocale() {
		$locale = false;
		$defaultLocale = setlocale(LC_ALL, 0);
		// If a session language is set
		if (isset($_SESSION['appLanguage'])) {
			$locale = $_SESSION['appLanguage'];
		}
		if (!$locale) {
			write_log("No saved locale set, detecting from system.", "INFO");
			if (trim($defaultLocale) != "") {
				if (preg_match("/en_/", $defaultLocale)) $locale = 'en';
				if (preg_match("/fr_/", $defaultLocale)) $locale = 'fr';
				write_log("Locale set from default: $locale", "INFO");
				if (trim($locale) == "") $locale = false;
			}
		}
		if (!$locale) {
			write_log("Couldn't detect a default or saved locale, defaulting to English.", "WARN");
			$locale = "en";
		}
		return $locale;
	}

	function localeName($locale = "en") {
		if (function_exists("locale_get_display_region")) {
			return ucfirst(locale_get_display_name($locale, $locale));
		} else switch ($locale) {
			case "af_NA":
				return "Afrikaans (Namibia)";
			case "af_ZA":
				return "Afrikaans (South Africa)";
			case "af":
				return "Afrikaans";
			case "ak_GH":
				return "Akan (Ghana)";
			case "ak":
				return "Akan";
			case "sq_AL":
				return "Albanian (Albania)";
			case "sq":
				return "Albanian";
			case "am_ET":
				return "Amharic (Ethiopia)";
			case "am":
				return "Amharic";
			case "ar_DZ":
				return "Arabic (Algeria)";
			case "ar_BH":
				return "Arabic (Bahrain)";
			case "ar_EG":
				return "Arabic (Egypt)";
			case "ar_IQ":
				return "Arabic (Iraq)";
			case "ar_JO":
				return "Arabic (Jordan)";
			case "ar_KW":
				return "Arabic (Kuwait)";
			case "ar_LB":
				return "Arabic (Lebanon)";
			case "ar_LY":
				return "Arabic (Libya)";
			case "ar_MA":
				return "Arabic (Morocco)";
			case "ar_OM":
				return "Arabic (Oman)";
			case "ar_QA":
				return "Arabic (Qatar)";
			case "ar_SA":
				return "Arabic (Saudi Arabia)";
			case "ar_SD":
				return "Arabic (Sudan)";
			case "ar_SY":
				return "Arabic (Syria)";
			case "ar_TN":
				return "Arabic (Tunisia)";
			case "ar_AE":
				return "Arabic (United Arab Emirates)";
			case "ar_YE":
				return "Arabic (Yemen)";
			case "ar":
				return "Arabic";
			case "hy_AM":
				return "Armenian (Armenia)";
			case "hy":
				return "Armenian";
			case "as_IN":
				return "Assamese (India)";
			case "as":
				return "Assamese";
			case "asa_TZ":
				return "Asu (Tanzania)";
			case "asa":
				return "Asu";
			case "az_Cyrl":
				return "Azerbaijani (Cyrillic)";
			case "az_Cyrl_AZ":
				return "Azerbaijani (Cyrillic, Azerbaijan)";
			case "az_Latn":
				return "Azerbaijani (Latin)";
			case "az_Latn_AZ":
				return "Azerbaijani (Latin, Azerbaijan)";
			case "az":
				return "Azerbaijani";
			case "bm_ML":
				return "Bambara (Mali)";
			case "bm":
				return "Bambara";
			case "eu_ES":
				return "Basque (Spain)";
			case "eu":
				return "Basque";
			case "be_BY":
				return "Belarusian (Belarus)";
			case "be":
				return "Belarusian";
			case "bem_ZM":
				return "Bemba (Zambia)";
			case "bem":
				return "Bemba";
			case "bez_TZ":
				return "Bena (Tanzania)";
			case "bez":
				return "Bena";
			case "bn_BD":
				return "Bengali (Bangladesh)";
			case "bn_IN":
				return "Bengali (India)";
			case "bn":
				return "Bengali";
			case "bs_BA":
				return "Bosnian (Bosnia and Herzegovina)";
			case "bs":
				return "Bosnian";
			case "bg_BG":
				return "Bulgarian (Bulgaria)";
			case "bg":
				return "Bulgarian";
			case "my_MM":
				return "Burmese (Myanmar [Burma])";
			case "my":
				return "Burmese";
			case "ca_ES":
				return "Catalan (Spain)";
			case "ca":
				return "Catalan";
			case "tzm_Latn":
				return "Central Morocco Tamazight (Latin)";
			case "tzm_Latn_MA":
				return "Central Morocco Tamazight (Latin, Morocco)";
			case "tzm":
				return "Central Morocco Tamazight";
			case "chr_US":
				return "Cherokee (United States)";
			case "chr":
				return "Cherokee";
			case "cgg_UG":
				return "Chiga (Uganda)";
			case "cgg":
				return "Chiga";
			case "zh_Hans":
				return "Chinese (Simplified Han)";
			case "zh_Hans_CN":
				return "Chinese (Simplified Han, China)";
			case "zh_Hans_HK":
				return "Chinese (Simplified Han, Hong Kong SAR China)";
			case "zh_Hans_MO":
				return "Chinese (Simplified Han, Macau SAR China)";
			case "zh_Hans_SG":
				return "Chinese (Simplified Han, Singapore)";
			case "zh_Hant":
				return "Chinese (Traditional Han)";
			case "zh_Hant_HK":
				return "Chinese (Traditional Han, Hong Kong SAR China)";
			case "zh_Hant_MO":
				return "Chinese (Traditional Han, Macau SAR China)";
			case "zh_Hant_TW":
				return "Chinese (Traditional Han, Taiwan)";
			case "zh":
				return "Chinese";
			case "kw_GB":
				return "Cornish (United Kingdom)";
			case "kw":
				return "Cornish";
			case "hr_HR":
				return "Croatian (Croatia)";
			case "hr":
				return "Croatian";
			case "cs_CZ":
				return "Czech (Czech Republic)";
			case "cs":
				return "Czech";
			case "da_DK":
				return "Danish (Denmark)";
			case "da":
				return "Danish";
			case "nl_BE":
				return "Dutch (Belgium)";
			case "nl_NL":
				return "Dutch (Netherlands)";
			case "nl":
				return "Dutch";
			case "ebu_KE":
				return "Embu (Kenya)";
			case "ebu":
				return "Embu";
			case "en_AS":
				return "English (American Samoa)";
			case "en_AU":
				return "English (Australia)";
			case "en_BE":
				return "English (Belgium)";
			case "en_BZ":
				return "English (Belize)";
			case "en_BW":
				return "English (Botswana)";
			case "en_CA":
				return "English (Canada)";
			case "en_GU":
				return "English (Guam)";
			case "en_HK":
				return "English (Hong Kong SAR China)";
			case "en_IN":
				return "English (India)";
			case "en_IE":
				return "English (Ireland)";
			case "en_JM":
				return "English (Jamaica)";
			case "en_MT":
				return "English (Malta)";
			case "en_MH":
				return "English (Marshall Islands)";
			case "en_MU":
				return "English (Mauritius)";
			case "en_NA":
				return "English (Namibia)";
			case "en_NZ":
				return "English (New Zealand)";
			case "en_MP":
				return "English (Northern Mariana Islands)";
			case "en_PK":
				return "English (Pakistan)";
			case "en_PH":
				return "English (Philippines)";
			case "en_SG":
				return "English (Singapore)";
			case "en_ZA":
				return "English (South Africa)";
			case "en_TT":
				return "English (Trinidad and Tobago)";
			case "en_UM":
				return "English (U.S. Minor Outlying Islands)";
			case "en_VI":
				return "English (U.S. Virgin Islands)";
			case "en_GB":
				return "English (United Kingdom)";
			case "en_US":
				return "English (United States)";
			case "en_ZW":
				return "English (Zimbabwe)";
			case "en":
				return "English";
			case "eo":
				return "Esperanto";
			case "et_EE":
				return "Estonian (Estonia)";
			case "et":
				return "Estonian";
			case "ee_GH":
				return "Ewe (Ghana)";
			case "ee_TG":
				return "Ewe (Togo)";
			case "ee":
				return "Ewe";
			case "fo_FO":
				return "Faroese (Faroe Islands)";
			case "fo":
				return "Faroese";
			case "fil_PH":
				return "Filipino (Philippines)";
			case "fil":
				return "Filipino";
			case "fi_FI":
				return "Finnish (Finland)";
			case "fi":
				return "Finnish";
			case "fr_BE":
				return "French (Belgium)";
			case "fr_BJ":
				return "French (Benin)";
			case "fr_BF":
				return "French (Burkina Faso)";
			case "fr_BI":
				return "French (Burundi)";
			case "fr_CM":
				return "French (Cameroon)";
			case "fr_CA":
				return "French (Canada)";
			case "fr_CF":
				return "French (Central African Republic)";
			case "fr_TD":
				return "French (Chad)";
			case "fr_KM":
				return "French (Comoros)";
			case "fr_CG":
				return "French (Congo - Brazzaville)";
			case "fr_CD":
				return "French (Congo - Kinshasa)";
			case "fr_CI":
				return "French (Côte d’Ivoire)";
			case "fr_DJ":
				return "French (Djibouti)";
			case "fr_GQ":
				return "French (Equatorial Guinea)";
			case "fr_FR":
				return "French (France)";
			case "fr_GA":
				return "French (Gabon)";
			case "fr_GP":
				return "French (Guadeloupe)";
			case "fr_GN":
				return "French (Guinea)";
			case "fr_LU":
				return "French (Luxembourg)";
			case "fr_MG":
				return "French (Madagascar)";
			case "fr_ML":
				return "French (Mali)";
			case "fr_MQ":
				return "French (Martinique)";
			case "fr_MC":
				return "French (Monaco)";
			case "fr_NE":
				return "French (Niger)";
			case "fr_RW":
				return "French (Rwanda)";
			case "fr_RE":
				return "French (Réunion)";
			case "fr_BL":
				return "French (Saint Barthélemy)";
			case "fr_MF":
				return "French (Saint Martin)";
			case "fr_SN":
				return "French (Senegal)";
			case "fr_CH":
				return "French (Switzerland)";
			case "fr_TG":
				return "French (Togo)";
			case "fr":
				return "French";
			case "ff_SN":
				return "Fulah (Senegal)";
			case "ff":
				return "Fulah";
			case "gl_ES":
				return "Galician (Spain)";
			case "gl":
				return "Galician";
			case "lg_UG":
				return "Ganda (Uganda)";
			case "lg":
				return "Ganda";
			case "ka_GE":
				return "Georgian (Georgia)";
			case "ka":
				return "Georgian";
			case "de_AT":
				return "German (Austria)";
			case "de_BE":
				return "German (Belgium)";
			case "de_DE":
				return "German (Germany)";
			case "de_LI":
				return "German (Liechtenstein)";
			case "de_LU":
				return "German (Luxembourg)";
			case "de_CH":
				return "German (Switzerland)";
			case "de":
				return "German";
			case "el_CY":
				return "Greek (Cyprus)";
			case "el_GR":
				return "Greek (Greece)";
			case "el":
				return "Greek";
			case "gu_IN":
				return "Gujarati (India)";
			case "gu":
				return "Gujarati";
			case "guz_KE":
				return "Gusii (Kenya)";
			case "guz":
				return "Gusii";
			case "ha_Latn":
				return "Hausa (Latin)";
			case "ha_Latn_GH":
				return "Hausa (Latin, Ghana)";
			case "ha_Latn_NE":
				return "Hausa (Latin, Niger)";
			case "ha_Latn_NG":
				return "Hausa (Latin, Nigeria)";
			case "ha":
				return "Hausa";
			case "haw_US":
				return "Hawaiian (United States)";
			case "haw":
				return "Hawaiian";
			case "he_IL":
				return "Hebrew (Israel)";
			case "he":
				return "Hebrew";
			case "hi_IN":
				return "Hindi (India)";
			case "hi":
				return "Hindi";
			case "hu_HU":
				return "Hungarian (Hungary)";
			case "hu":
				return "Hungarian";
			case "is_IS":
				return "Icelandic (Iceland)";
			case "is":
				return "Icelandic";
			case "ig_NG":
				return "Igbo (Nigeria)";
			case "ig":
				return "Igbo";
			case "id_ID":
				return "Indonesian (Indonesia)";
			case "id":
				return "Indonesian";
			case "ga_IE":
				return "Irish (Ireland)";
			case "ga":
				return "Irish";
			case "it_IT":
				return "Italian (Italy)";
			case "it_CH":
				return "Italian (Switzerland)";
			case "it":
				return "Italian";
			case "ja_JP":
				return "Japanese (Japan)";
			case "ja":
				return "Japanese";
			case "kea_CV":
				return "Kabuverdianu (Cape Verde)";
			case "kea":
				return "Kabuverdianu";
			case "kab_DZ":
				return "Kabyle (Algeria)";
			case "kab":
				return "Kabyle";
			case "kl_GL":
				return "Kalaallisut (Greenland)";
			case "kl":
				return "Kalaallisut";
			case "kln_KE":
				return "Kalenjin (Kenya)";
			case "kln":
				return "Kalenjin";
			case "kam_KE":
				return "Kamba (Kenya)";
			case "kam":
				return "Kamba";
			case "kn_IN":
				return "Kannada (India)";
			case "kn":
				return "Kannada";
			case "kk_Cyrl":
				return "Kazakh (Cyrillic)";
			case "kk_Cyrl_KZ":
				return "Kazakh (Cyrillic, Kazakhstan)";
			case "kk":
				return "Kazakh";
			case "km_KH":
				return "Khmer (Cambodia)";
			case "km":
				return "Khmer";
			case "ki_KE":
				return "Kikuyu (Kenya)";
			case "ki":
				return "Kikuyu";
			case "rw_RW":
				return "Kinyarwanda (Rwanda)";
			case "rw":
				return "Kinyarwanda";
			case "kok_IN":
				return "Konkani (India)";
			case "kok":
				return "Konkani";
			case "ko_KR":
				return "Korean (South Korea)";
			case "ko":
				return "Korean";
			case "khq_ML":
				return "Koyra Chiini (Mali)";
			case "khq":
				return "Koyra Chiini";
			case "ses_ML":
				return "Koyraboro Senni (Mali)";
			case "ses":
				return "Koyraboro Senni";
			case "lag_TZ":
				return "Langi (Tanzania)";
			case "lag":
				return "Langi";
			case "lv_LV":
				return "Latvian (Latvia)";
			case "lv":
				return "Latvian";
			case "lt_LT":
				return "Lithuanian (Lithuania)";
			case "lt":
				return "Lithuanian";
			case "luo_KE":
				return "Luo (Kenya)";
			case "luo":
				return "Luo";
			case "luy_KE":
				return "Luyia (Kenya)";
			case "luy":
				return "Luyia";
			case "mk_MK":
				return "Macedonian (Macedonia)";
			case "mk":
				return "Macedonian";
			case "jmc_TZ":
				return "Machame (Tanzania)";
			case "jmc":
				return "Machame";
			case "kde_TZ":
				return "Makonde (Tanzania)";
			case "kde":
				return "Makonde";
			case "mg_MG":
				return "Malagasy (Madagascar)";
			case "mg":
				return "Malagasy";
			case "ms_BN":
				return "Malay (Brunei)";
			case "ms_MY":
				return "Malay (Malaysia)";
			case "ms":
				return "Malay";
			case "ml_IN":
				return "Malayalam (India)";
			case "ml":
				return "Malayalam";
			case "mt_MT":
				return "Maltese (Malta)";
			case "mt":
				return "Maltese";
			case "gv_GB":
				return "Manx (United Kingdom)";
			case "gv":
				return "Manx";
			case "mr_IN":
				return "Marathi (India)";
			case "mr":
				return "Marathi";
			case "mas_KE":
				return "Masai (Kenya)";
			case "mas_TZ":
				return "Masai (Tanzania)";
			case "mas":
				return "Masai";
			case "mer_KE":
				return "Meru (Kenya)";
			case "mer":
				return "Meru";
			case "mfe_MU":
				return "Morisyen (Mauritius)";
			case "mfe":
				return "Morisyen";
			case "naq_NA":
				return "Nama (Namibia)";
			case "naq":
				return "Nama";
			case "ne_IN":
				return "Nepali (India)";
			case "ne_NP":
				return "Nepali (Nepal)";
			case "ne":
				return "Nepali";
			case "nd_ZW":
				return "North Ndebele (Zimbabwe)";
			case "nd":
				return "North Ndebele";
			case "nb_NO":
				return "Norwegian Bokmål (Norway)";
			case "nb":
				return "Norwegian Bokmål";
			case "nn_NO":
				return "Norwegian Nynorsk (Norway)";
			case "nn":
				return "Norwegian Nynorsk";
			case "nyn_UG":
				return "Nyankole (Uganda)";
			case "nyn":
				return "Nyankole";
			case "or_IN":
				return "Oriya (India)";
			case "or":
				return "Oriya";
			case "om_ET":
				return "Oromo (Ethiopia)";
			case "om_KE":
				return "Oromo (Kenya)";
			case "om":
				return "Oromo";
			case "ps_AF":
				return "Pashto (Afghanistan)";
			case "ps":
				return "Pashto";
			case "fa_AF":
				return "Persian (Afghanistan)";
			case "fa_IR":
				return "Persian (Iran)";
			case "fa":
				return "Persian";
			case "pl_PL":
				return "Polish (Poland)";
			case "pl":
				return "Polish";
			case "pt_BR":
				return "Portuguese (Brazil)";
			case "pt_GW":
				return "Portuguese (Guinea-Bissau)";
			case "pt_MZ":
				return "Portuguese (Mozambique)";
			case "pt_PT":
				return "Portuguese (Portugal)";
			case "pt":
				return "Portuguese";
			case "pa_Arab":
				return "Punjabi (Arabic)";
			case "pa_Arab_PK":
				return "Punjabi (Arabic, Pakistan)";
			case "pa_Guru":
				return "Punjabi (Gurmukhi)";
			case "pa_Guru_IN":
				return "Punjabi (Gurmukhi, India)";
			case "pa":
				return "Punjabi";
			case "ro_MD":
				return "Romanian (Moldova)";
			case "ro_RO":
				return "Romanian (Romania)";
			case "ro":
				return "Romanian";
			case "rm_CH":
				return "Romansh (Switzerland)";
			case "rm":
				return "Romansh";
			case "rof_TZ":
				return "Rombo (Tanzania)";
			case "rof":
				return "Rombo";
			case "ru_MD":
				return "Russian (Moldova)";
			case "ru_RU":
				return "Russian (Russia)";
			case "ru_UA":
				return "Russian (Ukraine)";
			case "ru":
				return "Russian";
			case "rwk_TZ":
				return "Rwa (Tanzania)";
			case "rwk":
				return "Rwa";
			case "saq_KE":
				return "Samburu (Kenya)";
			case "saq":
				return "Samburu";
			case "sg_CF":
				return "Sango (Central African Republic)";
			case "sg":
				return "Sango";
			case "seh_MZ":
				return "Sena (Mozambique)";
			case "seh":
				return "Sena";
			case "sr_Cyrl":
				return "Serbian (Cyrillic)";
			case "sr_Cyrl_BA":
				return "Serbian (Cyrillic, Bosnia and Herzegovina)";
			case "sr_Cyrl_ME":
				return "Serbian (Cyrillic, Montenegro)";
			case "sr_Cyrl_RS":
				return "Serbian (Cyrillic, Serbia)";
			case "sr_Latn":
				return "Serbian (Latin)";
			case "sr_Latn_BA":
				return "Serbian (Latin, Bosnia and Herzegovina)";
			case "sr_Latn_ME":
				return "Serbian (Latin, Montenegro)";
			case "sr_Latn_RS":
				return "Serbian (Latin, Serbia)";
			case "sr":
				return "Serbian";
			case "sn_ZW":
				return "Shona (Zimbabwe)";
			case "sn":
				return "Shona";
			case "ii_CN":
				return "Sichuan Yi (China)";
			case "ii":
				return "Sichuan Yi";
			case "si_LK":
				return "Sinhala (Sri Lanka)";
			case "si":
				return "Sinhala";
			case "sk_SK":
				return "Slovak (Slovakia)";
			case "sk":
				return "Slovak";
			case "sl_SI":
				return "Slovenian (Slovenia)";
			case "sl":
				return "Slovenian";
			case "xog_UG":
				return "Soga (Uganda)";
			case "xog":
				return "Soga";
			case "so_DJ":
				return "Somali (Djibouti)";
			case "so_ET":
				return "Somali (Ethiopia)";
			case "so_KE":
				return "Somali (Kenya)";
			case "so_SO":
				return "Somali (Somalia)";
			case "so":
				return "Somali";
			case "es_AR":
				return "Spanish (Argentina)";
			case "es_BO":
				return "Spanish (Bolivia)";
			case "es_CL":
				return "Spanish (Chile)";
			case "es_CO":
				return "Spanish (Colombia)";
			case "es_CR":
				return "Spanish (Costa Rica)";
			case "es_DO":
				return "Spanish (Dominican Republic)";
			case "es_EC":
				return "Spanish (Ecuador)";
			case "es_SV":
				return "Spanish (El Salvador)";
			case "es_GQ":
				return "Spanish (Equatorial Guinea)";
			case "es_GT":
				return "Spanish (Guatemala)";
			case "es_HN":
				return "Spanish (Honduras)";
			case "es_419":
				return "Spanish (Latin America)";
			case "es_MX":
				return "Spanish (Mexico)";
			case "es_NI":
				return "Spanish (Nicaragua)";
			case "es_PA":
				return "Spanish (Panama)";
			case "es_PY":
				return "Spanish (Paraguay)";
			case "es_PE":
				return "Spanish (Peru)";
			case "es_PR":
				return "Spanish (Puerto Rico)";
			case "es_ES":
				return "Spanish (Spain)";
			case "es_US":
				return "Spanish (United States)";
			case "es_UY":
				return "Spanish (Uruguay)";
			case "es_VE":
				return "Spanish (Venezuela)";
			case "es":
				return "Spanish";
			case "sw_KE":
				return "Swahili (Kenya)";
			case "sw_TZ":
				return "Swahili (Tanzania)";
			case "sw":
				return "Swahili";
			case "sv_FI":
				return "Swedish (Finland)";
			case "sv_SE":
				return "Swedish (Sweden)";
			case "sv":
				return "Swedish";
			case "gsw_CH":
				return "Swiss German (Switzerland)";
			case "gsw":
				return "Swiss German";
			case "shi_Latn":
				return "Tachelhit (Latin)";
			case "shi_Latn_MA":
				return "Tachelhit (Latin, Morocco)";
			case "shi_Tfng":
				return "Tachelhit (Tifinagh)";
			case "shi_Tfng_MA":
				return "Tachelhit (Tifinagh, Morocco)";
			case "shi":
				return "Tachelhit";
			case "dav_KE":
				return "Taita (Kenya)";
			case "dav":
				return "Taita";
			case "ta_IN":
				return "Tamil (India)";
			case "ta_LK":
				return "Tamil (Sri Lanka)";
			case "ta":
				return "Tamil";
			case "te_IN":
				return "Telugu (India)";
			case "te":
				return "Telugu";
			case "teo_KE":
				return "Teso (Kenya)";
			case "teo_UG":
				return "Teso (Uganda)";
			case "teo":
				return "Teso";
			case "th_TH":
				return "Thai (Thailand)";
			case "th":
				return "Thai";
			case "bo_CN":
				return "Tibetan (China)";
			case "bo_IN":
				return "Tibetan (India)";
			case "bo":
				return "Tibetan";
			case "ti_ER":
				return "Tigrinya (Eritrea)";
			case "ti_ET":
				return "Tigrinya (Ethiopia)";
			case "ti":
				return "Tigrinya";
			case "to_TO":
				return "Tonga (Tonga)";
			case "to":
				return "Tonga";
			case "tr_TR":
				return "Turkish (Turkey)";
			case "tr":
				return "Turkish";
			case "uk_UA":
				return "Ukrainian (Ukraine)";
			case "uk":
				return "Ukrainian";
			case "ur_IN":
				return "Urdu (India)";
			case "ur_PK":
				return "Urdu (Pakistan)";
			case "ur":
				return "Urdu";
			case "uz_Arab":
				return "Uzbek (Arabic)";
			case "uz_Arab_AF":
				return "Uzbek (Arabic, Afghanistan)";
			case "uz_Cyrl":
				return "Uzbek (Cyrillic)";
			case "uz_Cyrl_UZ":
				return "Uzbek (Cyrillic, Uzbekistan)";
			case "uz_Latn":
				return "Uzbek (Latin)";
			case "uz_Latn_UZ":
				return "Uzbek (Latin, Uzbekistan)";
			case "uz":
				return "Uzbek";
			case "vi_VN":
				return "Vietnamese (Vietnam)";
			case "vi":
				return "Vietnamese";
			case "vun_TZ":
				return "Vunjo (Tanzania)";
			case "vun":
				return "Vunjo";
			case "cy_GB":
				return "Welsh (United Kingdom)";
			case "cy":
				return "Welsh";
			case "yo_NG":
				return "Yoruba (Nigeria)";
			case "yo":
				return "Yoruba";
			case "zu_ZA":
				return "Zulu (South Africa)";
			case "zu":
				return "Zulu";
		}
		return $locale;
	}

	function hasGzip() {
		return (function_exists('ob_gzhandler') && ini_get('zlib.output_compression'));
	}


	function backupConfig() {
		write_log("Function fired!!");
		$configDir = file_build_path(dirname(__FILE__),'rw', "config.ini.php");
		$newFile = file_build_path($configDir . time() . ".bk");
		write_log("Backing up configuration file to $newFile.", "INFO");
		if (!copy($configDir, $newFile)) {
			write_log("Failed to back up configuration file!", "ERROR");
			return false;
		} else write_log("Configuration backup successful.", "INFO");
		return true;
	}

	function parseLog($log) {
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

	function checkGit() {
		exec("git", $lines);
		return ((preg_match("/git help/", implode(" ", $lines))) && (file_exists(dirname(__FILE__) . '/.git')));
	}

	/* gets content from a URL via curl */
	function getUrl($url) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_TIMEOUT, 2);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
		$content = curl_exec($ch);
		if (!curl_errno($ch)) {
			switch ($http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE)) {
				case 200:  # OK
					break;
				default:
					write_log('Unexpected HTTP code: ' . $http_code . ', URL: ' . $url, "ERROR");
					$content = false;
			}
		}
		curl_close($ch);
		return $content;
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

	if (!function_exists("formatLog")) {
		function formatLog($logData) {
			$authString = "'; <?php die('Access denied'); ?>" . PHP_EOL;
			$logData = str_replace($authString, "", $logData);
			$lines = array_reverse(explode("\n", $logData));
			$JSON = false;
			$records = [];
			unset($_GET['pollPlayer']);
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


	function doRequest($parts, $timeout = 6) {
		$type = isset($parts['type']) ? $parts['type'] : 'get';
		$response = false;
		$options = [];
		$cert = getContent(file_build_path(dirname(__FILE__),'rw', "cacert.pem"), 'https://curl.haxx.se/ca/cacert.pem');
		if (!$cert) $cert = file_build_path(dirname(__FILE__), "cert", "cacert.pem");
		if (is_array($parts)) {
			if (!isset($parts['uri'])) $parts['uri'] = $_SESSION['plexServerUri'];
			if (isset($parts['query'])) {
				if (!is_string($parts['query'])) {
					$string = '?';
					$i = 0;
					foreach ($parts['query'] as $key => $value) {
						if (!is_array($value)) {
							if ($i > 0) $string .= '&';
							$string .= $key . '=' . $value;
							$i++;
						} else {
							foreach ($value as $subkey => $subval) {
								if ($i > 0) $string .= '&';
								$string .= $subkey . '=' . urlencode($subval);
								$i++;
							}
						}
					}
					$parts['query'] = $string;
				}
			}

			$parts = array_merge(parse_url($parts['uri']), $parts);

			$url = (isset($parts['scheme']) ? "{$parts['scheme']}:" : '') . ((isset($parts['user']) || isset($parts['host'])) ? '//' : '') . (isset($parts['user']) ? "{$parts['user']}" : '') . (isset($parts['pass']) ? ":{$parts['pass']}" : '') . (isset($parts['user']) ? '@' : '') . (isset($parts['host']) ? "{$parts['host']}" : '') . (isset($parts['port']) ? ":{$parts['port']}" : '') . (isset($parts['path']) ? "{$parts['path']}" : '') . (isset($parts['query']) ? "{$parts['query']}" : '') . (isset($parts['fragment']) ? "#{$parts['fragment']}" : '');
		} else {
			$url = $parts;
		}
		$url = filter_var($url, FILTER_SANITIZE_URL);
		if (!filter_var($url, FILTER_VALIDATE_URL)) {
			//write_log("URL $url is not valid.");
			return false;
		}
		write_log("URL is " . protectURL($url), "INFO", getCaller());

		$client = new GuzzleHttp\Client([
			'timeout' => $timeout,
			'verify' => $cert
		]);

		try {
			if ($type == 'get') {
				$response = $client->get($url, $options);
			}

			if ($type == 'post') {
				if (isset($parts['headers'])) $options['headers'] = $parts['headers'];
				$response = $client->post($url, $options);
			}
		} catch (Throwable $e) {
			write_log("An exception occurred: " . $e->getMessage(), "ERROR");
			if ($e->getCode() == 401) {
				return false;
			}
		}
		if ($response) {
			$code = $response->getStatusCode();
			if ($code == 200) {
				return $response->getBody()->getContents();
			} else {
				write_log("An error has occurred: " . $response->getReasonPhrase(), "ERROR");
				return false;
			}
		} else write_log("Error getting response from URL fetch.", "ERROR");
		return false;
	}

	function fetchUrl($https = false) {
		if (isset($_SESSION['webApp'])) return $_SESSION['appAddress'];
		if ($https) $protocol = 'https://'; else {
			$protocol = (((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443) ? 'https://' : 'http://');
		}
		$actual_link = "$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
		$url = explode("/", $actual_link);
		$len = count($url);
		if (preg_match("/.php/", $url[$len - 1])) array_pop($url);
		$actual_link = $protocol;
		foreach ($url as $part) $actual_link .= $part . "/";
		return $actual_link;
	}

	function dumpRequest() {
		$data = [
			"Request Method" => $_SERVER['REQUEST_METHOD'],
			"Request URI" => $_SERVER['REQUEST_URI'],
			"Server Protocol" => $_SERVER['SERVER_PROTOCOL'],
			"Request Data" => $request = explode("/", substr(@$_SERVER['PATH_INFO'], 1))
		];

		foreach ($_SERVER as $name => $value) {
			if (preg_match('/^HTTP_/', $name)) {
				// convert HTTP_HEADER_NAME to Header-Name
				$name = strtr(substr($name, 5), '_', ' ');
				$name = ucwords(strtolower($name));
				$name = strtr($name, ' ', '-');
				// add to list
				$data[$name] = $value;
			}
		}
		if ($_SERVER['request_METHOD'] !== 'PUT') {
			$data['Request body'] = file_get_contents('php://input');
		} else {
			parse_str(file_get_contents("php://input"), $post_vars);
			foreach ($post_vars as $key => $value) {
				$data[$key] = $value;
			}
		}
		write_log("Request dump!!: " . json_encode($data), "WARN");
	}

	function bye($msg = false, $title = false, $url = false, $log = false, $clear = false) {
		if ($msg) {
			$display = "<script type=text/javascript>
						var array = [{title:'$title',message:'$msg',url:'$url'}];
						loopMessages(array);
					</script>";
			echo($display);
		}
		if ($log) write_log("Ending session now with message '$msg'.", "INFO");
		if ($clear) clearSession();
		// TODO: Make sure this is only done when webflag is set
		if (isset($_SESSION['db'])) $_SESSION['db']->disconnect();
		die();
	}

	if (!function_exists('http_build_url')) {
		define('HTTP_URL_REPLACE', 1);              // Replace every part of the first URL when there's one of the second URL
		define('HTTP_URL_JOIN_PATH', 2);            // Join relative paths
		define('HTTP_URL_JOIN_QUERY', 4);           // Join query strings
		define('HTTP_URL_STRIP_USER', 8);           // Strip any user authentication information
		define('HTTP_URL_STRIP_PASS', 16);          // Strip any password authentication information
		define('HTTP_URL_STRIP_AUTH', 32);          // Strip any authentication information
		define('HTTP_URL_STRIP_PORT', 64);          // Strip explicit port numbers
		define('HTTP_URL_STRIP_PATH', 128);         // Strip complete path
		define('HTTP_URL_STRIP_QUERY', 256);        // Strip query string
		define('HTTP_URL_STRIP_FRAGMENT', 512);     // Strip any fragments (#identifier)
		define('HTTP_URL_STRIP_ALL', 1024);         // Strip anything but scheme and host
		// Build an URL
		// The parts of the second URL will be merged into the first according to the flags argument.
		//
		// @param   mixed           (Part(s) of) an URL in form of a string or associative array like parse_url() returns
		// @param   mixed           Same as the first argument
		// @param   int             A bitmask of binary or'ed HTTP_URL constants (Optional)HTTP_URL_REPLACE is the default
		// @param   array           If set, it will be filled with the parts of the composed url like parse_url() would return
		function http_build_url($url, $parts = [], $flags = HTTP_URL_REPLACE, &$new_url = false) {
			$keys = [
				'user',
				'pass',
				'port',
				'path',
				'query',
				'fragment'
			];
			// HTTP_URL_STRIP_ALL becomes all the HTTP_URL_STRIP_Xs
			if ($flags & HTTP_URL_STRIP_ALL) {
				$flags |= HTTP_URL_STRIP_USER;
				$flags |= HTTP_URL_STRIP_PASS;
				$flags |= HTTP_URL_STRIP_PORT;
				$flags |= HTTP_URL_STRIP_PATH;
				$flags |= HTTP_URL_STRIP_QUERY;
				$flags |= HTTP_URL_STRIP_FRAGMENT;
			} // HTTP_URL_STRIP_AUTH becomes HTTP_URL_STRIP_USER and HTTP_URL_STRIP_PASS
			else if ($flags & HTTP_URL_STRIP_AUTH) {
				$flags |= HTTP_URL_STRIP_USER;
				$flags |= HTTP_URL_STRIP_PASS;
			}
			// Parse the original URL
			// - Suggestion by Sayed Ahad Abbas
			//   In case you send a parse_url array as input
			$parse_url = !is_array($url) ? parse_url($url) : $url;
			// Scheme and Host are always replaced
			if (isset($parts['scheme']))
				$parse_url['scheme'] = $parts['scheme'];
			if (isset($parts['host']))
				$parse_url['host'] = $parts['host'];
			// (If applicable) Replace the original URL with it's new parts
			if ($flags & HTTP_URL_REPLACE) {
				foreach ($keys as $key) {
					if (isset($parts[$key]))
						$parse_url[$key] = $parts[$key];
				}
			} else {
				// Join the original URL path with the new path
				if (isset($parts['path']) && ($flags & HTTP_URL_JOIN_PATH)) {
					if (isset($parse_url['path']))
						$parse_url['path'] = rtrim(str_replace(basename($parse_url['path']), '', $parse_url['path']), '/') . '/' . ltrim($parts['path'], '/');
					else
						$parse_url['path'] = $parts['path'];
				}
				// Join the original query string with the new query string
				if (isset($parts['query']) && ($flags & HTTP_URL_JOIN_QUERY)) {
					if (isset($parse_url['query']))
						$parse_url['query'] .= '&' . $parts['query'];
					else
						$parse_url['query'] = $parts['query'];
				}
			}
			// Strips all the applicable sections of the URL
			// Note: Scheme and Host are never stripped
			foreach ($keys as $key) {
				if ($flags & (int)constant('HTTP_URL_STRIP_' . strtoupper($key)))
					unset($parse_url[$key]);
			}
			$new_url = $parse_url;
			return
				((isset($parse_url['scheme'])) ? $parse_url['scheme'] . '://' : '')
				. ((isset($parse_url['user'])) ? $parse_url['user'] . ((isset($parse_url['pass'])) ? ':' . $parse_url['pass'] : '') . '@' : '')
				. ((isset($parse_url['host'])) ? $parse_url['host'] : '')
				. ((isset($parse_url['port'])) ? ':' . $parse_url['port'] : '')
				. ((isset($parse_url['path'])) ? $parse_url['path'] : '')
				. ((isset($parse_url['query'])) ? '?' . $parse_url['query'] : '')
				. ((isset($parse_url['fragment'])) ? '#' . $parse_url['fragment'] : '');
		}
	}




	if (! function_exists('isWebApp')) {
		function isWebApp() {
			return false;
		}
	}

if (! function_exists('webAddress')) {
	function webAddress() {
		return $_SESSION['publicAddress'];
	}
}

if (! function_exists("fetchBackground")) {
	function fetchBackground() {
		$path = "https://img.phlexchat.com";

		$code = 'var elem = document.createElement("img");'.PHP_EOL.
			'elem.setAttribute("src", "'.$path.'");'.PHP_EOL.
			'elem.className += "fade-in bg bgLoaded";'.PHP_EOL.
			'document.getElementById("bgwrap").appendChild(elem);'.PHP_EOL;
		return $code;
	}
}
