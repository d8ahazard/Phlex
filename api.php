<?php
require_once dirname(__FILE__) . '/vendor/autoload.php';
require_once dirname(__FILE__) . '/webApp.php';
require_once dirname(__FILE__) . '/util.php';
require_once dirname(__FILE__) . '/body.php';

use digitalhigh\Radarr\Radarr;
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

if (isset($_SESSION['apiToken'])) {
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
		$result = fetchUiData();
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
		$result['status'] = testConnection($_GET['test']);
		header('Content-Type: application/json');
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
		echo json_encode(popCommand($_GET['card']));
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
		echo json_encode(setSelectedDevice($type, $id));
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
		echo $valid;
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

	// This tells the api to parse our command with the plex "play" parser
	if (isset($_GET['play'])) {
		if (isset($_GET['command'])) {
			$command = cleanCommandString($_GET['command']);
			write_log('Got a request to play ' . $command, "INFO");
			$resultArray = parsePlayCommand($command);
			$queryOut = [];
			$queryOut['initialCommand'] = $command;
			$queryOut['parsedCommand'] = $command;
			if ($resultArray) {
				$result = $resultArray[0];
				$queryOut['mediaResult'] = $result;
				$playResult = playMedia($result);
				$searchType = $result['searchType'];
				$type = (($searchType == '') ? $result['type'] : $searchType);
				$queryOut['parsedCommand'] = 'Play the ' . $type . ' named ' . $command . '.';
				$queryOut['playResult'] = $playResult;

				if ($queryOut['mediaResult']['exact'] == 1) {
					$queryOut['mediaStatus'] = "SUCCESS: Exact match found.";
				} else {
					$queryOut['mediaStatus'] = "SUCCESS: Approximate match found.";
				}
			} else {
				$queryOut['mediaStatus'] = 'ERROR: No results found';
			}
			$queryOut['timestamp'] = timeStamp();
			$queryOut['serverURI'] = $_SESSION['plexServerUri'];
			$queryOut['serverToken'] = $_SESSION['plexServerToken'];
			$queryOut['clientURI'] = $_SESSION['plexClientUri'];
			$queryOut['clientName'] = $_SESSION['plexClientName'];
			$queryOut['commandType'] = 'play';
			$result = json_encode($queryOut);
			header('Content-Type: application/json');
			logCommand($result);
			echo $result;
			bye();
		}
	}


	// This tells the api to parse our command with the plex "control" parser
	if (isset($_GET['control'])) {
		if (isset($_GET['command'])) {
			$command = cleanCommandString($_GET['command']);
			write_log('Got a control request: ' . $command, "INFO");
			$result = parseControlCommand($command);
			$newCommand = json_decode($result, true);
			$newCommand['timestamp'] = timeStamp();
			$result = json_encode($newCommand);
			header('Content-Type: application/json');
			if (!isset($_GET['noLog'])) {
				logCommand($result);
				echo $result;
			}
			bye();
		}
	}

	// This tells the api to parse our command with the "fetch" parser
	if (isset($_GET['fetch'])) {
		if (isset($_GET['command'])) {
			$command = cleanCommandString($_GET['command']);
			write_log('Got a fetch request: ' . $command, "INFO");
			$result = parseFetchCommand($command);
			$result['commandType'] = 'fetch';
			$result['timestamp'] = timeStamp();
			logCommand(json_encode($result));
			header('Content-Type: application/json');
			echo json_encode($result);
			bye();
		}
	}
}

function fetchUiData() {
	$result['playerStatus'] = playerStatus();
	$contents = fetchCommands();
	$result['commands'] = urlencode(($contents));
	$result['devices'] = scanDevices(false);
	$result['dologout'] = $_SESSION['dologout'];
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

function parseFetchCommand($command, $type = false) {
	fireHook($command, "Fetch");
	$resultOut = [];
	$episode = $remove = $season = $tmdbResult = $useNext = false;
	//Sanitize our string and try to rule out synonyms for commands
	$result['initialCommand'] = $command;
	$command = translateControl(strtolower($command), $_SESSION['lang']['fetchSynonymsArray']);
	$commandArray = explode(' ', strtolower($command));
	if (arrayContains('movie', $commandArray)) {
		$commandArray = array_diff($commandArray, ['movie']);
		$command = implode(" ", $commandArray);
		$type = 'movie';
	}
	if (arrayContains('show', $commandArray)) {
		$commandArray = array_diff($commandArray, ['show']);
		$command = implode(" ", $commandArray);
		$type = 'show';
	}
	if (arrayContains('season', $commandArray)) {
		foreach ($commandArray as $word) {
			if ($useNext) {
				$season = intVal($word);
				break;
			}
			if ($word == 'season') {
				$useNext = true;
			}
		}
		if ($season) {
			$type = 'show';
			$commandArray = array_diff($commandArray, [
				'season',
				$season
			]);
			$command = implode(" ", $commandArray);
		}
	}
	$useNext = false;
	if (arrayContains('episode', $commandArray)) {
		foreach ($commandArray as $word) {
			if ($useNext) {
				$episode = intVal($word);
				break;
			}
			if ($word == 'episode') {
				$useNext = true;
			}
			if (($word == 'latest')) {
				$remove = $word;
				$episode = -1;
				break;
			}
		}
		if ($episode) {
			$type = 'show';
			$commandArray = array_diff($commandArray, [
				'episode',
				$episode
			]);
			if (($episode == -1) && ($remove)) $commandArray = array_diff($commandArray, [
				'episode',
				$remove
			]);
			$command = implode(" ", $commandArray);
		}
	}

	write_log("No type specified, let's ask the internet.", "INFO");
	$tmdbResult = fetchTMDBInfo($command);
	if ($tmdbResult) $type = $tmdbResult['type'] ?? false;
	if (!$type) $resultOut['parsedCommand'] = 'Fetch the first movie or show named ' . implode(" ", $commandArray);


	switch ($type) {
		case 'show':
			write_log("Searching explicitely for a show.", "INFO");
			if ($_SESSION['sonarrEnabled'] || $_SESSION['sickEnabled']) {
				$result = downloadSeries(implode(" ", $commandArray), $season, $episode, $tmdbResult);
				$resultTitle = $result['mediaResult']['title'];
				$resultOut['parsedCommand'] = 'Fetch ' . ($season ? 'Season ' . $season . ' of ' : '') . ($episode ? 'Episode ' . $episode . ' of ' : '') . 'the show named ' . $resultTitle;
				write_log("Result " . json_encode($result));

			} else {
				$result['status'] = 'ERROR: No fetcher configured for ' . $type . '.';
				write_log($result['status'], "WARN");
			}
			break;
		case 'movie':
			write_log("Searching explicitely for a movie.", "INFO");
			if (($_SESSION['couchEnabled']) || ($_SESSION['ombiEnabled']) || ($_SESSION['radarrEnabled'])) {
				$result = downloadMovie(implode(" ", $commandArray), $tmdbResult);
			} else {
				$result['status'] = 'ERROR: No fetcher configured for ' . $type . '.';
				write_log($result['status'], "WARN");
			}
			break;
		default:

			if (($_SESSION['couchEnabled']) || ($_SESSION['radarrEnabled'])) {
				write_log("Searching for first media matching title, starting with movies.");
				$result = downloadMovie(implode(" ", $commandArray), $tmdbResult);
			}

			if ((preg_match("/ERROR/", $result['status'])) && (($_SESSION['sonarrEnabled']) || ($_SESSION['sickEnabled']))) {
				$result = downloadSeries(implode(" ", $commandArray), $tmdbResult);
				break;
			}
			if (preg_match("/ERROR/", $result['status'])) {
				$result['status'] = 'ERROR: No results found or no fetcher configured.';
				write_log($result['status'], "WARN");
			}
			break;
	}
	$result['mediaStatus'] = $result['status'];
	$result['parsedCommand'] = $resultOut['parsedCommand'];
	$result['initialCommand'] = $command;
	return $result;
}

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

function parseControlCommand($command) {
	//Sanitize our string and try to rule out synonyms for commands
	$synonyms = $_SESSION['lang']['commandSynonymsArray'];
	$queryOut['initialCommand'] = $command;
	$command = translateControl(strtolower($command), $synonyms);
	$cmd = false;
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
		"volume"
	];
	if (strpos($command, "volume")) {
		$int = filter_var($command, FILTER_SANITIZE_NUMBER_INT);
		$queryOut['parsedCommand'] .= "Set the volume to " . $int . " percent.";
		$cmd = 'setParameters?volume=' . $int;
	}

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
		$cmd = 'setStreams?subtitleStreamID=' . $streamID;
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
		$result = sendCommand($cmd);
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
		unset($mods['num'][count($mods['num']) - 1]);
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
	$inputs = ['originalRequest']['data']['inputs'];
	foreach ($inputs as $input) {
		if ($input['intent'] == 'actions.intent.OPTION') {
			$action = 'playfromlist';
			$command = $rawspeech;
		}
	}
	foreach ($contexts as $context) {
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

	if (($action == 'fetch') && ($command)) {
		$queryOut['parsedCommand'] = 'Fetch the media named ' . $command . '.';
		$result = parseFetchCommand($command, $type);
		$media = $result['mediaResult'];
		$stats = explode(":", $result['status']);
		write_log("Fetch Result: " . json_encode($result), "INFO");
		if ($stats[0] === 'SUCCESS') {
			$queryOut['mediaResult'] = $media;
			$resultTitle = $media['title'] ?? $media['@attributes']['title'];
			$resultYear = $media['year'];
			$resultImage = $media['art'];
			$resultSummary = $media['summary'];
			$resultSubtitle = $media['subtitle'] ?? $media['tagline'];
			if (isset($media['type']) && isset($media['subtitle'])) {
				$itemString = $resultTitle . " " . $media['subtitle'];
			} else {
				$itemString = $resultTitle . " (" . $resultYear . ")";
			}
			$resultData['image'] = $resultImage;
			if (preg_match("/Already/", $stats[1])) {
				$speech = buildSpeech($_SESSION['lang']['speechDownloadExists1'], $resultTitle, $_SESSION['lang']['speechDownloadExists2']);
			} else {
				$speech = buildSpeech($_SESSION['lang']['speechDownloadAdded1'], $itemString, $_SESSION['lang']['speechDownloadAdded2']);
			}
			$card = [
				[
					"title" => $resultTitle . " (" . $resultYear . ")",
					"subtitle" => $resultSubtitle,
					"formattedText" => $resultSummary,
					'image' => ['url' => $resultImage]
				]
			];
			returnSpeech($speech, $contextName, $card);
			$queryOut['mediaStatus'] = $result['status'];
			$queryOut['card'] = $card;
			$queryOut['speech'] = $speech;
			logCommand(json_encode($queryOut));
			bye();
		} else {
			$errors = $_SESSION['lang']['speechDownloadErrorArray'];
			$speech = $errors[array_rand($errors)];
			returnSpeech($speech, $contextName);
			$queryOut['mediaStatus'] = $result['status'];
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
					$backup = $connection['protocol'] . "://" . $connection['address'] . ":" . $connection['port'] . $query;
					$local = (boolval($connection['local'] == boolval($server['publicAddressMatches'])));
					$web = filter_var($connection['address'], FILTER_VALIDATE_IP, FILTER_FLAG_NO_RES_RANGE);
					$secure = (($server['httpsRequired'] && $connection['protocol'] === 'https') || (!$server['httpsRequired']));
					$cloud = preg_match("/plex.services/", $connection['address']);
					write_log("This " . ($cloud ? "is " : "is not") . " a cloud server.");
					if (($local && $web && $secure) || $cloud) {
						write_log("Connection on $name is acceptable, checking.", "INFO");
						foreach ([
							         $uri,
							         $backup
						         ] as $url) {
							if (check_url($url)) {
								$server['uri'] = $connection['uri'];
								break;
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
				$host = explode(":", $uri)[0] ?? "nohost";
				$exHost = explode(":", $exUri)[0] ?? "nohost2";
				write_log("Comparing $host to $exHost and $id to $exId");
				if ((($exHost === $host) || ($exId === $id)) && $device['Type'] !== 'group') {
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
						'Key' => $device['key'] ?? False
					];
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
			if ($cleaned === $cleanedTitle || preg_match("/$cleaned/", $cleanedTitle)) {
				write_log("Returning exact result: " . $item['title']);
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
	return array_unique($returns);
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
		$uri = 'library://' . $media['librarySectionUUID'] . '/item/' . urlencode($key);
		$query = [
			'type' => ($audio ? 'audio' : 'video'),
			'uri' => $uri,
			'shuffle' => $shuffle ? '1' : '0',
			'repeat' => 0,
			'includeChapters' => 1,
			'own' => 1,
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
	$playUrl = false;
	$server = parse_url($_SESSION['plexServerUri']);
	$serverProtocol = $server['scheme'];
	$serverIP = $server['host'];
	$serverPort = $server['port'];
	$serverID = $_SESSION['plexServerId'];
	$parent = $_SESSION['plexClientParent'] ?? $_SESSION['plexServerUri'];
	write_log("Relay target is $parent");
	$queueID = (isset($media['queueID']) ? $media['queueID'] : queueMedia($media));
	$isAudio = ($media['type'] == 'album' || $media['type'] == 'artist' || $media['type'] == 'track');
	$type = $isAudio ? 'audio' : 'video';
	$key = urlencode($media['key']);
	$offset = ($media['viewOffset'] ?? 0);
	$commandId = $_SESSION['counter'];
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
		'Contenttype' => $isAudio ? 'music' : 'video',
		'Offset' => $media['viewOffset'] ?? 0,
		'Serverid' => $serverId,
		'Transcodervideo' => $transcoderVideo,
		'Serveruri' => $_SESSION['plexServerUri'],
		'Username' => $userName,
		'Queueid' => $queueID,
		'Token' => $transientToken
	];
	$url = $parent . "/chromecast/play?X-Plex-Token=" . $token;
	$headers = headerRequestArray($headers);
	write_log("Header array: " . json_encode($headers));
	$response = curlGet($url, $headers);
	if ($response) {
		write_log("Response from cast playback command: " . json_encode(flattenXML($response)));
		$return['status'] = 'success';
	} else {
		$return['status'] = 'error';
	}
	$return['url'] = 'chromecast://' . $client['host'] . ':' . $client['port'];
	return $return;

}


function castAudio($speech, $uri = false) {
	$path = TTS($speech);
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


function pollPlayer() {
	$pp = false;
	if (isset($_GET['pollPlayer'])) {
		unset($_GET['pollPlayer']);
		$pp = true;
	}
	$serverUri = $_SESSION['plexClientParent'] ?? $_SESSION['plexServerUri'];
	$count = $_SESSION['counter'] ?? 1;
	$params = headerQuery(array_merge(plexHeaders(), clientHeaders()));
	$url = "$serverUri/player/timeline/poll?wait=1&commandID=$count$params";
	$result = doRequest($url);
	if ($result) {
		write_log("State changed, polling player.");

		$result = flattenXML(new SimpleXMLElement($result));
		if (isset($result['commandID'])) $_SESSION['counter'] = $result['commandID'] + 1;
		foreach ($result['Timeline'] as $timeline) {
			if ($timeline['state'] !== "stopped") {
				$controls = $timeline['controllable'];
				$state = $timeline['state'];
				$volume = $timeline['volume'];
				return [
					'state' => $state,
					'controls' => $controls,
					'volume' => $volume
				];
			}
		}

	}
	if ($pp) $_GET['pollPlayer'] = true;
	return ["stopped"];
}


function playerStatus() {
	write_log("Function fired.");
	$addresses = parse_url($_SESSION['plexClientUri']);
	$status = [];
	$prestate['status'] = 'error';
	$url = $_SESSION['plexServerUri'] . '/status/sessions/?X-Plex-Token=' . $_SESSION['plexServerToken'];
	write_log("Status URL: " . protectURL($url));
	$result = curlGet($url);
	if ($result) {
		$container = new SimpleXMLElement($result);
		foreach ($container->children() as $Media) {
			$vidArray = json_decode(json_encode($Media), true);
			$ip = $addresses['host'];
			$isCast = (preg_match("/$ip/", $vidArray['Player']['@attributes']['address']));
			$isPlayer = ($vidArray['Player']['@attributes']['machineIdentifier'] == $_SESSION['plexClientId']);
			if (($isPlayer) || ($isCast)) {
				write_log("Current vidarray: " . json_encode($vidArray));
				$_SESSION['sessionId'] = $vidArray['Session']['@attributes']['id'];
				$state = $vidArray['Player']['@attributes']['state'];
				$time = $vidArray['@attributes']['viewOffset'];
				$duration = $vidArray['@attributes']['duration'];
				$result['plexServerId'] = $_SESSION['plexServerUri'];
				$type = $vidArray['@attributes']['type'];
				$summary = $vidArray['@attributes']['summary'] ?? $vidArray['@attributes']['parentTitle'] ?? "";
				$title = $vidArray['@attributes']['title'] ?? "";
				$year = $vidArray['@attributes']['year'] ?? false;
				$tagline = $vidArray['@attributes']['tagline'] ?? $vidArray['@attributes']['parentTitle'] ?? "";
				if ($type == 'track') {
					if (isset($vidArray['@attributes']['grandparentTitle'])) $title = $vidArray['@attributes']['grandparentTitle'] . " - " . $title;
					if ($year) $tagline .= "(" . $year . ")";
				}

				if ($type == 'movie') {
					if ($year) $title .= "(" . $year . ")";
				}

				if ($type == 'episode') {
					$title = $vidArray['@attributes']['grandparentTitle'] . " - " . $title;
					if ($year) $title .= "(" . $year . ")";
				}
				$thumb = (($vidArray['@attributes']['type'] == 'movie') ? $vidArray['@attributes']['thumb'] : $vidArray['@attributes']['parentThumb']);
				$thumb = (string)transcodeImage($thumb);
				$art = transcodeImage($vidArray['@attributes']['art']);
				$vidArray['thumb'] = $thumb;
				$vidArray['art'] = $art;
				$result['mediaResult'] = $vidArray;

				if ($state !== $_SESSION['state']) {
					$_SESSION['state'] = $state;
					$extra = pollPlayer();
					write_log("Extras: " . json_encode($extra));
					$volume = $extra['volume'] ?? '0';
				} else {
					$volume = 0;
				}

				$mediaResult = [
					'title' => $title,
					'tagline' => $tagline,
					'duration' => $duration,
					'summary' => $summary,
					'year' => $year,
					'art' => $art,
					'thumb' => $thumb
				];
				$status = [
					'status' => strtolower($state),
					'time' => $time,
					'type' => 'cast',
					'volume' => $volume,
					'mediaResult' => $mediaResult
				];
			} else {
				$_SESSION['sessionId'] = false;
			}
		}
	}
	return json_encode($status);
}

function sendCommand($cmd) {
	if (preg_match("/stop/", $cmd)) fireHook(false, "Stop");
	if (preg_match("/pause/", $cmd)) fireHook(false, "Paused");
	if ($_SESSION['plexClientProduct'] === 'Cast') {
		$cmd = strtolower($cmd);
		$result = castCommand($cmd);
	} else {
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

function castCommand($cmd) {
	$int = 100;
	// Set up our cast device
	if (preg_match("/volume/", $cmd)) {
		$int = filter_var($cmd, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
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
			break;
		default:
			$return['status'] = 'error';
			$valid = false;

	}

	if ($valid) {
		write_log("Sending $cmd command");
		$url = $_SESSION['plexServerUri'] . "/chromecast/cmd?X-Plex-Token=" . $_SESSION['plexServerToken'];
		$vol = $int ? $int : 100;
		$headers = [
			'Uri' => $_SESSION['plexClientId'],
			'Cmd' => $cmd,
			'Vol' => $vol
		];
		$header = headerRequestArray($headers);
		write_log("Headers: " . json_encode($headers));
		$result = curlGet($url, $header);
		if ($result) {
			$response = flattenXML($result);
			write_log("Got me some response! " . json_encode($response));
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
	$contents = fetchCommands();
	$dvr = ($_SESSION['plexDvrUri'] ? "true" : "");
	if ($contents == '[') $contents = '[]';
	$commandData = urlencode($contents);
	$tags .= '<meta id="usernameData" data="' . $_SESSION['plexUserName'] . '"/>' . PHP_EOL .
		'<meta id="updateAvailable" data="' . $_SESSION['updateAvailable'] . '"/>' . PHP_EOL .
		'<meta id="publicIP" data="' . $_SESSION['publicAddress'] . '"/>' . PHP_EOL .
		'<meta id="deviceID" data="' . $_SESSION['deviceID'] . '"/>' . PHP_EOL .
		'<meta id="serverURI" data="' . $_SESSION['plexServerUri'] . '"/>' . PHP_EOL .
		'<meta id="clientURI" data="' . $_SESSION['plexClientUri'] . '"/>' . PHP_EOL .
		'<meta id="clientName" data="' . $_SESSION['plexClientName'] . '"/>' . PHP_EOL .
		'<meta id="plexDvr" data-enable="' . $dvr . '"/>' . PHP_EOL .
		'<meta id="rez" value="' . $_SESSION['plexDvrResolution'] . '"/>' . PHP_EOL .
		'<meta id="couchpotato" data-enable="' . $_SESSION['couchEnabled'] . '"/>' . PHP_EOL .
		'<meta id="sonarr" data-enable="' . $_SESSION['sonarrEnabled'] . '"/>' . PHP_EOL .
		'<meta id="sick" data-enable="' . $_SESSION['sickEnabled'] . '"/>' . PHP_EOL .
		'<meta id="radarr" data-enable="' . $_SESSION['radarrEnabled'] . '"/>' . PHP_EOL .
		'<meta id="ombi" data-enable="' . $_SESSION['ombiEnabled'] . '"/>' . PHP_EOL .
		'<meta id="logData" data="' . $commandData . '"/>' . PHP_EOL;
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


function downloadSeries($command, $season = false, $episode = false, $tmdbResult = false) {
	$enableSick = $_SESSION['sickEnabled'];
	$enableSonarr = $_SESSION['sonarrEnabled'];

	if ($enableSonarr == 'true') {
		write_log("Using Sonarr for Episode agent");
		$response = sonarrDownload($command, $season, $episode, $tmdbResult);
		return $response;
	}

	if ($enableSick == 'true') {
		write_log("Using Sick for Episode agent");
		$response = sickDownload($command, $season, $episode, $tmdbResult);
		return $response;
	}
	return "No downloader";
}

function sickDownload($command, $season = false, $episode = false, $tmdbResult = false) {
	write_log("Function fired");
	$exists = $id = $response = $responseJSON = $resultID = $resultYear = $status = $results = $result = $show = false;
	$sickUri = $_SESSION['sickUri'];
	$sickApiKey = $_SESSION['sickToken'];
	$sick = new SickRage($sickUri, $sickApiKey);
	$results = json_decode($sick->shows(), true)['data'];
	foreach ($results as $show) {
		if (cleanCommandString($show['show_name']) == cleanCommandString($command)) {
			$title = $show['show_name'];
			write_log("Found $title in the library: " . json_encode($show));
			$exists = true;
			$result = $show;
			$id = $result['tvdbid'];
			$status = 'SUCCESS: Already in searcher.';
			break;
		}
	}

	if (!$result) {
		write_log("Not in library, searching TVDB.");
		$results = $sick->sbSearchTvdb($command);
		$responseJSON = json_decode($results, true);
		$results = $responseJSON['data']['results'];
		if ($results) {
			$score = .69;
			foreach ($results as $searchResult) {
				$resultName = ($exists ? (string)$searchResult['show_name'] : (string)$searchResult['name']);
				$newScore = similarity($command, cleanCommandString($resultName));
				if ($newScore > $score) {
					write_log("This is the highest matched result so far.");
					$score = $newScore;
					$result = $searchResult;
					if ($score === 1) break;
				}
			}
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
			//if ($winner) break;
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

function sonarrDownload($command, $season = false, $episode = false, $tmdbResult = false) {
	write_log("Function fired, searching for " . $command);
	$exists = $score = $seriesId = $show = $wanted = false;
	$response = ['status' => 'ERROR'];
	$sonarrUri = $_SESSION['sonarrUri'];
	$sonarrApiKey = $_SESSION['sonarrAuth'];
	$sonarr = new Sonarr($sonarrUri, $sonarrApiKey);
	$rootArray = json_decode($sonarr->getRootFolder(), true);
	$seriesArray = json_decode($sonarr->getSeries(), true);
	$root = $rootArray[0]['path'];

	// See if it's already in the library
	foreach ($seriesArray as $series) {
		if (cleanCommandString($series['title']) == cleanCommandString($command)) {
			write_log("This show is already in the library.");
			write_log("SERIES: " . json_encode($series));
			$exists = $show = $series;
			$response['status'] = "SUCCESS: Already In Searcher";
			break;
		}
	}

	// If not, look for it.
	if ((!$exists) || ($season && !$episode)) {
		if ($exists) $show = $exists; else {
			$search = json_decode($sonarr->getSeriesLookup($command), true);
			write_log("Searching for show, array is " . json_encode($search));
			$score = .69;
			foreach ($search as $series) {
				$newScore = similarity(cleanCommandString($command), cleanCommandString($series['title']));
				if ($newScore > $score) {
					$score = $newScore;
					$show = $series;
				}
				if ($newScore === 1) break;
			}
		}
		// If we found something to download and don't have it in the library, add it.
		if (is_array($show)) {
			$show['qualityProfileId'] = ($_SESSION['sonarrProfile'] ? intval($_SESSION['sonarrProfile']) : 0);
			$show['rootFolderPath'] = $root;
			$skip = ($season || $episode);
			if ($season && !$episode) {
				$newSeasons = [];
				foreach ($show['seasons'] as $check) {
					if ($check['seasonNumber'] == $season) {
						$check['monitored'] = true;
					}
					array_push($newSeasons, $check);
				}
				$show['seasons'] = $newSeasons;
				unset($show['rootFolderPath']);
				$show['isExisting'] = false;
				write_log("Attempting to update the series " . $show['title'] . ", JSON is: " . json_encode($show));
				$show = json_decode($sonarr->putSeries($show), true);
				write_log("Season add result: " . json_encode($show));
				$response['status'] = "SUCCESS: Season added!";
			} else {
				write_log("Attempting to add the series " . $show['title'] . ", JSON is: " . json_encode($show));
				$show = json_decode($sonarr->postSeries($show, $skip), true);
				write_log("Show add result: " . json_encode($show));
				$response['status'] = "SUCCESS: Series added!";
			}
		} else {
			$response['status'] = "ERROR: No Results Found.";
		}
	}

	// If we want a whole season, send the command to search it.
	if ($season && !$episode) {
		$data = [
			'seasonNumber' => $season,
			'seriesId' => $show['id'],
			'updateScheduledTask' => true
		];
		$result = json_decode($sonarr->postCommand("SeasonSearch", $data), true);
		write_log("Command result: " . json_encode($result));
		$response['status'] = (($result['body']['completionMessage'] == "Completed") ? "SUCCESS: Season added and searched." : "ERROR: Command failed");
		$show['subtitle'] = "Season " . sprintf("%02d", $season);
	}

	// If we want a specific episode, then we need to search it manually.
	if ($episode && !empty($show)) {
		write_log("Looking for a specific episode.");
		$seriesId = $show['id'];
		write_log("Show ID: " . $seriesId);
		if ($seriesId) {
			$episodeArray = json_decode($sonarr->getEpisodes($seriesId), true);
			write_log("Fetched episode array: " . json_encode($episodeArray));
			// If they said "the latest" - we need to parse the full list in reverse, find the last aired episode.
			if ($episode && !$season && ($episode == -1)) {
				foreach (array_reverse($episodeArray) as $episode) {
					$airDate = new DateTime($episode['airDateUtc']);
					if ($airDate <= new DateTime('now')) {
						$wanted = $episode;
						break;
					}
				}
			}

			if (($episode && !$season) || ($season && $episode)) {
				foreach ($episodeArray as $file) {
					$fileEpNum = $file['episodeNumber'];
					$fileSeasonNum = $file['seasonNumber'];
					$fileAbsNum = $file['absoluteEpisodeNumber'];
					// Episode Number only
					if ($episode && !$season) {
						if ($episode == $fileAbsNum) $wanted = $file;
					}
					// Episode and Season
					if ($season && $episode) {
						if (($fileSeasonNum == $season) && ($fileEpNum == $episode)) $wanted = $file;
					}
					if ($wanted) break;
				}
			}
		}

		if ($wanted) {
			write_log("We have something to add: " . json_encode($wanted));
			$episodeId = $wanted['id'];
			$data = [
				'episodeIds' => [(int)$episodeId],
				'updateScheduledTask' => true
			];
			$result = json_decode($sonarr->postCommand("EpisodeSearch", $data), true);
			write_log("Command result: " . json_encode($result));
			$response['status'] = (($result['body']['completionMessage'] == "Completed") ? "SUCCESS: EPISODE SEARCHED" : "ERROR: COMMAND FAILED");
		} else {
			$response['status'] = "ERROR: EPISODE NOT FOUND";
		}
	}

	if (preg_match("/SUCCESS/", $response['status'])) {
		write_log("We have a success message, building final output.");
		if ($show) {
			$seriesId = $show['tvdbId'];
			$extras = $tmdbResult ? $tmdbResult : fetchTMDBInfo(false, false, $seriesId);
			$mediaOut['thumb'] = $mediaOut['art'] = $extras['art'];
			$mediaOut['year'] = $extras['year'];
			$mediaOut['tagline'] = $extras['subtitle'];
			if (isset($show['subtitle'])) $mediaOut['subtitle'] = $show['subtitle'];
			if ($wanted) {
				$mediaOut['title'] = $show['title'];
				$mediaOut['subtitle'] = "S" . sprintf("%02d", $wanted['seasonNumber']) . "E" . sprintf("%02d", $wanted['episodeNumber']) . " - " . $wanted['title'];
				$mediaOut['summary'] = $wanted['overview'];
			} else {
				$mediaOut['title'] = $show['title'];
				$mediaOut['summary'] = $show['overview'];
			}

			$response['mediaResult'] = $mediaOut;
			$response['mediaResult']['type'] = 'tv';
		}
	}
	write_log("Final response: " . json_encode($response));
	return $response;
}

function fetchTMDBInfo($title = false, $tmdbId = false, $tvdbId = false, $type = false) {
	$response = false;
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

// Fetch a movie from CouchPotato or Radarr
function downloadMovie($command, $tmdbResult = false) {
	write_log("Function fired.");
	$enableOmbi = $_SESSION['ombiEnabled'];
	$enableCouch = $_SESSION['couchEnabled'];
	$enableRadarr = $_SESSION['radarrEnabled'];
	$response['status'] = "ERROR: No downloader configured.";
	if ($enableOmbi == 'true') {
		write_log("Using Ombi for Movie agent");
	}

	if ($enableCouch) {
		write_log("Using Couchpotoato for Movie agent");
		$response = couchDownload($command);
	}

	if ($enableRadarr) {
		write_log("Using Radarr for Movie agent");
		$response = radarrDownload($command, $tmdbResult);
	}
	return $response;
}

function couchDownload($command) {
	$couchUri = $_SESSION['couchUri'];
	$couchApikey = $_SESSION['couchToken'];
	$response = [];
	$response['initialCommand'] = $command;
	$response['parsedCommand'] = 'fetch the movie ' . $command;

	// Send our initial request to search the movie

	$url = $couchUri . "/api/" . $couchApikey . "/movie.search/?q=" . urlencode($command);
	write_log("Sending request to " . $url);
	$result = curlGet($url);

	// Parse the response, look for IMDB ID

	$body = json_decode($result, true);
	write_log("body:" . $result);
	$score = intval($_SESSION['searchAccuracy']) * .01;
	$winner = [];
	foreach ($body['movies'] as $movie) {
		$newScore = similarity(cleanCommandString($movie['titles'][0]), $command);
		write_log("Similarity: " . $newScore);
		if ($newScore > $score) {
			write_log("Highest Match: " . $movie['titles'][0] . " Score: " . $newScore, "INFO");
			$winner = $movie;
			$score = $newScore;
		}
		foreach ($movie['titles'] as $title) if (preg_match("/$command/", $title)) $score = 1;

		if ($score == 1) {
			write_log("Found an exact match with " . $movie['titles'][0]);
			$winner = $movie;
			break;
		}
	}

	// Now take the IMDB ID and send it with the title to Couchpotato
	if (!empty($winner)) {
		$title = $winner['titles'][0];
		$imdbID = (string)$winner['imdb'];
		$year = $winner['year'];
		$art = $winner['images']['backdrop_original'][0];
		$thumb = $art;
		write_log("Art URL should be " . $art);
		$plot = $winner['plot'];
		$subtitle = $winner['tagline'];
		write_log("imdbID: " . $imdbID);
		$resultObject['title'] = $title;
		$resultObject['year'] = $year;
		$resultObject['art'] = $art;
		$resultObject['thumb'] = $thumb;
		$resultObject['summary'] = $plot;
		$resultObject['subtitle'] = $subtitle;
		$resultObject['type'] = 'movie';
		$url2 = $couchUri . "/api/" . $couchApikey . "/movie.add/?identifier=" . $imdbID . "&title=" . urlencode($command) . ($_SESSION['couchProfile'] ? '&profile_id=' . $_SESSION['couchProfile'] : '');
		write_log("Sending add request to: " . $url2);
		curlGet($url2);
		$response['status'] = 'SUCCESS: Media added successfully.';
		$response['mediaResult'] = $resultObject;
		$response['mediaResult']['url'] = $url2;
		return $response;
	} else {
		$response['status'] = 'ERROR: No results for query.';
		return $response;
	}
}

function radarrDownload($command, $tmdbResult = false) {
	$exists = $score = $tmdbId = $movie = $wanted = false;
	$response = ['status' => 'ERROR'];
	$radarrUri = $_SESSION['radarrUri'];
	$radarrApiKey = $_SESSION['radarrToken'];
	$movie = false;
	$radarr = new Radarr($radarrUri, $radarrApiKey);
	$rootArray = json_decode($radarr->getRootFolder(), true);
	$movieCheck = json_decode($radarr->getMoviesLookup($command), true);
	$movieArray = json_decode($radarr->getMovies(), true);
	$rootPath = $rootArray[0]['path'];
	$highest = 0;
	$target = intval($_SESSION['searchAccuracy']) * .01;
	foreach ($movieCheck as $check) {
		$score = similarity(cleanCommandString($check['title']), cleanCommandString($command));
		if (($score >= $target) && ($score > $highest)) {
			$movie = $check;
			$highest = $score;
			write_log("This title is pretty similar: " . $check['title']);
		}
		if (($score == 1) || preg_match("/$command/", $check['title'])) {
			$movie = $check;
			write_log("Found a near-identical match");
			break;
		}
	}
	if ($movie) {
		foreach ($movieArray as $check) {
			if ($check['tmdbId'] == $movie['tmdbId']) {
				write_log("This movie exists already.");
				$movie = $check;
				$exists = true;
				break;
			}
		}
		if (!$exists) {
			write_log("Need to fetch this movie: " . json_encode($movie));
			$search = $movie;
			$search['monitored'] = true;
			$search['rootFolderPath'] = $rootPath;
			$search['addOptions'] = [
				'ignoreEpisodesWithFiles' => false,
				'searchForMovie' => true,
				'ignoreEpisodesWithoutFiles' => false
			];
			$search['qualityProfileId'] = ($_SESSION['radarrProfile'] ? intval($_SESSION['radarrProfile']) : 0);
			$search['profileId'] = ($_SESSION['radarrProfile'] ? $_SESSION['radarrProfile'] : "0");
			write_log("Final search item: " . json_encode($search));
			if (is_array($search)) $result = json_decode($radarr->postMovie($search));
			write_log("Add result: " . json_encode($movie));
			if (isset($result['addOptions']['searchForMovie'])) {
				$response['status'] = "SUCCESS: Movie added to searcher.";

			} else {
				$response['status'] = "ERROR: Error adding title to searcher.";
				$movie = false;
			}
		} else {
			$response['status'] = "SUCCESS: Already in searcher.";
		}
		if ($movie) {
			if (isset($movie['tmdbId'])) {
				$response['mediaResult'] = $tmdbResult ? $tmdbResult : fetchTMDBInfo(false, $movie['tmdbId']);
			} else {
				$response['mediaResult'] = $tmdbResult ? $tmdbResult : fetchTMDBInfo($movie['title']);
			}
		}
	}
	write_log("Final response: " . json_encode($response));
	return $response;
}

function fetchList($serviceName) {
	$list = $selected = false;
	if (!$_SESSION[$serviceName . "Enabled"]) return "";
	switch ($serviceName) {
		case "sick":
			if ($_SESSION['sickList']) {
				$list = $_SESSION['sickList'];
			} else {
				testConnection("Sick");
				$list = $_SESSION['sickList'];
			}
			$selected = $_SESSION['sickProfile'];
			break;
		case "ombi":
			if ($_SESSION['ombiList']) {
				$list = $_SESSION['ombi'];
			}
			break;
		case "sonarr":
			if ($_SESSION['sonarrList']) {
				$list = $_SESSION['sonarrList'];
			} else {
				testConnection("Sonarr");
				$list = $_SESSION['sonarrList'];
			}
			$selected = $_SESSION['sonarrProfile'];
			break;
		case "couch":
			if ($_SESSION['couchList']) {
				$list = $_SESSION['couchList'];
			} else {
				testConnection("Couch");
				$list = $_SESSION['couchList'];
			}
			$selected = $_SESSION['couchProfile'];
			break;
		case "radarr":
			if ($_SESSION['radarrList']) {
				$list = $_SESSION['radarrList'];
			} else {
				testConnection("Radarr");
				$list = $_SESSION['radarrList'];
			}
			$selected = $_SESSION['radarrProfile'];
			break;
	}
	$html = PHP_EOL;
	if ($list) {
		foreach ($list as $id => $name) {
			$html .= "<option data-index='" . $id . "' id='" . $name . "' " . (($selected == $id) ? 'selected' : '') . ">" . $name . "</option>" . PHP_EOL;
		}
	}
	return $html;
}


// Test the specified service for connectivity
function testConnection($serviceName) {
	write_log("Function fired, testing connection for " . $serviceName);

	switch ($serviceName) {

		case "Ombi":
			$ombiUri = $_SESSION['ombiUri'];
			$ombiAuth = $_SESSION['ombiAuth'];
			$authString = 'apikey:' . $ombiAuth;
			if (($ombiUri) && ($ombiAuth)) {
				$url = $ombiUri;
				write_log("Test URL is " . protectURL($url));
				$headers = [$authString];
				$result = curlPost($url, false, false, $headers);
				$result = ((strpos($result, '"success": true') ? 'Connection to CouchPotato Successful!' : 'ERROR: Server not available.'));
			} else $result = "ERROR: Missing server parameters.";
			break;

		case "CouchPotato":
			$couchURL = $_SESSION['couchUri'];
			$couchToken = $_SESSION['couchToken'];
			if (($couchURL) && ($couchToken)) {
				$url = "$couchURL/api/$couchToken/profile.list";
				$result = curlGet($url);
				if ($result) {
					$resultJSON = json_decode($result, true);
					write_log("Hey, we've got some profiles: " . json_encode($resultJSON));
					$array = [];
					$first = false;
					foreach ($resultJSON['list'] as $profile) {
						$id = $profile['_id'];
						$name = $profile['label'];
						$array[$id] = $name;
						if (!$first) $first = $id;
					}
					$_SESSION['couchList'] = $array;
					if (!$_SESSION['couchProfile']) $_SESSION['couchProfile'] = $first;
					updateUserPreference('couchProfile', $first);
					updateUserPreference('couchList', $array);
				}
				$result = ((strpos($result, '"success": true') ? 'Connection to CouchPotato Successful!' : 'ERROR: Server not available.'));
			} else $result = "ERROR: Missing server parameters.";
			break;

		case "Sonarr":
			$sonarrURL = $_SESSION['sonarrUri'];
			$sonarrAuth = $_SESSION['sonarrAuth'];
			if (($sonarrURL) && ($sonarrAuth)) {
				$url = "$sonarrURL/api/profile?apikey=$sonarrAuth";
				$result = curlGet($url);
				if ($result) {
					write_log("Result retrieved.");
					$resultJSON = json_decode($result, true);
					write_log("Result JSON: " . json_encode($resultJSON));

					$array = [];
					$first = false;
					foreach ($resultJSON as $profile) {
						$first = ($first ? $first : $profile['id']);
						$array[$profile['id']] = $profile['name'];
					}
					write_log("Final array is " . json_encode($array));
					$_SESSION['sonarrList'] = $array;
					if (!$_SESSION['sonarrProfile']) $_SESSION['sonarrProfile'] = $first;
					updateUserPreference('sonarrProfile', $first);
					updateUserPreference('sonarrList', $array);
				}
				$result = (($result !== false) ? 'Connection to Sonarr successful!' : 'ERROR: Server not available.');
			} else $result = "ERROR: Missing server parameters.";

			break;

		case "Radarr":
			$radarrURL = $_SESSION['radarrUri'];
			$radarrToken = $_SESSION['radarrToken'];
			if (($radarrURL) && ($radarrToken)) {
				$url = "$radarrURL/api/profile?apikey=$radarrToken";
				write_log("Request URL: " . $url);
				$result = curlGet($url);
				if ($result) {
					write_log("Result retrieved.");
					$resultJSON = json_decode($result, true);
					$array = [];
					$first = false;
					foreach ($resultJSON as $profile) {
						if ($profile === "Unauthorized") {
							return "ERROR: Incorrect API Token specified.";
						}
						$first = ($first ? $first : $profile['id']);
						$array[$profile['id']] = $profile['name'];
					}
					write_log("Final array is " . json_encode($array));
					$_SESSION['radarrList'] = $array;
					if (!$_SESSION['radarrProfile']) $_SESSION['radarrProfile'] = $first;
					updateUserPreference("radarrProfile", $first);
					updateUserPreference("radarrList", $array);
				}
				$result = (($result !== false) ? 'Connection to Radarr successful!' : 'ERROR: Server not available.');
			} else $result = "ERROR: Missing server parameters.";
			break;

		case "Sick":
			$sickURL = $_SESSION['sickUri'];
			$sickToken = $_SESSION['sickToken'];
			if (($sickURL) && ($sickToken)) {
				$sick = new SickRage($sickURL, $sickToken);
				try {
					$result = $sick->sbGetDefaults();
				} catch (\Kryptonit3\SickRage\Exceptions\InvalidException $e) {
					write_log("Error Curling sickrage: " . $e);
					$result = "ERROR: " . $e;
					break;
				}
				$result = json_decode($result, true);
				write_log("Got some kind of result " . json_encode($result));
				$list = $result['data']['initial'];
				$array = [];
				$count = 0;
				$first = false;
				foreach ($list as $profile) {
					$first = ($first ? $first : $count);
					$array[$count] = $profile;
					$count++;
				}
				$_SESSION['sickList'] = $array;
				updateUserPreference('sickList', $array);
				write_log("List: " . print_r($_SESSION['sickList'], true));
				$result = (($result) ? 'Connection to Sick successful!' : 'ERROR: Server not available.');
			} else $result = "ERROR: Missing server parameters.";
			break;

		case "Plex":
			$url = $_SESSION['plexServerUri'] . '?X-Plex-Token=' . $_SESSION['plexServerToken'];
			write_log('URL is: ' . protectURL($url));
			$result = curlGet($url);
			$result = (($result) ? 'Connection to ' . $_SESSION['plexServerName'] . ' successful!' : 'ERROR: ' . $_SESSION['plexServerName'] . ' not available.');
			break;

		default:
			$result = "ERROR: Service name not recognized";
			break;
	}
	return $result;
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




