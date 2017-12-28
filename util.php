<?PHP

use Cz\Git\GitRepository;

require_once dirname(__FILE__) . '/vendor/autoload.php';

// Checks whether an API Token exists for the current user, generates and saves one if none exists.
// Returns generated or existing API Token.
function checkSetUser(Array $userData) {
	// Check that we have generated an API token for our user, create and save one if none exists
	$userName = strtolower(trim($userData['plexUserName']));
	$apiToken = false;
	foreach ($GLOBALS['config'] as $section => $user) {
		if ($section !== "general") {
			$checkName = strtolower(trim($user['plexUserName']));
			$checkEmail = strtolower(trim($user['plexEmail']));
			write_log("Checking $userName against $checkName and $checkEmail");
			if (($userName === $checkName) || ($userName === $checkEmail)) {
				write_log("Found matching token for " . $user['plexUserName'] . ".");
				$apiToken = $user['apiToken'];
				break;
			}
		}
	}

	if (!$apiToken) {
		dumpRequest();
		write_log("NO API TOKEN FOUND, generating one for " . $userName, "INFO");
		$apiToken = randomToken(21);
		$userData['apiToken'] = $apiToken;
		$cleaned = str_repeat("X", strlen($apiToken));
		write_log("API token created " . $cleaned);
		$userString = $userData['string'];
		unset ($userData['string']);
		foreach ($userData as $item => $value) {
			$GLOBALS['config']->set($userString, $item, $value);
		}
		saveConfig($GLOBALS['config']);
		$_SESSION['newToken'] = true;
	} else {
		$userData['apiToken'] = $apiToken;
	}
	return $userData;
}


function validateToken($token) {
	$config = new Config_Lite('config.ini.php');
	// Check that we have some form of set credentials
	foreach ($config as $section => $setting) {
		$checkToken = false;
		if ($section != "general") {
			$checkToken = $setting['apiToken'] ?? false;
			if (trim($checkToken) == trim($token)) {
				$user = [
					'string' => $section,
					'plexUserName' => $setting['plexUserName'],
					'plexToken' => $setting['plexToken'],
					"plexEmail" => $setting['plexEmail'],
					"plexAvatar" => $setting['plexAvatar'],
					"plexCred" => $setting['plexCred'],
					"apiToken" => $setting['apiToken']
				];
				return $user;
			}
		}
	}
	$caller = getCaller("validateToken");
	write_log("ERROR, api token $token not recognized, called by $caller.", "ERROR");
	dumpRequest();
	return false;
}

function cleanCommandString($string) {
	$string = trim(strtolower($string));
	$string = preg_replace("/ask Flex TV/", "", $string);
	$string = preg_replace("/tell Flex TV/", "", $string);
	$string = preg_replace("/Flex TV/", "", $string);
	$stringArray = explode(" ", $string);
	$stripIn = ["of", "an", "a", "at", "th", "nd", "in", "from", "and"];
	$stringArray = array_diff($stringArray, array_intersect($stringArray, $stripIn));
	foreach ($stringArray as &$word) {
		$word = preg_replace("/[^\w\']+|\'(?!\w)|(?<!\w)\'/", "", $word);
	}
	$result = implode(" ", $stringArray);
	return $result;
}

function TTS($text) {
	$res = false;
	$words = substr($text, 0, 2000);
	write_log("Building speech for '$words'");
	$words = urlencode($words);
	$file  = md5($words);
	$cacheDir = file_build_path(dirname(__FILE__), "img", "cache");
	checkCache($cacheDir);

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
	if ($data['success']) {
		$id = $data['id'];
		$url = "https://soundoftext.com/api/sounds/$id";
		$data = curlGet($url,null,10);
		if ($data) {
			$response = json_decode($data,true);
			if (isset($response['location'])) return $response['location'];
		}
	}
	return false;
}

function flattenXML($xml) {
	libxml_use_internal_errors(true);
	$return = [];
	if (is_string($xml)) {
		$xml = new SimpleXMLElement($xml);
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
	return $return;
}


// Generate a random token using the first available PHP function
function randomToken($length = 32) {
	write_log("Function fired.");
	if (!isset($length) || intval($length) <= 8) {
		$length = 32;
	}
	if (function_exists('mcrypt_create_iv')) {
		write_log("Generating using mcrypt_create.");
		return bin2hex(mcrypt_create_iv($length, MCRYPT_DEV_URANDOM));
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

function transcodeImage($path, $uri = "", $token = "") {
	if (preg_match("/library/", $path)) {
		if ($uri) $server = $uri;
		$server = $server ?? $_SESSION['plexServerPublicUri'] ?? $_SESSION['plexServerUri'] ?? false;
		if ($token) $serverToken = $token;
		$token = $serverToken ?? $_SESSION['plexServerToken'];
		if ($server && $token) {
			return $server . "/photo/:/transcode?width=1920&height=1920&minSize=1&url=" . urlencode($path) . "%3FX-Plex-Token%3D" . $token . "&X-Plex-Token=" . $token;
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

// Fetch data from a URL using CURL
function curlGet($url, $headers = null, $timeout = 4) {
	$cert = getContent(file_build_path(dirname(__FILE__), "cacert.pem"), 'https://curl.haxx.se/ca/cacert.pem');
	if (!$cert) $cert = file_build_path(dirname(__FILE__), "cert", "cacert.pem");
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
	$cert = getContent(file_build_path(dirname(__FILE__), "cacert.pem"), 'https://curl.haxx.se/ca/cacert.pem');
	if (!$cert) $cert = file_build_path(dirname(__FILE__), "cert", "cacert.pem");
	$mc = JMathai\PhpMultiCurl\MultiCurl::getInstance();
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
	//$response = curl_exec($curl);
	//curl_close($curl);
	$call = $mc->addCurl($curl);
	// Access response(s) from your cURL calls.
	$result = $call->response;
	return $result;
}

// Write log information to $filename
// Auto rotates files larger than 2MB
function write_log($text, $level = null, $caller = false) {
	$filename = file_build_path(dirname(__FILE__), 'logs', "Phlex.log.php");
	if ($level === null) $level = 'DEBUG';
	if (isset($_SESSION) && $level === 'DEBUG' && !$_SESSION['Debug']) return;
	if (isset($_GET['pollPlayer']) || !file_exists($filename) || (trim($text) === "")) return;
	$caller = $caller ? $caller : getCaller();
	$text = '[' . date(DATE_RFC2822) . '] [' . $level . '] [' . $caller . "] - " . trim($text) . PHP_EOL;
	$youIdiot = file_build_path(dirname(__FILE__),'logs',"Phlex.log.php.old");
	if (file_exists($youIdiot)) unlink($youIdiot);
	if (filesize($filename) > 5 * 1024 * 1024) {
		$filename2 = "Phlex.log.old.php";
		if (file_exists($filename2)) unlink($filename2);
		rename($filename, $filename2);
		touch($filename);
		chmod($filename, 0666);
		$authString = "; <?php die('Access denied'); ?>".PHP_EOL;
		file_put_contents($filename,$authString);
	}
	if (!is_writable($filename)) return;
	if (!$handle = fopen($filename, 'a+')) return;
	if (fwrite($handle, $text) === FALSE) return;
	fclose($handle);
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

function logUpdate(array $log) {
	$config = new Config_Lite('config.ini.php');
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

function clientHeaders() {
	return ['X-Plex-Client-Identifier:' . checkSetDeviceID(), 'X-Plex-Target-Client-Identifier:' . $_SESSION['plexClientId'], 'X-Plex-Device:PhlexWeb', 'X-Plex-Device-Name:Phlex', 'X-Plex-Device-Screen-Resolution:1520x707,1680x1050,1920x1080', 'X-Plex-Platform:Web', 'X-Plex-Platform-Version:1.0.0', 'X-Plex-Product:Phlex', 'X-Plex-Version:3.9.1'];
}

function clientHeaderArray() {
	return ['X-Plex-Client-Identifier' => checkSetDeviceID(), 'X-Plex-Target-Client-Identifier' => $_SESSION['plexClientId'], 'X-Plex-Device' => 'PhlexWeb', 'X-Plex-Device-Name' => 'Phlex', 'X-Plex-Device-Screen-Resolution' => '1520x707,1680x1050,1920x1080', 'X-Plex-Platform' => 'Web', 'X-Plex-Platform-Version' => '1.0.0', 'X-Plex-Product' => 'Phlex', 'X-Plex-Version' => '3.9.1'];
}

function clientString() {
	$string = '&X-Plex-Product=Phlex' . '&X-Plex-Version=3.9.1' . '&X-Plex-Client-Identifier=' . checkSetDeviceID() . '&X-Plex-Platform=Web' . '&X-Plex-Platform-Version=1.0.0' . '&X-Plex-Device=PhlexWeb' . '&X-Plex-Device-Name=Phlex' . '&X-Plex-Device-Screen-Resolution=1520x707,1680x1050,1920x1080' . '&X-Plex-Token=' . $_SESSION['plexServerToken'] . '&X-Plex-Target-Client-Identifier=' . $_SESSION['plexClientId'];
	return $string;
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
	if (file_put_contents($configFile, $cache_new)) write_log("Config saved successfully by " . getCaller("saveConfig")); else write_log("Config save failed!", "ERROR");

}

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

// A more precise way of calculating the similarity between two strings
function similarity($str1, $str2) {
	$len1 = strlen($str1);
	$len2 = strlen($str2);

	$max = max($len1, $len2);
	$similarity = $i = $j = 0;

	while (($i < $len1) && isset($str2[$j])) {
		if ($str1[$i] == $str2[$j]) {
			$similarity++;
			$i++;
			$j++;
		} elseif ($len1 < $len2) {
			$len1++;
			$j++;
		} elseif ($len1 > $len2) {
			$i++;
			$len1--;
		} else {
			$i++;
			$j++;
		}
	}

	return round($similarity / $max, 2);
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
function check_url($url, $post = false,$device=false) {
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

function fetchCastDevices() {
	$returns = false;
	if (!(isset($_GET['pollPlayer']))) write_log("Function fired.");
	$result = Chromecast::scan();
	if ($result) $returns = [];
	if (!(isset($_GET['pollPlayer']))) write_log("Returns: " . json_encode($result));
	if ($result[0] == "Error") return false;
	foreach ($result as $key => $value) {
		$deviceOut = [];
		$nameString = preg_replace("/\._googlecast.*/", "", $key);
		$nameArray = explode('-', $nameString);
		$id = array_pop($nameArray);
		$deviceOut['name'] = $value['friendlyname'];
		$deviceOut['product'] = 'cast';
		$deviceOut['id'] = $id;
		$deviceOut['token'] = 'none';
		$ip = $value['ip'];
		$deviceOut['uri'] = "https://" . $value['ip'] . ":" . $value['port'];
		array_push($returns, $deviceOut);
	}

	return $returns;
}

// Sign in, get a token if we need it

function signIn($credString) {
	$token = $_SESSION['plex_token'] ?? false;
	if ($token) {
		$url = 'https://plex.tv/pms/servers.xml?X-Plex-Token=' . $token;
		$result = curlGet($url);
		if (strpos($result, 'Please sign in.')) {
			write_log("Token invalid, signing in.");
			$token = false;
		} else {
			unset($token);
			$token['authToken'] = $_SESSION['plex_token'];
		}
	}
	if (!$token) {
		write_log("No token or not signed in, signing into Plex.");
		$url = 'https://plex.tv/users/sign_in.xml';
		$headers = ['X-Plex-Client-Identifier: ' . checkSetDeviceID(), 'X-Plex-Device:PhlexWeb', 'X-Plex-Device-Screen-Resolution:1520x707,1680x1050,1920x1080', 'X-Plex-Device-Name:Phlex', 'X-Plex-Platform:Web', 'X-Plex-Platform-Version:1.0.0', 'X-Plex-Product:Phlex', 'X-Plex-Version:1.0.0', 'X-Plex-Provides:player,controller,sync-target,pubsub-player', 'Authorization:Basic ' . $credString];
		$result = curlPost($url, false, false, $headers);
		if ($result) {
			$container = new SimpleXMLElement($result);
			$container = json_decode(json_encode($container), true)['@attributes'];
			write_log("Container: " . json_encode($container));
			$token = $container;
		}
	}
	return $token;
}

function checkSetDeviceID() {
	$config = new Config_Lite('config.ini.php', LOCK_EX);
	$deviceID = $config->get('general', 'deviceID', false);
	if (!$deviceID) {
		$deviceID = randomToken(12);
		$config->set("general", "deviceID", $deviceID);
		saveConfig($config);
	}
	return $deviceID;
}

function fetchDirectory($id = 1) {
	if ($id == 1) return base64_decode("Y2QyMjlmNTU5NWZjYWEyNzI3MGI0NDU4OTIyOGE0OTI=");
	if ($id == 2) return base64_decode("Njk0Nzg2RjBBMkVCNEUwOQ==");
	if ($id == 3) return base64_decode("NjU2NTRmODIwZDQ2NDdhYjljZjdlZGRkZGJiYTZlMDI=");
	return false;
}

function setDefaults() {
	$GLOBALS['config'] = new Config_Lite(dirname(__FILE__) . '/config.ini.php', LOCK_EX);
	ini_set("log_errors", 1);
	ini_set('max_execution_time', 300);
	error_reporting(E_ERROR);
	$errorLogPath = file_build_path(dirname(__FILE__), 'logs', "Phlex_error.log.php");
	ini_set("error_log", $errorLogPath);
	date_default_timezone_set((date_default_timezone_get() ? date_default_timezone_get() : "America/Chicago"));
}

function checkFiles() {
	$messages = [];
	$extensions = ['sockets', 'curl', 'xml'];

	$logDir = file_build_path(dirname(__FILE__), "logs");

	$logPath = file_build_path($logDir, "Phlex.log.php");
	$errorLogPath = file_build_path($logDir, "Phlex_error.log.php");
	$updateLogPath = file_build_path($logDir, "Phlex_update.log.php");

	$old = [
		file_build_path($logDir, "PhlexUpdate.log"),
		file_build_path($logDir, "Phlex.log"),
		file_build_path($logDir, "Phlex.log.old"),
		file_build_path($logDir, "Phlex_error.log"),
		file_build_path($logDir, "Phlex_update.log")
	];

	foreach ($old as $delete) {
		if (file_exists($delete)) {
			write_log("Deleting insecure file $delete","INFO");
			unlink($delete);
		}
	}

	$files = [$logPath, $errorLogPath, $updateLogPath, 'config.ini.php', 'commands.php'];

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
	foreach ($files as $file) {
		if (!file_exists($file)) {
			mkdir(dirname($file), 0777, true);
			touch($file);
			chmod($file, 0777);
			file_put_contents($file,$secureString);
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
			$error = ['title' => 'File error.', 'message' => $message, 'url' => $url];
			array_push($messages, $error);
		}
	}


	foreach ($extensions as $extension) {
		if (!load_lib($extension)) {
			$message = "The " . $extension . " PHP extension, which is required for Phlex to work correctly, is not loaded." . " Please enable it in php.ini, restart your webserver, and then reload this page to continue.";
			write_log($message, "ERROR");
			$url = "http://php.net/manual/en/book.$extension.php";
			$error = ['title' => 'PHP Extension not loaded.', 'message' => $message, 'url' => $url];
			array_push($messages, $error);
		}
	}
	try {
		new Config_Lite('config.ini.php');
	} catch (Config_Lite_Exception_Runtime $e) {
		$message = "An exception occurred trying to load config.ini.php.  Please check that the directory and file are writeable by your webserver application and try again.";
		$error = ['title' => 'Config error.', 'message' => $message, 'url' => false];
		array_push($messages, $error);
	};
	//$testMessage = ['title'=>'Test message.','message'=>"This is a test of the emergency alert system. If this were a real emergency, you'd be screwed.",'url'=>'https://www.google.com'];
	//array_push($messages,$testMessage);
	return $messages;
}

function load_lib($ext) {
	if (extension_loaded($ext)) return true;
	write_log("Extension is not loaded, attempting to load $ext...", "INFO");
	if (function_exists('dl')) return dl(((PHP_SHLIB_SUFFIX === 'dll') ? 'php_' : '') . $ext . '.' . PHP_SHLIB_SUFFIX);
	write_log("DL function not available.", "WARN");
	return false;
}

function clearSession() {
	write_log("Function fired");
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
	session_start();
	session_unset();
	$has_session = session_status() == PHP_SESSION_ACTIVE;
	if ($has_session) session_destroy();
	session_write_close();
	setcookie(session_name(), '', 0, '/');
	session_regenerate_id(true);
}

function addScheme($url, $scheme = 'http://') {
	return parse_url($url, PHP_URL_SCHEME) === null ? $scheme . $url : $url;
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

function checkSSL() {
	$forceSSL = false;
	if (file_exists(dirname(__FILE__) . "/config.ini.php")) {
		$config = new Config_Lite('config.ini.php');
		$forceSSL = $config->getBool('general', 'forceSsl', false);
	}
	return $forceSSL;
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
	} else {
		if (file_exists(dirname(__FILE__) . "/config.ini.php") && isset($_SESSION['plexUserName'])) {
			$config = new Config_Lite('config.ini.php');
			$locale = $config->get('user-_-' . $_SESSION['plexUserName'], "appLanguage", false);
			if ($locale) $_SESSION['appLanguage'] = $locale;
			write_log("Locale not set for session, but saved in config: $locale");
		} else {
			write_log("No session username, can't look in settings.", "ERROR");
		}
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
				$repo->fetch('origin');
				$result = $repo->readLog('origin', 'HEAD');
				$revision = $repo->getRev();
				$logHistory = readUpdate();
				if ($revision) {
					$config = new Config_Lite('config.ini.php', LOCK_EX);
					$old = $config->get('general','revision',false);
					if ($old !== $revision) {
						$config->set('general', 'revision', $revision);
						saveConfig($config);
					}
				}
				if (count($logHistory)) $installed = $logHistory[0]['installed'];
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
					$_SESSION['updateAvailable'] = count($log);
					$html = parseLog($log);
					$html = $header . '<div class="cardHeader">
								Status: ' . count($log) . ' commit(s) behind.<br><br>
								Missing Update(s):' . $html . '</div>';
					if (($install) || ($autoUpdate)) {
						if (isset($_SESSION['pollPlayer'])) {
							$pp = true;
							unset($_SESSION['pollPlayer']);
						}
						backupConfig();
						write_log("Updating from repository - " . ($install ? 'Manually triggered.' : 'Automatically triggered.'), "INFO");
						$repo->pull('origin');
						//write_log("Pull result: ".$result);
						if ($pp) $_SESSION['pollPlayer'] = true;
						logUpdate($log);

					}
				} else {
					write_log("No changes detected.");
					if (count($logHistory)) {
						$html = parseLog($logHistory[0]['commits']);
						$installed = $logHistory[0]['installed'];
					} else {
						$html = parseLog($repo->readLog("origin/master", 0));
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

function backupConfig() {
	write_log("Function fired!!");
	$newFile = file_build_path(dirname(__FILE__), "config.ini.php_" . time() . ".bk");
	write_log("Backing up configuration file to $newFile.", "INFO");
	if (!copy(file_build_path(dirname(__FILE__), "config.ini.php"), $newFile)) {
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
	$output = str_replace("'; <?php die('Access denied'); ?>".PHP_EOL,"",$output);
	return $output;
}

function formatLog($logData) {
	$authString = "'; <?php die('Access denied'); ?>".PHP_EOL;
	$logData = str_replace($authString,"",$logData);
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
				$record = ['time' => substr($params[0], 1, -1), 'level' => substr($params[1], 1, -1), 'caller' => substr($params[2], 1, -1), 'message' => $message];
				if ($JSON) $record['JSON'] = trim($JSON);
				array_push($records, $record);
			}
		}
	}
	return json_encode($records);
}


function doRequest($parts, $timeout = 3) {
	$type = isset($parts['type']) ? $parts['type'] : 'get';
	$response = false;
	$options = [];
	$cert = getContent(file_build_path(dirname(__FILE__), "cacert.pem"), 'https://curl.haxx.se/ca/cacert.pem');
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
	write_log("URL is " . protectURL($url), "INFO", getCaller());

	$client = new GuzzleHttp\Client(['timeout' => $timeout, 'verify' => $cert]);

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
			write_log("Unauthorized error, rescanning devices...", "WARN");
			//scanDevices(true);
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
		"Request Data"=>$request = explode("/", substr(@$_SERVER['PATH_INFO'], 1))
	];

	foreach ($_SERVER as $name => $value) {
		if (preg_match('/^HTTP_/',$name)) {
			// convert HTTP_HEADER_NAME to Header-Name
			$name = strtr(substr($name,5),'_',' ');
			$name = ucwords(strtolower($name));
			$name = strtr($name,' ','-');
			// add to list
			$data[$name] = $value;
		}
	}
	if ($_SERVER['request_METHOD'] !== 'PUT') {
		$data['Request body'] = file_get_contents('php://input');
	} else {
		parse_str(file_get_contents("php://input"),$post_vars);
		foreach($post_vars as $key=>$value) {
			$data[$key] = $value;
		}
	}
	write_log("Request dump!!: ".json_encode($data),"WARN");
}
