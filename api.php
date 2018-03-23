<?php
require_once dirname(__FILE__) . '/php/vendor/autoload.php';
require_once dirname(__FILE__) . "/php/webApp.php";
$homeApp = dirname(__FILE__) . '/php/homeApp.php';
if (file_exists($homeApp)) require_once $homeApp;
require_once dirname(__FILE__) . '/php/util.php';

require_once dirname(__FILE__) . '/php/fetchers.php';
require_once dirname(__FILE__) . '/php/body.php';
require_once dirname(__FILE__) . '/php/JsonXmlElement.php';
require_once dirname(__FILE__) . '/php/dialogFlow/DialogFlow.php';

use Kryptonit3\SickRage\SickRage;
use Kryptonit3\Sonarr\Sonarr;
use digitalhigh\DialogFlow\DialogFlow;

analyzeRequest();

/**
 * Takes an incoming request and makes sure it's authorized and valid
 */
function analyzeRequest() {

    $json = file_get_contents('php://input');
    $sessionId = json_decode($json, true)['originalRequest']['data']['conversation']['conversationId'] ?? false;

    if (!session_started()) {
        if ($sessionId)session_id($sessionId);
        session_start();
        write_log("Session started with id of ".session_id().".","WARN");
    } else {
        write_log("Session with id of ".session_id()." is already started.","WARN");
    }
    write_log("-------NEW REQUEST RECEIVED-------", "INFO");
    setDefaults();

    if (isset($_GET['revision'])) {
        $rev = $GLOBALS['config']->get('general', 'revision', false);
        echo $rev ? substr($rev, 0, 8) : "unknown";
        die;
    }

    $token = false;
    if (isset($_SERVER['HTTP_APITOKEN'])) {
        write_log("Using token from POST", "INFO");
        $token = $_SERVER['HTTP_APITOKEN'];
    }

    if (isset($_GET['apiToken'])) $token = $_GET['apiToken'];

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
        // This is OK because it's called before setsessionvariables...
        $_SESSION['dologout'] = false;

	//$_SESSION['v2'] = true;

        foreach ($user as $key => $value) $_SESSION[$key] = $value;
        if (!(isset($_SESSION['counter']))) {
            $_SESSION['counter'] = 0;
        }

        $apiTokenMatch = ($apiToken === $_SESSION['apiToken']);
        $loaded = $_SESSION['loaded'] ?? false;
        // DO NOT SET ANY SESSION VARIABLES MANUALLY AFTER THIS IS CALLED
        if ($apiTokenMatch  && $loaded) {
            write_log("Looks like we have session vars set already?");
        } else {
            write_log("We should call setsessionvars now...");
            setSessionData(false);
        }

        initialize();
    } else {
        if (isset($_GET['testclient'])) {
            write_log("API Link Test failed, no user!", "ERROR");
            http_response_code(401);
            bye();
        }
    }
}

/**
 * Loads/ends session, figures out what to do
 */
function initialize() {

	if (isset($_GET['pollPlayer'])) {
		if (substr_count($_SERVER["HTTP_ACCEPT_ENCODING"], "gzip")) ob_start("ob_gzhandler"); else ob_start();
		$force = ($_GET['force'] === 'true');
		$result = getUiData($force);
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
		$status = testConnection($_GET['test']);
		header('Content-Type: application/json');
		$result['status'] = $status;
		echo json_encode($result);
		bye();
	}
	if (isset($_GET['registerServer'])) {
		write_log("Registering server with phlexchat.com", "INFO");
		sendServerRegistration();
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
		$id = $_GET['id'] ?? false;
		$data = $dev = false;
		if (!$id) {
		    write_log("No id specified, let's see if there's a name or URI");
		    $name = $_GET['name'] ?? false;
		    $uri = $_GET['uri'] ?? false;
		    if ($name) $dev = findDevice("Name",$name,$type);
            if ($uri) $dev = findDevice("Uri",$name,$type);
            if ($dev) $id = $dev['Id'] ?? false;
        }
		header('Content-Type: application/json');
		if ($id !== 'rescan' && $id !== false) {
			$data = setSelectedDevice($type, $id);
		} else if ($id == 'rescan') {
			$force = $_GET['passive'] ?? false;
			if (isset($_GET['passive'])) write_log("Passive refresh?","INFO",false,true);
			$data = selectDevices(scanDevices(!$force));
		}
		if ($data) {
            writeSession('deviceUpdated', true);
            write_log("Echoing new $type list: " . json_encode($data));
            echo json_encode($data);
        }
		bye();
	}
	if ((isset($_GET['id'])) && (!isset($_GET['device']))) {
		$valid = true;
		$id = $_GET['id'];
		$value = $_GET['value'];
		write_log("Setting Value changed: $id = $value", "INFO");
		$value = str_replace("?logout", "", $value);
		if ((preg_match("/IP/", $id) || preg_match("/Uri/", $id)) && !preg_match("/device/", $id)) {
			write_log("Sanitizing URL.");
			$value = cleanUri($value);
			if (!$value) $valid = false;
		}
		if (preg_match("Uri", $id)) {
			write_log("Sanitizing URI.");
			$value = cleanUri($value);
			if (!$value) $valid = false;
		}
		if (preg_match("/Path/", $id)) if ((substr($value, 0, 1) != "/") && (trim($value) !== "")) $value = "/" . $value;

		if ($valid) {
			updateUserPreference($id, $value);
			if ((trim($id) === 'useCast') || (trim($id) === 'noLoop')) scanDevices(true);
			if ($id == "appLanguage") checkSetLanguage($value);
		}
		echo($valid ? "valid" : "invalid");
		bye();
	}
	if (isset($_GET['msg'])) {
		if ($_GET['msg'] === 'FAIL') {
			write_log("Received response failure from server, firing fallback command.");
			sendFallback();
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
		$json = file_get_contents('php://input');
		$request = json_decode($json, true);
		$amazonRequest = false;
		if ($request) {
			if (isset($request['result']['resolvedQuery']) || isset($request['type'])) {
				write_log("JSON: " . $json);
				if (isset($request['type'])) {
					if ($request['reason'] == 'ERROR') {
						write_log("Alexa Error message: " . $request['error']['type'] . '::' . $request['error']['message'], "ERROR");
						bye();
					}
					writeSession('amazonRequest', true);
				}
				if (isset($_SESSION['v2'])) {
					$df = new DialogFlow(fetchDirectory(3), getDefaultLocale());
					$response = $df->process($request);
					write_log("WE HAVE THE MEATS!");
					mapApiRequest($response);
					bye("V2 session, seeyalater.");
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
			$v2 = $_SESSION['v2'] ?? false;
			$request = $v2 ? fetchApiAiData($command) : queryApiAi($command);
			if ($request) {
				if (isset($_SESSION['v2'])) {
					write_log("WE HAVE THE MEATS!");
					mapApiRequest($request);
					bye("V2 session, seeyalater.");
				}
			}
			parseApiCommand($request);
			bye();
		} catch (\Exception $error) {
			write_log(json_encode($error->getMessage()), "ERROR");
		}
	}
}

function setSessionData($rescan = true) {
    $data = fetchUserData();
    if ($data) {
        $userName = $data['plexUserName'];
        write_log("Found session data for $userName, setting.");
        $dlist = $data['dlist'] ?? false;
        $devices = json_decode(base64_decode($dlist), true);
        if ($rescan || !$devices) $devices = scanDevices(true);
        $_SESSION['deviceList'] = $devices;
        if (isset($data['dlist'])) unset($data['dlist']);
        foreach ($data as $key => $value) {
            $value = toBool($value);
            $_SESSION[$key] = $value;
        }
        $clientId = trim($data['plexClientId'] ?? "");
        $serverId = trim($data['plexServerId'] ?? "");
        $check = [
            'Client' => $clientId,
            'Server' => $serverId
        ];
        foreach ($check as $section => $value) {
            $sectionArray = $devices["$section"] ?? [];
            if (!$value && count($sectionArray)) {
                write_log("Selecting a $section.");
                setSelectedDevice($section, $sectionArray[0]);
            }
        }
        $_SESSION['deviceID'] = checkSetDeviceID();
        $_SESSION['plexHeaderArray'] = plexHeaders();
        $_SESSION['plexHeader'] = headerQuery(plexHeaders());
        $_SESSION['loaded'] = true;
    }
    if (!$data) write_log("Error, could not find userdata!!", "ERROR");
//	session_write_close();
//	write_log("Locking session.","INFO");
}

function getSessionData() {
    write_log("Function fired.");
    webAddress();
    $data = [];
    $boolKeys = [
        'couchEnabled',
        'sonarrEnabled',
        'radarrEnabled',
        'ombiEnabled',
        'sickEnabled',
        'headphonesEnabled',
        'lidarrEnabled',
        'darkTheme',
        'hasPlugin',
        'alertPlugin',
        'plexPassUser',
        'plexDvrReplaceLower',
        'plexDvrNewAirings',
        'hook',
        'hookPaused',
        'hookPlay',
        'hookFetch',
        'hookCustom',
        'hookSplit',
        'hookStop'
    ];
    foreach ($_SESSION as $key => $value) {
        if ($key !== "lang") {
            if (in_array($key, $boolKeys)) {
                $value = boolval($value);
            }
            $data[$key] = $value;
        }
    }
    $dvr = $_SESSION['plexDvrId'] ?? false;
    $data['dvrEnabled'] = boolval($dvr) ? true : false;
    write_log("Session data: ".json_encode($data),"INFO");
    return $data;
}

function getUiData($force = false) {

	$playerStatus = fetchPlayerStatus();
	$updates = checkUpdates();
	$devices = selectDevices(scanDevices(false));
	$deviceText = json_encode($devices);
	$userData = getSessionData();
	foreach($userData as $key=>$value) {
		if (preg_match("/List/",$key)) $userData["$key"] = fetchList(str_replace("List","",$key));
	}
	$commands = fetchCommands();
	if ($playerStatus) {
		$result['playerStatus'] = $playerStatus;
	}
	if ($force) {
	    write_log("outgoing UI Data: ".json_encode($userData),"INFO",false,true);
		$result['devices'] = $devices;
		$result['userData'] = $userData;
		$result['ui'] = makeSettingsBody();
		$updates = checkUpdates();
		$result['updates'] = $updates;
		$result['commands'] = $commands;
		writeSessionArray([
			'updates' => $updates,
			'commands' => $commands,
			'devices' => $deviceText
		]);
	} else {

		$updated = $_SESSION['updated'] ?? false;
		unset($_SESSION['updated']);
		if (is_array($updated)) {
			write_log("Data: ".json_encode($updated));
			foreach($updated as $key=>$value) {
				write_log("Key is $key");
				if (preg_match("/List/",$key)) {
					$target = str_replace("List","",$key);
					write_log("Fetching a profile list for $target","INFO",false,true);
					$updated["$key"] = fetchList($target);
				}
			}
			write_log("Echoing new userdata: ".json_encode($updated), "INFO", false, true);
			$result['userData'] = $updated;
		}
		$deviceUpdated = $_SESSION['devices'] !== $deviceText;
		if ($deviceUpdated) {
		   $result['devices'] = $devices;
			writeSession('devices',$deviceText);
		}
		$sessionCommands = $_SESSION['commands'] ?? [];
		$commandData = [];
		foreach ($commands as $command) {
			$exists = false;
			foreach ($sessionCommands as $session) {
				if ($command['timeStamp'] == $session['timeStamp']) {
					$exists = true;
				}
			}
			if (!$exists) {
				write_log("Adding new command: " . json_encode($command), "INFO", false, true);
				$commandData[] = $command;
			}
		}
		if (count($commandData)) {
			write_log("New command: " . json_encode($commandData), "INFO", false, true);
			$result['commands'] = $commandData;
			writeSession('commands', $commands);
		}
		if ($_SESSION['updates'] !== json_encode($updates)) {
			$result['updates'] = $updates;
			# TODO: These need to be internationalized
			if (!$_SESSION['autoUpdate']) $result['messages'][] = [
				'title'=>"An update is available.",
				'message'=>"An update is available for Phlex.  Click here to install it now.",
				'url'=>'api.php?apiToken=' . $_SESSION['apiToken'] . '&installUpdates=true'
			];
			writeSession('updates',json_encode($updates));
		}
	}
	if ($_SESSION['dologout'] ?? false) $result['dologout'] = true;
	if (isset($_SESSION['messages'])) {
		$result['messages'] = $_SESSION['messages'];
		writeSession('messages', null, true);
	}
	if (isset($_SESSION['webApp'])) {
		$lines = $_GET['logLimit'] ?? 50;
		$result['logs'] = formatLog(tailFile(file_build_path(dirname(__FILE__), "logs", "Phlex.log.php"), $lines));
	}
	$messages = $_SESSION['messages'] ?? false;
	if ($messages) {
	    write_log("Messages!");
	    $result['messages'] = $_SESSION['messages'];
	    writeSession('messages',false);
    }
	return $result;
}


/**
 * @deprecated
 * @param $command
 * @param bool $value
 * @return bool|string
 */
function parseControlCommand($command, $value = false) {
	//Sanitize our string and try to rule out synonyms for commands
	$synonyms = lang('commandSynonymsArray');
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
			$statusArray = fetchPlayerStatus();
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
		$result = sendCommand($cmd, $value);
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

/**
 * @deprecated
 * @param $command
 * @return array|bool
 */
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
				'startOffsetMinutes' => $_SESSION['plexDvrStartOffsetMinutes'],
				'endOffsetMinutes' => $_SESSION['plexDvrEndOffsetMinutes'],
				'comskipEnabled' => $_SESSION['plexDvrComskipEnabled'],
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
						$extra = fetchMovieInfo($title, false, false, 'tv');
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

/**
 * @deprecated
 * @param $command
 * @param bool $year
 * @param bool $artist
 * @param bool $type
 * @param bool $raw
 * @return array
 */
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
					"on my $name",
					"on the $name",
					"on $name",
					"in the $name",
					"in $name",
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
	}
	$commandArray = explode(" ", $command);
	// An array of words which don't do us any good
	// Adding the apostrophe and 's' are necessary for the movie "Daddy's Home", which Google Inexplicably returns as "Daddy ' s Home"
	$stripIn = lang('parseStripArray');
	// An array of words that indicate what kind of media we'd like
	$mediaIn = lang('parseMediaArray');
	// An array of words that would modify or filter our search
	$filterIn = lang('parseFilterArray');
	// An array of words that would indicate which specific episode or media we want
	$numberWordIn = lang('parseNumberArray');
	write_log("NumberWordIn: " . json_encode($numberWordIn));
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

/**
 * @deprecated
 * @param $request
 */
function parseApiCommand($request) {
	$lang = $request['lang'];
	$arr = [];
	if ($lang) $arr['appLanguage'] = strtolower($lang);
	$arr['lastRequest'] = json_encode($request);
	$greeting = $mediaResult = $rechecked = $screen = $year = false;
	$card = $suggestions = false;
	write_log("Full API.AI request: " . json_encode($request), "INFO");
	$result = $request["result"];
	$action = $result['parameters']["action"] ?? false;
	$request = $result['parameters'];
	$command = $result["parameters"]["command"] ?? false;
	$control = $result["parameters"]["Controls"] ?? false;
	$year = $result["parameters"]["age"]["amount"] ?? false;
	$type = $result['parameters']['type'] ?? false;
	$days = $result['parameters']['days'] ?? false;
	$artist = $result['parameters']['artist'] ?? false;
	$arr['apiVersion'] = $request['originalRequest']['version'] ?? "1";
	writeSessionArray($arr);
	if ($command) $command = cleanCommandString($command);
	$rawspeech = $result['resolvedQuery'];
//	if (cleanCommandString($rawspeech) == cleanCommandString($_SESSION['hookCustomPhrase'])) {
//		fireHook(false, "Custom");
//		write_log("Custom phrase triggered: " . $_SESSION['hookCustomPhrase'], "INFO");
//		$queryOut['initialCommand'] = $rawspeech;
//		$speech = ($_SESSION['hookCustomReply'] != "" ? $_SESSION['hookCustomReply'] : lang('speechHookCustomDefault'));
//		$queryOut['speech'] = $speech;
//		returnSpeech($speech, "yes", false, false);
//		logCommand(json_encode($queryOut));
//		bye();
//	}
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
	write_log("TOKENS: " . json_encode($tokens));
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
			foreach ($context as $sub) {
				if (isset($sub['name'])) array_push($con, $sub);
			}
		} else {
			array_push($con, $context);
		}
	}
	write_log("Session fallback? :" . json_encode($_SESSION['fallback']));
	foreach ($con as $context) {
		write_log("Input context: " . json_encode($context));
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
			$speech = "Okay, playing $resultTitle on " . $_SESSION['plexClientName'] . ".";
			$card = [
				[
					"title" => $resultTitle,
					"subtitle" => $resultSubtitle,
					"formattedText" => $resultSummary,
					'image' => ['url' => $resultImage]
				]
			];
			// #TODO: Work out whether to wait for a response or not...
			sendSpeechLegacy($speech, $context['name'], $card, false);
			sendFallback();
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
		$greetings = lang('speechGreetingArray');
		$speech = $greetings[array_rand($greetings)];
		$speech = joinSpeech($speech, lang('speechGreetingHelpPrompt'));
		$contextName = 'PlayMedia';
		$button = [
			[
				'title' => lang('cardReadmeButton'),
				'openUrlAction' => ['url' => 'https://github.com/d8ahazard/Phlex/blob/master/readme.md']
			]
		];
		$card = [
			[
				'title' => lang('cardGreetingText'),
				'formattedText' => '',
				'image' => ['url' => 'https://phlexchat.com/img/avatar.png'],
				'buttons' => $button
			]
		];
		$queryOut['card'] = $card;
		$queryOut['speech'] = $speech;
		sendSpeechLegacy($speech, $contextName, $card, true, false);
		logCommand(json_encode($queryOut));
		bye();
	}
	if (($action == 'shuffle') && ($command)) {
		$media = false;
		write_log("We got a shuffle, foo.");
		$queue = fetchHubItem($command, $type);
		write_log("Queue: " . json_encode($queue));
		if (count($queue)) $media = $queue[0];
		$key = (isset($media['ratingKey']) ? '/library/metadata/' . $media['ratingKey'] : false);
		$queue = false;
		$audio = ($media['type'] == 'artist' || $media['type'] == 'track' || $media['type'] == 'album');
		if ($key) $queue = fetchPlayQueue(['key' => $key], $audio, false, true, true);
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
			$speech = joinSpeech(lang('speechShuffleResponse'), $media['title']) . ".";
			$card = [
				[
					'title' => $childMedia['title'],
					'formattedText' => $childMedia['summary'],
					'image' => ['url' => transcodeImage($media['art'])]
				]
			];
			sendSpeechLegacy($speech, "PlayMedia", $card, false);
			$result = sendMediaLegacy($childMedia);
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
				$queryOut['parsedCommand'] = joinSpeech(lang('parsedDvrSuccessStart'), $type, lang('parsedDvrSuccessNamed'), "$title ($year)", lang('speechDvrSuccessEnd'));
				$speech = joinSpeech(lang('speechDvrSuccessStart'), $type, lang('parsedDvrSuccessNamed'), "$title ($year)", lang('speechDvrSuccessEnd'));
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
				$queryOut['parsedCommand'] = lang('parsedDvrFailStart') . $command;
				$speech = joinSpeech(lang('speechDvrNoDevice'), ucwords($command)) . "'.";
				$results['url'] = $result['url'];
				$card = false;
				$results['status'] = "No results.";
			}
		} else {
			$speech = lang('speechDvrNoDevice');
			$card = false;
		}
		sendSpeechLegacy($speech, $contextName, $card);
		$queryOut['speech'] = $speech;
		logCommand(json_encode($queryOut));
		bye();
	}
	if (($action == 'changeDevice') && ($command)) {
		changeDevice($command);
	}
	if ($action == 'status') {
		$status = fetchPlayerStatus();
		$status = json_decode($status, true);
		if ($status['status'] == 'playing') {
			$type = $status['mediaResult']['type'];
			$player = $_SESSION['plexClientName'];
			$thumb = $status['mediaResult']['art'];
			$title = $status['mediaResult']['title'];
			$summary = $status['mediaResult']['summary'];
			$tagline = $status['mediaResult']['tagline'];
			$speech = joinSpeech(lang('speechPlayerStatus1'), $type, $title, lang('speechPlayerStatus2'), $player . ".");
			if ($type == 'episode') {
				$showTitle = $status['mediaResult']['grandparentTitle'];
				$epNum = $status['mediaResult']['index'];
				$seasonNum = $status['mediaResult']['parentIndex'];
				$speech = joinSpeech(lang('speechPlayerStatus3'), $seasonNum . lang('speechPlayerStatusEpisode'), $epNum, lang('speechPlayerStatusOf'), $showTitle, lang('speechPlayerStatus4'), $title . ".");
			}
			if ($type == 'track') {
				$songtitle = $title;
				$artist = $status['mediaResult']['grandparentTitle'];
				$album = $status['mediaResult']['parentTitle'];
				$year = $status['mediaResult']['year'];
				$speech = joinSpeech(lang('speechPlayerStatus3'), $songtitle, lang('speechPlayerStatusBy'), $artist, lang('speechPlayerStatus6'), $album . '.');
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
			$speech = lang('speechStatusNothingPlaying');
		}
		$contextName = 'PlayMedia';
		sendSpeechLegacy($speech, $contextName, $card);
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
			$speech = (($action == 'recent') ? joinSpeech(lang('speechReturnRecent'), $type . ": ") : lang('speechReturnOndeck'));
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
					$speech = joinSpeech($speech, lang('speechWordAnd'), $title . ".");
				} else {
					$speech = joinSpeech($speech, $title . ", ");
				}
				$i++;
			}
			$speech = joinSpeech($speech, lang('speechReturnOndeckRecentTail'));
			writeSession('mediaList', $array);
			$queryOut['card'] = $cards;
			$queryOut['mediaStatus'] = 'SUCCESS: Hub array returned';
			$queryOut['mediaResult'] = $array[0];
		} else {
			write_log("Error fetching hub list.", "ERROR");
			$queryOut['mediaStatus'] = "ERROR: Could not fetch hub list.";
			$speech = lang('speechReturnOndeckRecentError');
		}
		$contextName = 'promptfortitle';
		sendSpeechLegacy($speech, $contextName, $cards, !true);
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
			writeSession('mediaList', $list);
			$i = 1;
			$speech = lang('speechAiringsReturn');
			if ($days == 'now') {
				$time = date('H');
				$speech = lang('speechAiringsToday');
				if ($time >= 12) $speech = lang('speechAiringsAfternoon');
				if ($time >= 17) $speech = lang('speechAiringsTonight');
				$days = $speech;
				$speech .= ", ";
			}
			if ($days == 'tomorrow') $speech = lang('speechAiringsTomorrow');
			if ($days == 'weekend') $speech = lang('speechAiringsWeekend');
			if (preg_match("/day/", $days)) $speech = lang('speechAiringsOn') . ucfirst($days) . ", ";
			$mids = lang('speechAiringsMids');
			$speech = joinSpeech($speech, $mids[array_rand($mids)]);
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
							$speech = joinSpeech($speech, lang('speechWordAnd'), $name);
						} else {
							$speech = joinSpeech($speech, $name . ',');
						}
						$i++;
					}
				} else {
					foreach ($names as $name) {
						if ($i == count($names)) {
							$speech = joinSpeech($speech, lang('speechWordAnd'), $name);
						} else {
							$speech = joinSpeech($speech, $name . ",");
						}
						$i++;
					}
				}
			} else $speech = joinSpeech($speech, $names[0]);
			$tails = lang('speechAiringsTails');
			$speech = joinSpeech($speech, $tails[array_rand($tails)]);
		} else {
			if ($days == 'now') {
				$time = date('H');
				$days = 'today';
				if ($time >= 12) $days = 'this afternoon';
				if ($time >= 17) $days = 'tonight';
			}
			$errors = lang('speechAiringsErrors');
			$speech = joinSpeech($errors[array_rand($errors)], $days . ".");
		}
		sendSpeechLegacy($speech, $contextName, $cards, false);
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
				$list = $_SESSION['mediaList'] ?? $_SESSION['mlist'] ?? [];
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
						$speech = lang('speechWordOkay') . ".";
					} else {
						$speech = joinSpeech(lang('speechDontUnderstand1'), $rawspeech, lang('speechDontUnderstand2'));
					}
					sendSpeechLegacy($speech, $contextName);
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
					$affirmatives = lang('speechMoreInfoArray');
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
					sendSpeechLegacy($speech, $contextName, [$card]);
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
				$affirmatives = lang('speechPlaybackAffirmatives');
				$titlelower = strtolower($title);
				switch ($titlelower) {
					case (strpos($titlelower, 'batman') !== false):
						$affirmative = lang('speechEggBatman');
						break;
					case (strpos($titlelower, 'ghostbusters') !== false):
						$affirmative = lang('speechEggGhostbusters');
						break;
					case (strpos($titlelower, 'iron man') !== false):
						$affirmative = lang('speechEggIronMan');
						break;
					case (strpos($titlelower, 'avengers') !== false):
						$affirmative = lang('speechEggAvengers');
						break;
					case (strpos($titlelower, 'frozen') !== false):
						$affirmative = lang('speechEggFrozen');
						break;
					case (strpos($titlelower, 'space odyssey') !== false):
						$affirmative = lang('speechEggOdyssey');
						break;
					case (strpos($titlelower, 'big hero') !== false):
						$affirmative = lang('speechEggBigHero');
						break;
					case (strpos($titlelower, 'wall-e') !== false):
						$affirmative = lang('speechEggWallE');
						break;
					case (strpos($titlelower, 'evil dead') !== false):
						$affirmative = lang('speechEggEvilDead'); //"playing Evil Dead 1/2/3/(2013)"
						break;
					case (strpos($titlelower, 'fifth element') !== false):
						$affirmative = lang('speechEggFifthElement'); //"playing The Fifth Element"
						break;
					case (strpos($titlelower, 'game of thrones') !== false):
						$affirmative = lang('speechEggGameThrones');
						break;
					case (strpos($titlelower, 'they live') !== false):
						$affirmative = lang('speechEggTheyLive');
						break;
					case (strpos($titlelower, 'heathers') !== false):
						$affirmative = lang('speechEggHeathers');
						break;
					case (strpos($titlelower, 'star wars') !== false):
						$affirmative = lang('speechEggStarWars');
						break;
					case (strpos($titlelower, 'resident evil') !== false):
						$affirmative = lang('speechEggResidentEvil');
						break;
					case (strpos($titlelower, 'attack the block') !== false):
						$affirmative = lang('speechEggAttackTheBlock');
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
				writeSession('affirmative', $affirmative);
				write_log("Building speech response for a $type.");
				switch ($type) {
					case "episode":
						$yearString = "($year)";
						$seriesTitle = $queryOut['mediaResult']['grandparentTitle'];
						$episodeTitle = $queryOut['mediaResult']['title'];
						$idString = "S" . $queryOut['mediaResult']['parentIndex'] . "E" . $queryOut['mediaResult']['index'];
						$seriesTitle = str_replace($yearString, "", $seriesTitle) . " $yearString";
						$speech = joinSpeech($affirmative . lang('speechPlaying'), $episodeTitle . ".");
						$title = "$idString - $episodeTitle";
						$tagline = $seriesTitle;
						break;
					case "track":
						$artist = $queryOut['mediaResult']['grandparentTitle'];
						$track = $queryOut['mediaResult']['title'];
						$tagline = $queryOut['mediaResult']['parentTitle'];
						if ($year) $tagline .= " (" . $year . ")";
						$title = "$artist - $track";
						$speech = joinSpeech($affirmative, lang('speechPlaying'), $track, lang('speechBy'), $artist . ".");
						break;
					case "artist":
						$speech = joinSpeech($affirmative, lang('speechPlaying'), "$title.");
						break;
					case "album":
						$artist = $queryOut['mediaResult']['parentTitle'];
						$album = $queryOut['mediaResult']['title'];
						$tagline = $queryOut['mediaResult']['parentTitle'];
						if ($year) $tagline .= " (" . $year . ")";
						$speech = joinSpeech($affirmative, lang('speechPlaying'), $album . lang('speechBy'), $artist . ".");
						break;
					case "playlist":
						$title = $queryOut['mediaResult']['title'];
						$speech = joinSpeech($affirmative, lang('speechPlaying'), $title . ".");
						break;
					default:
						$title = $queryOut['mediaResult']['title'];
						$title = $title . ($year ? " (" . $year . ")" : "");
						$speech = joinSpeech($affirmative, lang('speechPlaying'), $title . ".");
						break;
				}
				if ($_SESSION['promptfortitle'] == true) {
					$contextName = 'promptfortitle';
					writeSession('promptfortitle', false);
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
				sendSpeechLegacy($speech, $contextName, $card);
				$playResult = sendMediaLegacy($mediaResult[0]);
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
				writeSession('mediaList', $mediaResult);
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
				$questions = lang('speechMultiResultArray');
				$speech = joinSpeech($questions[array_rand($questions)], $speechString);
				$contextName = "promptfortitle";
				writeSession('fallback', ['media' => $mediaResult[0]]);
				writeSession('promptfortitle', true);
				sendSpeechLegacy($speech, $contextName, $cards, true);
				$queryOut['parsedCommand'] = 'Play a media item named ' . $command . '. (Multiple results found)';
				$queryOut['mediaStatus'] = 'SUCCESS: Multiple Results Found, prompting user for more information';
				$queryOut['speech'] = $speech;
				$queryOut['playResult'] = "Not a media command.";
				logCommand(json_encode($queryOut));
				bye();
			}
			if (!count($mediaResult)) {
				if ($command) {
					$errors = [
						joinSpeech(lang('speechPlayErrorStart1'), $command, lang('speechPlayErrorEnd1')),
						joinSpeech(lang('speechPlayErrorStart2'), $command . lang('speechPlayErrorEnd2')),
						lang('speechPlayError3')
					];
					$speech = $errors[array_rand($errors)];
					$contextName = 'yes';
					$suggestions = lang('suggestionYesNo');
					sendSpeechLegacy($speech, $contextName, false, true, $suggestions);
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
		$type = (($action == 'player') ? 'clients' : 'servers');
		$deviceString = (($action == 'player') ? lang('speechPlayer') : lang('speechServer'));
		$list = $_SESSION['deviceList'];
		$list = $list[$type];
		$speech = lang('speechDeviceListError');
		$contextName = "yes";
		$waitForResponse = false;
		if (count($list) >= 2) {
			$suggestions = [];
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
			writeSession('fallback', ['device' => [$list[0]['name']]]);
			$speech = joinSpeech(lang('speechChange'), $deviceString . lang('speechChangeDevicePrompt'), $speechString);
			$contextName = "waitforplayer";
			$waitForResponse = true;
		}
		if (count($list) == 1) {
			$suggestions = false;
			$errors = lang('speechDeviceListErrorArray');
			$speech = $errors[array_rand($errors)];
			$contextName = "waitforplayer";
			$waitForResponse = false;
		}
		sendSpeechLegacy($speech, $contextName, false, $waitForResponse, $suggestions);
		$queryOut['parsedCommand'] = 'Switch ' . $action . '.';
		$queryOut['mediaStatus'] = 'Not a media command.';
		$queryOut['speech'] = $speech;
		logCommand(json_encode($queryOut));
		bye();
	}
	if ($action == 'help') {
		$errors = lang('errorHelpSuggestionsArray');
		$speech = $errors[array_rand($errors)];
		write_log("Speech: $speech");
		$button = [
			[
				'title' => lang('btnReadmePrompt'),
				'openUrlAction' => ['url' => 'https://github.com/d8ahazard/Phlex/blob/master/readme.md']
			]
		];
		$card = [
			[
				'title' => lang('cardReadmeTitle'),
				'formattedText' => '',
				'image' => ['url' => 'https://phlexchat.com/img/avatar.png'],
				'buttons' => $button
			]
		];
		$contextName = 'yes';
		$suggestions = lang('errorHelpCommandsArray');
		if ($_SESSION['plexDvrUri']) array_push($suggestions, lang('suggestionDvr'));
		if (($_SESSION['couchEnabled']) || ($_SESSION['radarrEnabled'])) array_push($suggestions, lang('suggestionCouch'));
		if (($_SESSION['sickEnabled']) || ($_SESSION['sonarrEnabled'])) array_push($suggestions, lang('suggestionSick'));
		array_push($suggestions, lang('suggestionCancel'));
		foreach ($suggestions as $suggestion) $speech = joinSpeech($speech, $suggestion);
		write_log("Speech: $speech");
		if (!$GLOBALS['screen']) $card = $suggestions = false;
		sendSpeechLegacy($speech, $contextName, $card, true, $suggestions);
		bye();
	}
	if ($action == 'fetchAPI') {
		$response = $request["result"]['parameters']["YesNo"];
		if ($response == 'yes') {
			write_log("Setting action to fetch.");
			$action = 'fetch';
		} else {
			$speech = lang('speechChangeMind');
			sendSpeechLegacy($speech, $contextName);
			bye();
		}
	}
	if ($action === 'fetch' && count($params)) {
		$newResult = fetchMediaInfo($request);
		if ($newResult) {
			write_log("Got the result, wtf: " . json_encode($newResult));
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
			sendSpeechLegacy($speech, 'DownloadMedia-followup', $card, true);
			$queryOut['mediaStatus'] = "SUCCESS: Media exists";
			$queryOut['card'] = $card;
			$queryOut['speech'] = $speech;
			logCommand(json_encode($queryOut));
			bye();
		}
	}
	if (($action == 'control') || ($control != '')) {
		if ($action == '') $command = cleanCommandString($control);
		$speech = joinSpeech(lang('speechControlConfirm1'), $command);
		if (preg_match("/volume/", $command)) {
			$int = strtolower($request["result"]["parameters"]["percentage"]);
			if ($int != '') {
				$command .= " " . $int;
				$speech = joinSpeech(lang('speechControlVolumeSet'), $int);
			} else {
				if (preg_match("/up/", $rawspeech)) {
					$command .= " UP";
					$speech = lang('speechControlVolumeUp');
				}
				if (preg_match("/down/", $rawspeech)) {
					$command .= " DOWN";
					$speech = lang('speechControlVolumeDown');
				}
			}
		} else {
			$affirmatives = lang('speechControlConfirmGenericArray');
			switch ($command) {
				case "resume":
				case "play":
					$extras = lang('speechControlConfirmPlayArray');
					break;
				case "stop":
					$extras = lang('speechControlConfirmStopArray');
					break;
				case "pause":
					$extras = lang('speechControlConfirmPauseArray');
					break;
				case "subtitleson":
					$extras = lang('speechControlConfirmSubsOnArray');
					$queryOut['parsedCommand'] = "Enable Subtitles.";
					break;
				case "subtitlesoff":
					$extras = lang('speechControlConfirmSubsOffArray');
					$queryOut['parsedCommand'] = "Disable Subtitles.";
					break;
				default:
					$extras = [
						joinSpeech(lang('speechControlGeneric1'), $command . '.'),
						joinSpeech(lang('speechControlGeneric2'), $command . ".")
					];
					$queryOut['parsedCommand'] = $command;
			}
			array_merge($affirmatives, $extras);
			$speech = $affirmatives[array_rand($affirmatives)];
		}
		$queryOut['speech'] = $speech;
		sendSpeechLegacy($speech, $contextName);
		$result = parseControlCommand($command);
		$newCommand = json_decode($result, true);
		$newCommand = array_merge($newCommand, $queryOut);
		$newCommand['timestamp'] = timeStamp();
		$result = json_encode($newCommand);
		logCommand($result);
		bye();
	}
	// Say SOMETHING if we don't undersand the request.
	$unsureAtives = lang('speechNotUnderstoodArray');
	$speech = joinSpeech($unsureAtives[array_rand($unsureAtives)], $rawspeech . "'.");
	$contextName = 'playmedia';
	sendSpeechLegacy($speech, $contextName);
	$queryOut['parsedCommand'] = 'Command not recognized.';
	$queryOut['mediaStatus'] = 'ERROR: Command not recognized.';
	$queryOut['speech'] = $speech;
	logCommand(json_encode($queryOut));
	bye();
}

/**
 * @deprecated
 * @param $command
 */
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
			$speech = joinSpeech(lang('speechChangeDeviceSuccessStart'), $typeString, lang('speechWordTo'), $command . ".");
			$contextName = 'waitforplayer';
			sendSpeechLegacy($speech, $contextName);
			setSelectedDevice($typeString, $result['Id']);
			$queryOut['playResult']['status'] = 'SUCCESS: ' . $typeString . ' changed to ' . $command . '.';
		} else {
			$speech = joinSpeech(lang('speechChangeDeviceFailureStart'), $command, lang('speechChangeDeviceFailureEnd'));
			$contextName = 'waitforplayer';
			sendSpeechLegacy($speech, $contextName);
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

/**
 * @deprecated
 * @param $matrix
 * @return array
 */
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
        $results = fetchHubItem(strtolower($title), $type);
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
                            $num = $season;
                            $epNum = $episode;
                        }
                        if (($season) && (!$episode)) {
                            $num = $season;
                            $epNum = false;
                        }
                        if ((!$season) && ($episode)) {
                            $num = $episode;
                            $epNum = false;
                        }
                        write_log("Mods Found, fetching a numbered TV Item.", "INFO");
                        if ($num) $winner = fetchNumberedTVItem($key, $num, $epNum);
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
                    $queueId = fetchPlayQueue($winner, true);
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


function fetchMediaInfo(Array $params) {
    write_log("Function fired with params: " . json_encode($params));
    $track = $album = $subtype = $season = $mod = $episode = $title = $media = false;
    $action = $params['control'] ?? false;
    if ($action) {
        $check = explode(".", $action);
        $action = $check[0];
        $type = $check[1] ?? false;
    }
    $request = $params['request'] ?? $params['music-artist'] ?? false;
    $year = $params['year']['amount'] ?? false;
    $artist = $params['music-artist'] ?? false;
    if (!$artist) {
        $data = explode(" by ", $params['resolved']);
        $artist = $data[1] ?? false;
    }
    $type = $type ?? ((($artist) && ($artist == $request)) ? ['music.artist'] : false);
    $type = $type ?? ((($artist) && ($artist != $request)) ? ['music'] : false);
    $castMember = $params['given-name'] ?? "";
    $castMember = $castMember . " " . ($params['last-name'] ?? "");
    $castMember = (trim($castMember) !== "") ? $castMember : false;
    $request = ((!$request & $castMember) ? $castMember : $request);
    $time = $params['time'] ?? $params['duration'] ?? false;
    if (is_array($time)) {
        $unit = $time['unit'];
        if ($unit == 's') $unit = 'second';
        if ($unit == 'min') $unit = 'minute';
        $time = $time['amount'] . " " . $unit;
    }
    $time = ($time ? strtotime("+$time") : false);
    $time = ($time ? ($time - strtotime("now")) : 0) * 1000;
    // Associate number values and subtypes
    $types = $params['type'] ?? $type ?? false;
    if ($types) {
        $i = 0;
        foreach ($types as $checkType) {
            $check = explode(".", $checkType);
            $type = $checkType;
            $subType = $check[1] ?? false;
            $subValue = ($subType ? ($params['number'][$i] ?? $params['mod'][$i] ?? false) : false);
            write_log("Check is " . json_encode($check) . ", type is $type, subtype is $subType, subValue is $subValue");
            switch ($subType) {
                case 'season':
                    $season = $subValue;
                    break;
                case 'episode':
                    $episode = $subValue;
                    break;
                case 'artist':
                    if (($subtype !== "track") && ($subtype !== "album")) $subtype = "artist";
                    break;
                case 'album':
                    $album = $subValue;
                    break;
                case 'track':
                case 'song':
                    write_log("Song search?? $subValue");
                    $track = $subValue;
                    break;
            }
            if ($subType && $subValue) $data["$subType"] = $subValue;
            $i++;
        }
    }
    write_log("Track $track request $request");
    if ($track && $request) {
        write_log("Ripping the word track out, because DF sucks!");
        $request = trim(str_replace("Track", "", $request));
        $request = trim(str_replace("track", "", $request));
    }
    $type = ($type ? $type : ($artist ? 'music' : false));
    $type = ($type ? $type : ($album ? 'music.album' : false));
    $type = ($type ? $type : ($track ? 'music.track' : false));
    $data = [
        'action' => $action,
        'request' => $request,
        'type' => $type,
        'season' => $season,
        'episode' => $episode,
        'artist' => $artist,
        'album' => $album,
        'track' => $track,
        'offset' => $time,
        'year' => $year
    ];
    foreach ($data as $key => $value) if ($value === false) unset($data["$key"]);
    $musicData = $searches = [];
    if (preg_match("/music/", $type)) {
        $musicData = fetchMusicInfo($request, $artist, $album);
        $searches = array_merge($searches, $musicData['urls']);
    }
    if (preg_match("/show/", $type)) {
        $searches['show'] = fetchTvInfo($request);
    }
    if (preg_match("/movie/", $type)) {
        $searches['movie'] = fetchMovieInfo($request, 'movie');
    }
    if (!$type) {
        $musicData = fetchMusicInfo($request, $artist);
        $searches = array_merge($searches, $musicData['urls']);
        $searches['show'] = fetchTvInfo($request);
        $searches['movie'] = fetchMovieInfo($request, 'movie');
    }
    foreach ($_SESSION['deviceList']['Server'] as $server) {
        $id = $server['Id'];
        $searches["plex.$id"] = $server['Uri'] . "/hubs/search?query=" . urlencode($request) . "&limit=30&X-Plex-Token=" . $server['Token'];
    }
    write_log("Search array: " . json_encode($searches));
    $dataArray = multiCurl($searches);
    $md = $musicData['music.artist'];
    array_push($dataArray, $md);
    write_log("Raw data array: " . json_encode($dataArray));
    $result = mapData($dataArray);
    $media = $result['media'];
    $meta = $result['meta'];
    $ep = $key = $parent = false;
    if ($season || $episode && count($meta) >= 1) {
        write_log("We need a numbered TV item.");
        if (count($media)) {
            foreach ($media as $item) {
                if (compareTitles($item['title'], $request)) {
                    write_log("Found some parent media...");
                    $key = $item['key'] ?? false;
                    $source = $item['source'] ?? false;
                    if ($source) $parent = findDevice("Id", $source, "Server");
                }
            }
        }
        $eps = [];
        write_log("Meta here: " . json_encode($meta));
        foreach ($meta as $metaItem) {
            if ($metaItem['type'] == "show.episode") {
                if ($season && $episode) {
                    if ($season != -1 && $episode != -1) {
                        if ($metaItem['season'] == $season && $metaItem['episode'] == $episode) {
                            write_log("Found a matching episode in metadata.");
                            $meta = [$metaItem];
                            $ep = $metaItem;
                            $season = $metaItem['season'];
                            $episode = $metaItem['episode'];
                        }
                    }
                } else {
                    array_push($eps, $metaItem);
                }
            }
        }
        if ($episode && $eps) {
            $episode = (($episode == -1) ? (count($eps) - 1) : ($episode - 1));
            $ep = $eps[$episode] ?? false;
            write_log("Episode");
        }
        if ($ep) {
            write_log("We have a meta episode");
            $data['request'] = $ep['title'];
            $data['type'] = "episode";
            $data['year'] = $ep['year'];
            $data['season'] = $ep['season'];
            $data['episode'] = $ep['episode'];
            $season = $ep['season'];
            $episode = $ep['episode'];
        }
        if ($key && $parent) {
            $item = fetchNumberedTVItem($key, $season, $episode, $parent);
            $item['source'] = $parent['Id'];
            if ($item) {
                $media = [$item];
                $data['request'] = $item['title'];
                $data['type'] = 'episode';
                write_log("We have the episode, data should be updated: " . json_encode($data));
            }
        }
    }
    if ($track && count($meta) >= 1) {
        write_log("We need to find a specific track number.");
        if (count($media)) {
            $tracks = [];
            foreach ($media as $item) {
                if (strtolower($item['title']) == strtolower($params['request']) && $item['type'] == 'album') {
                    write_log("Got an album: " . json_encode($item));
                    $host = findDevice("Id", $item['source'], "Server");
                    $album = false;
                    if ($host) {
                        $url = $host['Uri'] . $item['key'] . "?X-Plex-Token=" . $host['Token'];
                        write_log("Album URL is '$url'.");
                        $album = curlGet($url);
                    }
                    if ($album) {
                        write_log("Album lookup worked!");
                        $container = new JsonXmlElement($album);
                        $container = $container->asArray();
                        $trackArray = $container['MediaContainer']['Track'] ?? [];
                        write_log("TrackArray: " . json_encode($trackArray));
                        foreach ($trackArray as $trackItem) {
                            write_log("Track index vs track is " . $trackItem['index'] . " and $track");
                            if ($trackItem['index'] == $track) {
                                $trackItem['source'] = $item['source'];
                                write_log("We found a matching track: " . json_encode($trackItem));
                                $new = mapDataPlex($trackItem);
                                write_log("Mapped: " . json_encode($new));
                                $tracks[] = $new;
                                break;
                            }
                        }
                    }
                }
            }
            if (count($tracks) == 1) {
                $media = $tracks;
                $data['request'] = $tracks[0]['title'];
                $data['type'] = 'track';
            }
        }
        foreach ($meta as $item) {
            //	write_log("Meta Item: ".json_encode($item));
        }
    }
    $matched = mapDataResults($data, $media, $meta);
    return $matched;
}

/**
 * @deprecated
 * @param $command
 * @return array|bool|mixed
 */
function queryApiAi($command) {
    $counter = (isset($_SESSION['counter2']) ? $_SESSION['counter2']++ : 0);
    writeSession('counter2', $counter);
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

/**
 * @deprecated
 * @param $speech
 * @param $contextName
 * @param bool $cards
 * @param bool $waitForResponse
 * @param bool $suggestions
 */
function sendSpeechLegacy($speech, $contextName, $cards = false, $waitForResponse = false, $suggestions = false) {
    write_log("My reply is going to be '$speech'.", "INFO");
    if (isset($_GET['say'])) return;
    $amazonRequest = $_SESSION['amazonRequest'] ?? false;
    if ($amazonRequest) {
        sendSpeechAlexa($speech, $contextName, $cards, $waitForResponse, $suggestions);
    } else {
        sendSpeechAssistant($speech, $contextName, $cards, $waitForResponse, $suggestions);
    }
    if (function_exists('fastcgi_finish_request')) {
        fastcgi_finish_request();
    }
}

//**
// PUT DEVICE STUFF HERE
//  */

function findDevice($key, $value, $type) {
    $devices = $_SESSION['deviceList'];
    $section = $devices["$type"] ?? false;
    write_log("Looking for a $type with a $key of $value");
    if ($section) {
        write_log("Full section: " . json_encode($section));
        foreach ($section as $device) {
            if (trim(strtolower($device["$key"])) === trim(strtolower($value))) {
                write_log("Returning matching device: " . json_encode($device));
                return $device;
            }
        }
    }
    return false;
}

function scanDevices($force = false) {
	//Variables
	$clients = $devices = $dvrs = $results = $servers = [];
	// Check to see if our cache should be refreshed.
	$now = microtime(true);
	$rescanTime = $_SESSION['rescanTime'] ?? 8;
	write_log("RESCAN TIME is " . $_SESSION['lastScan']);
	$ls = $_SESSION['lastScan'];
	$lastCheck = $ls ?? ceil(round($now) / 60) - $rescanTime;
	$diffMinutes = ceil(round($now - $lastCheck) / 60);
	$timeOut = ($diffMinutes >= $rescanTime);
	$list = fetchDeviceCache();
	write_log("Existing device list: " . json_encode($list));
	$noServers = (!count($list['Server']));
	// Log things
	$msg = "Re-caching because of ";
	if ($force) $msg .= "FORCE & ";
	if ($timeOut) $msg .= "TIMEOUT &";
	if ($noServers) $msg .= "NO SERVERS";
	$msg = rtrim($msg, " &");
	// Set things up to be recached
	if ($force || $timeOut || $noServers) {
		if (isset($_SESSION['scanning'])) {
			if ($_SESSION['scanning']) {
				writeSession('scanning', false);
				write_log("Breaking scanning loop.", "WARN");
				return $list;
			}
		}
		write_log("$msg", "INFO");
		writeSession('scanning', true);
		$https = !$_SESSION['noLoop'];
		$query = "?includeHttps=$https&includeRelay=0&X-Plex-Token=" . $_SESSION['plexToken'];
		$container = simplexml_load_string(doRequest([
			'uri' => 'https://plex.tv/api/resources',
			'query' => $query
		], 3));
		if ($container) {
			$devices = flattenXML($container);
			foreach ($devices['Device'] as $device) {
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
		} else {
			write_log("Plex.TV is down, breaking to save device list.", "WARN");
			writeSession('scanning', false);
			die();
		}
		write_log("Currently have " . count($servers) . " Servers and " . count($clients) . " clients.");
		// Check set URI and public URI for servers, testing both http and https variables
		if (count($servers)) {
			$result = [];
			foreach ($servers as $server) {
				$name = $server['Name'];
				write_log("Checking $name: " . json_encode($server), "INFO");
				$connections = $server['Connection'];
				if (isset($connections['protocol'])) $connections = [$connections];
				foreach ($connections as $connection) {
					$query = '?X-Plex-Token=' . $server['Token'];
					$uri = $connection['uri'] . $query;
					$proto = $server['httpsRequired'] ? "https://" : "http://";
					$localAddress = $proto . $connection['address'] . ":" . $connection['port'];
					$backup = $localAddress . $query;
					$local = (boolval($connection['local'] == boolval($server['publicAddressMatches'])));
					$web = filter_var($connection['address'], FILTER_VALIDATE_IP, FILTER_FLAG_NO_RES_RANGE);
					$secure = (($server['httpsRequired'] && $connection['protocol'] === 'https') || (!$server['httpsRequired']));
					$cloud = preg_match("/plex.services/", $connection['address']);
					if ($connection['local'] && !isset($connection['relay']) && !$cloud) $server['localUri'] = $localAddress;
					if (($local && $web && $secure) || $cloud) {
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
					write_log("Adding $name to server list.", "INFO");
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
			    write_log("NO PLUGIN DETECTED!!","ERROR",false,true);
				writeSession('alertPlugin', true);
			}
			// If this has never been set before
			if (!isset($_SESSION['alertPlugin'])) updateUserPreference('alertPlugin', true);
			if ($_SESSION['alertPlugin'] && !$_SESSION['hasPlugin']) {
			    write_log("Building message now!","INFO");
			    # TODO: Internationalize this
				$message = "The Cast plugin was not found on any of your servers. Click here to find out how to get it.";
				$alert = [
					[
						'title' => 'Cast Plugin Not Found!',
						'message' => $message,
						'url' => "https://github.com/d8ahazard/FlexTV.bundle"
					]
				];
				writeSession('messages', $alert);
				// Once we've sent the alert once, don't show it again
				updateUserPreference('alertPlugin', false);
			}
		}
		$results['Server'] = $servers;
		$results['Client'] = $clients;
		$results['Dvr'] = $dvrs;
		$results = sortDevices($results);
		updateDeviceCache($results);
		write_log("Final device array: " . json_encode($results), "INFO");
		writeSession('scanning', false);
	} else {
		$results = $list;
	}
	return $results;
}

function scrapeServers($serverArray) {
    $clients = $dvrs = $responses = $urls = [];
    write_log("Scraping " . count($serverArray) . " servers.");
    foreach ($serverArray as $device) {
        $serverUri = $device['uri'];
        $serverId = $device['Id'];
        $token = $device['Token'];
        $url1 = [
            'url' => "$serverUri/chromecast/clients?X-Plex-Token=$token",
            'device' => $device
        ];
        $url2 = [
            //'url' => "$serverUri/tv.plex.providers.epg.onconnect?X-Plex-Token=$token",
            'url' => "$serverUri/livetv/dvrs?X-Plex-Token=$token",
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
            $data = [
                'response' => $response,
                'device' => $ch[1]
            ];
            array_push($responses, $data);
        }
    }
    if (count($responses)) foreach ($responses as $data) {
        $device = $data['device'];
        $data = xmlToJson($data['response']);
        $token = $device['Token'];
        $serverId = $device['Id'];

        if ($data) {
            $castDevices = $data['MediaContainer']['Device'] ?? false;
            $dvrResponse = $data['MediaContainer']['Dvr'] ?? false;
            write_log("Data Response: ".json_encode($data));
            $key = $dvrResponse[0]['key'] ?? false;
            if ($key) {
                $device['key'] = $key;
                $lineup = explode("-",$dvrResponse[0]['lineup'])[1] ?? false;
                $lineup = $lineup ? str_replace("OTA","",explode("#",$lineup)[0]) : $lineup;
                $settings = $dvrResponse[0]['Settings'];
                foreach($settings as $setting) {
                    switch($setting['id']) {
                        case 'startOffsetMinutes':
                        case 'endOffsetMinutes':
                        case 'comskipEnabled':
                            $name = $setting['id'];
                            $device["$name"] = intval($setting['value']);
                    }
                }
                write_log("Zip code is $lineup");
                $device['zip'] = $lineup;

                array_push($dvrs, $device);
            }
            if ($castDevices) {
                $hasPlugin = $_SESSION['hasPlugin'] ?? false;
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
                            'Parent' => $serverId,
                            'Uri' => $castDevice['uri']
                        ];
                        array_push($clients, $device);
                    }
                }
            }
        }
    }
    if (count($clients) || count($dvrs)) {
        $returns = [
            'Client' => $clients,
            'Dvr' => $dvrs
        ];
    } else $returns = false;
    write_log("Final returns: " . json_encode($returns));
    return $returns;
}

function selectDevices($results) {
	$output = [];
	foreach ($results as $class => $devices) {
		$classOutput = [];
		$sessionId = $_SESSION["plex" . $class . "Id"] ?? false;
		$i = 0;
		//if ($sessionId) write_log("We've got a $class ID to match.","INFO",false,true);
		foreach ($devices as $device) {
			if ($sessionId) {
				if ($device['Id'] == $sessionId) {
					//write_log("Found a matching $class device named " . $device["Name"], "INFO",false,true);
					$device['Selected'] = true;
				} else {
					$device['Selected'] = false;
				}
			} else {
				if ($i === 0) {
					write_log("No selected $class currently, picking one.", "WARN");
					setSelectedDevice($class, $device['Id']);
				}
				$device['Selected'] = (($i === 0) ? true : false);
			}
			array_push($classOutput, $device);
			$i++;
		}
		$output[$class] = $classOutput;
	}
	return $output;
}

function setSelectedDevice($type,$id) {
    write_log("Function fired.");
    $list = $_SESSION['deviceList'] ?? [];
    $selected = false;
    $current = $_SESSION['plex'.$type."Id"] ?? "000";
    if ($current == $id) {
        write_log("Skipping because device is already selected.");
        return $list;
    }

    foreach($list[$type] as $device) {
        write_log("Comparing $id to ".$device['Id']);
        if (trim($id) === trim($device['Id'])) {
            $selected = $device;
            if (!isset($selected['Parent']) && $type == 'Client') {
                $selected['Parent'] = $_SESSION['plexServerId'];
            }
        }
    }

    if (!$selected && count($list[$type])) {
        write_log("Unable to find selected device in list, defaulting to first item.","WARN");
        $selected = $list[$type][0];
    }

    if (is_array($selected)) {
        $new = $push = [];
        foreach($list[$type] as $device) {
            $device['Selected'] = ($device['Id'] === $id) ? true : false;
            array_push($new, $device);
        }
        $list[$type] = $new;
        write_log("Going to select ". $selected['Name']);
        foreach ($selected as $key=>$value) {
            $uc = ucfirst($key);
            if ((($type === 'Server' || $type==='Dvr') && ($uc === "Parent" || $uc === "Selected")) ||
                ($uc ==='Presence' || $uc === 'Last_seen') || $uc === 'Sections') {
                write_log("Skipping attribute '$uc'.");
            } else {
                $itemKey = "plex$type$uc";
                $push[$itemKey] = $value;
            }
        }

        writeSessionArray([['deviceList' => $list, 'deviceUpdated' =>true]]);
        if ($type == 'Client' && isset($_SESSION['volume'])) writeSession('volume',"",true);
        $push['dlist'] = base64_encode(json_encode($list));
        updateUserPreferenceArray($push);
    } else {
        if ($type == 'Dvr') {
		$dvr = $_SESSION['plexDvrId'] ?? false;
		if ($dvr) updateUserPreference('plexDvrId',false);
        }
    }
    return $list;
}

function sortDevices($input) {
    write_log("Input list: " . json_encode($input));
    $results = [];
    foreach ($input as $class => $devices) {
        $ogCount = count($devices);
        write_log("Sorting $ogCount $class devices.", "INFO");
        $names = $output = [];
        foreach ($devices as $device) {
            $push = true;
            $name = $device['Name'];
            $id = $device['Id'];
            $type = $device['Type'] ?? $device['Product'];
            $uri = parse_url($device['Uri'])['host'] ?? true;
            foreach ($output as $existing) {
                $exUri = parse_url($existing['Uri'])['host'] ?? false;
                $exId = $existing['Id'];
                $exType = $existing['type'] ?? 'client';
                $idMatch = ($exId === $id);
                $hostMatch = ($exUri === $uri);
                $skipGroup = (($type === 'group') || ($exType === 'group'));
                if (($hostMatch || $idMatch) && (!$skipGroup)) {
                    write_log("Skipping $type device named $name because " . ($hostMatch ? 'uris' : 'ids') . " match.", "INFO");
                    write_log("Type $type extype $exType uri $uri exuri $exUri");
                    $push = false;
                }
            }
            $exists = array_count_values($names)[$name] ?? false;
            $displayName = $exists ? "$name ($exists)" : $name;
            if ($push) {
                if ($class == 'Client') {
                    $new = [
                        'Name' => $name,
                        'FriendlyName' => $displayName,
                        'Id' => $device['Id'],
                        'Product' => $device['Product'],
                        'Type' => 'Client',
                        'Token' => $device['Token'] ?? $_SESSION['plexToken']
                    ];
                    if (isset($device['Uri'])) $new['Uri'] = $device['Uri'];
                    if (isset($device['Parent'])) $new['Parent'] = $device['Parent'];
                } else {
                    $new = [
                        'Name' => $name,
                        'FriendlyName' => $displayName,
                        'Id' => $device['Id'],
                        'Uri' => $device['uri'],
                        'Token' => $device['Token'],
                        'Product' => $device['Product'],
                        'Type' => $class,
                        'Key' => $device['key'] ?? false,
                        'Version' => $device['Version']
                    ];
                    if ($class === 'Server') $new['Sections'] = json_encode(fetchSections($new));
                    if (($class !== "Dvr") && (isset($device['localUri']))) $new['localUri'] = $device['localUri'];
                }
                array_push($names, $name);
                array_push($output, $new);
            }
        }
        $ogCount = $ogCount - count($output);
        write_log("Removed $ogCount duplicate devices: " . json_encode(array_diff($devices, $output)));
        $results[$class] = $output;
    }
    return $results;
}

function updateDeviceCache($data) {
	$now = microtime(true);
	$list = $_SESSION['deviceList'] ?? [];
	$updated = [];
	foreach ($data as $section => $devices) {
	    $removeCurrent = true;
	    $selected = $_SESSION["plex".$section."Id"];
		$sectionOut = [];
		$existing = $list["$section"] ?? [];
		foreach ($devices as $device) {
			$device['last_seen'] = $now;
			$out = $device;
			$merged = false;
			$push = true;
			foreach ($existing as $check) {
				if ($device['Id'] === $check['Id'] && $device['Product'] === $check['Product']) {
					$out = array_merge($check, $device);
					$merged = true;
				}
			}
			$out['presence'] = $merged;
			if (!$merged) {
				$last = $out['last_seen'];
				$diff = ($now - $last) / 60 / 60;
				write_log("Now $now last $last diff is $diff");
				if ($diff >= 24) {
					write_log("Device hasn't been seen in 24 hours, dropping from cache.", "WARN");
					$push = false;
				}
			}
			if ($push) {
			    if ($selected == $out['Id']) $removeCurrent = false;
			    $sectionOut[] = $out;
            }
		}
		if ($removeCurrent) setSelectedDevice($section,"foo");
		$updated["$section"] = $sectionOut;
	}
	$devices = $updated;
	write_log("Original device cache: " . json_encode($list));
	write_log("Updated device cache: " . json_encode($devices));
	write_log("Diff: " . json_encode(array_diff_assoc($list, $devices)));
	writeSession('deviceList', $devices);
	writeSession('deviceUpdated', true);
	$string = base64_encode(json_encode($devices));
	$prefs = [
		'lastScan' => $now,
		'dlist' => $string
	];
	updateUserPreferenceArray($prefs);
}



//**
// GRAB DATA FROM VARIOUS ENDPOINTS
// */

function fetchAirings($params) {
    write_log("Function fired!");
    $list = [];
    $startDate = new DateTime('today');
    $endDate = new DateTime('tomorrow');
    $date = $params['date'] ?? false;
    $times = $params['time-period'] ?? false;
    if ($date) {
        $startDate = new DateTime("$date");
        $endDate = new DateTime("$date +1 day");
    }
    if ($times) {
        $times = explode("/", $times);
        $startTime = DateInterval::createFromDateString($times[0]);
        $endTime = DateInterval::createFromDateString($times[1]);
        $startDate = $startDate->add($startTime);
        $endDate = $endDate->add($endTime);
    }
    $date2 = $endDate->format('Y-m-d');
    $date1 = $startDate->format('Y-m-d');
    write_log("StartDate: $date1 EndDate: " . $date2);
    if ($_SESSION['sickEnabled'] ?? false) {
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
                            'title' => $showName,
                            'epnum' => $show['episode'],
                            'seasonnum' => $show['season'],
                            'summary' => $show['ep_plot'],
                            'type' => 'episode',
                            'source' => 'sick'
                        ];
                        write_log("Found a show on sick: " . json_encode($item), "INFO");
                        array_push($list, $item);
                    }
                }
            }
        }
    }
    if ($_SESSION['sonarrEnabled'] ?? false) {
        write_log("Checking Sonarr for episodes...");
        $sonarr = new Sonarr($_SESSION['sonarrUri'], $_SESSION['sonarrAuth']);
        $scheduled = json_decode($sonarr->getCalendar($date1, $date2), true);
        if ($scheduled) {
            foreach ($scheduled as $show) {
                $item = [
                    'title' => $show['series']['title'],
                    'epnum' => $show['episodeNumber'],
                    'seasonnum' => $show['seasonNumber'],
                    'summary' => $show['overview'] ?? $show['series']['overview'],
                    'type' => 'episode',
                    'source' => 'sonarr'
                ];
                write_log("Found a show on Sonarr: " . json_encode($item), "INFO");
                array_push($list, $item);
            }
        }
    }
    if ($_SESSION['plexDvrUri'] ?? false) {
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
                            'title' => $show['grandparentTitle'],
                            'epnum' => intval($show['index']),
                            'seasonnum' => intval($show['parentIndex']),
                            'summary' => $show['summary'],
                            'source' => $_SESSION['plexDvrId'],
                            'type' => 'episode'
                        ];
                        write_log("Found a show on Plex DVR: " . json_encode($item), "INFO");
                        array_push($list, $item);
                    }
                }
            }
        }
    }
    foreach ($list as &$item) {
        $data = curlGet(fetchTvInfo($item['title']));
        if ($data) {
            $data = json_decode($data, true);
            $data['type'] = 'show';
            $results = mapDataShow($data);
        }
        write_log("Item data? : " . json_encode($results));
        $item = $results ? $results : $item;
        $item['thumb'] = $item['art'];
    }
    $list = (count($list) ? $list : false);
    write_log("Final airings list: " . json_encode($list));
    return $list;
}

function fetchApiAiData($command) {
    $v2 = $_SESSION['v2'] ?? false;
    $context = $_SESSION['context'] ?? false;
    if ($context) write_log("We have a context!!", "INFO");
    $d = $v2 ? fetchDirectory(6) : fetchDirectory(3);
    $sessionId = $_SESSION['sessionId'] ?? rand(10000, 100000);
    writeSession('sessionId', $sessionId);
    $dialogFlow = new dialogFlow($d, getDefaultLocale(), 1, "$sessionId");
    $response = $dialogFlow->query($command, null, $context);
    $url = $dialogFlow->lastUrl();
    $json = json_decode($response, true);
    if (is_null($json)) {
        write_log("Error parsing API.ai response.", "ERROR");
        return false;
    }
    $request = $dialogFlow->process($json);
    return $request;
}

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

function fetchFirstUnwatchedEpisode($key, $parent = false) {
    $uri = $parent['Uri'] ?? $_SESSION['plexServerUri'];
    $token = $parent['Token'] ?? $_SESSION['plexServerToken'];
    $mediaDir = preg_replace('/children$/', 'allLeaves', $key);
    $result = doRequest([
        'uri' => $uri,
        'path' => $mediaDir,
        'query' => "?X-Plex-Token=$token"
    ]);
    if ($result) {
        $container = new JsonXmlElement($result);
        $container = $container->asArray();
        write_log("Container: " . json_encode($container));
        $videos = $container['MediaContainer']['Video'];
        foreach ($videos as $video) {
            $count = $video['viewcount'] ?? 0;
            if ($count == 0) {
                $video['art'] = $container['art'];
                $video['librarySectionID'] = $container['librarySectionID'];
                return $video;
            }
        }
        // If no unwatched episodes, return the first episode
        if (!$videos) {
            $video = $videos[0];
            $video['art'] = $container['art'];
            $video['librarySectionID'] = $container['librarySectionID'];
            return $video;
        }
    }
    return false;
}

function fetchHubItem($title, $type = false) {
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
            $match = compareTitles($cleaned, $cleanedTitle);
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
    foreach ($returns as $return) {
        $skip = false;
        foreach ($new as $existing) {
            if ($return['ratingKey'] == $existing['ratingKey']) $skip = true;
        }
        if (!$skip) array_push($new, $return);
    }
    return $new;
}

function fetchHubList($section, $type = false) {
    $path = $results = false;
    write_log("Type is $type");
    $query = [];
    $serverId = $_SESSION['plexServerId'];
    $host = findDevice("Id", $serverId, "Server");
    if ($section == 'recent') {
        $path = '/hubs';
        if ($type) {
            $type = explode(".", $type)[0];
            $types = [
                "show" => '2',
                'movie' => '1',
                'music' => '8'
            ];
            $type = $types["$type"] ?? false;
            $path = $type ? '/hubs/home/recentlyAdded' : $path;
            $query = ['type' => $type];
        }
    }
    if ($section == 'ondeck') $path = '/hubs/home/onDeck';
    if ($path) {
        $query = array_merge($query, [
            'X-Plex-Token' => $host['Token'],
            'X-Plex-Container-Start' => '0',
            'X-Plex-Container-Size' => $_SESSION['returnItems'] ?? '6'
        ]);
        $result = doRequest([
            'uri' => $host['Uri'],
            'path' => $path,
            'query' => $query
        ]);
        if ($result) {
            $container = new JsonXmlElement($result);
            $container = $container->asArray();
            write_log("Hub response: " . json_encode($container));
            if ($section == 'recent' && !$type) {
                $hubs = $container['MediaContainer']['Hub'];
                $results = [];
                foreach ($hubs as $hub) {
                    if ($hub['hubIdentifier'] == 'home.movies.recent' ||
                        $hub['hubIdentifier'] == 'home.television.recent' ||
                        $hub['hubIdentifier'] == 'home.music.recent') {
                        write_log("Merging videos from " . $hub['hubIdentifier']);
                        foreach ($hub['Video'] as $video) array_push($results, $video);
                    }
                }
                write_log("Results: " . json_encode($results));
                $results = shuffle_assoc($results);
                write_log("Results: " . json_encode($results));
            } else {
                $results = $container['MediaContainer']['Video'] ?? $container['MediaContainer']['Directory'] ?? false;
                $count = $_SESSION['returnItems'] ?? 6;
            }
            if (is_array($results) && count($results) > $count) $results = array_slice($results, 0, $count);
        }
    }
    $out = [];
    foreach ($results as $result) {
        $result['source'] = $serverId;
        $out[] = $result;
    }
    return $out;
}

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

function fetchMusicInfo($title, $artist = false, $album = false) {
    write_log("Function fired.");
    $d = fetchDirectory(4);
    $d2 = fetchDirectory(5);
    $title = urlencode($title);
    $artistData = $url = [];
    $data = $id = false;
    $artist = $artist ? urlencode($artist) : false;
    if ($artist) {
        $artistUrl = "http://www.theaudiodb.com/api/v1/json/$d/search.php?s=$artist";
        if ($album) {
            write_log("We know we're searching for a numbered album...no need to do track searches and stuff.");
            $data = curlGet($artistUrl);
            if ($data) {
                $data = json_decode($data, true)['artists'];
                write_log("Artist data: " . json_encode($data));
                foreach ($data as $artistCheck) {
                    if ($artistCheck['strArtist'] === $artist) {
                        $id = $artistCheck['idArtist'];
                        $artistData = ['artist.data' => $artistCheck];
                        break;
                    }
                }
                if ($id) $url['music.albums'] = "http://www.theaudiodb.com/api/v1/json/$d/album.php?i=$id";
            }
        } else {
            $url['music.artist'] = $artistUrl;
        }
    } else {
        $url['music.artist'] = "http://www.theaudiodb.com/api/v1/json/$d/search.php?s=$title";
    }
    if (!$album) $url['music.albums'] = "http://www.theaudiodb.com/api/v1/json/$d/searchalbum.php?s=$title";
    if (!$album) $url['music.album'] = "http://www.theaudiodb.com/api/v1/json/$d/searchalbum.php?a=$title" . ($artist ? "&s=$artist" : "");
    if ($artist && !$album) $url['music.track'] = "http://www.theaudiodb.com/api/v1/json/$d/searchtrack.php?s=$artist&t=$title";
    $url['music.deezer'] = "https://api.deezer.com/search?q=$title";
    $returns = [
        'urls' => $url,
        'music.artist' => $artistData
    ];
    write_log("Returns: " . json_encode($returns));
    return $returns;
}

function fetchMovieInfo($title, $type = false, $season = false, $episode = false) {
    write_log("Function fired.");
    $title = urlencode($title);
    $d = fetchDirectory(1);
    $type = ($type ? $type : 'multi');
    $url = 'https://api.themoviedb.org/3/search/' . $type . '?query=' . urlencode($title) . '&api_key=' . $d . '&page=1';
    return $url;
}

function fetchNowPlaying() {
    $result = $urls = [];
    $servers = $_SESSION['deviceList']['Server'] ?? [];
    foreach ($servers as $server) $urls[] = $server['Uri'] . "/status/sessions?X-Plex-Token=" . $server['Token'];
    $sessions = count($urls) ? multiCurl($urls) : false;
    foreach ($sessions as $serverSession) {
        $serverSession = new JsonXmlElement($serverSession);
        $serverSession = $serverSession->asArray();
        if (is_array($serverSession)) $sessionList = $serverSession['MediaContainer'] ?? false;
    }
    return $result;
}

function fetchTtsFile($text) {
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

function fetchTvInfo($title) {
    write_log("Function fired.");
    $title = urlencode($title);
    $url = "http://api.tvmaze.com/singlesearch/shows?q=$title&embed=episodes";
    return $url;
}

function fetchNumberedTVItem($seriesKey, $num, $epNum = false, $parent = false) {
    $selector = $epNum ? 'season' : 'episode';
    $match = false;
    write_log("Searching for " . $selector . " number " . $num . ($epNum ? ' and episode number ' . $epNum : ''), "INFO");
    $mediaDir = preg_replace('/children$/', 'allLeaves', $seriesKey);
    $host = $parent ? $parent['Uri'] : $_SESSION['plexServerUri'];
    $token = $parent ? $parent['Token'] : $_SESSION['plexServerToken'];
    $url = "$host$mediaDir?X-Plex-Token=$token";
    $result = curlGet($url);
    if ($result) {
        $container2 = new JsonXmlElement($result);
        $series = $container2->asArray();
        $episodes = $series['MediaContainer']['Video'];
        write_log("New container: " . json_encode($container2));
        // If we're specifying a season, get all those episodes who's ParentIndex matches the season number specified
        if ($selector == "season") {
            foreach ($episodes as $episode) {
                if ($epNum) {
                    if (($episode['parentIndex'] == $num) && ($episode['index'] == $epNum)) {
                        $match = $episode;
                        break;
                    }
                } else {
                    if ($episode['parentIndex'] == $num) {
                        $match['index'] = $episode['parentIndex'];
                        break;
                    }
                }
            }
        } else {
            $episode = $episodes[intval($num) - 1] ?? false;
            if ($episode) {
                $match = $episode;
            }
        }
    }
    if ($match) {
        $match = mapDataPlex($match);
        write_log("Found match: " . json_encode($match), "INFO");
    }
    return $match;
}

function fetchPlayerState($wait = false) {
    $result = false;
    $timeout = $wait ? 5 : 1;
    $status = ['status' => 'idle'];
    $serverId = $_SESSION['plexClientParent'] ?? $_SESSION['plexServerId'];
    $server = findDevice("Id", $serverId, "Server");
    $serverUri = $server['Uri'] ?? false;
    if ($serverUri) {
        $count = $_SESSION['counter'] ?? 1;
        $headers = array_merge(plexHeaders(), clientHeaders());
        $headers = array_unique($headers);
        $params = headerQuery($headers);
        if ($_SESSION['plexClientProduct'] == 'Cast') {
            $uri = urlencode($_SESSION['plexClientId']);
            $url = "$serverUri/chromecast/status?X-Plex-Clienturi=$uri&X-Plex-Token=" . $_SESSION['plexClientToken'];
        } else {
            $url = "$serverUri/player/timeline/poll?wait=1&commandID=$count$params";
        }
        write_log("URL is '$url'", "INFO", false, true);
        $result = doRequest($url, $timeout);
    } else {
        write_log("Error fetching Server info: ".json_encode($server),"ERROR",false,true);
    }
    if ($result) {
        $result = new JsonXmlElement($result);
        $result = $result->asArray();
        write_log("Player JSON: " . json_encode($result), "INFO", false, true);
        if (isset($result['Timeline'])) {
            foreach ($result['Timeline'] as $timeline) {
                $id = $timeline['machineIdentifier'];
                $sessionId = $_SESSION['plexClientId'];
                write_log("Id $id vs clientId $sessionId");
                if ($id == $sessionId) {
                    write_log("ID's match.", "INFO", false, true);
                    $state = strtolower($timeline['state']);
                    $volume = $timeline['volume'];
                    $status = [
                        'state' => $state,
                        'volume' => intval($volume) / 100
                    ];
                }
            }
        } else {
            $status = [
                'state' => strtolower($result['state']),
                'volume' => $result['volume'],
                'muted' => $result['muted']
            ];
        }
    }
    write_log("Returning player status: " . json_encode($status), "INFO", false, true);
    return $status;
}

function fetchPlayerStatus() {
    //write_log("Function fired.","INFO",false,true);
    $addresses = parse_url($_SESSION['plexClientUri']);
    $host = $_SESSION['plexClientParent'] ?? $_SESSION['plexServerId'];
    $token = $_SESSION['plexClientToken'] ?? $_SESSION['plexServerToken'];
    $clientIp = $addresses['host'] ?? true;
    $clientId = $_SESSION['plexClientId'];
    $clientProduct = $_SESSION['plexClientProduct'] ?? true;
    $state = 'idle';
    $status = ['status' => $state];
    $host = findDevice("Id", $host, "Server");
    $url = $host['Uri'] . '/status/sessions?X-Plex-Token=' . $token;
    $result = curlGet($url);
    if ($result) {
        //write_log("Raw result: ".$result,"INFO",false,true);
        $jsonXML = new JsonXmlElement($result);
        $jsonXML = $jsonXML->asArray();
        //write_log("JSONXML: ".json_encode($jsonXML),"INFO",false,true);
        $mc = $jsonXML['MediaContainer'] ?? false;
        if ($mc) {
            $track = $mc['Track'] ?? [];
            $video = $mc['Video'] ?? [];
            $obj = array_merge($track, $video);
            foreach ($obj as $media) {
                //write_log("Media: ".json_encode($media),"INFO",false,true);
                // Get player info
                $player = $media['Player'][0];
                $product = $player['product'] ?? false;
                $playerId = $player['machineIdentifier'] ?? false;
                $playerIp = $player['address'] ?? true;
                //write_log("Player IP? $playerIp and client IP $clientIp and client ID $clientId and product $product","INFO",false,true);
                $streams = $media['Media'][0]['Part'][0]['Stream'];
                $castDevice = (($product === 'Plex Chromecast') && ($clientProduct = "cast"));
                $isCast = ($castDevice && ($clientIp == $playerIp));
                $isPlayer = (($clientId && $playerId) && ($clientId == $playerId));
                $state = 'idle';
                if ($isPlayer || $isCast) {
                    //write_log("We got a bite!","INFO",false,true);
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
                    $thumb = transcodeImage($thumb, $host['Uri'], $host['Token']);
                    $art = transcodeImage($media['art'], $host['Uri'], $host['Token'], true);
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
                        'type' => $type,
                        'streams' => $streams
                    ];
                    $status = [
                        'status' => $state,
                        'time' => $time,
                        'mediaResult' => $mediaResult,
                        'volume' => $_SESSION['volume'] ?? false
                    ];
                }
            }
        }
    }
    write_log("Status: " . json_encode($status));
    $currentState = $_SESSION['playerStatus'] ?? 'idle';
    if ($currentState !== $state || !(isset($_SESSION['volume']))) {
        write_log("Player state changed, polling...", "INFO", false, true);
        writeSession('playerStatus', $state);
        $playerData = fetchPlayerState(true);
        $volume = 100;
        if (isset($playerData['volume'])) {
            $volume = $playerData['volume'] * 100;
            $status['volume'] = $volume;
        }
        writeSession("volume", $volume);
    }
    return $status;
}

function fetchPlayQueue($media, $shuffle = false, $returnQueue = false) {
    write_log("Queuedamedia: " . json_encode($media));
    $key = $media['key'];
    $queueID = $media['queueID'] ?? false;
    $isAudio = ($media['type'] == 'album' || $media['type'] == 'artist' || $media['type'] == 'track');
    // #TODO: Just set the audio, other values by what's in media.
    $host = findDevice("Id", $media['source'], "Server");
    $sections = $host['Sections'] ?? false;
    $sections = $sections ? json_decode($sections, true) : false;
    $sectionId = $media['librarySectionID'] ?? false;
    $uuid = false;
    $typeCheck = ($isAudio ? 'artist' : ($media['type'] == 'movie' ? 'movie' : 'show'));
    foreach ($sections as $section) {
        if ($sectionId) {
            if ($section['Id'] == $sectionId) $uuid = $section['uuid'];
        } else {
            if ($section['type'] == $typeCheck) $uuid = $section['uuid'];
        }
    }
    write_log("SectionId is $sectionId and uuid is $uuid and sections: " . json_encode($sections));
    $result = false;
    $uri = urlencode("library://$uuid/item/" . urlencode($key));
    $query = [
        'type' => ($isAudio ? 'audio' : 'video'),
        'uri' => $uri,
        'continuous' => 1,
        'shuffle' => $shuffle ? '1' : '0',
        'repeat' => 0,
        'own' => 1,
        'includeChapters' => 1,
        'includeGeolocation' => 1,
        'X-Plex-Client-Identifier' => $_SESSION['plexClientId']
    ];
    // #TODO: Crap, do we need to rebuild headers for the right device now?
    $headers = clientHeaders($host);
    # TODO: Validate that we should be queueing on the parent server, versus media server.
    $result = doRequest([
        'uri' => $host['Uri'],
        'path' => '/playQueues' . ($queueID ? '/' . $queueID : ''),
        'query' => array_merge($query, plexHeaders($host)),
        'type' => 'post',
        'headers' => $headers
    ]);
    if ($result) {
        $container = new SimpleXMLElement($result);
        $container2 = new JsonXmlElement($result);
        $container2 = $container2->asArray();
        $container = json_decode(json_encode($container), true);
        write_log("Response container from queue: " . json_encode($container2));
        if ($returnQueue) return $container;
        $queueID = $container['@attributes']['playQueueID'] ?? false;
    } else {
        write_log("Error fetching queue ID!", "ERROR");
    }
    return $queueID;
}

function fetchPlayQueueAudio($media) {
    $artistKey = $id = $response = $result = $sections = $song = $url = $uuid = false;
    write_log("Trying to queue some meeedia: " . json_encode($media));
    $host = findDevice("Id", $media['source'], "Server");
    if ($host) $sections = json_decode($host['Sections'], true);
    // #TODO: Figure out if we can avoid requiring another lookup for UUID
    if (is_array($sections)) foreach ($sections as $section) if ($section['type'] == "artist") $uuid = $section['uuid'];
    $ratingKey = $media['ratingKey'] ?? false;
    $ratingKey = $ratingKey ? urlencode($ratingKey) : false;
    $type = $media['type'] ?? false;
    if (($ratingKey) && ($type) && ($uuid)) {
        $url = $host['Uri'] . "/playQueues?type=audio&uri=library%3A%2F%2F" . $uuid . "%2F";
        switch ($type) {
            case 'album':
                $url .= "item%2F%252Flibrary%252Fmetadata%252F" . $ratingKey . "&shuffle=0";
                break;
            case 'artist':
                $url .= "item%2F%252Flibrary%252Fmetadata%252F" . $ratingKey . "&shuffle=1";
                break;
            case 'track':
                $url .= "directory%2F%252Fservices%252Fgracenote%252FsimilarPlaylist%253Fid%253D" . $ratingKey . "&shuffle=0";
                break;
            default:
                write_log("NOT A VALID AUDIO ITEM!", "ERROR");
                return false;
        }
    }
    if ($url) {
        $header = headerQuery(plexHeaders($host));
        $url .= "&repeat=0&includeChapters=1&includeRelated=1" . $header;
        write_log("URL is " . protectURL(($url)));
        $result = curlPost($url);
    }
    if ($result) {
        $container = new JsonXmlElement($result);
        $container = $container->asArray();
        write_log("Playqueue container: " . json_encode($container));
        if (isset($container['playQueueID'])) {
            $selectedOffset = intval($container['playQueueSelectedOffset'] ?? 0);
            $track = $container['MediaContainer']['Track'][$selectedOffset];
            $response = [
                'title' => $track['title'],
                'artist' => $track['grandparentTitle'],
                'album' => $track['parentTitle'],
                'key' => "library/metadata/" . $container['playQueueSelectedMetadataItemID'],
                'queueID' => $container['playQueueID']
            ];
        }
    }
    return $response;
}

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

function fetchSections($server = false) {
    $sections = [];
    $uri = $server['Uri'] ?? $_SESSION['plexServerUri'];
    $token = $server['Token'] ?? $_SESSION['plexServertoken'];
    $url = "$uri/library/sections?X-Plex-Token=$token";
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
    if (count($sections)) {
        writeSession('sections', $sections);
    }
    return $sections;
}

function fetchTransientToken($host = false) {
    $host = $host ? $host : findDevice("Id", $_SESSION['plexServerId'], "Server");
    $header = headerQuery(plexHeaders($host));
    $url = $host['Uri'] . '/security/token?type=delegation&scope=all' . $header;
    $result = curlGet($url);
    if ($result) {
        $container = new SimpleXMLElement($result);
        $ttoken = (string)$container['token'];
        if ($ttoken) {
            return $ttoken;
        } else {
            write_log("Error fetching transient token.", "ERROR");
        }
    }
    return false;
}

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
			$queue = fetchPlayQueue($track);
		} else {
			$track['queueId'] = $queue;
			fetchPlayQueue($track);
		}
	}
	return $playlist;
}


//**
// Send data out
//  */

function sendCommand($cmd, $value = false) {
    if (preg_match("/stop/", $cmd)) sendWebHook(false, "Stop");
    if (preg_match("/pause/", $cmd)) sendWebHook(false, "Paused");
    if ($_SESSION['plexClientProduct'] === 'Cast') {
        $cmd = strtolower($cmd);
        $result = sendCommandCast($cmd, $value);
    } else {
        //TODO: VOlume command for non-cast devices
        $url = $_SESSION['plexServerUri'] . '/player/playback/' . $cmd . '?type=video&commandID=' . $_SESSION['counter'] . headerQuery(array_merge(plexHeaders(), clientHeaders()));
        $result = doRequest($url);
        writeSession('counter', $_SESSION['counter'] + 1);
    }
    if ($cmd === 'volume') writeSession("volume", $value);
    $result['cmd'] = $cmd;
    if ($value) $result['value'] = $value;
    return $result;
}

function sendCommandCast($cmd, $value = false) {
    write_log("Incoming are $cmd and $value");
    // Set up our cast device
    if ("volume" == $cmd) {
        $value = $value ? intval($value) : filter_var($cmd, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
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
        case "volume.down":
        case "volume.up":
        case "volume.mute":
        case "volume.unmute":
        case "seek":
            break;
        default:
            $return['status'] = 'error';
            $valid = false;
    }
    if ($valid) {
        write_log("Sending $cmd command");
        $host = findDevice("Id", $_SESSION['plexClientParent'], 'Server');
        $url = $host['Uri'] . "/chromecast/cmd?X-Plex-Token=" . $host['Token'];
        $headers = [
            'Uri' => $_SESSION['plexClientId'],
            'Cmd' => $cmd
        ];
        if ($value) $headers['Val'] = $value;
        $header = headerRequestArray($headers);
        write_log("Headers: " . json_encode($headers));
        $result = curlGet($url, $header);
        if ($result) {
            $response = flattenXML($result);
            write_log("Got me some response! " . json_encode($response));
            if (isset($response['title2'])) {
                $data = base64_decode($response['title2']);
                write_log("Real data: " . $data);
            }
        }
        $return['url'] = "No URL";
        $return['status'] = 'success';
        return $return;
    }
    $return['status'] = 'error';
    return $return;
}

function sendCommandPlayer($url) {
    if (!(preg_match('/http/', $url))) $url = $_SESSION['plexClientUri'] . $url;
    $status = 'success';
    writeSession('counter', $_SESSION['counter'] + 1);
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

function sendFallback() {
    if (isset($_SESSION['fallback'])) {
        $fb = $_SESSION['fallback'];
        if (isset($fb['media'])) sendMediaLegacy($fb['media']);
        if (isset($fb['device'])) changeDevice($fb['device']);
        writeSession('fallback', null, true);
    }
}

function sendMediaLegacy($media) {
    write_log("Incoming media: " . json_encode($media));
    $playUrl = false;
    $id = $_SESSION['plexClientParent'] ?? "";
    write_log("Parent id? $id");
    $id = ((trim($id) !== "") ? $id : $_SESSION['plexServerId']);
    write_log("Parent id? $id");
    $parent = findDevice("Id", $id, "Server");
    $hostId = $media['source'] ?? $_SESSION['plexServerId'];
    $host = findDevice("Id", $hostId, 'Server');
    if (!$host) {
        write_log("Couldn't find a host...");
        return false;
    }
    write_log("Parent and host: " . json_encode([
            'parent' => $parent,
            'host' => $host
        ]));
    $server = parse_url($host['Uri']);
    $serverProtocol = $server['scheme'];
    $serverIP = $server['host'];
    $serverPort = $server['port'];
    $serverID = $host['Id'];
    write_log("Relay target is $serverProtocol");
    // #TODO: Figure out which server this needs to come from. Probably parent.
    $queueID = (isset($media['queueID']) ? $media['queueID'] : fetchPlayQueue($media));
    $isAudio = ($media['type'] == 'album' || $media['type'] == 'artist' || $media['type'] == 'track');
    $type = $isAudio ? 'music' : 'video';
    $key = urlencode($media['key']);
    $offset = ($media['viewOffset'] ?? 0);
    $commandId = $_SESSION['counter'];
    $token = $parent['Token'];
    $transientToken = fetchTransientToken($host);
    if ($queueID && $transientToken) {
        write_log("Media has a queue ID, we're good.");
        if ($_SESSION['plexClientProduct'] === 'Cast') {
            $isAudio = ($media['type'] == 'album' || $media['type'] == 'artist' || $media['type'] == 'track');
            $userName = $_SESSION['plexUserName'];
            $version = explode("-", $parent['Version'])[0];
            $headers = [
                'Clienturi' => $_SESSION['plexClientId'],
                'Contentid' => $media['key'],
                'Contenttype' => $isAudio ? 'audio' : 'video',
                'Offset' => $media['viewOffset'] ?? 0,
                'Serverid' => $parent['Id'],
                'Serveruri' => $parent['Uri'],
                'Serverversion' => $version,
                'Username' => $userName,
                'Queueid' => $queueID,
                'Transienttoken' => $transientToken
            ];
            $url = $parent['Uri'] . "/chromecast/play?X-Plex-Token=" . $token;
            $headers = headerRequestArray($headers);
            write_log("Header array: " . json_encode($headers));
            $result = curlGet($url, $headers);
            write_log("Result: ".$result);
            $status = "FOO";
        } else {
            writeSession('counter', $_SESSION['counter']++);
            fetchPlayerState(false);
            $playUrl = $parent['Uri'] . "/player/playback/playMedia" .
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
            $headers = clientHeaders($parent);
            $playUrl .= headerQuery($headers);
            write_log('Playback URL is ' . protectURL($playUrl));
            $result = curlGet($playUrl);
            write_log("Result: $result");
            $status = (((preg_match("/200/", $result) && (preg_match("/OK/", $result)))) ? 'success' : 'error');
        }
    } else {
        $status = "ERROR: can't fetch queue or Token.";
        write_log("Error queueing or fetching Ttoken!", "ERROR");
    }
    $return['url'] = $playUrl;
    $return['status'] = $status;
    return $return;
}

function sendMedia($media) {
	write_log("Function fired: " . json_encode($media));
	$item = false;
	$type = $media['type'];
	$source = $media['source'];
	$parent = findDevice("Id", $source, "Server");
	if ($parent) {
		switch ($type) {
			case 'movie':
			case 'playlist':
			case 'episode':
				write_log("Movie/playlist playback, just start it.");
				$item = $media;
				break;
			case 'show':
				write_log("Play the latest on-deck item, or first unwatched episode.");
				$item = $media['onDeck'][0] ?? fetchFirstUnwatchedEpisode($media['key'], $parent);
				$item['source'] = $media['source'];
				write_log("Show item: " . json_encode($item));
				break;
			case 'season':
				write_log("Start a season from the beginning.");
				break;
			case 'artist':
			case 'album':
			case 'track':
				write_log("Queueing $type");
				$item = fetchPlayQueueAudio($media);
				$item['source'] = $media['source'];
				$item['art'] = $media['art'];
				$item['thumb'] = $media['thumb'];
				$item['summary'] = $media['summary'];
				$item['type'] = $type;
				write_log("Queued item: " . json_encode($item));
				break;
			default:
				$item = false;
		}
	}
	if ($item) sendMediaLegacy($item);
	return $item;
}

function sendSpeech($data) {
    $speech = $data['speech'];
    $cards = $data['cards'] ?? false;
    $contextName = $data['contextName'] ?? "end";
    $waitForResponse = $data['waitForResponse'] ?? $data['wait'] ?? false;
    $suggestions = $data['suggestions'] ?? false;
    write_log("My reply is going to be '$speech'.", "INFO");
    if (isset($_GET['say'])) return;
    $amazonRequest = $_SESSION['amazonRequest'] ?? false;
    if ($amazonRequest) {
        sendSpeechAlexa($speech, $contextName, $cards, $waitForResponse, $suggestions);
    } else {
        sendSpeechAssistant($speech, $contextName, $cards, $waitForResponse, $suggestions);
    }
    if (function_exists('fastcgi_finish_request')) {
        fastcgi_finish_request();
    }
}

function sendSpeechAssistant($speech, $contextName, $cards, $waitForResponse, $suggestions) {
    if (count($cards)) write_log("Card array: " . json_encode($cards));
    ob_start();
    $data = [];
    $items = $richResponse = $sugs = [];
    if (!trim($speech)) $speech = "There was an error building this speech response, please inform the developer.";
    $data["google"]["expectUserResponse"] = boolval($waitForResponse);
    $data["google"]["isSsml"] = false;
    $data["google"]["noInputPrompts"] = [];
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
                        $item['title'] = substr($cardTitle, 0, 50);
                        $item['description'] = $card['description'];
                        $item['optionInfo']['key'] = "play " . ($card['key'] ?? $cardTitle);
                        array_push($carousel, $item);
                    } else {
                        write_log("Not displaying card for $cardTitle because image is not https.", "INFO");
                    }
                }
                $data['google']['systemIntent']['intent'] = 'actions.intent.OPTION';
                $data['google']['systemIntent']['data']['@type'] = 'type.googleapis.com/google.actions.v2.OptionValueSpec';
                $data['google']['systemIntent']['data']['listSelect']['items'] = $carousel;
                $data['google']['expectedInputs'][0]['possibleIntents'][0]['inputValueData']['@type'] = "type.googleapis.com/google.actions.v2.OptionValueSpec";
                $data['google']['expectedInputs'][0]['possibleIntents'][0]['intent'] = "actions.intent.OPTION";
            }
        }
    }
    $data['google']['richResponse']['items'] = $items;
    if (is_array($suggestions)) {
        $sugs = [];
        foreach ($suggestions as $suggestion) {
            array_push($sugs, ["title" => $suggestion]);
        }
        if (count($sugs)) $data['google']['richResponse']['suggestions'] = $sugs;
    }
    $output["speech"] = $speech;
    $output['displayText'] = $speech;
    $output['data'] = $data;
    $output["contextOut"][0] = [
        "name" => $contextName,
        "lifespan" => 2,
        "parameters" => []
    ];
    $output['source'] = "PhlexChat";
    ob_end_clean();
    echo json_encode($output);
    write_log("JSON out: " . json_encode($output));
}

function sendSpeechAlexa($speech, $contextName, $cards, $waitForResponse, $suggestions) {
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
    header('Content-Type: application/json');
    echo json_encode($response);
    write_log("JSON out is " . json_encode($response));
}

function sendServerRegistration() {
    $address = $_SESSION['appAddress'] ?? $_SESSION['publicAddress'];
    $registerUrl = "https://phlexserver.cookiehigh.us/api.php" . "?apiToken=" . $_SESSION['apiToken'] . "&serverAddress=" . htmlentities($address);
    write_log("registerServer: URL is " . protectURL($registerUrl), 'INFO');
    $result = curlGet($registerUrl);
    if ($result == "OK") {
        write_log("Successfully registered with server.", "INFO");
    } else {
        write_log("Server registration failed.  Response: " . $result, "ERROR");
    }
}

function sendWebHook($param = false, $type = false) {
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


//**
// Make sense of data
//  */

function mapApiRequest($request) {
    write_log("Got me a marlin: " . json_encode($request));
    //First, figure out what intent we've fired:
    $intent = $request['metadata']['intentName'];
    $params = $request['parameters'] ?? [];
    $contexts = $request['contexts'] ?? [];
    $resolvedQuery = $request['resolvedQuery'];
    foreach ($contexts as $context) if ($context['name'] == 'actions_intent_option') {
        $resolvedQuery = $context['parameters']['OPTION'];
        $strings = explode(" ", $resolvedQuery);
        if ($strings[0] == 'play' || $strings[0] == 'fetch') {
            $intent = 'Media.multipleResults';
            unset($strings[0]);
            $params['request'] = implode(" ", $strings);
        }
    }
    $params['resolved'] = $resolvedQuery;
    $params['intent'] = $intent;
    $params['contexts'] = $contexts;
    write_log("Resolved query is '$resolvedQuery'", "INFO");
    // #TODO: Make sure this fires AFTER we output elsewhere...
//	if ($_SESSION['hookEnabled']) {
//		$custom = cleanCommandString($_SESSION['hookCustomPhrase']);
//		$resolved = cleanCommandString($resolvedQuery);
//		if (preg_match("/$custom/",$resolved)) {
//			write_log("Custom webhook fired.");
//			fireHook();
//		}
//	}
    /*
     *  Result format:
     *
     *  string $speech
     *  array $cards
     *  array $suggestions
     *  bool $waitForResponse
     *
     */
    #TODO Add a parser here to determine if we can prompt for more info, or if we just play something
    $playResult = false;
    $result = false;
    switch ($intent) {
        case 'Media.multipleResults':
        case 'fetchInfo-MediaSelect':
            $result = buildQueryMulti($params);
            write_log("Sorted multi query: " . json_encode($result));
            $media = $result['media'];
            if (count($media) == 1) {
                write_log("Count is good!", "INFO");
                if ($_SESSION['intent'] == 'playMedia' || $_SESSION['intent'] == 'fetchInfo') {
                    write_log("Session intent is good.");
                    $params['resolved'] = $resolvedQuery = "Play " . $media[0]['title'] . ".";
                    $actionResult = sendMedia($media[0]);
                } else {
                    //$actionResult = fecthMedia($result[0]);
                    $actionResult = "foo";
                }
                $result['actionResult'] = $actionResult;
                $params['control'] = $_SESSION['intent'];
            }
            break;
        case 'playMedia':
            write_log("Play request.", "INFO");
            $result = buildQueryMedia($params);
            $media = $result['media'];
            $lastCheck = [];
            if (count($media) >= 2) {
                foreach ($media as $item) {
                    $push = true;
                    foreach ($lastCheck as $check) {
                        $titleMatch = ($item['title'] === $check['title']);
                        $yearMatch = ($item['year'] === $check['year']);
                        $itemId = $item['tmdbId'] ?? $item['id'] ?? $item['key'] ?? 'item';
                        $checkId = $check['tmdbId'] ?? $check['id'] ?? $check['key'] ?? 'check';
                        $idMatch = ($itemId === $checkId);
                        write_log("Item $itemId check $checkId match $idMatch titlematch $titleMatch");
                        if ($titleMatch && $yearMatch && $idMatch) {
                            $preferredId = $_SESSION['plexServerId'];
                            if ($check['source'] == $preferredId) {
                                write_log("Found identical items, but one is preferred.", "INFO");
                                $push = false;
                            } else if ($item['source'] === $preferredId) {
                                write_log("New item is preferred, replacing.", "INFO");
                                $lastCheck[$preferredId] = $item;
                                $push = false;
                            }
                        }
                    }
                    if ($push) array_push($lastCheck, $item);
                }
                $result['media'] = $lastCheck;
            }
            $noPrompts = (isset($_GET['say']) && !isset($_GET['web']));
            if (count($result['media']) == 1 || $noPrompts) $result['playback'] = $result['media'][0];
            if (count($result['media']) >= 2 && !$noPrompts) {
                $_SESSION['mediaArray'] = $result['media'];
                $oontext = "playMedia-followup";
                $data = [
                    'mediaArray' => $result['media'],
                    'context' => $oontext
                ];
                $result = array_merge($data, $result);
            }
            if ($result['playback']) $playResult = sendMedia($result['playback']);
            write_log("PlayResult: " . json_encode($playResult));
            break;
        case 'controlMedia':
            write_log("Control command!", "INFO");
            $result = buildQueryControl($params);
            break;
        case 'fetchInfo':
            write_log("Info command!", "INFO");
            $result = buildQueryInfo($params);
            break;
        case 'Default Welcome Intent':
            write_log("Let's say hello!", "INFO");
            $resolvedQuery = "Talk to Flex TV";
            break;
        case 'help':
        case 'welcome.help':
            write_log("Help request!!", "INFO");
            break;
        default:
            write_log("Unknown intent $intent: " . json_encode($params));
            $errorMessage = "I don't know how to handle that request yet.";
            $result = buildQueryMulti($params);
    }
    $result = buildSpeech($params, $result);
    if (isset($_SESSION['v2'])) {
        $data = [
            'mediaArray' => $result['media'] ?? [],
            'context' => $result['contextName'] ?? [],
            'sessionId' => $_SESSION['sessionId'],
            'intent' => $intent
        ];
        $clearSet = $result['wait'] ?? true;
        $clearSet = $clearSet ? false : true;
        writeSessionArray($data, $clearSet);
        $string = ($clearSet ? "Clearing" : "Setting");
        write_log("$string session context and media: " . json_encode($data));
    }
    $result['initialCommand'] = $resolvedQuery;
    $result['timeStamp'] = timeStamp();
    write_log("Terminating now.");
    write_log("Logging result object: " . json_encode($result), "INFO");
    if (isset($_SESSION['v2'])) {
        if (!isset($_GET['say'])) {
            write_log("Returning speech for V2.", "INFO");
            sendSpeech($result);
        }
        logCommand($result);
        bye();
    }
}

function mapData($dataArray) {
	$info = $media = $results = [];
	write_log("Full data array: " . json_encode($dataArray));
	foreach ($dataArray as $key => $data) {
		$keys = explode(".", $key);
		$type = $keys[0];
		$sub = $keys[1] ?? null;
		switch ($type) {
			case 'movie':
				write_log("Movie result: " . json_encode($data));
				$data = $data['results'];
				$count = count($data);
				write_log("Found $count possible results on TMDB.");
				foreach ($data as $item) {
					$item['source'] = $type;
					$return = mapDataMovie($item);
					$info[] = $return;
				}
				break;
			case 'music':
				write_log("Audio result: " . json_encode($data));
				$data = $data['artist'] ?? $data['album'] ?? $data['track'] ?? $data['artists'] ?? $data['albums'] ?? $data['data'] ?? null;
				if ($data !== null) foreach ($data as $item) {
					$item['source'] = $type;
					$type = $sub ?? $type;
					$type = str_replace("albums", "album", $type);
					$item['type'] = $item['type'] ?? $type;
					$return = mapDataMusic($item);
					$info[] = $return;
				}
				break;
			case 'show':
				write_log("TV result");
				$episodes = $data['_embedded']['episodes'] ?? false;
				if ($episodes) {
					foreach ($episodes as $episode) {
						$episode['type'] = "show.episode";
						$episode['seriesTitle'] = $data['name'];
						$info[] = mapDataShow($episode);
					}
					unset($data['_embedded']);
					write_log("Without eps: " . json_encode($data));
				}
				$data['type'] = "show";
				$info[] = mapDataShow($data);
				break;
			case 'plex':
				write_log("Plex search result");
				$hubs = $data['MediaContainer']['Hub'] ?? false;
				if (is_array($hubs)) {
					foreach ($hubs as $hub) {
						if ($hub['size'] >= 1) {
							$items = $hub['Directory'] ?? $hub['Track'] ?? $hub['Video'] ?? $hub['Season'] ?? $hub['Actor'] ?? false;
							if (is_array($items)) {
								write_log("This hub has stuff in it!: " . json_encode($items));
								foreach ($items as $item) {
									$item['source'] = $sub;
									$return = mapDataPlex($item);
									write_log("Return item: " . json_encode($return), "INFO");
									$media[] = $return;
								}
							}
						}
					}
				}
				break;
		}
	}
	$results = [
		'media' => $media,
		'meta' => $info
	];
	write_log("ITEMS: " . json_encode($results));
	return $results;
}

function mapDataMovie($data) {
	$year = explode("-", $data['release_date'] ?? $data['first_air_date'])[0];
	$artPath = $data['backdrop_path'] !== null ? 'https://image.tmdb.org/t/p/original' . $data['backdrop_path'] : false;
	$thumbPath = $data['poster_path'] !== null ? 'https://image.tmdb.org/t/p/original' . $data['poster_path'] : false;
	return [
		'title' => $data['title'] ?? $data['name'],
		'summary' => $data['overview'],
		'year' => $year,
		'type' => 'movie',
		'thumb' => $thumbPath,
		'art' => $artPath,
		'language' => $data['original_language'],
		'tmdbId' => $data['id']
	];
}

function mapDataMusic($data) {
	$lang = strtoupper(getDefaultLocale());
	$type = $data['type'];
	if (preg_match("/artist/", $type)) {
		$return = [
			'title' => $data['strArtist'],
			'artist' => $data['strArtist'],
			'type' => $data['type'],
			'source' => $data['source'],
			'summary' => $data["strBiography$lang"] ?? $data["strBiographyEN"],
			'thumb' => proxyImage($data['strArtistWideThumb']),
			'art' => proxyImage($data['strArtistFanart']),
			'tadbId' => $data['idArtist']
		];
	} else if (preg_match("/album/", $type)) {
		;
		$return = [
			'title' => $data['strAlbum'],
			'type' => $data['type'],
			'source' => $data['source'],
			'year' => $data['intYearReleased'],
			'summary' => $data["strDescription$lang"] ?? $data["strDescriptionEN"],
			'thumb' => proxyImage($data['strAlbumThumb'] ?? $data['album']['cover_big']),
			'art' => proxyImage($data['strAlbumCDart'] ?? $data['artist']['picture_big']),
			'tadbId' => $data['idAlbum'],
			'artist' => $data['strArtist']
		];
	} else if ($type === 'track') {
		$return = [
			'title' => $data['title'],
			'type' => $data['type'],
			'source' => $data['source'],
			'album' => $data['album']['title'],
			'artist' => $data['artist']['name'],
			'thumb' => $data['album']['cover_big'],
			'art' => $data['artist']['picture_big'],
		];
	}
	if ($type == "music.deezer") {
		$return = [];
	}
	return $return;
}

function mapDataShow($data) {
	$return = [
		'title' => $data['name'],
		'type' => $data['type'],
		'id' => $data['id'],
		'thumb' => proxyImage($data['thumb']),
		'art' => proxyImage($data['art']),
		'summary' => $data['summary'],
		'source' => $data['source']
	];
	$cust = [];
	if ($data['type'] == 'show.episode') {
		$cust = [
			'seriesTitle' => $data['seriesTitle'],
			'year' => explode("-", $data['airdate'])[0],
			'episode' => $data['number'],
			'season' => $data['season'],
			'parent' => $data['parent']
		];
	}
	if ($data['type'] == 'show') {
		$cust = [
			'year' => explode("-", $data['premiered'])[0]
		];
	}
	return array_merge($return, $cust);
}

function mapDataPlex($data) {
	$result = [
		'title' => $data['title'],
		'year' => $data['year'],
		'duration' => $data['duration'],
		'key' => $data['key'],
		'ratingKey' => $data['ratingKey'] ?? '',
		'sectionKey' => $data['librarySectionKey'],
		'type' => $data['type'],
		'source' => $data['source'],
		'viewOffset' => $data['viewOffset'] ?? 0
	];
	$server = findDevice("Id", $data['source'], "Server");
	$uri = $server['Uri'] ?? $_SESSION['plexServerUri'];
	$token = $server['Token'] ?? $_SESSION['plexServerToken'];
	$thumb = $data['grandparentThumb'] ?? $data['parentThumb'] ?? $data['thumb'] ?? false;
	$art = $data['grandparentArt'] ?? $data['parentArt'] ?? $data['art'] ?? false;
	if ($art) $result['art'] = transcodeImage($art, $uri, $token);
	if ($thumb) $result['thumb'] = transcodeImage($thumb, $uri, $token);
	if ($data['type'] == 'track') {
		$result['artist'] = $data['grandparentTitle'];
		$result['album'] = $data['parentTitle'];
	}
	if ($data['type'] == 'show') {
		write_log("Mappadashowdataaaaa: " . json_encode($data));
		$ondecks = $data['OnDeck'];
		foreach ($ondecks as $ondeck) {
			$result['ondeck'][] = $ondeck['Video'][0];
		}
	}
	if (isset($data['tagline'])) $result['tagline'] = $data['tagline'];
	return array_filter($result);
}

function mapDataResults($search, $media, $meta) {
	write_log("Hello, yes, I'm here...");
	write_log("Input search: " . json_encode($search));
	write_log("Media search: " . json_encode($media));
	write_log("Data search: " . json_encode($meta));
	$results = [];
	$artist = $search['artist'] ?? false;
	$album = $search['album'] ?? false;
	$season = $search['season'] ?? false;
	$episode = $search['episode'] ?? false;
	$vars = [
		'media' => $media,
		'meta' => $meta
	];
	$newMedia = [];
	$metaMatch = [];
	if ($media && $meta) {
		foreach ($media as $item) {
			foreach ($meta as $check) {
				$itemTitle = strtolower($item['title']);
				$checkTitle = strtolower($check['title']);
				$itemType = $item['type'];
				$checkType = explode(".", $check['type'])[1] ?? $check['type'];
				if ($itemTitle == $checkTitle && $itemType == $checkType) {
					$merge = true;
					if (isset($item['artist']) && isset($check['artist'])) {
						if ($item['artist'] !== $check['artist']) {
							$merge = false;
						}
					}
					if (isset($item['year']) && isset($check['year'])) {
						if ($item['year'] !== $check['year']) {
							$merge = false;
						}
					}
					if ($merge) {
					    if (isset($item['art']) && isset($check['art'])) unset($item['art']);
                        if (isset($item['thumb']) && isset($check['thumb'])) unset($item['thumb']);
						$item = array_merge($check, $item);
						write_log("Creating merged media item from $itemTitle: " . json_encode($item), "INFO");
						break(1);
					}
				}
			}
			array_push($newMedia, $item);
		}
	}
	$media = $newMedia;
	$vars['media'] = $media;
	$vars['meta'] = $meta;
	write_log("Merged media: " . json_encode($vars));
	if ($artist && $album && count($meta)) {
		$albums = [];
		foreach ($meta as $item) {
			if ($item['type'] == 'album') {
				$year = $item['year'];
				$albums["$year"] = $item;
			}
		}
		ksort($albums);
		if (count($albums)) {
			$search = $album == -1 ? end($albums) : array_values($albums)[$album - 1];
			$search['type'] = 'album';
			$search['request'] = $search['title'];
			write_log("We want album number $album out of " . count($albums));
			write_log("Overriding search with an album: " . json_encode($search));
		}
	}
	foreach ($vars as $section => $data) {
		$exact = $fuzzy = false;
		foreach ($data as $item) {
			$typeMatch = $yearMatch = true;
			$searchTitle = $search['request'];
			$itemTitle = $item['title'];
			if (isset($search['type'])) {
				$types = explode(".", $item['type']);
				$itemTypes = explode(".", $search['type']);
				$mt = $types[1] ?? $types[0] ?? $item['type'];
				$it = $itemTypes[1] ?? $itemTypes[0] ?? $search['type'];
				$typeMatch = ($mt == $it || $it == 'music');
			}
			if (isset($search['year']) && isset($item['year'])) {
				$searchYear = trim($search['year']);
				$itemYear = trim($item['year']);
				$yearMatch = ($searchYear == $itemYear);
			}
			if (isset($search['artist'])) {
				$artist = $data['artist'] ?? 'asdf';
				$artistMatch = ($search['artist'] !== $artist);
			} else $artistMatch = true;
			if ($yearMatch && $typeMatch && $artistMatch) {
				$exact = $exact ? $exact : [];
				if (isset($search['offset'])) $item['viewOffset'] = $search['offset'];
				if (strtolower($itemTitle) == strtolower($searchTitle)) {
					write_log("'$itemTitle' matches exactly: " . json_encode($item), "INFO");
					$exact[] = $item;
				} else if (compareTitles(strtolower($searchTitle), strtolower($itemTitle)) && !is_array($exact)) {
					$fuzzy = $fuzzy ? $fuzzy : [];
					$fuzzy[] = $item;
					write_log("'$itemTitle' matches fuzzy: " . json_encode($item));
				}
			}
		}
		$results["$section"] = ($exact ? $exact : ($fuzzy ? $fuzzy : []));
	}
	return $results;
}

//**
//Build queries, speech, cards
// */

function buildCards($cards) {
    $returns = [];
    foreach ($cards as $card) {
        $title = $card['title'];
        switch ($card['type']) {
            case 'episode':
            case 'track':
                $title = ($card['grandparentTitle'] ?? $card['artist']) . " - $title";
                $subTitle = ($card['season'] ?? $card['album']);
                break;
            case 'album':
                $title = ($card['parentTitle'] ?? $card['artist']) . " - $title";
                break;
        }
        $subTitle = $card['tagline'] ?? $card['description'] ?? '';
        $formattedText = $card['summary'] ?? $card['description'] ?? '';
        $image = $card['art'] ?? $card['thumb'] ?? '';
        if (preg_match("/library\/metadata/", $image)) {
            $image = transcodeImage($image);
        }
        $returns[] = [
            'title' => $title,
            'key' => $card['key'] ?? $card['imdbId'] ?? $card['id'],
            'subTitle' => $subTitle,
            'formattedText' => $formattedText,
            'image' => ['url' => $image]
        ];
    }
    return $returns;
}

function buildQueryControl($params) {
    write_log(" params: " . json_encode($params));
    //Sanitize our string and try to rule out synonyms for commands
    $command = $params['controls'];
    $value = $params['percentage'] ?? $params['duration'] ?? $params['language'] ?? false;
    //$synonyms = lang('commandSynonymsArray');
    $queryOut['initialCommand'] = $command;
    $queryOut['parsedCommand'] = "";
    $commands = [
        "volume.down" => "volume.down",
        "volume.up" => "volume.up",
        "volume.mute" => "volume.mute",
        "volume.unmute" => "volume.unmute",
        "volume" => "volume",
        "resume" => "play",
        "pause" => "pause",
        "stop" => "stop",
        "back" => "previous",
        "next" => "next",
        "seek" => "seek",
        "subtitles.off" => "subtitles",
        "subtitles.on" => "subtitles",
        "subtitles.change" => "subtitles"
    ];
    $cmd = $commands["$command"] ?? false;
    write_log("Command and value are $command and $value");
    if ($command == "subtitles.on" || ($command == 'subtitles.change' && $value)) {
        $streamID = 0;
        $status = fetchPlayerStatus();
        write_log("Got status array: " . json_encode($status));
        $streams = $status['mediaResult']['streams'] ?? false;
        if ($streams) {
            foreach ($streams as $stream) {
                $lang = $stream['language'];
                $lang = localeName(substr($lang, 0, 2));
                $picked = $value ?? $_SESSION['appLanguage'];
                write_log("Lang vs. selected is $lang and $picked");
                if ($lang === $picked) {
                    write_log("Found a matching subtitle.");
                    $streamID = $stream['id'];
                }
            }
            if (!$streamID) $streamID = $streams['0']['id'] ?? false;
        }
        if ($streamID) {
            $cmd = 'subtitles';
            $value = $streamID;
        }
    }
    if (!$cmd) {
        write_log("No command set so far, making one.", "INFO");
        $cmds = explode(" ", strtolower($command));
        $newString = array_intersect($commands, $cmds);
        $result = implode(" ", $newString);
        $cmd = trim($result) ? $result : false;
    }
    return $cmd ? sendCommand($cmd, $value) : $cmd;
}

function buildQueryFetch($params) {
    write_log(" params: " . json_encode($params));
    return ['speech' => __FUNCTION__];
}

function buildQueryInfo($params) {
    write_log(" params: " . json_encode($params));
    $type = $params['infoRequests'] ?? false;
    switch ($type) {
        case 'recent':
            $mediaType = $params['type'] ?? false;
            write_log("Media type is $mediaType");
        case 'ondeck':
            $result['media'] = fetchHubList($type, $mediaType);
            $mediaType = 'show';
            $results['type'] = $mediaType;
            break;
        case 'airings':
            $days = $params['resolved'];
            $result['media'] = fetchAirings($params);
    }
    return $result;
}

function buildQueryMedia($params) {

    write_log(" params: " . json_encode($params));
    $request = $params['request'] ?? false;
    $device = $params['Devices'] ?? false;
    if (!$device) {
        $loc = [
            " on the ",
            " in the ",
            " in ",
            " on "
        ];
        foreach ($loc as $delimiter) {
            $device = explode($delimiter, $request)[1] ?? false;
            $player = $device ? findDevice("Name", $device, "Client") : false;
            if ($player) break;
        }
    } else {
        $player = $device ? findDevice("Name", $device, "Client") : false;
    }
    if ($player) {
        write_log("Switching client...", "INFO");
        setSelectedDevice("Client", $player['Id']);
        $params['request'] = str_replace($delimiter . $device, "", $request);
    }
    $results = fetchMediaInfo($params);
    return $results;
}

function buildQueryMulti($params) {
    write_log(" params: " . json_encode($params));
    $title = $params['request'] ?? $params['resolved'] ?? false;
    $resolved = strtolower($params['resolved']);
    $year = $params['age']['amount'] ?? $params['number'] ?? false;
    $ordinal = $params['ordinal'] ?? false;
    $mediaArray = $_SESSION['mediaArray'] ?? [];
    write_log("Session Media array: " . json_encode($mediaArray));
    $result = [];
    if ($ordinal) {
        $ordinal = intval($ordinal);
        write_log("We have an ordinal: $ordinal");
        if ($ordinal <= count($mediaArray)) {
            write_log("We have the stuff.");
            $ordinal--;
            write_log("Ordinal is now $ordinal");
            $result = [$mediaArray[$ordinal]];
            write_log("Result: " . json_encode($result));
        }
    } else if ($year || $title) {
        foreach ($mediaArray as $media) {
            $resCheck = $resolved;
            $match = $title ? (strtolower($title) == strtolower($media['title'])) : true;
            $match = $match ? $match : ($title == $media['key']);
            if ($year && $match) {
                $match = $year == $media['year'];
            }
            if ($match) {
                write_log("Easy match.");
                array_push($result, $media);
            } else {
                write_log("No easy match, checking the hard way.");
                foreach ($media as $key => $value) {
                    $value = strtolower($value);
                    if (strpos($resCheck, $value) !== false) {
                        write_log("We have a substring!");
                        $resCheck = str_replace($value, "", $resCheck);
                        write_log("Resolved value is now $resCheck");
                    }
                }
                $resCheck = cleanCommandString($resCheck);
                if (trim($resCheck) === "") {
                    write_log("Hey, no more words.");
                    array_push($result, $media);
                }
            }
        }
    }
    write_log("Found " . count($result) . " results out of " . count($mediaArray) . " items.");
    $response = [];
    $response['media'] = $result;
    return $response;
}

function buildSpeech($params, $results) {
	$cards = [];
	$playback = $meta = $media = $suggestions = $wait = false;
	$context = "end";
	write_log("Incoming: " . json_encode($params, $results));
	$speech = "Tell dude to build me a speech string!";
	if ($params['intent'] == 'playMedia') {
		$speech = "Media query.";
		$cards = [];
		$wait = $params['wait'] ?? false;
		write_log("Retrieved data: " . json_encode($results), "INFO");
		$media = $results['media'];
		$meta = $results['meta'];
		$playback = false;
		if ($params['control'] === 'fetchMedia') {
			if (count($media)) {
				$request = $media[0]['title'];
				$speech = "It looks like '$request' is already in your collection. Would you like me to play it?";
				$wait = true;
				$cards = $media;
				writeSessionArray([
					'mediaItems' => $media,
					'metaItems' => $meta
				]);
			}
		} else {
			write_log("Media array: " . json_encode($media), "INFO");
			$cards = buildCards($media);
			switch (count($cards)) {
				case 0:
					write_log("No results found.");
					if (count($meta)) {
						$speech = "I wasn't able to find " . $meta[0]['title'] . " in your library.";
						$cards = $meta;
					} else {
						$speech = "I wasn't able to find any results for that request.";
					}
					break;
				case 1:
					write_log("just the right amount.");
					$speech = buildSpeechAffirmative($cards[0]);
					$playback = $media[0];
					break;
				default:
					$speech = "Which one did you want?  ";
					$speech .= joinTitles($media, "or");
					write_log("We found a bunch! " . count($media));
					$wait = true;
					$context = "playMedia-followup";
			}
		}
	}
	if ($params['intent'] == 'controlQuery') {
		write_log("Building a control query reply!");
		$cmd = $results['cmd'];
		#TODO: Make sure this uses random stuff
		$speech = "Okay, sending a $cmd command.";
	}
	if ($params['intent'] == 'fetchInfo') {
		$cards = buildCards($media);
		$speech = buildSpeechInfoQuery($params,$cards);
	}
	if ($params['intent'] == 'Media.multipleResults') {
		$media = $results['media'];
		$wait = false;
		switch (count($media)) {
			case 0:
				$speech = "Unfortunately, I couldn't find anything by that name.";
				break;
			case 1:
				$speech = buildSpeechAffirmative($media[0]);
				$cards = buildCards($media);
				break;
			default:
				$speech = "I'm still not sure which one you wanted.";
		}
	}
	if ($params['intent'] == 'Default Welcome Intent') {
		$speech = buildSpeechWelcome();
		$wait = true;
	}
	if ($params['intent'] == 'help' || $params['intent'] == 'welcome.help') {
		$help = buildSpeechHelp();
		$speech = $help[0];
		$suggestions = $help[1];
		$wait = true;
	}
	write_log("Cards?: " . json_encode($cards));
	#TODO: Add the output context here
	return [
		'speech' => $speech,
		'cards' => $cards,
		'playback' => $playback,
		'suggestions' => $suggestions,
		'meta' => $meta,
		'media' => $media,
		'wait' => $wait,
		'contextName' => $context
	];
}

function buildSpeechAffirmative($media) {
	$affirmatives = lang("speechPlaybackAffirmatives");
	$title = $media['title'];
	$eggs = lang("speechEggArray");
	write_log("Egg array: " . json_encode($eggs));
	foreach ($eggs as $eggTitle => $egg) {
		if (compareTitles($title, $eggTitle)) {
			write_log("Pushing $egg");
			array_push($affirmatives, $egg);
		}
	}
	do {
		$affirmative = $affirmatives[array_rand($affirmatives)];
	} while ($affirmative !== $_SESSION['affirmative'] ?? 'foo');
	write_log("Picked $affirmative out of: " . json_encode($affirmatives));
	writeSession("affirmative", $affirmative);
	return "$affirmative playing $title";
}

function buildSpeechInfoQuery($params,$cards) {
    $type = $params['type'] ?? false;
    $count = count($cards);
    switch($count) {
        case 0:
            $speech = buildSpeechNoInfoResults($params);
            break;
        case 1:
            break;
        default:

    }
}

function buildSpeechNoInfoResults($request) {
    $array = lang('speechNoInfoResultsArray');
    do {
        $msg = $array[array_rand($array)];
    } while ($msg !== $_SESSION['errorMsg'] ?? 'foo');
    writeSession('errorMsg',$msg);
    $string = $request['type'] ?? "that request";
    $msg = str_replace("<VAR>",$string,$msg);
    return $msg;
}

function buildSpeechNoResults($request) {
    $array = lang('speechNoInfoResultsArray');
    do {
        $msg = $array[array_rand($array)];
    } while ($msg !== $_SESSION['errorMsg'] ?? 'foo');
    writeSession('errorMsg',$msg);
}

function buildSpeechMultipleResults($media, $params) {

}

function buildSpeechWelcome() {
	$greetings = lang("speechGreetingArray");
	$help = lang("speechGreetingHelpPrompt");
	do {
		$greeting = $greetings[array_rand($greetings)];
	} while ($greeting == $_SESSION['greeting'] ?? "foo");
	do {
		$helpPrompt = $help[array_rand($help)];
	} while ($helpPrompt == $_SESSION['helpPrompt'] ?? "foo");
	writeSessionArray([
		"greeting" => $greeting,
		"helpPrompt" => $helpPrompt
	]);
	return "$greeting $helpPrompt";
}

function buildSpeechHelp() {
	$helpArray = lang("errorHelpSuggestionsArray");
	$lastHelp = $_SESSION['help'] ?? "foo";
	do {
		$speech = $helpArray[array_rand($helpArray)];
	} while ($lastHelp == $speech);
	writeSession('help', $lastHelp);
	$helpSuggestions = lang("errorHelpCommandsArray");
	$appSuggestions = lang("suggestionsApps");
	$movie = ($_SESSION['couchEnabled'] ?? false) || ($_SESSION['radarrEnabled'] ?? false);
	$show = ($_SESSION['sickEnabled'] ?? false) || ($_SESSION['sonarrEnabled'] ?? false);
	$music = ($_SESSION['headphonesEnabled'] ?? false) || ($_SESSION['lidarrEnabled'] ?? false);
	$dvr = $_SESSION['plexDvrId'] ?? false;
	if ($movie) $helpSuggestions = array_merge($helpSuggestions, $appSuggestions['movie']);
	if ($show) $helpSuggestions = array_merge($helpSuggestions, $appSuggestions['show']);
	if ($music) $helpSuggestions = array_merge($helpSuggestions, $appSuggestions['music']);
	if ($dvr) $helpSuggestions = array_merge($helpSuggestions, $appSuggestions['dvr']);
	$helpSuggestions[] = "Cancel";
	$suggestions = $helpSuggestions;
	return [
		$speech,
		$suggestions
	];
}




