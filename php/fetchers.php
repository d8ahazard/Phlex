<?php
require_once dirname(__FILE__) . '/vendor/autoload.php';
require_once dirname(__FILE__) . '/webApp.php';
require_once dirname(__FILE__) . '/fetchers/digitalhigh/watcher/src/Watcher.php';
require_once dirname(__FILE__) . '/fetchers/digitalhigh/lidarr/src/Lidarr.php';
require_once dirname(__FILE__) . '/fetchers/digitalhigh/headphones/src/Headphones.php';
require_once dirname(__FILE__) . '/util.php';
require_once dirname(__FILE__) . '/multiCurl.php';
use Kryptonit3\CouchPotato\CouchPotato;
use digitalhigh\Radarr\Radarr;
use digitalhigh\Lidarr\Lidarr;
use Kryptonit3\SickRage\SickRage;
use Kryptonit3\Sonarr\Sonarr;
use digitalhigh\Watcher\Watcher;
use digitalhigh\Headphones\Headphones;
use digitalhigh\multiCurl;

function downloadMedia(array $data, array $fetchers) {
    write_log("Incoming: ".json_encode([$data,$fetchers]));
    $results = [];
    foreach($fetchers as $fetcher) {
        $function = 'download'.ucfirst($fetcher);
        $results["$fetcher"] = $function($data);
    }
    return $results;
}


function downloadCouch(array $data) {
    write_log("Function fired: ".json_encode($data));
    $success = false;
	$couchUri = $_SESSION['couchUri'];
	$couchApikey = $_SESSION['couchToken'];
	$couch = new CouchPotato($couchUri,$couchApikey);

	$imdbId = $data['imdbId'] ?? false;
	$profile = $_SESSION['couchProfile'];
	$title = $data['title'];
    if (!$imdbId) {
        write_log("Okay, searching here...");
        try {
            $movies = $couch->getSearch($title);
        } catch (\Kryptonit3\CouchPotato\Exceptions\InvalidException $e) {
            write_log("Error $e","ERROR");
            $movies = false;
        }
        if ($movies) {
            $movies = json_decode($movies,true);
            foreach($movies['movies'] as $movie) {
                if ($movie['tmdb_id'] == $data['tmdbId']) {
                    write_log("Found our match...");
                    $imdbId = $movie['imdb'];
                    break;
                }
            }
            write_log("Movies: ".json_encode($movies));

        }
    }

	if ($imdbId) {
		$params = [
			'profile_id' => "$profile",
			'title' => urlencode($title),
			'identifier' => "$imdbId"
		];

		write_log("Search params: " . json_encode($params));
        try {
            $added = $couch->getMovieAdd($params);
        } catch (\Kryptonit3\CouchPotato\Exceptions\InvalidException $e) {
            write_log("Exception adding movie: $e");
            $added = [];
        }
        write_log("Result of adding movie: " . $added);
		$added = json_decode($added,true);
		$status = $added['movie']['status'] ?? false;
		$success = $status == "active";

	}
	return $success;
}

function downloadHeadphones(array $data) {
    return false;
}

function downloadLidarr(array $data) {
    return false;
}

function downloadRadarr($data) {
    write_log("Function fired: ".json_encode($data));
	$command = $data['title'];
	$movie = $wanted = false;
	$success = false;
	$radarrUri = $_SESSION['radarrUri'];
	$radarrApiKey = $_SESSION['radarrToken'];
	$radarr = new Radarr($radarrUri, $radarrApiKey);
	// Search for the movie object as Radarr requires it
	$movieCheck = json_decode($radarr->getMoviesLookup($command), true);
    write_log("Movie check result: ".json_encode($movieCheck));
	foreach ($movieCheck as $check) {
		if ($check['tmdbId'] === $data['tmdbId']) {
			$movie = $check;
			break;
		}
	}

	if ($movie) {
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
			$success = true;
		}
	} else write_log("No movie found in radarr.","WARN");

	return $success;
}

function downloadSick($data) {
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
	$response['media'] = $show;
	$response['mediaResult']['type'] = 'tv';
	return $response;
}

function downloadSonarr($command, $season = false, $episode = false, $tmdbResult = false) {
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
			$extras = $tmdbResult ? $tmdbResult : fetchMovieInfo(false, false, $seriesId);
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

function downloadWatcher($data) {
    write_log("Function fired: ".json_encode($data));
    $tmdbId = $data['tmdbId'] ?? false;
    $imdbId = $data['imdbId'] ?? false;
    $type = $tmdbId ? 'tmdb' : ($imdbId ? 'imdb' : false);
    $id = $tmdbId ? $tmdbId : ($imdbId ? $imdbId : false);
    $uri = $_SESSION['watcherUri'] ?? false;
    $token = $_SESSION['watcherToken'] ?? false;
    $success = false;
    if ($uri && $token && $type) {
        $watcher = new Watcher($uri,$token);
        $result = $watcher->addMovie($id,$type);
        write_log("Watcher result: ".json_encode($result));
        $success = $result['response'] ?? false;
    } else write_log("Missing watcher info!","ERROR");

    return $success;
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
        case "radarr":
        case "lidarr":
            if ($_SESSION[$serviceName . 'List']) {
                $list = $_SESSION[$serviceName . 'List'];
            } else {
                testConnection(ucfirst($serviceName));
                $list = $_SESSION[$serviceName . 'List'];
            }
            $selected = $_SESSION[$serviceName . 'Profile'];
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
        case "headphones":
            if ($_SESSION['headphonesList']) {
                $list = $_SESSION['headphonesList'];
            } else {
                testConnection("Headphones");
                $list = $_SESSION['headphonesList'];
            }
            $selected = $_SESSION['headphonesProfile'];
            break;
        case "watcher":
            if ($_SESSION['watcherList']) {
                $list = $_SESSION['watcherList'];
            } else {
                testConnection("Watcher");
                $list = $_SESSION['watcherList'];
            }
            $selected = $_SESSION['watcherProfile'];
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


/**
 * parseFetchers
 *
 * Take our list of media from various fetchers and parse it into
 * uniform data objects
 * @param $data
 * @return array
 */
function parseFetchers(array $data) {
    $media = [];
    foreach($data as $fetcher => $library) {
        $items = [];
        switch($fetcher) {
            case 'couch':
                $items = parseCouch($library);
                break;
            case 'headphones':
                $items = parseHeadphones($library);
                break;
            case 'lidarr':
                $items = parseLidarr($library);
                break;
            case 'radarr':
                $items = parseRadarr($library);
                break;
            case 'sick':
                $items = parseSick($library);
                break;
            case 'sonarr':
                $items = parseSonarr($library);
                break;
            case 'watcher':
                $items = parseWatcher($library);
                break;
        }
        foreach($items as $item) array_push($media,$item);
    }
    write_log("Parsed media: ".json_encode($media));
    return $media;
}

function parseCouch($library) {
    $library = $library['movies'];
    write_log("Lib in: ".json_encode($library));
    $src = 'couch';
    $items = [];
    foreach($library as $item) {
        $out = [
            'title'=>$item['title'],
            'year'=>$item['info']['year'],
            'imdbId'=>$item['info']['imdb'],
            'tmdbId'=>$item['info']['tmdb_id'],
            'summary'=>$item['info']['plot'],
            'source'=>$src
        ];
        array_push($items, $out);
    }
    return $items;
}

function parseHeadphones($library) {
    write_log("Lib in: ".json_encode($library));
    $src = 'headphones';
    $items = [];
    return $items;
}

function parseLidarr($library) {
    write_log("Lib in: ".json_encode($library));
    $src = 'lidarr';
    $items = [];
    return $items;
}

function parseRadarr($library) {
    write_log("Lib in: ".json_encode($library));
    $src = 'radarr';
    $items = [];
    foreach($library as $item) {
        $out = [
            'title'=>$item['title'],
            'year'=>$item['year'],
            'imdbId'=>$item['imdbId'],
            'tmdbId'=>$item['tmdbId'],
            'summary'=>$item['overview'],
            'source'=>$src
        ];
        array_push($items,$out);
    }
    return $items;
}

function parseSick($library) {
    write_log("Lib in: ".json_encode($library));
    $src = 'sick';
    $library = $lbrary['data'] ?? false;
    $items = [];
    if ($library) foreach($library as $id => $item) {
        $out = [
            "title" => $item["show_name"],
            "tvdbId" => $item["tvdbid"],
            "id" => $id,
            "source" => $src
        ];
        array_push($items, $out);
    }
    return $items;
}

function parseSonarr($library) {
    write_log("Lib in: ".json_encode($library));
    $src = "sonarr";
    $items = [];
    foreach($library as $item) {
        $out = [
            "title" => $item['title'],
            "tvdbId" => $item['tvdbId'],
            "source" => $src
        ];
        $items[] = $out;
    }
    return $items;
}

function parseWatcher($library) {
    write_log("Lib in: ".json_encode($library));
    $src = 'watcher';
    $items = [];
    $library = $library['movies'] ?? [];
    foreach($library as $item) {
        $out = [
            'title'=>$item['title'],
            'year'=>$item['year'],
            'imdbId'=>$item['imdbid'],
            'tmdbId'=>$item['tmdbid'],
            'summary'=>$item['plot'],
            'source'=>$src
        ];
        array_push($items,$out);
    }
    return $items;
}
//**
// These guys are responsible for pulling together a list of all of the library items
//  */

function scanFetchers($type = false, $id=false) {
    $fetchers = $searchArray = [];
    write_log("Function fired, searching for type of $type. Session data is currently: ".json_encode(getSessionData()));
    switch($type) {
        case 'movie':
            if ($_SESSION['couchEnabled'] ?? false) $searchArray['couch'] = scanCouch();
            if ($_SESSION['radarrEnabled'] ?? false) $searchArray['radarr'] = scanRadarr();
            if ($_SESSION['watcherEnabled'] ?? false) $searchArray['watcher'] = scanWatcher();
            break;
        case 'show':
        case 'episode':
        case 'show.episode':
            if ($_SESSION['sickEnabled'] ?? false) $searchArray['sick'] = scanSick($id);
            if ($_SESSION['sonarrEnabled'] ?? false) $searchArray['sonarr'] = scanSonarr();
            break;
        case 'music':
        case 'album':
        case 'artist':
            if ($_SESSION['headphonesEnabled'] ?? false) $searchArray['headphones'] = scanHeadphones();
            if ($_SESSION['lidarrEnabled'] ?? false) $searchArray['lidarr'] = scanLidarr();
            break;
        default:
            if ($_SESSION['couchEnabled'] ?? false) $searchArray['couch'] = scanCouch();
            if ($_SESSION['radarrEnabled'] ?? false) $searchArray['radarr'] = scanRadarr();
            if ($_SESSION['watcherEnabled'] ?? false) $searchArray['watcher'] = scanWatcher();
            if ($_SESSION['sickEnabled'] ?? false) $searchArray['sick'] = scanSick();
            if ($_SESSION['sonarrEnabled'] ?? false) $searchArray['sonarr'] = scanSonarr();
            if ($_SESSION['headphonesEnabled'] ?? false) $searchArray['headphones'] = scanHeadphones();
            if ($_SESSION['lidarrEnabled'] ?? false) $searchArray['lidarr'] = scanLidarr();
    }
    write_log("Final fetcher search array: ".json_encode($searchArray),"INFO");
    foreach($searchArray as $key=>$value) if (!$value) unset($searchArray["$key"]); else array_push($fetchers,$key);
    $data = new multiCurl($searchArray);
    $data = $data->process();
    $parsed = parseFetchers($data);
    return ['items'=>$parsed,'fetchers'=>$fetchers];
}

function scanCouch() {
    write_log("Function fired!");
    $name = "couch";
    $result = false;
    $uri = $_SESSION["$name" .'Uri'] ?? false;
    $token = $_SESSION["$name".'Token'] ?? false;
    if ($uri && $token) {
        $result = "$uri/api/$token/media.list?type=movie&status=active";
    }
    return $result;
}

function scanHeadphones() {
    write_log("Function fired!");
    $name = "headphones";
    $result = false;
    $uri = $_SESSION["$name" .'Uri'] ?? false;
    $token = $_SESSION["$name".'Token'] ?? false;
    if ($uri && $token) {
        $result = "$uri/api?apikey=$token&cmd=getWanted";
    }
    return $result;
}

function scanLidarr() {
    write_log("Function fired!");
    $name = "lidarr";
    $result = false;
    $uri = $_SESSION["$name" .'Uri'] ?? false;
    $token = $_SESSION["$name".'Token'] ?? false;
    if ($uri && $token) {
        $result = "$uri/api/v1/artist?apikey=$token";
    }
    return $result;
}

function scanRadarr() {
    write_log("Function fired!");
    $name = "radarr";
    $result = false;
    $uri = $_SESSION["$name" .'Uri'] ?? false;
    $token = $_SESSION["$name".'Token'] ?? false;
    if ($uri && $token) {
        $result = "$uri/api/movie?apikey=$token";
    }
    return $result;
}

function scanSick($tvdbId=false) {
    write_log("Function fired!");
    $name = "sick";
    $result = false;
    $uri = $_SESSION["$name" .'Uri'] ?? false;
    $token = $_SESSION["$name".'Token'] ?? false;
    write_log("Uri and token are $uri and $token");
    if ($uri && $token) {
        if ($tvdbId) {
            $result = "$uri/api/$token/?cmd=show.seasons&tvdbid=$tvdbId";
        } else {
            $result = "$uri/api/$token/?cmd=shows";
        }

    }
    write_log("Result: ".json_encode($result));
    return $result;
}

function scanSonarr() {
    write_log("Function fired!");
    $name = "sonarr";
    $result = false;
    $uri = $_SESSION["$name" .'Uri'] ?? false;
    $token = $_SESSION["$name".'Token'] ?? false;
    if ($uri && $token) {
        $result = "$uri/api/series?apikey=$token";
    }
    return $result;
}

function scanWatcher() {
    write_log("Function fired!");
    $name = "watcher";
    $result = false;
    $uri = $_SESSION["$name" .'Uri'] ?? false;
    $token = $_SESSION["$name".'Token'] ?? false;
    if ($uri && $token) {
        $result = "$uri/api/?apikey=$token&mode=liststatus";
    }
    return $result;
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
						$array["$id"] = $name;
						if (!$first) $first = $id;
					}
					write_log("CouchList: ".json_encode($array));
					updateUserPreferenceArray(['couchProfile'=>$first,'couchList'=>$array]);
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
		case "Radarr":
		case "Lidarr":
			$svc = strtolower($serviceName);
			write_log("Service string should be $svc plus Uri or Token");
			$string1 = $svc . "Uri";
			$string2 = $svc . "Token";
            $url = $_SESSION["$string1"] ?? false;
            $token = $_SESSION["$string2"] ?? false;
            write_log("Fucking session data: ".json_encode(getSessionData()));
			write_log("DARRRRR search $serviceName, uri and token are $url and $token");
			if (($token) && ($url)) {
				if ($serviceName == "Lidarr") {
					$url = "$url/api/v1/qualityprofile?apikey=$token";
				} else {
					$url = "$url/api/profile?apikey=$token";
				}

				write_log("Request URL: " . $url);
				$result = curlGet($url,null,10);
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
					updateUserPreferenceArray([$svc . 'Profile'=>$first,$svc . 'List'=>$array]);
				}
				$result = (($result !== false) ? "Connection to $serviceName successful!" : 'ERROR: Server not available.');
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
				updateUserPreference('sickList', $array);
				write_log("List: " . print_r($_SESSION['sickList'], true));
				$result = (($result) ? 'Connection to Sick successful!' : 'ERROR: Server not available.');
			} else $result = "ERROR: Missing server parameters.";
			break;
        case "Watcher":
            $url = $_SESSION['watcherUri'] ?? false;
            $token = $_SESSION['watcherToken'] ?? false;
            $config = false;
            if ($url && $token) {
                $watcher = new Watcher($url, $token);
                $config = $watcher->getConfig('Quality');
            }
            if ($config) {
                write_log("Got me a config: ".json_encode($config));
                $list = $config['Profiles'];
                $array = [];
                $count = 0;
                $selected = null;
                foreach ($list as $name => $profile) {
                    $selected = $profile['default'] ? $count : null;
                    $array[$count] = $name;
                    $count++;
                }
                $data = ['watcherList'=>$array,'watcherProfile'=>($selected == null ? "0": $selected)];
                write_log("Saving some watcher data: ".json_encode($data),"INFO");
                updateUserPreferenceArray($data);
            }
            $result = (($config) ? 'Connection to Watcher successful!' : 'ERROR: Connection failed.');
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
