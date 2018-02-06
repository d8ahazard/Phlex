<?php
require_once dirname(__FILE__) . '/vendor/autoload.php';
require_once dirname(__FILE__) . '/webApp.php';
require_once dirname(__FILE__) . '/util.php';
use Kryptonit3\CouchPotato\CouchPotato;
use digitalhigh\Radarr\Lidarr;

// Fetch a movie from CouchPotato or Radarr
function downloadMovie(array $data) {
	write_log("Function fired.");
	$enableCouch = $_SESSION['couchEnabled'];
	$enableRadarr = $_SESSION['radarrEnabled'];
	$enableOmbi = $_SESSION['ombiEnabled'];

	$reply = [];
	$response = false;
	if ($enableCouch) {
		write_log("Using Couchpotoato for Movie agent");
		$response = couchDownload($data);
		return $response;
	}
	$reply['couch'] = $response;

	$response = false;
	if ($enableRadarr) {
		write_log("Using Radarr for Movie agent");
		$response = radarrDownload($data);
		return $response;
	}
	$reply['radarr'] = $response;

	$response = false;

	if ($enableOmbi) {
		write_log("Using Ombi for Movie agent");
	}
	$reply['ombi'] = false;

	return $reply;
}

function downloadSeries($data) {
	$enableSick = $_SESSION['sickEnabled'];
	$enableSonarr = $_SESSION['sonarrEnabled'];
	$enableOmbi = $_SESSION['ombiEnabled'];

	$reply = [];

	$response = false;
	if ($enableSonarr) {
		write_log("Using Sonarr for Episode agent");
		$response = sonarrDownload($data);
	}
	$reply['sonarr'] = $response;

	$response = false;
	if ($enableSick) {
		write_log("Using Sick for Episode agent");
		$response = sickDownload($data);
		return $response;
	}
	$reply['sick'] = $response;

	if ($enableOmbi) {
		write_log("Using Ombi for Movie agent");
	}
	$reply['ombi'] = false;


	return $reply;
}

function downloadMusic(array $data) {
	$enableHeadphones = $_SESSION['headphonesEnabled'] ?? false;
	$enableLidarr = $_SESSION['lidarrEnabled'] ?? false;

	$reply = [];

	$response = false;
	if ($enableHeadphones) {
		write_log("Using headphones for fetcher.");
		$response = headphonesDownload($data);
	}
	$reply['headphones'] = $response;

	$response = false;
	if ($enableLidarr) {
		$response = lidarrDownload($data);
	}
	$reply['lidarr'] = $response;

	return $reply;
}


function couchDownload(array $data) {
	$couchUri = $_SESSION['couchUri'];
	$couchApikey = $_SESSION['couchToken'];
	write_log("Function fired.");
	$couch = new CouchPotato($couchUri,$couchApikey);
	write_log("Still alive?");
	$existing = $couch->getMovieList(["search"=>$data['title']]);
	write_log("Existing: ".$existing);
	if ($existing) {
		$ex = json_decode($existing,true);
		$movies = $ex['movies'];
		if (empty($movies)) {
			write_log("No movies found in fetcher.");
		} else {
			foreach($movies as $movie) {
				write_log("Found a movie that could be a match: ".json_encode($movie),"INFO");
				write_log("Comparing " . $movie['info']['tmdb_id'] ." to ". $data['id']);
				if ($movie['info']['tmdb_id'] === $data['id']) {
					write_log("IMDB ID Match, nothing to see here.");
					$message = $data['title']." is already added to Couchpotato.";
					return [
						'message'=>$message,
						'media'=>$data
					];
				}
			}
		}
	}

	$id = $data['id'];
	$profile = $_SESSION['couchProfile'];
	$title = $data['title'];

	$search = json_decode($couch->getSearch($title),true);
	write_log("Search results: ".json_encode($search));
	$imdbId = false;

	foreach($search['movies'] as $movie) {
		if ($movie['tmdb_id'] === $id) $imdbId = $movie['imdb'];
	}

	if ($imdbId) {
		$params = [
			'profile_id' => "$profile",
			'title' => urlencode($title),
			'identifier' => "$imdbId"
		];

		write_log("Search params: " . json_encode($params));
		$added = $couch->getMovieAdd($params);
		write_log("Result of adding movie: " . $added);
		$added = json_decode($added,true);
		if (isset($added['movie']['status'])) {
			if ($added['movie']['status'] == "active") {
				$response = [
					'message'=>"Okay, I've added $title to Couchpotato.",
					'media'=>$data
				];

				write_log("Movie successfully added!");
				return $response;
			}
		}
	} else {
		return ['message'=>"Couldn't find anything to add."];
	}
}

function radarrDownload($data) {
	$command = $data['title'];
	$exists = $score = $movie = $wanted = false;
	$message = "Unable to search in Radarr.";
	$radarrUri = $_SESSION['radarrUri'];
	$radarrApiKey = $_SESSION['radarrToken'];
	$movie = false;
	$radarr = new Lidarr($radarrUri, $radarrApiKey);
	// Search for the movie object as Radarr requires it
	$movieCheck = json_decode($radarr->getMoviesLookup($command), true);
	// Search the library for existing media with the ID
	$movieArray = json_decode($radarr->getMovies(), true);

	write_log("MovieArray: ".json_encode($movieArray));
	foreach ($movieCheck as $check) {
		if ($check['tmdbId'] === $data['id']) {
			$movie = $check;
			break;
		}
	}

	if ($movie) {
		foreach ($movieArray as $check) {
			if ($check['tmdbId'] == $movie['tmdbId']) {
				write_log("This movie exists already.");
				$message = $data['title'] . " has already been added to Radarr.";
				$exists = true;
			}
		}
	}

	if ($movie && !$exists) {

		write_log("Need to fetch this movie: " . json_encode($movie));
		$search = $movie;
		// #TODO: Grab this value when we set it up instead of doing it repeatedly.
		$rootArray = json_decode($radarr->getRootFolder(), true);
		$rootPath = $rootArray[0]['path'];
		$search['monitored'] = true;
		$search['rootFolderPath'] = $rootPath;
		$search['qualityProfileId'] = ($_SESSION['radarrProfile'] ? intval($_SESSION['radarrProfile']) : 0);
		$search['profileId'] = ($_SESSION['radarrProfile'] ? $_SESSION['radarrProfile'] : "0");
		$search['addOptions'] = [
			'ignoreEpisodesWithFiles' => false,
			'searchForMovie' => true,
			'ignoreEpisodesWithoutFiles' => false
		];

		write_log("Final search item: " . json_encode($search));
		if (is_array($search)) $result = json_decode($radarr->postMovie($search),true);
		write_log("Add result: " . json_encode($result));
		if (isset($result['addOptions']['searchForMovie'])) {
			$message = "Successfully added " . $data['title'] . " to Radarr.";
		} else {
			$message = "There was an error adding " . $data['title'] . " to Radarr.";
		}
	}

	$response['media'] = $data;
	$response['message'] = $message;

	write_log("Final response: " . json_encode($response));
	return $response;
}

function sickDownload($data) {
	write_log("Function fired");
	$exists = $id = $response = $responseJSON = $resultID = $resultYear = $status = $results = $result = $show = false;
	$command = $data['title'];
	$season = $data['season'];
	$episode = $data['episode'];
	$message = "Unable to search on Sickrage";
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
			$message = $data['title'] . " has already been set to download.";
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
		$show = $data;
		$show['type'] = 'show';
	} else {
		$message = "I wasn't able to find any results for that on Sickrage.";
	}

	if ((!$exists) && ($result) && ($id)) {
		if ($season && $episode) $status = 'skipped'; else $status = 'wanted';
		write_log("Show not in list, adding.");
		$result = $sick->showAddNew($id, null, 'en', null, $status, $_SESSION['sickProfile']);
		$responseJSON = json_decode($result, true);
		write_log('Fetch result: ' . $result);
		$message = strtoupper($responseJSON['result']) . ': ' . $responseJSON['message'];
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
					$message = "The episode has been added successfully.";
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
					$message = "The episode has been successfully added.";
				}
			}
		}
		write_log("Show result: " . json_encode($winner));
	}

	$response['message'] = $message;
	$response['media'] = $tv;
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


function lidarrDownload(array $data) {
	return false;
}


function headphonesDownload(array $data) {
	return false;
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

		case "Couch":
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

		case "Headphones":
			$headphonesURL = $_SESSION['headphonesUri'];
			$headphonesToken = $_SESSION['headphonesToken'];
			if (($headphonesURL) && ($headphonesToken)) {
				$url = "$headphonesURL/api/?apikey=$headphonesToken&cmd=getVersion";
				$result = curlGet($url);
				if ($result) {
					$data = json_decode($result,true);
					write_log("We've got a successful result: ".$result);
					if (isset($data['current_version'])) $result = true;
				}
				$result = ($result ? 'Connection to Headphones Successful!' : 'ERROR: Server not available.');
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
