<?php
require_once dirname(__FILE__) . '/vendor/autoload.php';
require_once dirname(__FILE__) . '/webApp.php';
require_once dirname(__FILE__) . '/util.php';
require_once dirname(__FILE__) . '/fetchers.php';
require_once dirname(__FILE__) . '/body.php';
require_once dirname(__FILE__) . '/JsonXmlElement.php';

use Kryptonit3\SickRage\SickRage;
use Kryptonit3\Sonarr\Sonarr;

write_log("-------NEW REQUEST RECEIVED-------", "INFO");
setDefaults();

if (isset($_GET['revision'])) {
	$rev = $GLOBALS['config']->get('general', 'revision', false);
	echo $rev ? substr($rev, 0, 8) : "unknown";
	die;
}

$token = false;

if (isset($_GET['apiToken'])) {
	write_log("Using token from GET.", "INFO");
	$token = $_GET['apiToken'];
}

if (isset($_SERVER['HTTP_APITOKEN'])) {
	write_log("Using token from POST", "INFO");
	$token = $_SERVER['HTTP_APITOKEN'];
}

if (isset($_SERVER['HTTP_METHOD'])) {
	$method = $_SERVER['HTTP_METHOD'];
	if ($method == 'google') $token = $_SERVER['HTTP_TOKEN'];
	write_log("Using token from Google Auth.", "INFO");
}

if (!$token && isset($_SESSION['apiToken'])) {
	write_log("Using stored session token.", "INFO");
	$token = $_SESSION['apiToken'];
}

$user = $token ? verifyApiToken($token) : False;

if ($user) {
	write_log("User is valid, initializing.", "INFO");
	$apiToken = $user['apiToken'];

	$_SESSION['dologout'] = false;
	if (session_started() === FALSE) {
		write_log("Starting new session.", "INFO");
		session_id($apiToken);
		session_start();
	}

	foreach ($user as $key => $value) $_SESSION[$key] = $value;
	setSessionVariables(false);
	initialize();
}

function initialize() {

	$_SESSION['lang'] = checkSetLanguage();

	if (!(isset($_SESSION['counter']))) {
		$_SESSION['counter'] = 0;
	}

	if (isset($_GET['pollPlayer'])) {
		if (substr_count($_SERVER["HTTP_ACCEPT_ENCODING"], "gzip")) ob_start("ob_gzhandler"); else ob_start();
		$force = ($_GET['force'] === 'true');
		$result = fetchUiData($force);
		unset($_GET['pollPlayer']);
		if (isset($result['commands'])) write_log("UI DATA: ".json_encode($result));
		header('Content-Type: application/json');
		echo JSON_ENCODE($result);
		bye();
	}

	if (isset($_GET['testclient'])) {
		write_log("API Link Test successful!!", "INFO");
		echo 'success';
		bye();
	}

	if (isset($_GET['test'])) {
		$result = [];
		if ($_GET['test'] == 'plex') {
			$status = testPlex();
		} else {
			$status = testConnection($_GET['test']);
		}
		header('Content-Type: application/json');
		$result['status'] = $status;
		echo json_encode($result);
		bye();
	}

	if (isset($_GET['registerServer'])) {
		write_log("Registering server with phlexchat.com", "INFO");
		registerServer();
		echo "OK";
		bye();
	}

	if (isset($_GET['card'])) {
		popCommand($_GET['card']);
		bye();
	}

	if (isset($_GET['checkUpdates'])) {
		echo checkUpdates();
		bye();
	}

	if (isset($_GET['installUpdates'])) {
		echo checkUpdates(true);
		bye();
	}

	if (isset($_GET['device'])) {
		$type = $_GET['device'];
		$id = $_GET['id'];
		header('Content-Type: application/json');
		if ($id !== 'rescan') {
			$data = setSelectedDevice($type, $id);
		} else {
			$data = scanDevices(true);
		}
		write_log("Echoing new $type list: " . json_encode($data));
		echo json_encode($data);
		bye();
	}

	// If we are changing a setting variable via the web UI.
	if ((isset($_GET['id'])) && (!isset($_GET['device']))) {
		$valid = true;
		$id = $_GET['id'];
		$value = $_GET['value'];
		write_log("Setting Value changed: $id = $value", "INFO");
		$value = str_replace("?logout", "", $value);
		if (preg_match("/IP/", $id) && !preg_match("/device/", $id)) {
			write_log("Sanitizing URL.");
			$value = cleanUri($value);
			if (!$value) $valid = false;
		}
		if (preg_match("Uri",$id)) {
			write_log("Sanitizing URI.");
			$value = cleanUri($value);
			if (!$value) $valid = false;
		}

		if (preg_match("/Path/", $id)) if ((substr($value, 0, 1) != "/") && (trim($value) !== "")) $value = "/" . $value;
		if (preg_match("/static_/", $id)) {
			$devId = explode("_", $id)[1];
			$subKey = str_replace([
				'[',
				']'
			], '', explode("_", $id)[2]);
			$device = $GLOBALS['config']->get($_SESSION['apiToken'], "static_" . $devId . "_");
			$device[$subKey] = $value;
			updateUserPreference('static_' . $devId . "_", $device);
			$newClients = [];
			$pushed = false;
			foreach ($_SESSION['deviceList']['Client'] as $client) {
				if ($client['id'] === $devId) {
					write_log("Replacing existing client.");
					$client = $device;
					$pushed = true;
				}
				array_push($newClients, $client);
			}
			if (!$pushed) array_push($newClients, $device);
			$_SESSION['deviceList']['Client'] = $newClients;
			write_log("New device list: " . json_encode($_SESSION['deviceList']));
			die();
		}

		if ($valid) {
			updateUserPreference($id, $value);
			if ((trim($id) === 'useCast') || (trim($id) === 'noLoop')) scanDevices(true);
			if ($id == "appLanguage") checkSetLanguage($value);

		}
		echo ($valid ? "valid" : "invalid");
		bye();
	}

	if (isset($_GET['msg'])) {
		if ($_GET['msg'] === 'FAIL') {
			write_log("Received response failure from server, firing fallback command.");
			fireFallback();
		}
	}

	if (isset($_GET['fetchList'])) {
		$fetch = $_GET['fetchList'];
		$list = fetchList($fetch);
		write_log("API: Returning profile list for " . $fetch . ": " . json_encode($list), "INFO");
		echo $list;
		bye();
	}

	if (isset($_GET['notify'])) {
		$message = false;
		$json = trim(file_get_contents('php://input'));
		write_log("Notify body: " . $json);
		if (preg_match("/message=/", $json)) {
			write_log("Got a hook command from couchpotato!");
			$var = explode("=", $json)[1];
			if (trim($var)) {
				write_log("We have a hook message from couchpotato: $var");
				$var = urldecode($var);
				castAudio($var);
			}
		}
		if (preg_match("/EventType/", $json)) {
			write_log("This looks like a Radarr or event!");
			$json = json_decode($json, true);
			if (isset($json['Movie']['Title'])) {
				write_log("Yeah, this is a Radarr event.");
				$media = $json['Movie']['Title'];
				$event = $json['EventType'];
				$message = "The Movie $media has been $event on Radarr.";
			}
			if (isset($json['Episodes'][0]['Title'])) {
				write_log("Yeah, this is a Radarr event.");
				$media = $json['Episodes'][0]['Title'];
				$event = $json['EventType'];
				$message = "The Movie $media has been $event on Sonarr.";
			}
		}

		if (isset($_GET['message'])) {
			$message = $_GET['message'];
		}
		if ($message) {
			write_log("Casting audio: $message");
			castAudio($message);
		}
		bye();
	}

	if ($_SERVER['REQUEST_METHOD'] === 'POST') {
		$_SESSION['amazonRequest'] = false;
		$json = file_get_contents('php://input');
		$request = json_decode($json, true);
		if ($request) {
			if (isset($request['result']['resolvedQuery']) || isset($request['type'])) {
				write_log("JSON: " . $json);
				if (isset($request['type'])) {
					if ($request['reason'] == 'ERROR') {
						write_log("Alexa Error message: " . $request['error']['type'] . '::' . $request['error']['message'], "ERROR");
						bye();
					}
					$_SESSION['amazonRequest'] = true;
				}
				parseApiCommand($request);
				bye();
			}
		}
	}

	$command = $_GET['command'] ?? $_SERVER['HTTP_COMMAND'] ?? false;
	if ((isset($_GET['say'])) && $command) {
		write_log("Incoming API request detected.", "INFO");
		try {
			$request = queryApiAi($command);
			parseApiCommand($request);
			bye();
		} catch (\Exception $error) {
			write_log(json_encode($error->getMessage()), "ERROR");
		}
	}
}

	function fetchUiData($force=false) {

	$playerStatus = playerStatus();
	$updates = checkUpdates();
	$devices = scanDevices(false);
	if ($playerStatus) {
		$result['playerStatus'] = $playerStatus;
	}

	if (!isset($_SESSION['devices']) || $force) {
		$_SESSION['devices'] = json_encode($devices);
		$result['devices'] = $devices;
		$result['commands'] = fetchCommands();
		if ($force) {
			$result['ui'] = settingBody();
			$result['updates'] = checkUpdates();
			$result['userdata'] = sessionData();
			$_SESSION['updates'] = $result['updates'];
		}
	} else {
		if (isset($_SESSION['newCommand'])) {
			write_log("New command: ".json_encode($_SESSION['newCommand']),"INFO",false,true);
			$result['commands'] = $_SESSION['newCommand'];
			unset($_SESSION['newCommand']);
		}
		if ($_SESSION['devices'] !== json_encode($devices)) {
			$_SESSION['devices'] = json_encode($devices);
			$result['devices'] = $devices;
		}
		if ($_SESSION['updates'] !== $updates) {
			$result['updates'] = $updates;
			if(!$_SESSION['autoUpdate']) $result['messages'][] = ["An update is available.",
			                         "An update is available for Phlex.  Click here to install it now.",
			                         'api.php?apiToken=' .$_SESSION['apiToken']. '&installUpdates=true'];
		}
	}

	if ($_SESSION['dologout'] ?? false) $result['dologout'] = true;

	if (isset($_SESSION['messages'])) {
		$result['messages'] = $_SESSION['messages'];
		unset($_SESSION['messages']);
	}

	if (isset($_SESSION['webApp'])) {
		$lines = $_GET['logLimit'] ?? 50;
		$result['logs'] = formatLog(tailFile(file_build_path(dirname(__FILE__), "logs", "Phlex.log.php"), $lines));
	}
	return $result;
}


function setSessionVariables($rescan = true) {
	$data = fetchUserData();
	if ($data) {
		$userName = $data['plexUserName'];
		write_log("Found session data for $userName, setting.");
		if ($rescan) {
			scanDevices($rescan);
		} else {
			foreach ($data as $key => $value) {
				if ($key === 'dlist') {
					$devices = json_decode(base64_decode($value), true);
					if (is_array($devices)) {
						$_SESSION['deviceList'] = $devices;
					} else {
						scanDevices(true);
					}
				} else {
					$value = toBool($value);
					$_SESSION[$key] = $value;

				}
			}
		}
	}

	if (!$data) write_log("Error, could not find userdata!!", "ERROR");
	$_SESSION['mc'] = initMCurl();
	$_SESSION['deviceID'] = checkSetDeviceID();
	if ($_SESSION['plexServerUri']) fetchSections();
	$_SESSION['plexHeaderArray'] = plexHeaders();
	$_SESSION['plexHeader'] = headerQuery($_SESSION['plexHeaderArray']);
}


function sessionData() {
	$data = [];
	foreach ($_SESSION as $key => $value) {
		if ($key !== "lang") {
			$value = boolval($value) ?? $value;
			$data[$key] = $value;
		}
	}
	return $data;
}

/* This is our handler for fetch commands

	You can either say just the name of the show or series you want to fetch,
	or explicitely state "the movie" or "the show" or "the series" to specify which one.

	If no media type is specified, a search will first be executed for a movie, and then a
	show, with the first found result being added.

	If a searcher is not enabled in settings, nothing will happen and an appropriate status
	message should be returned as the 'status' value of our object.

	*/



function translateControl($string, $searchArray) {
	foreach ($searchArray as $replace => $search) {
		$string = str_replace($search, $replace, $string);
	}
	return $string;
}

function fireFallback() {
	if (isset($_SESSION['fallback'])) {
		$fb = $_SESSION['fallback'];
		if (isset($fb['media'])) playMedia($fb['media']);
		if (isset($fb['device'])) changeDevice($fb['device']);
		unset($_SESSION['fallback']);
	}
}

function parseControlCommand($command,$value=false) {
	//Sanitize our string and try to rule out synonyms for commands
	$synonyms = $_SESSION['lang']['commandSynonymsArray'];
	$queryOut['initialCommand'] = $command;
	$command = translateControl(strtolower($command), $synonyms);
	$cmd = $int = false;
	$queryOut['parsedCommand'] = "";
	$commandArray = [
		"play",
		"pause",
		"stop",
		"next",
		"stepforward",
		"stepback",
		"previous",
		"volup",
		"voldown",
		"mute",
		"unmute",
		"volume",
		"seek"
	];
	if (strpos($command, "volume")) {
		$int = filter_var($command, FILTER_SANITIZE_NUMBER_INT);
		$queryOut['parsedCommand'] .= "Set the volume to " . $int . " percent.";
		$cmd = 'setParameters?volume=' . $int;
	}
	$value = $int ? $int : $value;

	if (preg_match("/subtitles/", $command)) {
		$streamID = 0;
		if (preg_match("/on/", $command)) {
			$status = playerStatus();
			$statusArray = json_decode($status, true);
			$streams = $statusArray['mediaResult']['Media']['Part']['Stream'];
			foreach ($streams as $stream) {
				$type = $stream['@attributes']['streamType'];
				if ($type == 3) {
					$code = $stream['@attributes']['languageCode'];
					if (preg_match("/eng/", $code)) {
						$streamID = $stream['@attributes']['id'];
					}
				}
			}
		}
		$cmd = 'subtitles';
		$value = $streamID;
	}

	if (!$cmd) {
		write_log("No command set so far, making one.", "INFO");
		$cmds = explode(" ", strtolower($command));
		$newString = array_intersect($commandArray, $cmds);
		$result = implode(" ", $newString);
		if ($result) {
			$cmd = $queryOut['parsedCommand'] .= $cmd = $result;
		}
	}
	if ($cmd) {
		$result = sendCommand($cmd,$value);
		$results['url'] = $result['url'];
		$results['status'] = $result['status'];
		$queryOut['playResult'] = $results;
		$queryOut['mediaStatus'] = 'SUCCESS: Not a media command';
		$queryOut['commandType'] = 'control';
		$queryOut['clientURI'] = $_SESSION['plexClientUri'];
		$queryOut['clientName'] = $_SESSION['plexClientName'];
		return json_encode($queryOut);
	}
	return false;

}


function parseRecordCommand($command) {
	write_log("Function fired.");
	$request = [
		'uri' => $_SESSION['plexDvrUri'],
		'path' => '/' . urldecode($_SESSION['plexDvrKey']) . '/hubs/search',
		'query' => [
			'sectionId' => '',
			'query' => urlencode($command),
			'X-Plex-Token' => $_SESSION['plexDvrToken']
		]
	];

	$result = doRequest($request, 5);

	if ($result) {
		$newContainer = new SimpleXMLElement($result);
		$result = false;
		$newScore = .69;
		if (!empty($newContainer->Hub)) {
			foreach ($newContainer->Hub as $hub) {
				if ($hub['type'] == 'show' || $hub['type'] == 'movie') {
					if (!empty($hub->Directory)) {
						foreach ($hub->Directory as $show) {
							$show = flattenXML($show);
							$score = similarity(cleanCommandString($show['title']), cleanCommandString($command));
							if ($score >= $newScore) {
								write_log("We have a match: " . json_encode($show), "INFO");
								$result = $show;
								$newScore = $score;
							}
						}
					}
				}
			}
		}
	}
	if ($result) {
		$query = '?guid=' . urlencode($result['guid']) . '&X-Plex-Token=' . $_SESSION['plexDvrToken'];
		$template = doRequest([
			'uri' => $_SESSION['plexDvrUri'],
			'path' => '/media/subscriptions/template' . $query
		]);
		if (!$template) {
			write_log("Error fetching download template, aborting.", "ERROR");
			return false;
		}
		$container = flattenXML(new SimpleXMLElement($template));
		$sectionId = $result['librarySectionID'];
		$title = $result['title'];
		parse_str($container['SubscriptionTemplate']['MediaSubscription']['parameters'], $hints);
		$params = [
			'prefs' => [
				'onlyNewAirings' => $_SESSION['plexDvrNewAirings'] ? 1 : 0,
				'minVideoQuality' => $_SESSION['plexDvrResolution'],
				'replaceLowerQuality' => $_SESSION['plexDvrRelaceLower'] ? 'true' : 'false',
				'recordPartials' => $_SESSION['plexDvrRecordPartials'] ? 'true' : 'false',
				'startOffsetMinutes' => $_SESSION['plexDvrStartOffset'],
				'endOffsetMinutes' => $_SESSION['plexDvrEndOffset'],
				'lineupChannel' => '',
				'startTimeslot' => -1,
				'oneShot' => "true",
				'autoDeletionItemPolicyUnwatchedLibrary' => 0,
				'autoDeletionItemPolicyWatchedLibrary' => 0
			],
			'targetLibrarySectionID' => $sectionId,
			'targetSectionLocationID' => '',
			'includeGrabs' => 1,
			'type' => $sectionId,
			'X-Plex-Token' => $_SESSION['plexDvrToken']
		];
		$queryString = http_build_query(array_merge($params, $hints));
		$query = [
			'uri' => $_SESSION['plexDvrUri'],
			'path' => '/media/subscriptions',
			'query' => "?" . $queryString,
			'type' => 'post'
		];
		$result = doRequest($query, 0);
		if ($result) {
			$container = new SimpleXMLElement($result);
			if (isset($container->MediaSubscription)) {
				foreach ($container->MediaSubscription as $subscription) {
					$show = flattenXML($subscription);
					write_log("Show: " . json_encode($show));
					$foundTitle = $show['Directory']['title'];
					if (cleanCommandString($title) == cleanCommandString($foundTitle)) {
						$extra = fetchTMDBInfo($title, false, false, 'tv');
						$art = $extra['art'] ?? $show['Directory']['thumb'];
						$return = [
							"title" => $foundTitle,
							"year" => $show['Directory']['year'],
							"type" => $show['Directory']['type'],
							"thumb" => $art,
							"art" => $art,
							"url" => $_SESSION['plexServerUri'] . '/subscriptions/' . $show['key'] . '?X-Plex-Token=' . $_SESSION['plexServerToken']
						];
						write_log("Show added to record successfully: " . json_encode($return), "INFO");
						return $return;
					}
				}
			}
		}
	}
	return false;
}

// This is now our one and only handler for searches.
function parsePlayCommand($command, $year = false, $artist = false, $type = false, $raw = false) {

	$playerIn = false;

	foreach ($_SESSION['deviceList']['Client'] as $client) {
		$name = strtolower(trim($client['Name']));
		if ($name != "") {
			write_log("Searhing for $name in '$command'");
			$clientName = '/' . $name . '/';
			if (preg_match($clientName, $command) || preg_match($clientName, strtolower($raw))) {
				write_log("I was just asked me to play something on a specific device: " . $client['Name'], "INFO");
				$name = strtolower($client['Name']);
				$playerIn = [
					"on the " . $name,
					"on " . $name,
					"in the " . $name,
					"in " . $name,
					$name
				];

				setSelectedDevice('Client', $client['Id']);
			}
		}
	}

	if ($playerIn) {
		foreach ($playerIn as $search) {
			if (preg_match("/$search/", $command)) write_log("Removing string $search from command.", "INFO");
			$command = preg_replace("/$search/", "", $command);
		}
		$_SESSION['cleaned_search'] = ucwords($command);
	}

	$commandArray = explode(" ", $command);
	// An array of words which don't do us any good
	// Adding the apostrophe and 's' are necessary for the movie "Daddy's Home", which Google Inexplicably returns as "Daddy ' s Home"
	$stripIn = $_SESSION['lang']['parseStripArray'];

	// An array of words that indicate what kind of media we'd like
	$mediaIn = $_SESSION['lang']['parseMediaArray'];

	// An array of words that would modify or filter our search
	$filterIn = $_SESSION['lang']['parseFilterArray'];

	// An array of words that would indicate which specific episode or media we want
	$numberWordIn = $_SESSION['lang']['parseNumberArray'];
	write_log("NumberWordIn: " . json_encode($numberWordIn));


	if (isset($_SESSION['cleaned_search'])) unset($_SESSION['cleaned_search']);


	// An array of words from our command that are numeric
	$numberIn = [];
	foreach ($commandArray as $number) {
		if ((is_numeric($number)) || in_array($number, $numberWordIn)) {
			array_push($numberIn, $number);
		}
	}

	// Create arrays of values we need to evaluate
	$stripOut = array_intersect($commandArray, $stripIn);
	$mediaOut = array_intersect($commandArray, $mediaIn);
	$filterOut = array_intersect($commandArray, $filterIn);
	$numberOut = array_intersect($commandArray, $numberIn);

	if ($year) {
		array_push($mediaOut, 'year');
		array_push($numberOut, $year);
	}

	$mods = [];
	$mods['num'] = [];
	$mods['filter'] = [];
	$mods['media'] = [];

	if ($stripOut) {
		$commandArray = array_diff($commandArray, $stripOut);
	}

	if ($filterOut) {
		$commandArray = array_diff($commandArray, $filterOut);
		//					 "genre","year","actor","director","directed","starring","featuring","with","made","created","released","filmed"
		$replaceArray = [
			"",
			"",
			"actor",
			"director",
			"director",
			"actor",
			"actor",
			"actor",
			"year",
			"year",
			"year",
			"year"
		];
		$filterOut = str_replace($filterIn, $replaceArray, $filterOut);
		$mods['filter'] = $filterOut;
	}
	$mods['preFilter'] = implode(" ", $commandArray);
	if ($mediaOut) {
		$commandArray = array_diff($commandArray, $mediaOut);
		//					  "season","series","show","episode","movie","film","beginning","rest","end","minute","minutes","hour","hours"
		$replaceArray = [
			"season",
			"season",
			"show",
			"episode",
			"movie",
			"movie",
			"0",
			"-1",
			"-1",
			"mm",
			"mm",
			"hh",
			"hh",
			"ss",
			"ss"
		];
		$mediaOut = str_replace($mediaIn, $replaceArray, $mediaOut);
		foreach ($mediaOut as $media) {
			if (is_numeric($media)) {
				$mediaOut = array_diff($mediaOut, [$media]);
				array_push($mediaOut, "offset");
				array_push($numberOut, $media);
			}
		}
		$mods['media'] = $mediaOut;
	}
	if ($numberOut) {
		$commandArray = array_diff($commandArray, $numberOut);
		// "first","pilot","second","third","last","final","latest","random"
		$replaceArray = [
			1,
			1,
			2,
			3,
			-1,
			-1,
			-1,
			-2
		];
		$mods['num'] = str_replace($numberWordIn, $replaceArray, $numberOut);
	}

	if ((empty($commandArray)) && (count($mods['num']) > count($mods['media']))) {
		array_push($commandArray, $mods['num'][count($mods['num']) - 1]);
		($mods['num'][count($mods['num']) - 1]);
	}
	$mods['target'] = implode(" ", $commandArray);
	if ($artist) $mods['artist'] = $artist;
	if ($type) $mods['type'] = $type;
	$result = fetchInfo($mods); // Returns false if nothing found
	return $result;
}


// Parse and handle API.ai commands
function parseApiCommand($request) {
	$lang = $request['lang'];
	if ($lang) $_SESSION['lang'] = checkSetLanguage($lang);
	$_SESSION['lastRequest'] = json_encode($request);
	$greeting = $mediaResult = $rechecked = $screen = $year = false;
	$card = $suggestions = false;
	write_log("Full API.AI request: " . json_encode($request), "INFO");
	$result = $request["result"];
	$action = $result['parameters']["action"] ?? false;
	$request = $result['parameters'];
	$command = $result["parameters"]["command"] ?? false;
	$control = $result["parameters"]["Controls"] ?? false;
	$year = $request["result"]["parameters"]["age"]["amount"] ?? false;
	$type = $result['parameters']['type'] ?? false;
	$days = $result['parameters']['days'] ?? false;
	$artist = $result['parameters']['artist'] ?? false;
	$_SESSION['apiVersion'] = $request['originalRequest']['version'] ?? "1";

	if ($command) $command = cleanCommandString($command);
	$rawspeech = $result['resolvedQuery'];
	if (cleanCommandString($rawspeech) == cleanCommandString($_SESSION['hookCustomPhrase'])) {
		fireHook(false, "Custom");
		write_log("Custom phrase triggered: " . $_SESSION['hookCustomPhrase'], "INFO");
		$queryOut['initialCommand'] = $rawspeech;
		$speech = ($_SESSION['hookCustomReply'] != "" ? $_SESSION['hookCustomReply'] : $_SESSION['lang']['speechHookCustomDefault']);
		$queryOut['speech'] = $speech;
		returnSpeech($speech, "yes", false, false);
		logCommand(json_encode($queryOut));
		bye();
	}
	if ($control) $control = strtolower($control);

	$capabilities = $request['originalRequest']['data']['surface']['capabilities'];
	$GLOBALS['screen'] = false;
	foreach ($capabilities as $capability) {
		if ($capability['name'] == "actions.capability.SCREEN_OUTPUT") $GLOBALS['screen'] = true;
	}

	if ($rawspeech == "GOOGLE_ASSISTANT_WELCOME") $rawspeech = "Talk to Flex TV.";
	write_log("Raw speech is " . $rawspeech);
	$queryOut = [];
	$queryOut['serverURI'] = $_SESSION['plexServerUri'];
	$queryOut['serverToken'] = $_SESSION['plexServerToken'];
	$queryOut['clientURI'] = $_SESSION['plexClientUri'];
	$queryOut['clientName'] = $_SESSION['plexClientName'];
	$queryOut['initialCommand'] = $rawspeech;
	$queryOut['timestamp'] = timeStamp();
	write_log("Action is currently " . $action);

	$contexts = $result["contexts"];
	$tokens = ['originalRequest']['data']['conversation']['conversationToken'];
	write_log("TOKENS: ".json_encode($tokens));
	$inputs = ['originalRequest']['data']['inputs'];
	foreach ($inputs as $input) {
		if ($input['intent'] == 'actions.intent.OPTION') {
			$action = 'playfromlist';
			$command = $rawspeech;
		}
	}
	$con = [];
	foreach ($contexts as $context) {
		if (!isset($context['name']) && is_array($context)) {
			foreach($context as $sub) {
				if (isset($sub['name'])) array_push($con,$sub);
			}
		} else {
			array_push($con,$context);
		}

	}

	write_log("Session fallback? :".json_encode($_SESSION['fallback']));
	foreach ($con as $context) {
		write_log("Input context: ".json_encode($context));
		if ($context['name'] == 'actions_intent_option') {
			if (preg_match("/play/", $context['parameters']['OPTION'])) {
				$option = $context['parameters']['OPTION'];
				$action = 'playfromlist';
				$command = str_replace('play', '', $option);
				write_log("Hey, we got it.  Command is now: " . $command);
				$rawspeech = $command;
				$command = cleanCommandString($command);
			}
		}
		if (($context['name'] == 'promptfortitle') && ($action == '') && ($control == '') && ($command == '')) {
			$action = 'play';
			write_log("This is a response to a title query.");
			if (!($command)) $command = cleanCommandString($result['resolvedQuery']);
			if ($command == 'googleassistantwelcome') {
				$action = $command = false;
				$greeting = true;
			}
		}

		if ((cleanCommandString($rawspeech) == 'talk to flex tv') && (!$greeting)) {
			write_log("Fixing duplicate talk to request", "INFO");
			$action = $command = false;
			$greeting = true;
		}

		if (($artist) && (!$command)) {
			$command = $artist;
			$artist = false;
		}

		if (($control == 'play') && ($action == '') && (!$command == '')) {
			$action = 'play';
			$control = false;
		}

		if (($command == '') && ($control == '') && ($action == 'play') && ($type == '')) {
			$action = 'control';
			$command = 'play';
		}

		if (($context['name'] == 'downloadmedia-followup') && (isset($_SESSION['fallback']['media']))) {
			write_log("Firing fallback request");
			$newResult = $_SESSION['fallback'];
			$resultTitle = $newResult['media']['lastEpisodeName'] ?? $newResult['media']['title'];
			$resultSummary = $newResult['media']['summary'];
			$resultSubtitle = $newResult['media']['artist'] ?? $newResult['media']['grandparentTitle'] ?? $newResult['media']['tagline'] ?? $newResult[1]['year'];
			$resultImage = $newResult['media']['thumb'];
			$speech = "Okay, playing $resultTitle on ".$_SESSION['plexClientName'].".";

			$card = [
				[
					"title" => $resultTitle,
					"subtitle" => $resultSubtitle,
					"formattedText" => $resultSummary,
					'image' => ['url' => $resultImage]
				]
			];

			// #TODO: Work out whether to wait for a response or not...
			returnSpeech($speech, $context['name'], $card,false);
			fireFallback();
			$queryOut['mediaStatus'] = "SUCCESS: Playing media.";
			$queryOut['card'] = $card;
			$queryOut['speech'] = $speech;
			logCommand(json_encode($queryOut));
			bye();

		}

		if (($context['name'] == 'yes') && ($action == 'fetchAPI')) {
			$command = (string)$context['parameters']['command'];
			$type = (isset($context['parameters']['type']) ? (string)$context['parameters']['type'] : false);
			$command = cleanCommandString($command);
			$playerIn = false;
			foreach ($_SESSION['deviceList']['clients'] as $client) {
				$clientName = strtolower($client['name']);
				if (preg_match("/$clientName/", $command)) {
					write_log("Re-removing device name from fetch search: " . $client['name'], "INFO");
					$playerIn = explode(" ", cleanCommandString($client['name']));
					array_push($playerIn, "on", "in");
				}
			}
			if (isset($_SESSION['cleaned_search'])) unset($_SESSION['cleaned_search']);

			if ($playerIn) {
				$commandArray = explode(" ", $command);
				$commandArray = array_diff($commandArray, $playerIn);
				$command = ucwords(implode(" ", $commandArray));
			}
		}
		if (($context['name'] == 'google_assistant_welcome') && ($action == '') && ($command == '') && ($control == '')) {
			$greeting = true;
		}
	}

	if ($action == 'changeDevice') {
		$command = $request['result']['parameters']['player'];
	}

	if ($control == 'skip forward') {
		$action = 'control';
		$command = 'skip forward';
	}

	if ($control == 'skip backward') {
		$action = 'control';
		$command = 'skip backward';
	}

	if ($action === 'broadcast') {
		if (trim($command)) castAudio($command);
		write_log("Broadcast complete.");
		bye();
	}

	if (preg_match("/subtitles/", $control)) {
		$action = 'control';
		$command = str_replace(' ', '', $control);
	}

	$params = [
		'action' => $action,
		'command' => $command,
		'type' => $type,
		'control' => $control
	];
	$i = 1;
	$msg = "Final params should be ";
	$params = array_filter($params);
	foreach ($params as $key => $param) {
		if ($param) {
			if ($i === count($params) && count($params) > 1) {
				$msg .= "and ";
			}
			if ($param == "action") {
				$msg .= "an $key of $param";
			} else {
				$msg .= "a $key of $param";
			}
			if ($i === count($params)) {
				$msg .= ".";
			} else {
				$msg .= ", ";
			}

			$i++;
		}
	}
	write_log("$msg", "INFO");

	// This value tells API.ai that we are done talking.  We set it to a positive value if we need to ask more questions/get more input.
	$contextName = "yes";
	$queryOut['commandType'] = $action;
	$resultData = [];

	if ($greeting) {
		$greetings = $_SESSION['lang']['speechGreetingArray'];
		$speech = $greetings[array_rand($greetings)];
		$speech = buildSpeech($speech, $_SESSION['lang']['speechGreetingHelpPrompt']);
		$contextName = 'PlayMedia';
		$button = [
			[
				'title' => $_SESSION['lang']['cardReadmeButton'],
				'openUrlAction' => ['url' => 'https://github.com/d8ahazard/Phlex/blob/master/readme.md']
			]
		];
		$card = [
			[
				'title' => $_SESSION['lang']['cardGreetingText'],
				'formattedText' => '',
				'image' => ['url' => 'https://phlexchat.com/img/avatar.png'],
				'buttons' => $button
			]
		];
		$queryOut['card'] = $card;
		$queryOut['speech'] = $speech;
		returnSpeech($speech, $contextName, $card, true, false);
		logCommand(json_encode($queryOut));
		bye();
	}

	if (($action == 'shuffle') && ($command)) {
		$media = false;
		write_log("We got a shuffle, foo.");
		$queue = plexSearch($command, $type);
		write_log("Queue: " . json_encode($queue));
		if (count($queue)) $media = $queue[0];
		$key = (isset($media['ratingKey']) ? '/library/metadata/' . $media['ratingKey'] : false);
		$queue = false;
		$audio = ($media['type'] == 'artist' || $media['type'] == 'track' || $media['type'] == 'album');
		if ($key) $queue = queueMedia(['key' => $key], $audio, false, true, true);
		write_log("Got a queue: " . json_encode($queue));
		$queueId = $queue['@attributes']['playQueueID'] ?? false;
		if ($queueId) {
			$selectedMedia = $queue['@attributes']['playQueueSelectedItemID'];
			$videos = $queue['Video'];
			foreach ($videos as $video) {
				if ($video['@attributes']['playQueueItemID'] == $selectedMedia) {
					$childMedia = $video['@attributes'];
				}
			}
			$childMedia['queueID'] = $queueId;
			$childMedia['key'] = $key;
			write_log("Media: " . json_encode($childMedia));
			$speech = buildSpeech($_SESSION['lang']['speechShuffleResponse'], $media['title']) . ".";
			$card = [
				[
					'title' => $childMedia['title'],
					'formattedText' => $childMedia['summary'],
					'image' => ['url' => transcodeImage($media['art'])]
				]
			];
			returnSpeech($speech, "PlayMedia", $card, false);
			$result = playMedia($childMedia);
			$queryOut = [
				'parsedCommand' => "Shuffle " . $media['title'],
				'initialCommand' => $command,
				'mediaResult' => $media,
				'speech' => $speech,
				'commandType' => 'playback',
				'mediaStatus' => 'SUCCESS',
				'card' => $card,
				'playStatus' => $result
			];
			logCommand(json_encode($queryOut));
		}

		die;
	}

	if (($action == 'record') && ($command)) {
		$contextName = 'waitforplayer';
		if ($_SESSION['plexDvrUri']) {
			$result = parseRecordCommand($command);
			if ($result) {
				$title = $result['title'];
				$year = $result['year'];
				$type = $result['type'];
				$queryOut['parsedCommand'] = buildSpeech($_SESSION['lang']['parsedDvrSuccessStart'], $type, $_SESSION['lang']['parsedDvrSuccessNamed'], "$title ($year)", $_SESSION['lang']['speechDvrSuccessEnd']);
				$speech = buildSpeech($_SESSION['lang']['speechDvrSuccessStart'], $type, $_SESSION['lang']['parsedDvrSuccessNamed'], "$title ($year)", $_SESSION['lang']['speechDvrSuccessEnd']);
				$card = [
					[
						'title' => $title,
						'image' => ['url' => $result['thumb']],
						'subtitle' => ''
					]
				];
				$results['url'] = $result['url'];
				$results['status'] = "Success.";
				$queryOut['mediaResult'] = $result;
				$queryOut['card'] = $card;
				$queryOut['mediaStatus'] = 'SUCCESS: Not a media command';
				$queryOut['commandType'] = 'dvr';
			} else {
				$queryOut['parsedCommand'] = $_SESSION['lang']['parsedDvrFailStart'] . $command;
				$speech = buildSpeech($_SESSION['lang']['speechDvrNoDevice'], ucwords($command)) . "'.";
				$results['url'] = $result['url'];
				$card = false;
				$results['status'] = "No results.";
			}
		} else {
			$speech = $_SESSION['lang']['speechDvrNoDevice'];
			$card = false;
		}
		returnSpeech($speech, $contextName, $card);
		$queryOut['speech'] = $speech;
		logCommand(json_encode($queryOut));
		bye();

	}

	if (($action == 'changeDevice') && ($command)) {
		changeDevice($command);
	}

	if ($action == 'status') {
		$status = playerStatus();
		$status = json_decode($status, true);
		if ($status['status'] == 'playing') {
			$type = $status['mediaResult']['type'];
			$player = $_SESSION['plexClientName'];
			$thumb = $status['mediaResult']['art'];
			$title = $status['mediaResult']['title'];
			$summary = $status['mediaResult']['summary'];
			$tagline = $status['mediaResult']['tagline'];
			$speech = buildSpeech($_SESSION['lang']['speechPlayerStatus1'], $type, $title, $_SESSION['lang']['speechPlayerStatus2'], $player . ".");
			if ($type == 'episode') {
				$showTitle = $status['mediaResult']['grandparentTitle'];
				$epNum = $status['mediaResult']['index'];
				$seasonNum = $status['mediaResult']['parentIndex'];
				$speech = buildSpeech($_SESSION['lang']['speechPlayerStatus3'], $seasonNum . $_SESSION['lang']['speechPlayerStatusEpisode'], $epNum, $_SESSION['lang']['speechPlayerStatusOf'], $showTitle, $_SESSION['lang']['speechPlayerStatus4'], $title . ".");
			}

			if ($type == 'track') {
				$songtitle = $title;
				$artist = $status['mediaResult']['grandparentTitle'];
				$album = $status['mediaResult']['parentTitle'];
				$year = $status['mediaResult']['year'];
				$speech = buildSpeech($_SESSION['lang']['speechPlayerStatus3'], $songtitle, $_SESSION['lang']['speechPlayerStatusBy'], $artist, $_SESSION['lang']['speechPlayerStatus6'], $album . '.');
				$title = $artist . ' - ' . $songtitle;
				$tagline = $album . ' (' . $year . ')';

			}

			$card = [
				[
					"title" => $title,
					"subtitle" => $tagline,
					"formattedText" => $summary,
					'image' => ['url' => $thumb]
				]
			];
			$queryOut['card'] = $card;
		} else {
			$speech = $_SESSION['lang']['speechStatusNothingPlaying'];
		}
		$contextName = 'PlayMedia';
		returnSpeech($speech, $contextName, $card);
		$queryOut['parsedCommand'] = "Report player status";
		$queryOut['speech'] = $speech;
		$queryOut['mediaStatus'] = "Success: Player status retrieved";
		$queryOut['mediaResult'] = $status['mediaResult'];
		logCommand(json_encode($queryOut));
		bye();
	}

	if (($action == 'recent') || ($action == 'ondeck')) {
		$type = $request["result"]['parameters']["type"] ?? $request['result']['parameters']['command'];
		$list = (($action == 'recent') ? fetchHubList($action, $type) : fetchHubList($action));
		$cards = false;
		if ($list) {
			$array = json_decode($list, true);
			$speech = (($action == 'recent') ? buildSpeech($_SESSION['lang']['speechReturnRecent'], $type . ": ") : $_SESSION['lang']['speechReturnOndeck']);
			$i = 1;
			$count = count($array);
			$cards = [];
			foreach ($array as $result) {
				$title = $result['title'];
				$summary = $result['tagline'] ?? $result['summary'];
				$thumb = transcodeImage($result['art']);
				$type = trim($result['type']);
				$item = [
					"title" => $title,
					"description" => $summary,
					'image' => ['url' => $thumb],
					"command" => $title
				];
				array_push($cards, $item);
				if (($i == $count) && ($count >= 2)) {
					$speech = buildSpeech($speech, $_SESSION['lang']['speechWordAnd'], $title . ".");
				} else {
					$speech = buildSpeech($speech, $title . ", ");
				}
				$i++;
			}

			$speech = buildSpeech($speech, $_SESSION['lang']['speechReturnOndeckRecentTail']);

			$_SESSION['mediaList'] = $array;
			$queryOut['card'] = $cards;
			$queryOut['mediaStatus'] = 'SUCCESS: Hub array returned';
			$queryOut['mediaResult'] = $array[0];

		} else {
			write_log("Error fetching hub list.", "ERROR");
			$queryOut['mediaStatus'] = "ERROR: Could not fetch hub list.";
			$speech = $_SESSION['lang']['speechReturnOndeckRecentError'];
		}


		$contextName = 'promptfortitle';
		returnSpeech($speech, $contextName, $cards, !true);
		$queryOut['parsedCommand'] = "Return a list of " . $action . ' ' . (($action == 'recent') ? $type : 'items') . '.';
		$queryOut['speech'] = $speech;
		logCommand(json_encode($queryOut));
		bye();
	}

	if ($action == 'upcoming') {
		if ($command != "" && $days == "") $days = $command;
		write_log("This is an upcoming request: $days");
		$queryOut['parsedCommand'] = $rawspeech;
		$cards = [];
		$list = fetchAirings($days);
		if ($list) {
			write_log("List retrieved.");
			$_SESSION['mediaList'] = $list;
			$i = 1;
			$speech = $_SESSION['lang']['speechAiringsReturn'];
			if ($days == 'now') {
				$time = date('H');
				$speech = $_SESSION['lang']['speechAiringsToday'];
				if ($time >= 12) $speech = $_SESSION['lang']['speechAiringsAfternoon'];
				if ($time >= 17) $speech = $_SESSION['lang']['speechAiringsTonight'];
				$days = $speech;
				$speech .= ", ";
			}
			if ($days == 'tomorrow') $speech = $_SESSION['lang']['speechAiringsTomorrow'];
			if ($days == 'weekend') $speech = $_SESSION['lang']['speechAiringsWeekend'];
			if (preg_match("/day/", $days)) $speech = $_SESSION['lang']['speechAiringsOn'] . ucfirst($days) . ", ";
			$mids = $_SESSION['lang']['speechAiringsMids'];
			$speech = buildSpeech($speech, $mids[array_rand($mids)]);
			$names = [];
			foreach ($list as $upcoming) {
				array_push($names, $upcoming['title']);
				$thumb = $upcoming['thumb'];
				$title = $upcoming['title'];
				array_push($cards, [
					'title' => $title,
					'description' => $upcoming['summary'],
					'image' => ['url' => $thumb],
					"key" => ""
				]);
			}
			$cards = count($cards) ? array_unique($cards) : false;
			$names = array_unique($names);
			write_log("List of names for upcomings: " . json_encode($names));
			if (count($names) >= 2) {
				if (count($names) >= 3) {
					foreach ($names as $name) {
						if ($i == count($names)) {
							$speech = buildSpeech($speech, $_SESSION['lang']['speechWordAnd'], $name);
						} else {
							$speech = buildSpeech($speech, $name . ',');
						}
						$i++;
					}
				} else {
					foreach ($names as $name) {
						if ($i == count($names)) {
							$speech = buildSpeech($speech, $_SESSION['lang']['speechWordAnd'], $name);
						} else {
							$speech = buildSpeech($speech, $name . ",");
						}
						$i++;
					}
				}
			} else $speech = buildSpeech($speech, $names[0]);
			$tails = $_SESSION['lang']['speechAiringsTails'];
			$speech = buildSpeech($speech, $tails[array_rand($tails)]);
		} else {
			if ($days == 'now') {
				$time = date('H');
				$days = 'today';
				if ($time >= 12) $days = 'this afternoon';
				if ($time >= 17) $days = 'tonight';

			}
			$errors = $_SESSION['lang']['speechAiringsErrors'];
			$speech = buildSpeech($errors[array_rand($errors)], $days . ".");
		}
		returnSpeech($speech, $contextName, $cards, false);
		$queryOut['speech'] = $speech;
		$queryOut['card'] = $cards;
		logCommand(json_encode($queryOut));
		bye();
	}

	// Start handling playback commands now"
	if (($action == 'play') || ($action == 'playfromlist')) {
		if (($year) && ($action == 'playfromlist')) $command = $year;
		if (!($command)) {
			write_log("This does not have a command.  Checking for a different identifier.", "WARN");
			foreach ($request["result"]['parameters'] as $param => $value) {
				if ($param == 'type') {
					$mediaResult = fetchRandomNewMedia($value);
					$queryOut['parsedCommand'] = 'Play a random ' . $value;
				}
			}
		} else {
			if ($action == 'playfromlist') {
				$cleanedRaw = cleanCommandString($rawspeech);
				$list = $_SESSION['mediaList'] ?? json_decode(base64_decode($GLOBALS['config']->get($_SESSION['apiToken'], 'mlist', false)), true);
				$target = intval($_SESSION['searchAccuracy']) * .01;
				foreach ($list as $mediaItem) {
					$title = cleanCommandString($mediaItem['title']);
					$weight = similarity($title, $cleanedRaw);
					$year = $mediaItem['year'];
					$sameYear = (trim($command) === trim($year));
					if (($weight >= $target) || $sameYear) {
						$mediaResult = [$mediaItem];
						break;
					}
					$title .= " " . $mediaItem['year'];
					$weight = similarity($title, $cleanedRaw);
					if (($weight >= $target) || $sameYear) {
						$mediaResult = [$mediaItem];
						break;
					}
					if (preg_match("/$cleanedRaw/", $title) || preg_match("/$year/", $title)) {
						$mediaResult = [$mediaItem];
						break;
					}
				}
				if (!$mediaResult) {
					if (preg_match('/none/', $cleanedRaw) || preg_match('/neither/', $cleanedRaw) || preg_match('/never mind/', $cleanedRaw) || preg_match('/nevermind/', $cleanedRaw) || preg_match('/cancel/', $cleanedRaw)) {
						$speech = $_SESSION['lang']['speechWordOkay'] . ".";
					} else {
						$speech = buildSpeech($_SESSION['lang']['speechDontUnderstand1'], $rawspeech, $_SESSION['lang']['speechDontUnderstand2']);
					}
					returnSpeech($speech, $contextName);
					bye();
				}
			} else {
				write_log("Final media result: " . json_encode($mediaResult), "INFO");
				$mediaResult = parsePlayCommand(strtolower($command), $year, $artist, $type, $rawspeech);
			}
		}
		recheck:
		if (isset($mediaResult)) {
			if ((count($mediaResult) >= 2) && isset($_GET['say'])) $mediaResult = [$mediaResult[0]];
			if (count($mediaResult) == 1) {
				if ($mediaResult[0]['type'] == 'airing') {
					$affirmatives = $_SESSION['lang']['speechMoreInfoArray'];
					$speech = $affirmatives[array_rand($affirmatives)];
					$button = [
						[
							'title' => 'Search Results',
							'openUrlAction' => ['url' => 'https://www.google.com/search?q=' . urlencode($mediaResult[0]['title'])]
						]
					];
					$card = [
						'title' => $mediaResult[0]['title'],
						'formattedText' => $mediaResult[0]['summary'],
						'image' => ['url' => $mediaResult[0]['thumb']],
						'buttons' => $button
					];
					returnSpeech($speech, $contextName, [$card]);
					bye();
				}
				$queryOut['mediaResult'] = $mediaResult[0];
				$searchType = $queryOut['mediaResult']['searchType'];
				$title = $queryOut['mediaResult']['title'];
				$year = $queryOut['mediaResult']['year'] ?? false;
				$type = $queryOut['mediaResult']['type'];
				$tagline = $queryOut['mediaResult']['tagline'];
				$summary = $queryOut['mediaResult']['summary'] ?? false;
				$thumb = $queryOut['mediaResult']['art'];
				$queryOut['parsedCommand'] = 'Play the ' . (($searchType == '') ? $type : $searchType) . ' named ' . $title . '.';
				unset($affirmatives);
				$affirmatives = $_SESSION['lang']['speechPlaybackAffirmatives'];
				$titlelower = strtolower($title);
				switch ($titlelower) {
					case (strpos($titlelower, 'batman') !== false):
						$affirmative = $_SESSION['lang']['speechEggBatman'];
						break;
					case (strpos($titlelower, 'ghostbusters') !== false):
						$affirmative = $_SESSION['lang']['speechEggGhostbusters'];
						break;
					case (strpos($titlelower, 'iron man') !== false):
						$affirmative = $_SESSION['lang']['speechEggIronMan'];
						break;
					case (strpos($titlelower, 'avengers') !== false):
						$affirmative = $_SESSION['lang']['speechEggAvengers'];
						break;
					case (strpos($titlelower, 'frozen') !== false):
						$affirmative = $_SESSION['lang']['speechEggFrozen'];
						break;
					case (strpos($titlelower, 'space odyssey') !== false):
						$affirmative = $_SESSION['lang']['speechEggOdyssey'];
						break;
					case (strpos($titlelower, 'big hero') !== false):
						$affirmative = $_SESSION['lang']['speechEggBigHero'];
						break;
					case (strpos($titlelower, 'wall-e') !== false):
						$affirmative = $_SESSION['lang']['speechEggWallE'];
						break;
					case (strpos($titlelower, 'evil dead') !== false):
						$affirmative = $_SESSION['lang']['speechEggEvilDead']; //"playing Evil Dead 1/2/3/(2013)"
						break;
					case (strpos($titlelower, 'fifth element') !== false):
						$affirmative = $_SESSION['lang']['speechEggFifthElement']; //"playing The Fifth Element"
						break;
					case (strpos($titlelower, 'game of thrones') !== false):
						$affirmative = $_SESSION['lang']['speechEggGameThrones'];
						break;
					case (strpos($titlelower, 'they live') !== false):
						$affirmative = $_SESSION['lang']['speechEggTheyLive'];
						break;
					case (strpos($titlelower, 'heathers') !== false):
						$affirmative = $_SESSION['lang']['speechEggHeathers'];
						break;
					case (strpos($titlelower, 'star wars') !== false):
						$affirmative = $_SESSION['lang']['speechEggStarWars'];
						break;
					case (strpos($titlelower, 'resident evil') !== false):
						$affirmative = $_SESSION['lang']['speechEggResidentEvil'];
						break;
					case (strpos($titlelower, 'attack the block') !== false):
						$affirmative = $_SESSION['lang']['speechEggAttackTheBlock'];
						break;
					default:
						$affirmative = false;
						break;
				}
				// Put our easter egg affirmative in the array of other possible options, so it's only sometimes used.

				if ($affirmative) array_push($affirmatives, $affirmative);

				// Make sure we didn't just say whatever affirmative we decided on.
				do {
					$affirmative = $affirmatives[array_rand($affirmatives)];
				} while ($affirmative == $_SESSION['affirmative']);

				// Store the last affirmative.
				$_SESSION['affirmative'] = $affirmative;
				write_log("Building speech response for a $type.");
				switch ($type) {
					case "episode":
						$yearString = "($year)";
						$seriesTitle = $queryOut['mediaResult']['grandparentTitle'];
						$episodeTitle = $queryOut['mediaResult']['title'];
						$idString = "S" . $queryOut['mediaResult']['parentIndex'] . "E" . $queryOut['mediaResult']['index'];
						$seriesTitle = str_replace($yearString, "", $seriesTitle) . " $yearString";
						$speech = buildSpeech($affirmative . $_SESSION['lang']['speechPlaying'], $episodeTitle . ".");
						$title = "$idString - $episodeTitle";
						$tagline = $seriesTitle;
						break;
					case "track":
						$artist = $queryOut['mediaResult']['grandparentTitle'];
						$track = $queryOut['mediaResult']['title'];
						$tagline = $queryOut['mediaResult']['parentTitle'];
						if ($year) $tagline .= " (" . $year . ")";
						$title = "$artist - $track";
						$speech = buildSpeech($affirmative, $_SESSION['lang']['speechPlaying'], $track, $_SESSION['lang']['speechBy'], $artist . ".");
						break;
					case "artist":
						$speech = buildSpeech($affirmative, $_SESSION['lang']['speechPlaying'], "$title.");
						break;
					case "album":
						$artist = $queryOut['mediaResult']['parentTitle'];
						$album = $queryOut['mediaResult']['title'];
						$tagline = $queryOut['mediaResult']['parentTitle'];
						if ($year) $tagline .= " (" . $year . ")";
						$speech = buildSpeech($affirmative, $_SESSION['lang']['speechPlaying'], $album . $_SESSION['lang']['speechBy'], $artist . ".");
						break;
					case "playlist":
						$title = $queryOut['mediaResult']['title'];
						$speech = buildSpeech($affirmative, $_SESSION['lang']['speechPlaying'], $title . ".");
						break;
					default:
						$title = $queryOut['mediaResult']['title'];
						$title = $title . ($year ? " (" . $year . ")" : "");
						$speech = buildSpeech($affirmative, $_SESSION['lang']['speechPlaying'], $title . ".");
						break;
				}

				if ($_SESSION['promptfortitle'] == true) {
					$contextName = 'promptfortitle';
					$_SESSION['promptfortitle'] = false;
				}
				write_log("Final Media Result: " . json_encode($queryOut['mediaResult']));
				if (!preg_match("/http/", $thumb)) $thumb = transcodeImage(($thumb));
				$card = [
					[
						"title" => $title,
						"subtitle" => $tagline,
						'image' => ['url' => $thumb]
					]
				];
				if ($summary) $card[0]['formattedText'] = $summary;
				returnSpeech($speech, $contextName, $card);
				$playResult = playMedia($mediaResult[0]);
				$exact = $mediaResult[0]['@attributes']['exact'];
				$queryOut['speech'] = $speech;
				$queryOut['card'] = $card;
				$queryOut['mediaStatus'] = "SUCCESS: " . ($exact ? 'Exact' : 'Fuzzy') . " result found";
				$queryOut['playResult'] = $playResult;
				logCommand(json_encode($queryOut));
				bye();
			}

			if (count($mediaResult) >= 2) {
				write_log("Got multiple results, prompting for moar info.", "INFO");
				$speechString = "";
				$resultTitles = [];
				$count = 0;
				$_SESSION['mediaList'] = $mediaResult;
				updateUserPreference('mlist', base64_encode(json_encode($mediaResult)));
				$cards = [];
				foreach ($mediaResult as $Media) {
					write_log("Media: " . json_encode($Media));
					$title = $Media['title'];
					$year = $Media['year'] ?? false;
					$tagline = $Media['tagline'];
					$thumb = $Media['art'];

					$count++;
					if ($count == count($mediaResult)) {
						$speechString .= " or " . $title . ($year ? " " . $year : "") . ".";
					} else {
						$speechString .= " " . $title . ($year ? " " . $year : "") . ",";
					}
					array_push($resultTitles, $title . ($year ? " " . $year : ""));
					$card = [
						"title" => $title . ($year ? " " . $year : ""),
						"description" => $tagline,
						'image' => ['url' => $thumb],
						"key" => $title . " " . $year
					];
					array_push($cards, $card);
				}
				$queryOut['card'] = $cards;
				$_SESSION['fallback']['media'] = $mediaResult[0];
				$questions = $_SESSION['lang']['speechMultiResultArray'];
				$speech = buildSpeech($questions[array_rand($questions)], $speechString);
				$contextName = "promptfortitle";
				$_SESSION['promptfortitle'] = true;
				returnSpeech($speech, $contextName, $cards, true);
				$queryOut['parsedCommand'] = 'Play a media item named ' . $command . '. (Multiple results found)';
				$queryOut['mediaStatus'] = 'SUCCESS: Multiple Results Found, prompting user for more information';
				$queryOut['speech'] = $speech;
				$queryOut['playResult'] = "Not a media command.";
				logCommand(json_encode($queryOut));
				bye();
			}
			if (!count($mediaResult)) {
				if ($command) {
					if (isset($_SESSION['cleaned_search'])) {
						$command = $_SESSION['cleaned_search'];
						unset($_SESSION['cleaned_search']);
					}
					$errors = [
						buildSpeech($_SESSION['lang']['speechPlayErrorStart1'], $command, $_SESSION['lang']['speechPlayErrorEnd1']),
						buildSpeech($_SESSION['lang']['speechPlayErrorStart2'], $command . $_SESSION['lang']['speechPlayErrorEnd2']),
						$_SESSION['lang']['speechPlayError3']
					];
					$speech = $errors[array_rand($errors)];
					$contextName = 'yes';
					$suggestions = $_SESSION['lang']['suggestionYesNo'];
					returnSpeech($speech, $contextName, false, true, $suggestions);
					$queryOut['parsedCommand'] = "Play a media item with the title of '" . $command . ".'";
					$queryOut['mediaStatus'] = 'ERROR: No results found, prompting to download.';
					$queryOut['speech'] = $speech;
					logCommand(json_encode($queryOut));
					bye();
				}
			}
		}
	}


	if (($action == 'player') || ($action == 'server')) {
		$speechString = '';
		unset($_SESSION['deviceList']);
		$type = (($action == 'player') ? 'clients' : 'servers');
		$deviceString = (($action == 'player') ? $_SESSION['lang']['speechPlayer'] : $_SESSION['lang']['speechServer']);
		$list = $_SESSION['deviceList'];
		$list = $list[$type];
		$speech = $_SESSION['lang']['speechDeviceListError'];
		$contextName = "yes";
		$waitForResponse = false;
		if (count($list) >= 2) {
			$suggestions = [];
			$_SESSION['deviceList'] = $list;
			$_SESSION['type'] = $action;
			$count = 0;
			foreach ($list as $device) {
				array_push($suggestions, $device['name']);
				$count++;
				if ($count == count($list)) {
					$speechString .= " or " . $device['name'] . ".";
				} else {
					$speechString .= " " . $device['name'] . ",";
				}
			}
			$_SESSION['fallback']['device'] = [$list[0]['name']];
			$speech = buildSpeech($_SESSION['lang']['speechChange'], $deviceString . $_SESSION['lang']['speechChangeDevicePrompt'], $speechString);
			$contextName = "waitforplayer";
			$waitForResponse = true;
		}
		if (count($list) == 1) {
			$suggestions = false;
			$errors = $_SESSION['lang']['speechDeviceListErrorArray'];
			$speech = $errors[array_rand($errors)];
			$contextName = "waitforplayer";
			$waitForResponse = false;
		}
		returnSpeech($speech, $contextName, false, $waitForResponse, $suggestions);
		$queryOut['parsedCommand'] = 'Switch ' . $action . '.';
		$queryOut['mediaStatus'] = 'Not a media command.';
		$queryOut['speech'] = $speech;

		logCommand(json_encode($queryOut));
		bye();

	}

	if ($action == 'help') {
		$errors = $_SESSION['lang']['errorHelpSuggestionsArray'];
		$speech = $errors[array_rand($errors)];
		write_log("Speech: $speech");
		$button = [
			[
				'title' => $_SESSION['lang']['btnReadmePrompt'],
				'openUrlAction' => ['url' => 'https://github.com/d8ahazard/Phlex/blob/master/readme.md']
			]
		];
		$card = [
			[
				'title' => $_SESSION['lang']['cardReadmeTitle'],
				'formattedText' => '',
				'image' => ['url' => 'https://phlexchat.com/img/avatar.png'],
				'buttons' => $button
			]
		];
		$contextName = 'yes';
		$suggestions = $_SESSION['lang']['errorHelpCommandsArray'];
		if ($_SESSION['plexDvrUri']) array_push($suggestions, $_SESSION['lang']['suggestionDvr']);
		if (($_SESSION['couchEnabled']) || ($_SESSION['radarrEnabled'])) array_push($suggestions, $_SESSION['lang']['suggestionCouch']);
		if (($_SESSION['sickEnabled']) || ($_SESSION['sonarrEnabled'])) array_push($suggestions, $_SESSION['lang']['suggestionSick']);
		array_push($suggestions, $_SESSION['lang']['suggestionCancel']);
		array_push($suggestions, $_SESSION['lang']['suggestionNowYou']);
		foreach ($suggestions as $suggestion) $speech = buildSpeech($speech, $suggestion);
		write_log("Speech: $speech");
		if (!$GLOBALS['screen']) $card = $suggestions = false;
		returnSpeech($speech, $contextName, $card, true, $suggestions);
		bye();
	}

	if ($action == 'fetchAPI') {
		$response = $request["result"]['parameters']["YesNo"];
		if ($response == 'yes') {
			write_log("Setting action to fetch.");
			$action = 'fetch';
		} else {
			$speech = $_SESSION['lang']['speechChangeMind'];
			returnSpeech($speech, $contextName);
			bye();
		}
	}

	if ($action === 'fetch' && count($params)) {
		$newResult = fetchMedia($request);
		if ($newResult) {
			write_log("Got the result, wtf: ".json_encode($newResult));
			$speech = $newResult['message'];
			$resultTitle = $newResult['media']['lastEpisodeName'] ?? $newResult['media']['title'];
			$resultSummary = $newResult['media']['summary'];
			$resultSubtitle = $newResult['media']['artist'] ?? $newResult['media']['grandparentTitle'] ?? $newResult['media']['tagline'] ?? $newResult[1]['year'];
			$resultImage = $newResult['media']['thumb'];
			$card = [
				[
					"title" => $resultTitle,
					"subtitle" => $resultSubtitle,
					"formattedText" => $resultSummary,
					'image' => ['url' => $resultImage]
				]
			];

			// #TODO: Work out whether to wait for a response or not...
			returnSpeech($speech, 'DownloadMedia-followup', $card,true);
			$queryOut['mediaStatus'] = "SUCCESS: Media exists";
			$queryOut['card'] = $card;
			$queryOut['speech'] = $speech;
			logCommand(json_encode($queryOut));
			bye();
		}
	}

	if (($action == 'control') || ($control != '')) {
		if ($action == '') $command = cleanCommandString($control);
		$speech = buildSpeech($_SESSION['lang']['speechControlConfirm1'], $command);
		if (preg_match("/volume/", $command)) {
			$int = strtolower($request["result"]["parameters"]["percentage"]);
			if ($int != '') {
				$command .= " " . $int;
				$speech = buildSpeech($_SESSION['lang']['speechControlVolumeSet'], $int);
			} else {
				if (preg_match("/up/", $rawspeech)) {
					$command .= " UP";
					$speech = $_SESSION['lang']['speechControlVolumeUp'];
				}
				if (preg_match("/down/", $rawspeech)) {
					$command .= " DOWN";
					$speech = $_SESSION['lang']['speechControlVolumeDown'];
				}
			}
		} else {
			$affirmatives = $_SESSION['lang']['speechControlConfirmGenericArray'];
			switch ($command) {
				case "resume":
				case "play":
					$extras = $_SESSION['lang']['speechControlConfirmPlayArray'];
					break;
				case "stop":
					$extras = $_SESSION['lang']['speechControlConfirmStopArray'];
					break;
				case "pause":
					$extras = $_SESSION['lang']['speechControlConfirmPauseArray'];
					break;
				case "subtitleson":
					$extras = $_SESSION['lang']['speechControlConfirmSubsOnArray'];
					$queryOut['parsedCommand'] = "Enable Subtitles.";
					break;
				case "subtitlesoff":
					$extras = $_SESSION['lang']['speechControlConfirmSubsOffArray'];
					$queryOut['parsedCommand'] = "Disable Subtitles.";
					break;
				default:
					$extras = [
						buildSpeech($_SESSION['lang']['speechControlGeneric1'], $command . '.'),
						buildSpeech($_SESSION['lang']['speechControlGeneric2'], $command . ".")
					];
					$queryOut['parsedCommand'] = $command;
			}
			array_merge($affirmatives, $extras);
			$speech = $affirmatives[array_rand($affirmatives)];
		}
		$queryOut['speech'] = $speech;
		returnSpeech($speech, $contextName);
		$result = parseControlCommand($command);
		$newCommand = json_decode($result, true);
		$newCommand = array_merge($newCommand, $queryOut);
		$newCommand['timestamp'] = timeStamp();
		$result = json_encode($newCommand);
		logCommand($result);
		bye();

	}

	// Say SOMETHING if we don't undersand the request.
	$unsureAtives = $_SESSION['lang']['speechNotUnderstoodArray'];
	$speech = buildSpeech($unsureAtives[array_rand($unsureAtives)], $rawspeech . "'.");
	$contextName = 'playmedia';
	returnSpeech($speech, $contextName);
	$queryOut['parsedCommand'] = 'Command not recognized.';
	$queryOut['mediaStatus'] = 'ERROR: Command not recognized.';
	$queryOut['speech'] = $speech;
	logCommand(json_encode($queryOut));
	bye();


}

function changeDevice($command) {
	$list = $_SESSION['deviceList'];
	$type = $_SESSION['type'];
	$result = false;
	if (isset($list) && isset($type)) {
		$typeString = (($type == 'player') ? 'Client' : 'Server');
		$score = 0;
		$target = intval($_SESSION['searchAccuracy']) * .01;
		foreach ($list as $device) {
			$value = similarity(cleanCommandString($device['name']), cleanCommandString($command));
			if (($value >= $target) && ($value >= $score)) {
				write_log("Found a matching device: " . $device['name'], "INFO");
				$result = $device;
				$score = $value;
			}
			if (preg_match("/$command/", $device['name'])) $result = $device;
		}

		if ($result) {
			$speech = buildSpeech($_SESSION['lang']['speechChangeDeviceSuccessStart'], $typeString, $_SESSION['lang']['speechWordTo'], $command . ".");
			$contextName = 'waitforplayer';
			returnSpeech($speech, $contextName);
			setSelectedDevice($typeString, $result['Id']);
			$queryOut['playResult']['status'] = 'SUCCESS: ' . $typeString . ' changed to ' . $command . '.';
		} else {
			$speech = buildSpeech($_SESSION['lang']['speechChangeDeviceFailureStart'], $command, $_SESSION['lang']['speechChangeDeviceFailureEnd']);
			$contextName = 'waitforplayer';
			returnSpeech($speech, $contextName);
			$queryOut['playResult']['status'] = 'ERROR: No device to select.';
		}
		$queryOut['parsedCommand'] = "Change " . $typeString . " to " . $command . ".";
		$queryOut['speech'] = $speech;
		$queryOut['mediaStatus'] = "Not a media command.";
		logCommand(json_encode($queryOut));

		unset($_SESSION['type']);
		bye();
	} else write_log("No list or type to pick from.", "ERROR");
}


function searchSwap($command) {
	$outArray = [];
	$commandArray = explode(" ", $command);
	foreach ($commandArray as $word) {
		write_log("Word in: " . $word);
		if (is_numeric($word)) {
			$word = NumbersToWord($word);
		} else {
			$word = wordsToNumber($word);
		}
		write_log("Word out: " . $word);
		array_push($outArray, $word);
	}
	$command = implode(" ", $outArray);
	return $command;
}

// Replace all number words with an equivalent numeric value
function wordsToNumber($data) {
	$search = [
		'zero',
		'one',
		'two',
		'three',
		'four',
		'five',
		'six',
		'seven',
		'eight',
		'nine',
		'ten',
		'eleven',
		'twelve',
		'thirteen',
		'fourteen',
		'fifteen',
		'sixteen',
		'seventeen',
		'eighteen',
		'nineteen',
		'twenty',
		'thirty',
		'forty',
		'fourty',
		'fifty',
		'sixty',
		'seventy',
		'eighty',
		'ninety',
		'hundred',
		'thousand',
		'million',
		'billion'
	];
	$replace = [
		'0',
		'1',
		'2',
		'3',
		'4',
		'5',
		'6',
		'7',
		'8',
		'9',
		'10',
		'11',
		'12',
		'13',
		'14',
		'15',
		'16',
		'17',
		'18',
		'19',
		'20',
		'30',
		'40',
		'40',
		'50',
		'60',
		'70',
		'80',
		'90',
		'100',
		'1000',
		'1000000',
		'1000000000'
	];
	$data = str_replace($search, $replace, $data);
	return $data;
}

function NumbersToWord($data) {
	$search = [
		'0',
		'1',
		'2',
		'3',
		'4',
		'5',
		'6',
		'7',
		'8',
		'9',
		'10',
		'11',
		'12',
		'13',
		'14',
		'15',
		'16',
		'17',
		'18',
		'19',
		'20',
		'30',
		'40',
		'40',
		'50',
		'60',
		'70',
		'80',
		'90',
		'100',
		'1000',
		'1000000',
		'1000000000'
	];
	$replace = [
		'zero',
		'one',
		'two',
		'three',
		'four',
		'five',
		'six',
		'seven',
		'eight',
		'nine',
		'ten',
		'eleven',
		'twelve',
		'thirteen',
		'fourteen',
		'fifteen',
		'sixteen',
		'seventeen',
		'eighteen',
		'nineteen',
		'twenty',
		'thirty',
		'forty',
		'fourty',
		'fifty',
		'sixty',
		'seventy',
		'eighty',
		'ninety',
		'hundred',
		'thousand',
		'million',
		'billion'
	];
	$data = str_replace(array_reverse($search), array_reverse($replace), $data);
	return $data;
}

// #TODO: Move functions around so they're all here...
//
// ############# Client/Server Functions ############
//


// The process of fetching and storing devices is too damned tedious.
// This aims to address that.
function scanDevices($force = false) {
	//Variables
	$clients = $devices = $dvrs = $results = $servers = [];

	// Check to see if our cache should be refreshed.
	$now = microtime(true);
	$rescanTime = $_SESSION['rescanTime'] ?? 8;
	write_log("RESCAN TIME: " . $_SESSION['lastScan']);
	$ls = $_SESSION['lastScan'];
	$lastCheck = $ls ?? ceil(round($now) / 60) - $rescanTime;
	$diffMinutes = ceil(round($now - $lastCheck) / 60);
	$timeOut = ($diffMinutes >= $rescanTime);
	write_log("Vars: $ls, $lastCheck, $diffMinutes, $timeOut");

	// Grab existing device list
	$list = fetchDeviceCache();


	write_log("Device list at scan: " . json_encode($list));
	$noServers = (!count($list['Server']));

	// Log things
	$msg = "Re-caching because of ";
	if ($force) $msg .= "FORCE & ";
	if ($timeOut) $msg .= "TIMEOUT &";
	if ($noServers) $msg .= "NO SERVERS";

	// Set things up to be recached
	if ($force || $timeOut || $noServers) {
		if (isset($_SESSION['scanning'])) {
			if ($_SESSION['scanning']) {
				$_SESSION['scanning'] = false;
				write_log("Breaking scanning loop.", "WARN");
				return $list;
			}
		} else {
			write_log("No scanning loop check detected.", "INFO");
		}
		write_log("$msg", "INFO");
		$_SESSION['scanning'] = true;
		$now = $_SESSION['webApp'] ? "now" : $now;
		$https = !$_SESSION['noLoop'];
		$query = "?includeHttps=$https&includeRelay=0&X-Plex-Token=" . $_SESSION['plexToken'];
		$container = simplexml_load_string(doRequest([
			'uri' => 'https://plex.tv/api/resources',
			'query' => $query
		], 3));

		if ($container) {
			$devices = flattenXML($container);
			foreach ($devices['Device'] as $device) {
				write_log("Device: " . json_encode($device));
				if (($device['presence'] == "1" || $device['product'] == "Plex for Vizio") && count($device['Connection'])) {
					$out = [
						'Product' => $device['product'],
						'Id' => $device['clientIdentifier'],
						'Name' => $device['name'],
						'Token' => $device['accessToken'],
						'Connection' => $device['Connection'],
						'Owned' => $device['owned'],
						'publicAddressMatches' => $device['publicAddressMatches'],
						'Key' => ""
					];
					if (preg_match("/Server/", $device['product'])) {
						$out['Version'] = $device['productVersion'];
						array_push($servers, $out);
					} else {
						$conn = isset($device['Connection']['address']) ? $device['Connection'] : $device['Connection'][0];
						$out['Uri'] = $conn['address'] . ":" . $conn['port'];
						array_push($clients, $out);
					}
				}
			}
		}

		write_log("Currently have " . count($servers) . " Servers and " . count($clients) . " clients.");
		// Check set URI and public URI for servers, testing both http and https variables
		if (count($servers)) {
			$result = [];
			foreach ($servers as $server) {
				$name = $server['Name'];
				write_log("Checking server: " . json_encode($server));
				$connections = $server['Connection'];
				if (isset($connections['protocol'])) $connections = [$connections];
				foreach ($connections as $connection) {
					write_log("Connection: " . json_encode($connection));
					$query = '?X-Plex-Token=' . $server['Token'];
					$uri = $connection['uri'] . $query;
					$proto = $server['httpsRequired'] ? "https://" : "http://";
					$localAddress = $proto . $connection['address'] . ":" . $connection['port'];
					$backup = $localAddress . $query;
					$local = (boolval($connection['local'] == boolval($server['publicAddressMatches'])));
					$web = filter_var($connection['address'], FILTER_VALIDATE_IP, FILTER_FLAG_NO_RES_RANGE);
					$secure = (($server['httpsRequired'] && $connection['protocol'] === 'https') || (!$server['httpsRequired']));
					$cloud = preg_match("/plex.services/", $connection['address']);
					write_log("This " . ($cloud ? "is " : "is not") . " a cloud server.");
					if ($connection['local'] && !isset($connection['relay']) && !$cloud) $server['localUri'] = $localAddress;
					if (($local && $web && $secure) || $cloud) {
						write_log("Connection on $name is acceptable, checking.", "INFO");
						foreach ([
							         $uri,
							         $backup
						         ] as $url) {
							if (check_url($url) && !isset($server['uri'])) {
								$server['uri'] = $connection['uri'];
							}
						}
					} else {
						$reasons = [];
						if (!($local && $web && $secure)) {
							if (!$local) array_push($reasons, "connecton match");
							if (!$web) array_push($reasons, "url filter");
							if (!$secure) array_push($reasons, "no secure connection");
						} else {
							if (!$cloud) array_push($reasons, "not cloud");
						}
						$string = "";
						foreach ($reasons as $reason) $string .= " '$reason' ";
					}
				}

				if (isset($server['uri'])) {
					array_push($result, $server);
				}
			}
			$servers = $result;
		}

		write_log("Currently have " . count($servers) . " Servers and " . count($clients) . " clients (pre-scrape).", "INFO");

		// Scrape servers for cast devices, local devices, DVR status
		if (count($servers)) {
			$check = [];
			foreach ($servers as $server) {
				$cloud = preg_match("/plex.services/", $server['uri']);
				if (($server['Owned']) && (!$cloud)) {
					array_push($check, $server);
				}
			}
			$res = scrapeServers($check);
			write_log("Server scrapin' result: " . json_encode($res), "INFO");
			if ($res) {
				$castLocal = $res['Client'];
				$dvrs = $res['Dvr'];
				//Push local devices first
				foreach ($castLocal as $client) {
					if ($client['Product'] !== 'Cast') {
						array_push($clients, $client);
					}
				}
				//Finally, cast devices
				foreach ($castLocal as $client) {
					if ($client['Product'] === 'Cast') {
						array_push($clients, $client);
					}
				}
			} else {
				$_SESSION['alertPlugin'] = true;
			}
			// If this has never been set before
			if (!isset($_SESSION['alertPlugin'])) updateUserPreference('alertPlugin', true);

			if ($_SESSION['alertPlugin'] && !$_SESSION['hasPlugin']) {
				$message = "No FlexTV plugin detected. Click here to find out how to get it.";
				$alert = [
					[
						'title' => 'FlexTV Plugin Not Found.',
						'message' => $message,
						'url' => "https://github.com/d8ahazard/FlexTV.bundle"
					]
				];
				$_SESSION['messages'] = $alert;
				// Once we've sent the alert once, don't show it again
				updateUserPreference('alertPlugin', false);
			}
		}


		$results['Server'] = $servers;
		$results['Client'] = $clients;
		$results['Dvr'] = $dvrs;

		$results = sortDevices($results);
		$results = selectDevices($results);

		$_SESSION['deviceList'] = $results;
		$string = base64_encode(json_encode($results));
		$prefs = [
			'lastScan' => $now,
			'dlist' => $string
		];
		updateUserPreferenceArray($prefs);
		write_log("Final device array: " . json_encode($results), "INFO");
		$_SESSION['scanning'] = false;
	} else {
		$results = $list;
	}
	return $results;
}

function sortDevices($input) {
	$results = [];
	foreach ($input as $class => $devices) {
		$ogCount = count($devices);
		write_log("Checking out $class devices, a total of " . $ogCount . " devices.", "INFO");
		$names = $output = [];

		foreach ($devices as $device) {
			$push = true;
			$name = $device['Name'];
			$id = $device['Id'];
			$uri = $device['Uri'] ?? $id;
			write_log("Sorting $class $name $id");

			foreach ($output as $existing) {
				$exUri = $existing['Uri'];
				$exId = $existing['Id'];

				
				write_log("Comparing $uri to $exUri and $id to $exId");
				if (($exUri === $uri) || ($exId === $id)) {
					write_log("Skipping device $name");
					$push = false;
				}
			}
			$exists = array_count_values($names)[$name] ?? false;
			$displayName = $exists ? "$name ($exists)" : $name;

			if ($push) {
				if ($class == 'Client') {
					$new = [
						'Name' => $displayName,
						'Id' => $device['Id'],
						'Product' => $device['Product'],
						'Type' => 'Client',
						'Token' => $device['Token'] ?? $_SESSION['plexToken']
					];
					if (isset($device['Uri'])) $new['Uri'] = $device['Uri'];
					if (isset($device['Parent'])) $new['Parent'] = $device['Parent'];
				} else {
					$new = [
						'Name' => $displayName,
						'Id' => $device['Id'],
						'Uri' => $device['uri'],
						'Token' => $device['Token'],
						'Product' => $device['Product'],
						'Type' => $class,
						'Key' => $device['key'] ?? False,
						'Version' => $device['Version']
					];
					if (($class !== "Dvr") && (isset($device['localUri']))) $new['localUri'] = $device['localUri'];
				}
				array_push($names, $name);
				array_push($output, $new);
			}
		}
		$ogCount = $ogCount - count($output);
		write_log("Removed $ogCount duplicate devices: " . json_encode($output));
		$results[$class] = $output;
	}
	return $results;
}

function selectDevices($results) {
	$output = [];
	foreach ($results as $class => $devices) {
		$classOutput = [];
		$sessionId = $_SESSION["plex" . $class . "Id"] ?? false;
		$master = $_SESSION['plexMasterId'] ?? false;
		$i = 0;
		foreach ($devices as $device) {
			if ($sessionId) {
				if ($device['Id'] == $sessionId) {
					write_log("Found a matching $class device named " . $device["Name"], "INFO");
					$device['Selected'] = "yes";
				} else {
					$device['Selected'] = "no";
				}
			} else {
				$device['Selected'] = (($i === 0) ? "yes" : "no");
			}
			if ($master && ($class !== "Client")) {
				if ($device['Id'] == $master) {
					$device['Master'] = "yes";
				} else {
					$device['Master'] = "no";
				}
			} else {
				if ($class !== "Client") $device['Master'] = (($i === 0) ? "yes" : "no");
			}
			if ($device['Master'] === "yes") updateUserPreference('plexMasterId', $device['Id']);
			if ($device['Selected'] === "yes") setSelectedDevice($class, $device['Id']);
			array_push($classOutput, $device);
			$i++;
		}

		$output[$class] = $classOutput;
	}
	return $output;
}

function scrapeServers($serverArray) {
	$clients = $dvrs = $responses = $urls = [];
	write_log("Scraping " . count($serverArray) . "servers.");
	foreach ($serverArray as $device) {
		$server = $device['uri'];
		$token = $device['Token'];
		$url1 = [
			'url' => "$server/chromecast/clients?X-Plex-Token=$token",
			'device' => $device
		];
		$url2 = [
			'url' => "$server/tv.plex.providers.epg.onconnect?X-Plex-Token=$token",
			'device' => $device
		];
		array_push($urls, $url1);
		array_push($urls, $url2);
	}

	if (count($urls)) {
		$handlers = [];
		$mh = curl_multi_init();
		// Handle all of our URL's
		foreach ($urls as $item) {
			$url = $item['url'];
			write_log("URL: " . $url);
			$device = $item['device'];
			$ch = curl_init($url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_multi_add_handle($mh, $ch);
			$cr = [
				$ch,
				$device
			];
			array_push($handlers, $cr);
		}
		// Execute queries
		$running = null;
		do {
			curl_multi_exec($mh, $running);
		} while ($running);

		foreach ($handlers as $ch) {
			curl_multi_remove_handle($mh, $ch[0]);
		}
		curl_multi_close($mh);

		foreach ($handlers as $ch) {
			$response = curl_multi_getcontent($ch[0]);
			write_log("Response: " . $response);
			$data = [
				'response' => $response,
				'device' => $ch[1]
			];
			array_push($responses, $data);
		}
	}

	if (count($responses)) foreach ($responses as $data) {
		$castContainer = $data['response'];
		$device = $data['device'];
		$token = $device['Token'];
		$server = $device['uri'];
		write_log("Raw response: " . json_encode($castContainer));
		if (trim($castContainer)) {
			$castDevices = flattenXML($castContainer)['Device'] ?? false;
			$epg = flattenXML($castContainer);
			$key = $epg['Directory'][1]['key'] ?? false;
			if ($key) {
				$device['key'] = $key;
				array_push($dvrs, $device);
			}
			if ($castDevices) {
				$hasPlugin = $_SESSION['hasPlugin'] ?? False;
				if (!$hasPlugin) updateUserPreference('hasPlugin', true);
				foreach ($castDevices as $castDevice) {
					if (isset($castDevice['name'])) {
						$type = $castDevice['type'];
						$type = ($type == 'audio' || $type == 'group' || $type == 'cast') ? 'Cast' : $type;
						$device = [
							'Name' => $castDevice['name'],
							'Id' => $castDevice['id'],
							'Product' => $type,
							'Type' => $castDevice['type'],
							'Token' => $token,
							'Parent' => $server,
							'Uri' => $castDevice['uri']
						];
						write_log("Pushing device: " . json_encode($device));
						array_push($clients, $device);
					}
				}
			}
		}
	}


	write_log("Found " . count($clients) . " local devices.", "INFO");
	write_log("Found " . count($dvrs) . " dvr devices.");

	if (count($clients) || count($dvrs)) {
		$returns = [
			'Client' => $clients,
			'Dvr' => $dvrs
		];
	} else $returns = false;

	write_log("Final returns: " . json_encode($returns));

	return $returns;
}

// Call this after changing the target server so that we have the section UUID's stored
function fetchSections() {
	$sections = [];
	$url = $_SESSION['plexServerUri'] . '/library/sections?X-Plex-Token=' . $_SESSION['plexServerToken'];
	$results = curlGet($url);
	if ($results) {
		$container = new SimpleXMLElement($results);
		if ($container) {
			foreach ($container->children() as $section) {
				array_push($sections, [
					"id" => (string)$section['key'],
					"uuid" => (string)$section['uuid'],
					"type" => (string)$section['type']
				]);
			}
		} else {
			write_log("Error retrieving section data!", "ERROR");
		}
	}
	if (count($sections)) $_SESSION['sections'] = $sections;
	return $sections;
}


// Fetch a transient token from our server, might be key to proxy/offsite playback
function fetchTransientToken() {
	$url = $_SESSION['plexServerUri'] . '/security/token?type=delegation&scope=all' . $_SESSION['plexHeader'];
	$result = curlGet($url);
	if ($result) {
		$container = new SimpleXMLElement($result);
		$ttoken = (string)$container['token'];
		if ($ttoken) {
			$_SESSION['transientToken'] = $ttoken;
			return $ttoken;
		} else {
			write_log("Error fetching transient token.", "ERROR");
		}
	}
	return false;
}


//
// ############# Media Find Functions ############
//


// Once we have parsed the play string and stripped out what we think are key terms,
//send it over here to figure out if we have media that matches the user's query.
function fetchInfo($matrix) {
	$episode = $epNum = $num = $preFilter = $season = $selector = $type = $winner = $year = false;
	$offset = 'foo';
	$title = $matrix['target'];
	unset($matrix['target']);
	$nums = $matrix['num'];
	$media = $matrix['media'];
	$type = $matrix['type'] ?? false;

	foreach ($matrix as $key => $mod) {
		if ($key == 'media') {
			foreach ($mod as $flag) {
				if (($flag == 'movie') || ($flag == 'show')) {
					$type = $flag;
				}
				if (($key = array_search($type, $media)) !== false) {
					unset($media[$key]);
				}
			}
		}
		if ($key == 'filter') {
			foreach ($mod as $flag) {
				if (($flag == 'movie') || ($flag == 'show')) {
					$type = $flag;
				}
				if (($key = array_search($type, $media)) !== false) {
					unset($media[$key]);
				}
			}
		}
		if ($key == 'preFilter') {
			$preFilter = $mod;
		}
	}
	$searchType = $type;
	$matchup = [];
	if ((count($media) == count($nums)) && (count($media))) {
		$matchup = array_combine($media, $nums);
	} else {
		if ($preFilter) $title = $preFilter;
	}

	if (count($matchup)) {
		foreach ($matchup as $key => $mod) {
			if (($key == 'offset') || ($key == 'hh') || ($key == 'mm') || ($key == 'ss')) {
				$offset = 0;
			}
		}
		foreach ($matchup as $key => $mod) {
			switch ($key) {
				case 'hh':
					$offset += $mod * 3600000;
					break;
				case 'mm':
					$offset += $mod * 60000;
					break;
				case 'ss':
					$offset += $mod * 1000;
					break;
				case 'offset':
					$offset = $mod;
					break;
				case 'season':
					$type = 'show';
					$season = $mod;
					break;
				case 'movie':
					$type = 'movie';
					break;
				case 'episode':
					$type = 'show';
					$episode = $mod;
					break;
				case 'year':
					$year = $mod;
					break;
			}
		}
	}

	if ($offset !== 'foo') write_log("Offset has been set to " . $offset, "INFO");

	checkString: {
		$winner = false;
		$results = plexSearch(strtolower($title), $type);
		if ($results) {
			if ((count($results) >= 2) && (count($matchup))) {
				foreach ($results as $result) {
					if ($year == $result['year']) {
						$result['searchType'] = $searchType;
						$results = [$result];
						break;
					}
				}
			}

			// If we have just one result, check to see if it's a show.
			if (count($results) == 1) {
				$winner = $results[0];
				if ($winner['type'] == 'show') {
					$showResult = $winner;
					$winner = false;
					$key = $showResult['key'];
					if (($season) || (($episode) && ($episode >= 1))) {
						if (($season) && ($episode)) {
							$selector = 'season';
							$num = $season;
							$epNum = $episode;
						}
						if (($season) && (!$episode)) {
							$selector = 'season';
							$num = $season;
							$epNum = false;
						}
						if ((!$season) && ($episode)) {
							$selector = 'episode';
							$num = $episode;
							$epNum = false;
						}
						write_log("Mods Found, fetching a numbered TV Item.", "INFO");
						if ($num && $selector) $winner = fetchNumberedTVItem($key, $num, $selector, $epNum);
					}
					if ($episode == -2) {
						write_log("Mods Found, fetching random episode.", "INFO");
						$winner = fetchRandomEpisode($key);
					}
					if ($episode == -1) {
						write_log("Mods Found, fetching latest/newest episode.", "INFO");
						$winner = fetchLatestEpisode($key);
					}
					if (!$winner) {
						write_log("Result: " . json_encode($showResult));
						write_log("No Mods Found, returning first on Deck Item.", "INFO");
						$onDeck = $showResult['OnDeck']['Video'];
						if ($onDeck) {
							$winner = $onDeck;
						} else {
							write_log("Show has no on deck items, fetching first episode.", "INFO");
							$winner = fetchFirstUnwatchedEpisode($key);
						}
					}
				}
				if ($winner['type'] === 'artist') {
					$queueId = queueMedia($winner, true);
					write_log("Queue ID is $queueId");
					$winner['queueID'] = $queueId;
				}
			}
		}
	}
	if ($winner) {
		// -1 is our magic key to tell it to just use whatever is there
		$winner['thumb'] = transcodeImage($winner['thumb']);
		$winner['art'] = transcodeImage($winner['art'] ?? $winner['thumb']);
		if (($offset !== 'foo') && ($offset != -1)) {
			$winner['viewOffset'] = $offset;
		}
		$final = [$winner];
		return $final;
	} else {
		$resultsOut = [];
		foreach ($results as $result) {
			$result['art'] = transcodeImage($result['art']);
			$result['thumb'] = transcodeImage($result['thumb']);
			array_push($resultsOut, $result);
		}
		return $resultsOut;
	}
}

function plexSearch($title, $type = false) {
	$castGenre = $hubs = $randomize = $results = false;
	write_log($type ? "Searching for the $type $title." : "Searching for $title.");
	$request = [
		'path' => '/hubs/search',
		'query' => [
			'query' => urlencode($title) . headerQuery(plexHeaders()),
			'limit' => '30',
			'X-Plex-Token' => $_SESSION['plexServerToken']
		]
	];
	$result = doRequest($request);

	if ($result) {
		$container = new SimpleXMLElement($result);
		if (isset($container->Hub)) {
			$castGenre = $results = [];
			foreach ($container->Hub as $Hub) {
				$check = $push = false;
				$size = $Hub['size'];
				$hubType = (string)trim($Hub['type']);
				if ($size != "0") {
					if ($type) {
						if (trim($type) === trim($hubType) || (trim($type) == 'episode' && trim($hubType) == 'show')) {
							$push = true;
						}
					} else {
						$push = ($hubType !== 'actor' && $hubType !== 'director');
					}
				}
				if (($hubType === 'actor' || $hubType === 'director' || $hubType === 'genre')) $check = true;
				if ($push || $check) {
					if ($push) write_log("Grabbing hub results for $hubType.");
					foreach ($Hub->children() as $Element) {
						$Element = flattenXML($Element);
						if ($push) array_push($results, $Element);
						if ($check) {
							$search = cleanCommandString($title);
							$mediaSearch = cleanCommandString($Element['tag']);
							if ($search === $mediaSearch) {
								write_log("$search is an exact match for cast or genre: " . json_encode($Element), "INFO");
								if ($hubType === 'genre') array_push($castGenre, fetchRandomMediaByKey($Element['key']));
								$randomize = true;
							}
						}
					}
				}
			}
		}
	}

	if (count($castGenre)) {
		write_log("Found matches for cast or genre, discarding other results.", "INFO");
		$results = [];

		foreach ($castGenre as $result) {
			if ($type) {
				if ($result['type'] === $type) array_push($results, $result);
			} else array_push($results, $result);
		}
	}

	$returns = [];
	if ($results) {
		foreach ($results as $item) {
			$cleaned = cleanCommandString($title);
			$cleanedTitle = cleanCommandString($item['title']);
			array_push($returns, $item);
			$match = compareTitles($cleaned,$cleanedTitle);
			if ($match) {
				write_log("Returning exact result: " . ucfirst($match));
				array_push($returns, $item);
				break;
			}
		}
	} else {
		write_log("No results found for query.", "INFO");
	}

	if ($randomize && count($returns) >= 2) {
		$size = count($returns) - 1;
		$random = rand(0, $size);
		$returns = [$returns[$random]];
	}

	foreach ($returns as &$item) {
		$thumb = $item['thumb'];
		$art = $item['art'];
		if (!isset($item['summary'])) {
			$extra = fetchMediaExtra($item['ratingKey']);
			if ($extra) $item['summary'] = $extra['summary'];
		}
		$item['art'] = $art;
		$item['thumb'] = $thumb;
		if ($item['type'] === 'artist') $item['key'] = str_replace("/children", "", $item['key']);
	}
	write_log("Return array: " . json_encode($returns), "INFO");
	$new = [];
	foreach($returns as $return) {
		$skip = false;
		foreach($new as $existing) {
			if ($return['ratingKey'] == $existing['ratingKey']) $skip = true;
		}
		if (!$skip) array_push($new,$return);
	}
	return $new;
}

function fetchHubList($section, $type = null) {
	$path = false;
	$query = [];
	if ($section == 'recent') {
		$path = '/hubs/home/recentlyAdded';
		$query['type'] = $type === 'show' ? '2' : '1';
	}
	if ($section == 'ondeck') $path = '/hubs/home/onDeck';
	if ($path) {
		$query = array_merge($query, [
			'X-Plex-Token' => $_SESSION['plexServerToken'],
			'X-Plex-Container-Start' => '0',
			'X-Plex-Container-Size' => $_SESSION['returnItems'] ?? '6'
		]);
		$result = doRequest([
			'path' => $path,
			'query' => $query
		]);
		if ($result) {
			$container = new SimpleXMLElement($result);
			if ($container) {
				$results = [];
				if (isset($container->Video)) {
					foreach ($container->Video as $video) {
						$item = json_decode(json_encode($video), true)['@attributes'];
						if ($item['type'] == 'episode') $item['title'] = $item['grandparentTitle'] . " - " . $item['title'];
						array_push($results, $item);
					}
				}
			}
		}
	}
	return empty($results) ? false : json_encode($results);
}

function fetchMediaExtra($ratingKey, $returnAll = false) {
	$result = doRequest(['path' => "/library/metadata/$ratingKey?X-Plex-Token=" . $_SESSION['plexServerToken']]);
	if ($result) {
		$extras = json_decode(json_encode(new SimpleXMLElement($result)), true);
		if ($returnAll) return $extras;
		$extra = $extras['Video']['@attributes'] ?? $extras['Directory']['@attributes'];
		return $extra;
	} else {
		write_log("No media extra found.", "WARN");
	}
	return false;
}

// Build a list of genres available to our user
// TODO
// Need to determine if this list is static, or changes depending on the collection
// If static, MAKE IT A STATIC LIST AND SAVE THE CALLS
function fetchAvailableGenres() {
	$genres = [];
	$result = doRequest(['path' => "library/sections?X-Plex-Token=" . $_SESSION['plexServerToken']]);
	if ($result) {
		$container = new SimpleXMLElement($result);
		foreach ($container->children() as $section) {
			$result = doRequest(['path' => '/library/sections/' . $section->Location['id'] . '/genre' . '?X-Plex-Token=' . $_SESSION['plexServerToken']]);
			if ($result) {
				$container = new SimpleXMLElement($result);
				if (isset($container->Directory)) {
					foreach ($container->Directory as $genre) {
						$genres[strtolower($genre['fastKey'])] = $genre['title'];
					}
				}
			}
		}
		if (count($genres)) {
			return $genres;
		}
	}
	return false;
}


// We should pass something here that will be a directory of shows or movies
function fetchRandomMediaByKey($key) {
	$winner = false;
	$result = doRequest([
		'path' => $key,
		'query' => '&limit=30&X-Plex-Token=' . $_SESSION['plexServerToken']
	]);
	if ($result) {
		$matches = [];
		$container = new SimpleXMLElement($result);
		foreach ($container->children() as $video) {
			array_push($matches, $video);
		}
		$size = sizeof($matches);
		if ($size > 0) {
			$winner = rand(0, $size);
			write_log("Selecting random item $winner / $size.", "INFO");
			$winner = $matches[$winner];
			if ($winner['type'] == 'show') {
				$winner = fetchFirstUnwatchedEpisode($winner['key']);
			}
		}
	}
	if ($winner) {
		$item = json_decode(json_encode($winner), true)['@attributes'];
		return $item;
	}
	return false;
}


function fetchRandomNewMedia($type) {
	$winner = false;
	$result = doRequest(['path' => '/library/recentlyAdded' . '?X-Plex-Token=' . $_SESSION['plexServerToken']]);
	if ($result) {
		$matches = [];
		$container = new SimpleXMLElement($result);
		foreach ($container->children() as $video) {
			if ($video['type'] == $type) {
				array_push($matches, $video);
			}
			if (($video['type'] == 'season') && ($type == 'show')) {
				array_push($matches, $video);
			}
		}
		$size = sizeof($matches);
		if ($size > 0) {
			$winner = rand(0, $size - 1);
			$winner = $matches[$winner];
			if ($winner['type'] == 'season') {
				$result = fetchFirstUnwatchedEpisode($winner['parentKey'] . '/children');
				$winner = $result;
			}
		} else {
			write_log("Can't seem to find any random " . $type . ".", "WARN");
		}
	}
	if ($winner) {
		$item = json_decode(json_encode($winner), true)['@attributes'];
		$item['thumb'] = $_SESSION['plexServerPublicUri'] . $winner['thumb'] . "?X-Plex-Token=" . $_SESSION['plexServerToken'];
		$item['art'] = $_SESSION['plexServerPublicUri'] . $winner['art'] . "?X-Plex-Token=" . $_SESSION['plexServerToken'];
		$winner = [$item];
	}
	return $winner;

}

// Music function(s):
function fetchTracks($ratingKey) {
	$playlist = $queue = false;
	$result = doRequest(['path' => '/library/metadata/' . $ratingKey . '/allLeaves?X-Plex-Token=' . $_SESSION['plexServerToken']]);
	$data = [];
	if ($result) {
		$container = new SimpleXMLElement($result);
		foreach ($container->children() as $track) {
			$trackJSON = json_decode(json_encode($track), true);
			if (isset($track['ratingCount'])) {
				if ($track['ratingCount'] >= 1700000) array_push($data, $trackJSON['@attributes']);
			}
		}
	}

	usort($data, "cmp");
	foreach ($data as $track) {
		if (!$queue) {
			$queue = queueMedia($track, true);
		} else {
			queueMedia($track, true, $queue);
		}
	}
	return $playlist;
}


// Compare the ratings of songs and make an ordered list
function cmp($a, $b) {
	if ($b['ratingCount'] == $a['ratingCount']) return 0;
	return $b['ratingCount'] > $a['ratingCount'] ? 1 : -1;

}

// TV Functions


function fetchFirstUnwatchedEpisode($key) {
	$mediaDir = preg_replace('/children$/', 'allLeaves', $key);
	$result = doRequest([
		'path' => $mediaDir,
		'query' => '?X-Plex-Token=' . $_SESSION['plexServerToken']
	]);
	if ($result) {
		$container = new SimpleXMLElement($result);
		foreach ($container->children() as $video) {
			if ($video['viewCount'] == 0) {
				$video['art'] = $container['art'];
				return $video;
			}
		}
		// If no unwatched episodes, return the first episode
		if (!empty($container->Video)) {
			return $container->Video[0];
		}
	}
	return false;
}


// We assume that people want to watch the latest unwatched episode of a show
// If there are no unwatched, we'll play the newest one
function fetchLatestEpisode($key) {
	$last = false;
	$mediaDir = preg_replace('/children$/', 'allLeaves', $key);
	$result = doRequest([
		'path' => $mediaDir,
		'query' => '?X-Plex-Token=' . $_SESSION['plexServerToken']
	]);
	if ($result) {
		$container = new SimpleXMLElement($result);
		if (isset($container->Video)) foreach ($container->Video as $episode) {
			$last = $episode;
		}
	}
	return $last;
}


function fetchRandomEpisode($showKey) {
	$results = false;
	$result = doRequest([
		'path' => preg_replace('/children/', 'allLeaves', $showKey),
		'query' => '?X-Plex-Token=' . $_SESSION['plexServerToken']
	]);
	if ($result) {
		$container = new SimpleXMLElement($result);
		$contArray = json_decode(json_encode($container), true);
		$parentArt = (string)$contArray['@attributes']['art'];

		if (isset($container->Video)) {
			$size = sizeof($container->Video);
			$winner = rand(0, $size - 1);
			$result = $container->Video[$winner];
			$result = json_decode(json_encode($result), true);
			$result['@attributes']['art'] = $parentArt;
			$results = $result['@attributes'];
			$results['@attributes'] = $result['@attributes'];
		}
	}
	return $results;
}


function fetchNumberedTVItem($seriesKey, $num, $selector, $epNum = null) {
	$match = false;
	write_log("Searching for " . $selector . " number " . $num . ($epNum != null ? ' and episode number ' . $epNum : ''), "INFO");
	$mediaDir = preg_replace('/children$/', 'allLeaves', $seriesKey);
	$result = doRequest([
		'path' => $mediaDir,
		'query' => '?X-Plex-Token=' . $_SESSION['plexServerToken']
	]);
	if ($result) {
		$container = new SimpleXMLElement($result);
		// If we're specifying a season, get all those episodes who's ParentIndex matches the season number specified
		if ($selector == "season") {
			foreach ($container as $ep) {
				$episode = json_decode(json_encode($ep), true)['@attributes'];
				if ($epNum) {
					if (($episode['parentIndex'] == $num) && ($episode['index'] == $epNum)) {
						$match = $episode;
						$match['art'] = $container['art'];
						break;
					}
				} else {
					if ($episode['parentIndex'] == $num) {
						$match['index'] = $episode['parentIndex'];
						$match['thumb'] = $episode['parentThumb'];
						$match['art'] = $container['art'];
						break;
					}
				}
			}
		} else {
			if (isset($container[intval($num) - 1])) {
				$match = $container[intval($num) - 1];
				$match['art'] = $container['art'];
			}
		}
	}
	write_log("Matching episode: " . json_encode($match), "INFO");
	return $match;
}


function fetchRandomMediaByGenre($fastKey, $type = false) {
	$result = doRequest(['path' => $fastKey . '&X-Plex-Token=' . $_SESSION['plexServerToken']]);
	if ($result) {
		$container = new SimpleXMLElement($result);
		$winners = [];
		foreach ($container->children() as $directory) {
			if (($directory['type'] == 'movie') && ($type != 'show')) {
				array_push($winners, $directory);
			}
			if (($directory['type'] == 'show') && ($type != 'movie')) {
				$media = fetchLatestEpisode($directory['title']);
				if ($media) array_push($winners, $media);
			}
		}
		$size = sizeof($winners);
		if ($size > 0) {
			$winner = rand(0, $size);
			$winner = $winners[$winner];
			write_log("Matching result: " . $winner['title'], "INFO");
			return $winner;
		}
	}
	return false;
}


function fetchRandomMediaByCast($actor, $type = 'movie') {
	$section = false;
	$result = doRequest([
		'path' => '/library/sections',
		'query' => '?X-Plex-Token=' . $_SESSION['plexServerToken']
	]);
	$actorKey = false;
	if ($result) {
		$container = new SimpleXMLElement($result);
		foreach ($container->children() as $directory) {
			if ($directory['type'] == $type) {
				$section = $directory->Location['id'];
				break;
			}
		}
	} else {
		write_log("Unable to list sections", "WARN");
		return false;
	}
	if ($section) {
		$result = doRequest([
			'path' => '/library/sections/' . $section . '/actor',
			'query' => '?X-Plex-Token=' . $_SESSION['plexServerToken']
		]);
	}
	if ($result) {
		$container = new SimpleXMLElement($result);
		foreach ($container->children() as $actors) {
			if ($actors['title'] == ucwords(trim($actor))) {
				$actorKey = $actors['fastKey'];
			}
		}
		if (!($actorKey)) {
			write_log("No actor key found, I should be done now.", "WARN");
			return false;
		}
	} else {
		write_log("No result found, I should be done now.", "WARN");
		return false;
	}

	$result = doRequest(['query' => $actorKey . '&X-Plex-Token=' . $_SESSION['plexServerToken']]);
	if ($result) {
		$matches = [];
		$container = new SimpleXMLElement($result);
		foreach ($container->children() as $video) {
			array_push($matches, $video);
		}
		$size = sizeof($matches);
		if ($size > 0) {
			$winner = rand(0, $size);
			$winner = $matches[$winner];
			write_log("Matching result: " . $winner['title'], "INFO");
			return $winner;
		}
	}
	return false;
}


// Send some stuff to a play queue
function queueMedia($media, $audio = false, $queueID = false, $shuffle = false, $returnQueue = false) {
	$key = $media['key'];
	$result = doRequest([
		'uri' => $_SESSION['plexServerUri'],
		'path' => $key,
		'query' => ['X-Plex-Token' => $_SESSION['plexServerToken']],
		'type' => 'get'
	]);
	if ($result) {
		$container = new SimpleXMLElement($result);
		$result = false;
		$media = $container;
	}
	if (isset($media['librarySectionUUID'])) {
		$uri = urlencode('library:///item/' . urlencode($key));
		$query = [
			'type' => ($audio ? 'audio' : 'video'),
			'uri' => $uri,
			'shuffle' => $shuffle ? '1' : '0',
			'repeat' => 0,
			'own' => 1,
			'includeChapters' => 1,
			'includeGeolocation'=>1,
			'X-Plex-Client-Identifier' => $_SESSION['plexClientId']
		];
		$headers = clientHeaders();
		# TODO: Validate that we should be queueing on the parent server, versus media server.
		$result = doRequest([
			'uri' => $_SESSION['plexServerUri'],
			'path' => '/playQueues' . ($queueID ? '/' . $queueID : ''),
			'query' => array_merge($query, plexHeaders()),
			'type' => 'post',
			'headers' => $headers
		]);
	}

	if ($result) {
		$container = new SimpleXMLElement($result);
		$container = json_decode(json_encode($container), true);
		if ($returnQueue) return $container;
		$queueID = $container['@attributes']['playQueueID'] ?? false;
	} else {
		write_log("Error fetching queue ID!", "ERROR");
	}
	return $queueID;
}

function queueAudio($media) {
	$array = $artistKey = $id = $response = $result = $song = $url = $uuid = false;
	$sections = fetchSections();
	foreach ($sections as $section) if ($section['type'] == "artist") $uuid = $section['uuid'];
	$ratingKey = (isset($media['ratingKey']) ? urlencode($media['ratingKey']) : false);
	$key = (isset($media['key']) ? urlencode($media['key']) : false);

	$type = $media['type'] ?? false;
	if (($key) && ($type) && ($uuid)) {
		$url = $_SESSION['plexServerUri'] . "/playQueues?type=audio&uri=library%3A%2F%2F" . $uuid . "%2F";
		switch ($type) {
			case 'album':
				$url .= "item%2F%252Flibrary%252Fmetadata%252F" . $ratingKey . "&shuffle=0";
				$artistKey = $media['parentRatingKey'];
				break;
			case 'artist':
				$url .= "item%2F%252Flibrary%252Fmetadata%252F" . $ratingKey . "&shuffle=0";
				$artistKey = $media['ratingKey'];
				break;
			case 'track':
				$artistKey = $media['grandparentRatingKey'];
				$url .= "directory%2F%252Fservices%252Fgracenote%252FsimilarPlaylist%253Fid%253D" . $ratingKey . "&shuffle=0";
				break;
			default:
				write_log("NOT A VALID AUDIO ITEM!", "ERROR");
				return false;
		}
	}

	if ($url) {
		$url .= "&repeat=0&includeChapters=1&includeRelated=1" . $_SESSION['plexHeader'];
		write_log("URL is " . protectURL(($url)));
		$result = curlPost($url);
	}

	if ($result) {
		$container = new SimpleXMLElement($result);
		$array = json_decode(json_encode($container), true);
		$id = $array['@attributes']['playQueueID'] ?? false;
		if (($id) && isset($_SESSION['queueID'])) {
			if ($id == $_SESSION['queueID']) {
				$url = $_SESSION['plexServerUri'] . '/player/playback/refreshPlayQueue?playQueueID=' . $id . '&X-Plex-Token=' . $_SESSION['plexServerToken'];
				curlGet($url);
			}
		}
	}
	if ($id) $_SESSION['queueID'] = $id;
	if (($id) && ($array)) {
		$song = $array['Track'][0]['@attributes'] ?? false;
	}
	if ($id && $song) {
		$response = $song;
		$response['queueID'] = '/playQueues/' . $id;
	}
	write_log("Final response: " . json_encode($response), "INFO");
	if ($artistKey) {
		$extraURL = $_SESSION['plexServerUri'] . '/library/metadata/' . $artistKey . "?X-Plex-Token=" . $_SESSION['plexServerToken'];
		$extra = curlGet($extraURL);
		if ($extra) {
			$extra = new SimpleXMLElement($extra);
			$extra = json_decode(json_encode($extra), true)['Directory']['@attributes'];
			$response['summary'] = $extra['summary'];
		}
	}
	return $response;
}

function playMedia($media) {
	write_log("Function fired!");
	if (isset($media['key'])) {
		$clientProduct = $_SESSION['plexClientProduct'];
		$result = ($clientProduct === "Cast") ? playMediaCast($media) : playMediaRelayed($media);
		fireHook($media['title'], "Play");
		write_log("Playback Result: " . json_encode($result), "INFO");
		return $result;
	} else {
		write_log("No media to play!!", "ERROR");
		$result['status'] = 'error';
		fireHook($media['title'], "Play");
		return $result;
	}
}


function playMediaRelayed($media) {
	write_log("Incoming media: ".json_encode($media));
	$playUrl = false;
	$server = parse_url($_SESSION['plexServerUri']);
	$serverProtocol = $server['scheme'];
	$serverIP = $server['host'];
	$serverPort = $server['port'];
	$serverID = $_SESSION['plexServerId'];
	$parent = $_SESSION['plexServerUri'];
	if (isset($_SESSION['plexClientParent'])) {
		if (trim($_SESSION['plexClientParent']) !== "") $parent = $_SESSION['plexClientParent'];
	}
	if ($parent == "no") $parent = $_SESSION['plexServerUri'];
	$uri = $_SESSION['plexClientParent'];

	foreach($_SESSION['deviceList']['Server'] as $server) {
		write_log("Comparing ".$server['Uri']." to $parent");
		if ($server['Uri'] == $uri) {
			$serverAddress = parse_url($uri);
			$serverProtocol = $serverAddress['scheme'];
			$serverIP = $serverAddress['host'];
			$serverPort = $serverAddress['port'];
			$serverID = $server['Id'];
			$data = [$serverProtocol,$serverIP,$serverPort,$serverID];
			write_log("GOT AN UPDATED SET OF VARS FOR OUR TARGET: ".json_encode($data));
			break;
		}
	}

	write_log("Relay target is $parent");
	$queueID = (isset($media['queueID']) ? $media['queueID'] : queueMedia($media));
	$isAudio = ($media['type'] == 'album' || $media['type'] == 'artist' || $media['type'] == 'track');
	$type = $isAudio ? 'music' : 'video';
	$key = urlencode($media['key']);
	$offset = ($media['viewOffset'] ?? 0);
	$commandId = $_SESSION['counter'];
	pollPlayer();
	if ($queueID) {
		write_log("Media has a queue ID, we're good.");
		$transientToken = fetchTransientToken();
		if ($transientToken) {
			$_SESSION['counter']++;
			$playUrl = "$parent/player/playback/playMedia" .
				"?type=$type" .
				"&containerKey=%2FplayQueues%2F$queueID%3Fown%3D1" .
				"&key=$key" .
				"&offset=$offset" .
				"&machineIdentifier=$serverID" .
				"&protocol=$serverProtocol" .
				"&address=$serverIP" .
				"&port=$serverPort" .
				"&token=$transientToken" .
				"&commandID=$commandId";
			//$headers = convertHeaders(clientHeaders());
			//write_log("Headers: " . json_encode($headers));
			$playUrl .= headerQuery(clientHeaders());
			write_log('Playback URL is ' . protectURL($playUrl));
			$result = curlGet($playUrl);
			$status = (((preg_match("/200/", $result) && (preg_match("/OK/", $result)))) ? 'success' : 'error');
		} else {
			$status = "ERROR: Could not fetch transient token.";
			write_log("Couldn't get a transient token!!", "ERROR");
		}
	} else {
		$status = "ERROR: Couldn't queue media.";
		write_log("Error queueing media!", "ERROR");
	}
	$return['url'] = $playUrl;
	$return['status'] = $status;
	return $return;
}


function playMediaCast($media) {
	write_log("Session vars: " . json_encode(sessionData()));
	//Set up our variables like a good boy
	$key = $media['key'];
	$serverId = $_SESSION['plexServerId'];
	$userName = $_SESSION['plexUserName'];
	$parent = $_SESSION['plexClientParent'] ?? $_SESSION['plexServerUri'];
	$token = $_SESSION['plexClientToken'] ?? $_SESSION['plexServerToken'];
	$transcoderVideo = ($media['type'] != 'track') ? "true" : "false";
	$isAudio = ($media['type'] == 'album' || $media['type'] == 'artist' || $media['type'] == 'track');
	$queueID = $media['queueID'] ?? queueMedia($media, $isAudio);
	$transientToken = fetchTransientToken();
	$client = parse_url($_SESSION['plexClientUri']);
	$count = $_SESSION['counter'];
	$headers = [
		'Uri' => $_SESSION['plexClientId'],
		'Requestid' => $count,
		'Contentid' => $key,
		'Contenttype' => $isAudio ? 'audio' : 'video',
		'Offset' => $media['viewOffset'] ?? 0,
		'Serverid' => $serverId,
		'Transcodervideo' => $transcoderVideo,
		'Serveruri' => $_SESSION['plexServerLocalUri'] ?? $_SESSION['plexServerUri'],
		'Username' => $userName,
		'Queueid' => $queueID,
		'Token' => $transientToken
	];
	$url = $parent . "/chromecast/play?X-Plex-Token=" . $token;
	$headers = headerRequestArray($headers);
	write_log("Header array: " . json_encode($headers));
	$response = curlGet($url, $headers);
	if ($response) {
		write_log("Response from cast playback command: " . $response);
		$return['status'] = 'success';
	} else {
		$return['status'] = 'error';
	}
	$return['url'] = 'chromecast://' . $client['host'] . ':' . $client['port'];
	return $return;

}


function castAudio($speech, $uri = false) {
	$path = TTS($speech);
	if (!$path) {
		write_log("No path, re-requesting speech clip.");
		$path = TTS($speech);
	}
	write_log("Getting ready to broadcast '$speech''.");
	if ($path) {
		$queryOut = [
			'initialCommand' => "Broadcast the message '$speech'.",
			'speech' => "Playing the clip at '$path'."
		];
		$endpoint = $uri ? "audio" : "broadcast";
		write_log("Sending cast $endpoint: '$speech'.", "INFO");
		$url = $_SESSION['plexServerUri'] . "/chromecast/$endpoint?X-Plex-Token=" . $_SESSION['plexServerToken'];
		$headers = ['Path' => $path];
		if ($uri) $headers['Uri'] = $uri;
		$header = headerRequestArray($headers);
		$result = curlGet($url, $header);
		if ($result) write_log("Sent a broadcast, baby!");
		logCommand(json_encode($queryOut));
	} else {
		write_log("Unable to retrieve audio clip!", "ERROR");
	}
	return $path;
}


function pollPlayer($wait=false) {
	$timeout = $wait ? 5 : 1;
	$status = ['status'=>'idle'];
	$serverUri = $_SESSION['plexClientParent'] ?? $_SESSION['plexServerUri'];
	$count = $_SESSION['counter'] ?? 1;
	$params = headerQuery(array_merge(plexHeaders(), clientHeaders()));
	$url = "$serverUri/player/timeline/poll?wait=1&commandID=$count$params";
	$result = doRequest($url, $timeout);
	if ($result) {
		$result = flattenXML(new SimpleXMLElement($result));
		if (isset($result['commandID'])) $_SESSION['counter'] = $result['commandID'] + 1;
		foreach ($result['Timeline'] as $timeline) {
			write_log("Timeline: ".json_encode($timeline),"INFO",false,true);
			if ($timeline['state'] !== "stopped") {
				$controls = $timeline['controllable'];
				$state = $timeline['state'];
				$volume = $timeline['volume'];
				$status = [
					'state' => $state,
					'controls' => $controls,
					'volume' => $volume
				];
			}
		}

	}
	return $status;
}


function playerStatus() {
	write_log("Function fired.");
	$addresses = parse_url($_SESSION['plexClientUri']);
	$clientIp = $addresses['host'] ?? false;
	$clientId = $_SESSION['plexClientId'] ?? false;
	$state = 'idle';
	$status = ['status'=>$state];
	$url = $_SESSION['plexServerUri'] . '/status/sessions?X-Plex-Token=' . $_SESSION['plexServerToken'];
	$result = curlGet($url);
	$_SESSION['sessionId'] = false;
	if ($result) {
		$jsonXML = new JsonXmlElement($result);
		$jsonXML = $jsonXML->asJson();
		$mc = $jsonXML['MediaContainer'] ?? false;
		if ($mc) {
			$track = $mc['Track'] ?? [];
			$video = $mc['Video'] ?? [];
			$obj = array_merge($track,$video);
			foreach($obj as $media) {
				// Get player info
				$player = $media['Player'][0] ?? $media['Player'];
				$playerIp = $player['address'];
				$playerId = $player['machineIdentifier'] ?? false;
				$isCast = (($clientIp && $playerIp) && ($clientIp == $playerIp));
				$isPlayer = (($clientId && $playerId) && ($clientId == $playerId));
				if ($isPlayer || $isCast) {
					$state = strtolower($player['state']);
					$time = $media['viewOffset'];
					$duration = $media['duration'];
					$type = $media['type'];
					$summary = $media['summary'] ?? $media['parentTitle'] ?? "";
					$title = $media['title'] ?? "";
					$year = $media['year'] ?? false;
					$tagline = $media['tagline'] ?? "";
					$parentTitle = $media['parentTitle'] ?? "";
					$grandParentTitle = $media['grandparentTitle'] ?? "";
					$parentIndex = $media['parentIndex'] ?? "";
					$index = $media['index'] ?? "";
					$thumb = (($media['type'] == 'movie') ? $media['thumb'] : $media['parentThumb']);
					$thumb = (string)transcodeImage($thumb);
					$art = transcodeImage($media['art'],"","",true);

					$mediaResult = [
						'title' => $title,
						'parentTitle' => $parentTitle,
						'grandParentTitle' => $grandParentTitle,
						'parentIndex' => $parentIndex,
						'index' => $index,
						'tagline' => $tagline,
						'duration' => $duration,
						'time' => $time,
						'summary' => $summary,
						'year' => $year,
						'art' => $art,
						'thumb' => $thumb,
						'type' => $type
					];

					$status = [
						'status' => $state,
						'time' => $time,
						'mediaResult' => $mediaResult,
						'volume' => 100
					];
				}
			}
		}
	}

	write_log("Status: ".json_encode($status));

	if ($_SESSION['playerStatus'] !== $status) {
		$_SESSION['stateChanged'] = true;
		$playerData = pollPlayer();
		if (isset($playerData['volume'])) $status['volume'] = $playerData['volume'];
	}

	return $status;
}

function sendCommand($cmd,$value=false) {
	if (preg_match("/stop/", $cmd)) fireHook(false, "Stop");
	if (preg_match("/pause/", $cmd)) fireHook(false, "Paused");
	if ($_SESSION['plexClientProduct'] === 'Cast') {
		$cmd = strtolower($cmd);
		$result = castCommand($cmd,$value);
	} else {
		//TODO: VOlume command for non-cast devices
		$url = $_SESSION['plexServerUri'] . '/player/playback/' . $cmd . '?type=video&commandID=' . $_SESSION['counter'] . headerQuery(array_merge(plexHeaders(), clientHeaders()));
		$result = doRequest($url);
		$_SESSION['counter']++;
	}
	return $result;
}

function playerCommand($url) {
	if (!(preg_match('/http/', $url))) $url = $_SESSION['plexClientUri'] . $url;
	$status = 'success';
	$_SESSION['counter']++;
	$url .= '&commandID=' . $_SESSION['counter'];
	$headers = clientHeaders();
	$container = curlPost($url, false, false, $headers);
	if (preg_match("/error/", $container)) {
		write_log('Request failed, HTTP status code: ' . $status, "ERROR");
		$status = 'error';
	} else {
		$status = 'success';
	}
	$return['url'] = $url;
	$return['status'] = $status;
	return $return;

}

function castCommand($cmd,$value=false) {
	// Set up our cast device
	if (preg_match("/volume/", $cmd)) {
		$value = $value ? $value : filter_var($cmd, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
		$cmd = "volume";
	}
	$valid = true;
	switch ($cmd) {
		case "play":
		case "pause":
		case "stepforward":
		case "stop":
		case "previous":
		case "skipforward":
		case "next":
		case "volume":
		case "voldown":
		case "volup":
		case "mute":
		case "unmute":
		case "seek":
			break;
		default:
			$return['status'] = 'error';
			$valid = false;

	}

	if ($valid) {
		write_log("Sending $cmd command");
		$url = $_SESSION['plexServerUri'] . "/chromecast/cmd?X-Plex-Token=" . $_SESSION['plexServerToken'];
		$value = $value ? $value : 100;
		$headers = [
			'Uri' => $_SESSION['plexClientId'],
			'Cmd' => $cmd,
			'Val' => $value
		];
		$header = headerRequestArray($headers);
		write_log("Headers: " . json_encode($headers));
		$result = curlGet($url, $header);
		if ($result) {
			$response = flattenXML($result);
			write_log("Got me some response! " . json_encode($response));
			if (isset($response['title2'])) {
				$data = base64_decode($response['title2']);
				write_log("Real data: ".$data);
			}
		}
		$return['url'] = "No URL";
		$return['status'] = 'success';
		return $return;
	}
	$return['status'] = 'error';
	return $return;
}

// Write and save some data to the webUI for us to parse
// IDK If we need this anymore
function metaTags() {
	$tags = '';
	$dvr = ($_SESSION['plexDvrUri'] ? "true" : "");
	$tags .= '<meta id="usernameData" data="' . $_SESSION['plexUserName'] . '"/>' . PHP_EOL .
		'<meta id="updateAvailable" data="' . $_SESSION['updateAvailable'] . '"/>' . PHP_EOL .
		'<meta id="deviceID" data="' . $_SESSION['deviceID'] . '"/>' . PHP_EOL .
		'<meta id="serverURI" data="' . $_SESSION['plexServerUri'] . '"/>' . PHP_EOL .
		'<meta id="clientURI" data="' . $_SESSION['plexClientUri'] . '"/>' . PHP_EOL .
		'<meta id="clientName" data="' . $_SESSION['plexClientName'] . '"/>' . PHP_EOL .
		'<meta id="plexDvr" data-enable="' . $dvr . '"/>' . PHP_EOL .
		'<meta id="rez" value="' . $_SESSION['plexDvrResolution'] . '"/>' . PHP_EOL;
	return $tags;
}

function fetchAirings($days) {
	write_log("Function fired: " . strtolower($days));
	$list = [];
	$enableSonarr = $_SESSION['sonarrEnabled'];
	$enableDvr = isset($_SESSION['plexDvrUri']);

	switch (strtolower($days)) {
		case 'tomorrow':
			$startDate = new DateTime ('tomorrow');
			$endDate = new DateTime('tomorrow +1 day');
			break;
		case 'weekend':
			$startDate = new DateTime('next Saturday');
			$endDate = new DateTime('next Monday');
			break;
		case 'monday':
		case 'tuesday':
		case 'wednesday':
		case 'thursday':
		case 'friday':
		case 'saturday':
		case 'sunday':
		case 'next monday':
		case 'next tuesday':
		case 'next wednesday':
		case 'next thursday':
		case 'next friday':
		case 'next saturday':
		case 'next sunday':
			write_log("This is for a weekday, foo: " . $days);
			$endDate = new DateTime($days . '+1 day');
			$startDate = new DateTime($days);
			break;
		case 'now':
		default:
			$startDate = new DateTime('today');
			$endDate = new DateTime('tomorrow');
			break;
	}
	$date2 = $endDate->format('Y-m-d');
	$date1 = $startDate->format('Y-m-d');
	write_log("StartDate: $date1 EndDate: " . $date2);

	if ($_SESSION['sickEnabled']) {
		write_log("Checking sickrage for episodes...");
		$sick = new SickRage($_SESSION['sickUri'], $_SESSION['sickToken']);
		$scheduled = json_decode($sick->future('date', 'today|soon'), true);
		if ($scheduled) {
			$shows = $shows2 = [];
			if (isset($scheduled['data']['soon'])) {
				$shows = $scheduled['data']['soon'];
			}
			if (isset($scheduled['data']['today'])) {
				$shows2 = $scheduled['data']['today'];
			}
			$shows = array_merge($shows, $shows2);
			if (is_array($shows)) {
				foreach ($shows as $show) {
					$airDate = DateTime::createFromFormat('Y-m-d', $show['airdate']);
					if ($airDate >= $startDate && $airDate <= $endDate) {
						$showName = $show['show_name'];
						$showName = preg_replace("/\ \(\d{4}\)/", "", $showName);
						$item = [
							'series' => $showName,
							'epnum' => $show['episode'],
							'seasonnum' => $show['season'],
							'summary' => $show['ep_plot']
						];
						write_log("Found a show on sick: " . json_encode($item), "INFO");
						array_push($list, $item);
					}
				}
			}
		}
	}

	if ($enableSonarr) {
		write_log("Checking Sonarr for episodes...");
		$sonarr = new Sonarr($_SESSION['sonarrUri'], $_SESSION['sonarrAuth']);
		$scheduled = json_decode($sonarr->getCalendar($date1, $date2), true);
		if ($scheduled) {
			foreach ($scheduled as $show) {
				$item = [
					'series' => $show['series']['title'],
					'epnum' => $show['episodeNumber'],
					'seasonnum' => $show['seasonNumber'],
					'summary' => $show['overview'] ?? $show['series']['overview']
				];
				write_log("Found a show on Sonarr: " . json_encode($item), "INFO");
				array_push($list, $item);
			}
		}
	}

	if ($enableDvr) {
		write_log("Checking DVR for episodes...");
		$scheduled = flattenXML(doRequest([
			'uri' => $_SESSION['plexDvrUri'],
			'path' => "/media/subscriptions/scheduled",
			'query' => "?X-Plex-Token=" . $_SESSION['plexDvrToken']
		], 5));
		if ($scheduled) {
			foreach ($scheduled['MediaGrabOperation'] as $showItem) {
				$isAiring = false;
				if ($showItem['status'] === 'scheduled') {
					$show = $showItem['Video'];
					if (!isset($show['Media']['beginsAt'])) {
						foreach ($show['Media'] as $airing) {
							$airingDate = $airing['beginsAt'];
							$airDate = new DateTime("@$airingDate");
							if ($airDate >= $startDate && $airDate <= $endDate) {
								$isAiring = true;
								break;
							}
						}
					} else {
						$date = $show['Media']['beginsAt'];
						$airDate = new DateTime("@$date");
						if ($airDate >= $startDate && $airDate <= $endDate) {
							$isAiring = true;
						}
					}
					if ($isAiring) {
						$item = [
							'series' => $show['grandparentTitle'],
							'epnum' => intval($show['index']),
							'seasonnum' => intval($show['parentIndex']),
							'summary' => $show['summary']
						];
						write_log("Found a show on Plex DVR: " . json_encode($item), "INFO");
						array_push($list, $item);
					}
				}
			}
		}
	}

	foreach ($list as &$item) {
		$data = fetchTMDBInfo($item['series']);
		$item = $data ? $data : $item;
		$item['thumb'] = $item['art'];
	}

	$list = (count($list) ? $list : false);
	write_log("List? " . json_encode($list));
	return $list;
}


/**
 * fetchMedia - Parse our API.ai response, determine if it's already in the library,
 * and send it to the appropriate fetcher if possible.
 *
 * Since we're now fetching information on the media in this method before sending to an app,
 * we only need the app's method to return an int representing a status of the search.
 *
 * This way, it's easier to format a response message that can be translated later.
 *
 * Possible returns from fetchers:
 *
 * INT_SUCCESS = 0 / The thing we want was added
 * INT_EXISTS = 1 / The thing was already there
 * INT_NOMATCH = 2 / Don't know what thing you're talking about
 * INT_ERROR = -1 / There was a problem searching
 * INT_NOFETCHERS = -2 / Nothing to search with for the request
 *
 * @param array $params
 * @return array|bool|mixed
 */


function fetchMedia(Array $params) {
	write_log("Function fired with params: ".json_encode($params));
	$type = $subtype = $season = $mod = $episode = $artist = $title = false;
	$data = [];
	// Iterate over params and set our data
	foreach($params as $key=>$value) {
		switch($key) {
			case "mediaType":
				$type = explode("-",$value[0])[0];
				$i = 0;
				foreach($value as $sub) {
					if (preg_match("/season/",$sub)) {
						if ($subtype !== "episode") $subtype = "season";
						$season = $params['number'][$i] ?? $params['ordinal'][$i] ?? $params['timeModifier'][$i];
					}
					if (preg_match("/episode/",$sub)) {
						$subtype = "episode";
						$episode = $params['number'][$i] ?? $params['ordinal'][$i] ?? $params['timeModifier'][$i];
					}
					if (preg_match("/artist/",$sub)) {
						if (($subtype !== "track") && ($subtype !== "album")) $subtype = "artist";
					}

					if (preg_match("/album/",$sub)) {
						if ($subtype !== "track") $subtype = "album";
						$subtype = "album";
						$mod = $params['number'][$i] ?? $params['ordinal'][$i] ?? $params['timeModifier'][$i];
					}
					if (preg_match("/track/",$sub)) {
						$subtype = "track";
						$mod = $params['number'][$i] ?? $params['ordinal'][$i] ?? $params['timeModifier'][$i];
					}
					if ($type === 'album') {
						$mod = $params['number'][$i] ?? $params['ordinal'][$i] ?? $params['timeModifier'][$i];
						write_log("Album, mod- $mod ");
					}
					$i ++;
				}
				break;
			case "title":
			case "command":
				$title = $value;
				break;
			case "music-artist":
				$artist = $value;
				break;
		}
	}

	if (($result) && isset($result['tvdbid'])) {
		$id = $result['tvdbid'];
		$show = $tmdbResult ? $tmdbResult : fetchTMDBInfo(false, false, $id, 'show');
		$show['type'] = 'show';
	} else {
		$status = 'ERROR: No results.';
	}

	if ((!$exists) && ($result) && ($id)) {
		if ($season && $episode) $status = 'skipped'; else $status = 'wanted';
		write_log("Show not in list, adding.");
		$result = $sick->showAddNew($id, null, 'en', null, $status, $_SESSION['sickProfile']);
		$responseJSON = json_decode($result, true);
		write_log('Fetch result: ' . $result);
		$status = strtoupper($responseJSON['result']) . ': ' . $responseJSON['message'];
	}

	if ($season && $id) {
		if ($episode) {
			write_log("Searching for season $season episode $episode of show with ID of $id");
			$result = $sick->episodeSearch($id, $season, $episode);
			$result2 = json_decode($sick->episodeSetStatus($id, $season, 'wanted', $episode, 1), true);
			if ($result2) {
				write_log("Episode search worked, result is " . json_encode($result2));
				$responseJSON = json_decode($result, true);
				if ($result2['result'] === 'success') {
					$show['year'] = explode("-", $responseJSON['data']['airdate'])[0];
					$show['subtitle'] = "S" . sprintf("%02d", $season) . "E" . sprintf("%02d", $episode) . " - " . $responseJSON['data']['name'];
					$show['summary'] = $responseJSON['data']['description'];
					write_log("Title appended to : " . $show['title']);
					$status = "SUCCESS: Episode added to searcher.";
				}
			}
		} else {
			$result2 = json_decode($sick->episodeSetStatus($id, $season, 'wanted', null, 1), true);
			$status = strtoupper($result2['result']) . ": " . $result2['message'];
			if ($result2['result'] === 'success') $show['subtitle'] = "Season " . sprintf("%02d", $season);

		}
		write_log("Result2: " . json_encode($result2));
	}

	if (!$season && $id && $episode) {
		write_log("Looking for episode number $episode.");
		$seasons = json_decode($sick->showSeasonList($id, 'asc'), true);
		write_log("Season List: " . json_encode($seasons));
		$i = $seasons['data'][0] ?? 0;
		$result = json_decode($sick->showSeasons($id), true);
		$f = 1;
		$epsList = [];
		$winner = false;
		foreach ($result['data'] as $seasonItem) {
			foreach ($seasonItem as $key => $episodeItem) {
				$episodeItem['season'] = $i;
				$episodeItem['episode'] = $key;
				$episodeItem['absNum'] = $f;
				$episodeItem['aired'] = 0;
				if ($episodeItem['airdate'] !== 'Never') $episodeItem['aired'] = new DateTime($episodeItem['airdate']) <= new DateTime("now");
				write_log("S$i E$key");
				if (intval($f) == intval($episode)) {
					write_log("Found matching number.");
					$winner = $episodeItem;
				}
				array_push($epsList, $episodeItem);
				if ($i) $f++;
			}
			$i++;
		}
		// Find the newest aired episode
		if (!$winner) {
			write_log("EpsList: " . json_encode($epsList));
			foreach (array_reverse($epsList) as $episodeItem) {
				if ($episodeItem['aired']) {
					$winner = $episodeItem;
					break;
				}

			}
		}
		write_log("Searching episode: " . json_encode($winner), "INFO");
		if ($winner) {
			$result = $sick->episodeSearch($id, $winner['season'], $winner['episode']);
			$result2 = json_decode($sick->episodeSetStatus($id, $winner['season'], 'wanted', $winner['episode'], 1), true);
			if ($result2) {
				write_log("Episode search worked, result is " . json_encode($result2));
				$responseJSON = json_decode($result, true);
				if ($result2['result'] === 'success') {
					$show['year'] = explode("-", $responseJSON['data']['airdate'])[0];
					$show['subtitle'] = "S" . sprintf("%02d", $winner['season']) . "E" . sprintf("%02d", $winner['episode']) . " - " . $responseJSON['data']['name'];
					$show['summary'] = $responseJSON['data']['description'];
					$status = "SUCCESS: Episode added to searcher.";
				}
			}
		}
		write_log("Show result: " . json_encode($winner));
	}

	$response['status'] = $status;
	$response['mediaResult'] = $show;
	$response['mediaResult']['type'] = 'tv';
	return $response;
}

function fetchTMDBInfo($title = false, $tmdbId = false, $tvdbId = false, $type = false) {
	$response = false;
	if ($type == 'show') $type = 'tv';
	$url = 'https://api.themoviedb.org/3';
	$d = fetchDirectory(1);
	if ($title) {
		$search = $url . '/search/' . ($type ? $type : 'multi') . '?query=' . urlencode($title) . '&api_key=' . $d . '&page=1';
		$results = json_decode(doRequest($search), true);
		write_log("Result array: " . json_encode($results));
		$score = $_SESSION['searchAccuracy'] * .01;
		$winner = [];
		foreach ($results['results'] as $result) {
			$resultTitle = $result['title'] ?? $result['name'];
			$newScore = similarity(cleanCommandString($resultTitle), cleanCommandString($title));
			$resultType = $result['media_type'];
			write_log("$score vs $newScore for $resultTitle");
			if (($newScore > $score) && ((($resultType === "movie") || ($resultType === "tv")) || ($type))) {
				write_log("This matches: " . $result['title'] . " Score: $newScore.");
				write_log("JSON: " . json_encode($result));
				$winner = $result;
				if ($newScore == 1) break;
			}
		}
		if (empty($winner) && count($results['results'])) $winner = $results[0];
		write_log("Winner? " . json_encode($winner));
		$type = $type ? $type : $winner['media_type'] ?? false;
		$tmdbId = $winner['id'] ?? false;
	}
	if ($tmdbId) {
		$url .= '/' . ($type ? $type : 'movie') . '/' . $tmdbId . '?api_key=' . $d;
		$result = json_decode(doRequest($url), true);
		if (isset($result['overview'])) {
			$year = explode("-", $result['release_date'] ?? $result['first_air_date'])[0];
			$artPath = $result['backdrop_path'] !== null ? 'https://image.tmdb.org/t/p/original' . $result['backdrop_path'] : false;
			$thumbPath = $result['poster_path'] !== null ? 'https://image.tmdb.org/t/p/original' . $result['poster_path'] : false;
			$response = [
				'title' => $result['title'] ?? $result['name'],
				'year' => $year,
				'type' => $type,
				'id' => $tmdbId,
				'summary' => $result['overview'],
				'tagline' => $result['tagline'] ?? $year . " - " . $result['status']
			];
			if ($artPath) $response['art'] = $artPath;
			if ($thumbPath) $response['thumb'] = $thumbPath;
			if ($type == 'tv') $type = 'show';
			if ($type) $response['type'] = $type;
		}
	}
	if ($tvdbId) {
		$url .= '/find/' . $tvdbId . '?api_key=' . $d . '&external_source=tvdb_id';
		$result = json_decode(doRequest($url), true)['tv_results'][0];
		if (isset($result['overview'])) {
			$year = explode("-", $result['first_air_date'])[0];
			$response = [
				'title' => $result['title'] ?? $result['name'],
				'year' => $year,
				'summary' => $result['overview'],
				'type' => $type,
				'id' => $result['id'] ?? $tvdbId,
				'tagline' => $result['tagline'] ?? $year . " - " . $result['origin_country'][0],
				'art' => 'https://image.tmdb.org/t/p/original' . $result['backdrop_path'],
				'thumb' => 'https://image.tmdb.org/t/p/original' . $result['poster_path']
			];
			if ($type == 'tv') $type = 'show';
			if ($type) $response['type'] = $type;
		}
	}
	write_log("Response: " . json_encode($response));
	return $response;
}


function returnSpeech($speech, $contextName, $cards = false, $waitForResponse = false, $suggestions = false) {
	write_log("My reply is going to be '$speech'.", "INFO");
	if (isset($_GET['say'])) return;
	if ($_SESSION['amazonRequest']) {
		returnAlexaSpeech($speech, $contextName, $cards, $waitForResponse, $suggestions);
	} else {
		returnAssistantSpeech($speech, $contextName, $cards, $waitForResponse, $suggestions);
	}
	if (function_exists('fastcgi_finish_request')) {
		fastcgi_finish_request();
	}
}

// #TODO: API.ai now has a V2 api, we should probably update it...
// APIAI ITEMS
// Put our calls to API.ai here
// #######################################################################
// Push API.ai bot to other's account.  This can go after Google approval

function queryApiAi($command) {
	$_SESSION['counter2'] = (isset($_SESSION['counter2']) ? $_SESSION['counter2']++ : 0);
	$d = fetchDirectory(3);
	try {
		$lang = getDefaultLocale();
		$url = 'https://api.api.ai/v1/query?v=20150910&query=' . urlencode($command) . '&lang=' . $lang . '&sessionId=' . substr($_SESSION['apiToken'], 0, 36);
		$response = curlGet($url, ['Authorization: Bearer ' . $d], 3);
		if ($response == null) {
			write_log("Null response received from API.ai, re-submitting.", "WARN");
			$response = curlGet($url, ['Authorization: Bearer ' . $d], 10);
		}
		$request = json_decode($response, true);
		$request = array_filter_recursive($request);
		$request['originalRequest']['data']['inputs'][0]['raw_inputs'][0]['query'] = $request['result']['resolvedQuery'];
		return $request;
	} catch (Exception $e) {
		write_log("An exception has occurred: " . $e, "ERROR");
		return false;
	}
}

function returnAssistantSpeech($speech, $contextName, $cards, $waitForResponse, $suggestions) {
	if (count($cards)) write_log("Card array: " . json_encode($cards));
	header('Content-Type: application/json');
	ob_start();
	$items = $richResponse = $sugs = [];
	if (!trim($speech)) $speech = "There was an error building this speech response, please inform the developer.";
	$output["speech"] = $speech;
	$output["contextOut"][0] = [
		"name" => $contextName,
		"lifespan" => 2,
		"parameters" => []
	];
	$output["data"]["google"]["expectUserResponse"] = boolval($waitForResponse);
	$output["data"]["google"]["isSsml"] = false;
	$output["data"]["google"]["noInputPrompts"] = [];
	$items[0] = [
		'simpleResponse' => [
			'textToSpeech' => $speech,
			'displayText' => $speech
		]
	];

	if (is_array($cards)) {
		$count = count($cards);
		if ($count == 1) {
			$cardTitle = $cards[0]['title'];
			$cards[0]['image']['accessibilityText'] = "Image for $cardTitle.";
			if (preg_match("/https/", $cards[0]['image']['url'])) {
				array_push($items, ['basicCard' => $cards[0]]);
			} else {
				write_log("Not displaying card for $cardTitle because image is not https.", "INFO");
			}
		} else {
			if ($count >= 2 && $count <= 29) {
				$carousel = [];
				foreach ($cards as $card) {
					$cardTitle = $card['title'];
					$item = [];
					$img = $card['image']['url'];
					if (!(preg_match("/http/", $card['image']['url']))) $img = transcodeImage($card['image']['url']);
					if (preg_match("/https/", $img)) {
						$item['image']['url'] = $img;
						$item['image']['accessibilityText'] = $card['title'];
						$item['title'] = substr($card['title'], 0, 25);
						$item['description'] = $card['description'];
						$item['optionInfo']['key'] = 'play ' . $card['title'];
						array_push($carousel, $item);
					} else {
						write_log("Not displaying card for $cardTitle because image is not https.", "INFO");
					}
				}
				$output['data']['google']['systemIntent']['intent'] = 'actions.intent.OPTION';
				$output['data']['google']['systemIntent']['data']['@type'] = 'type.googleapis.com/google.actions.v2.OptionValueSpec';
				$output['data']['google']['systemIntent']['data']['listSelect']['items'] = $carousel;
				$output['data']['google']['expectedInputs'][0]['possibleIntents'][0]['inputValueData']['@type'] = "type.googleapis.com/google.actions.v2.OptionValueSpec";
				$output['data']['google']['expectedInputs'][0]['possibleIntents'][0]['intent'] = "actions.intent.OPTION";
			}
		}
	}

	$output['data']['google']['richResponse']['items'] = $items;

	if (is_array($suggestions)) {
		$sugs = [];
		foreach ($suggestions as $suggestion) {
			array_push($sugs, ["title" => $suggestion]);
		}
	}

	$output['data']['google']['richResponse']['suggestions'] = $sugs;

	ob_end_clean();
	echo json_encode($output);
	write_log("JSON out is " . json_encode($output));
}

function returnAlexaSpeech($speech, $contextName, $cards, $waitForResponse, $suggestions) {
	write_log("Function fired!");
	ob_start();
	$endSession = !$waitForResponse;
	write_log("ContextName, Suggestions: $contextName $suggestions");
	write_log("I " . ($endSession ? "should " : "shouldn't ") . "end the session.");
	$response = [
		"version" => "1.0",
		"response" => [
			"outputSpeech" => [
				"type" => "PlainText",
				"text" => $speech
			]
		],
		"reprompt" => [
			"outputSpeech" => [
				"type" => "PlainText",
				"text" => "I'm sorry, I didn't catch that."
			]
		]
	];
	if ($cards) {
		$cardTitle = $cards[0]['title'];
		if (preg_match('/https/', $cards[0]['image']['url'])) {
			$response['response']['card'] = [
				"type" => "Standard",
				"title" => $cardTitle,
				"text" => $cards[0]['summary'] ?? $cards[0]['formattedText'] ?? $cards[0]['description'] ?? $cards[0]['tagline'] ?? $cards[0]['subtitle'] ?? '',
				"image" => [
					"smallImageUrl" => $cards[0]['image']['url'],
					"largeImageUrl" => $cards[0]['image']['url']
				]
			];
		} else {
			write_log("Not displaying card for $cardTitle because image is not https.", "INFO");
		}
	}
	$response['originalRequest'] = $_SESSION['lastRequest'];
	$response['shouldEndSession'] = $endSession;
	ob_end_clean();
	echo json_encode($response);
	write_log("JSON out is " . json_encode($response));
}


// Register our server with the mothership and link google account
function registerServer() {
	$_SESSION['publicAddress'] = $_SESSION['appAddress'] ?? $_SESSION['publicAddress'];
	$registerUrl = "https://phlexserver.cookiehigh.us/api.php" . "?apiToken=" . $_SESSION['apiToken'] . "&serverAddress=" . htmlentities($_SESSION['publicAddress']);
	write_log("registerServer: URL is " . protectURL($registerUrl), 'INFO');
	$result = curlGet($registerUrl);
	if ($result == "OK") {
		write_log("Successfully registered with server.", "INFO");
	} else {
		write_log("Server registration failed.  Response: " . $result, "ERROR");
	}
}


function fireHook($param = false, $type = false) {
	if ($_SESSION['hookEnabled'] == "true") {
		write_log("Webhooks are enabled.", "INFO");
		if ($type && ($_SESSION['hookSplit'] == "true")) {
			$url = $_SESSION['hook' . $type . 'Url'];
		} else {
			$url = $_SESSION['hookUrl'];
		}
		if (($url) && ($url !== "")) {
			if ($type) {
				if ($param) {
					$url .= "?value1=" . urlencode($param);
				}
				$url .= "&value2=" . $type;
			} else {
				if ($param) $url .= "?value1=" . urlencode($param);
			}
			write_log("Final hook URL: " . $url);
			$result = curlGet($url);
			write_log("Hook result: " . $result);
		} else {
			write_log("ERROR, no URL!", "ERROR");
		}
	}
}




