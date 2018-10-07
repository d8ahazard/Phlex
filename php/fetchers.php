<?php
require_once dirname(__FILE__) . '/vendor/autoload.php';
require_once dirname(__FILE__) . '/webApp.php';
require_once dirname(__FILE__) . '/fetchers/digitalhigh/watcher/src/Watcher.php';
require_once dirname(__FILE__) . '/fetchers/digitalhigh/lidarr/src/Lidarr.php';
require_once dirname(__FILE__) . '/fetchers/digitalhigh/headphones/src/Headphones.php';
require_once dirname(__FILE__) . '/fetchers/digitalhigh/radarr/src/Radarr.php';
require_once dirname(__FILE__) . '/fetchers/digitalhigh/ombi/src/Ombi.php';
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

function matchVideoMedia($itemA, $itemB) {
    $keys = ['tmdbId','imdbId','tvdbId'];
    foreach($keys as $key) {
        if (isset($itemA[$key]) && isset($itemB[$key])) {
            if ($itemA[$key] === $itemB['$key']) {
                write_log("Found a match for $key");
                return true;
            }
        }
    }
    return false;
}

function downloadOmbi(array $data) {
	write_log("Incoming data: ".json_encode($data));
	$ombiUri = $_SESSION['ombiUri'];
	$ombiAuth = $_SESSION['ombiToken'];
	$type = $data['type'] ?? false;
	$ombi = new Ombi($ombiUri, $ombiAuth);
	$imdbId = $data['imdbId'] ?? false;
	$title = $data['title'];
	$results = [];
	$items = [];
	try {
		if (!$type) {
			$results = $ombi->getSearchMulti($title);
			$movies = json_decode($results['movie'], true) ?? [];
			$shows = json_decode($results['tv'],true) ?? [];
			$items = [];
			foreach($movies as &$movie) {
				$movie['type'] = 'movie';
				array_push($items, $movie);

			}

			foreach($shows as &$show) {
				$show['type'] = 'show';
				array_push($items, $show);
			}

		} else {
			if ($type == 'show') $results = $ombi->getSearchTv($title);
			if ($type == 'movie') $results = $ombi->getSearchMovie($title);
			$items = json_decode($results, true);
		}
		$results = [];
		$year = $data['year'];
		foreach($items as $result) {
			if (compareTitles($title, $result['title'],false,true)) {
				$result['type'] = $type;
				$resultYear = $result['releaseDate'] ?? false;
				if ($resultYear) {
					write_log("We should be able to match by year...");
					$resultYear = explode("-",$resultYear)[0];
					write_log("Checking $resultYear against $year");
					if ($resultYear === $year) write_log("MATCH");
				}
				$yearMatch = $resultYear ? ($resultYear == $year) : true;
				if ($yearMatch) array_push($results, $result);
			}
		}
		write_log("Got some results: ".json_encode($results));
	} catch (Exception $e)  {
		write_log("Well, this is exceptional.");
	}
	$items = $results;
	write_log("Items: ".json_encode($items));
	if (count($items) == 1) {
		write_log("Downloading single item.");
		if ($items[0]['type'] == 'movie') {
			$result = json_decode($ombi->postRequestMovie($items[0]['theMovieDbId']),true);
		} else {
			$id = $items[0]['id'];
			$info = json_decode($ombi->getSearchTvInfo($id),true);
			write_log("Show info: ".json_encode($info));
			$tvDbId = $info['theTvDbId'];
			$result = json_decode($ombi->postRequestTv($tvDbId, $info['seasonRequests']),true);
		}
		if ($result['isError']) {
			$msg = $result['errorMessage'];
		} else {
			$msg = $result['message'];
		}
		write_log("Addition result '$msg': ".json_encode($result));
		return $result['isError'];
	}
	return false;
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
    write_log("Function fired: ".json_encode($data));
    $success = false;
    $ids = [];
    $uri = $_SESSION['headphonesUri'] ?? false;
    $token = $_SESSION['headphonesToken'] ?? false;
    if (!$uri || !$token) return false;
    $phones = new Headphones($uri,$token);
    $type = $data['type'];
    $request = $data['title'];

    write_log("Type and request are $type and $request");
    if ($type == 'artist') {
        $data = $phones->findArtist($request);
    } else {
        $data = $phones->findAlbum($request);
    }
    $data = json_decode($data,true);

    write_log("Search data: ".json_encode($data));
    $items = [];
    if ($data) {
        foreach($data as $item) {
            if ($item['score'] == 100) {
                write_log("Found matching item: ".json_encode($item));
                array_push($items,$item);
            }
        }
    }

    write_log("ID's: ".json_encode($items));
    $id = false;
    $idStr = ($type == "album") ? "albumid" : "id";
    if (count($items)) {
        if (count($items) == 1) {
            write_log("Single item found, adding to DB.");
            $id = $items[0]["$idStr"];
        } else {
            if ($type == 'album') {
                write_log("Multiple results with 100% match, trying to filter by country.");
                $loc = $_SERVER['REMOTE_ADDR'] ?? false;
                $cCode = false;
                if ($loc) {
                    $locInfo = json_decode(curlGet("https://api.ipdata.co/$loc"), true);
                    write_log("Got me some location info: " . json_encode($locInfo));
                    if ($locInfo) $cCode = $locInfo['country_code'] ?? false;
                }
                $cCode = $cCode ? $cCode : 'US';
                foreach ($items as $item) {
                    if ($item['country'] == $cCode) {
                        write_log("Found a matching item for user's country. Cool.");
                        $id = $item["$idStr"];
                        break;
                    }
                }
            }
        }
    }
    if ($id) {
        $response = ($type=='artist') ? $phones->addArtist($id) : $phones->addAlbum($id);
        write_log("Response: ".$response);
        $success = ($response == "OK");
    }
    return $success;
}

function downloadLidarr(array $data) {
    write_log("Function fired: ".json_encode($data));
    $profile = $_SESSION['lidarrProfile'] ?? "1";
    $uri = $_SESSION['lidarrUri'] ?? false;
    $token = $_SESSION['lidarrToken'] ?? false;
    $album = $artist = $exists = $scanId = $success = false;
    if (!$uri || !$token) return false;
    $type = $data['type'];
    $lidarr = new Lidarr($uri,$token);
    $root = $_SESSION['lidarrRoot'] ?? json_decode($lidarr->getRootFolder(),true)['path'];
    $response = json_decode($lidarr->getArtist(),true);
    $id = $data['artistMbId'] ?? $data['mbId'];
    $exists = false;
    foreach($response as $item) {
        if ($item['foreignArtistId'] == $id) {
            $artist = $item;
            $exists = true;
            break;
        }
    }

    if (!$artist) {
        $response = json_decode($lidarr->getArtistLookup("lidarr:$id"),true);
        write_log("Data array: " . json_encode($response));
        if (count($response)) $artist = $response[0];
    }

    if (is_array($artist)) {
        write_log("We've found a matching artist: ".json_encode($artist));
        $monitored = ($type == 'artist');
        $artist['monitored'] = $monitored;
        $artist['qualityProfileId'] = $profile;
        $artist['languageProfileId'] = 1;
        $artist['metadataProfileId'] = 1;
        $artist['albumFolder'] = true;
        $artist['rootFolderPath'] = $root;
        $options = [
            "ignoreAlbumsWithFiles" => false,
            "ignoreAlbumsWithoutFiles" => false,
            "monitored" => $monitored,
            "searchForMissingAlbums" => $monitored
        ];
        $artist['addOptions'] = $artist['addOptions'] ?? $options;
        write_log("Artist payload: ".json_encode($artist));
        $result = $exists ? $lidarr->updateArtist($artist) : $lidarr->postArtist($artist);
        $result = json_decode($result,true);
        write_log("Result: ".json_encode($result));
        $success = isset($result['path']);
        //$scanId = $artist['id'];
    }

    if ($type != 'artist') {
        $success = false;
        write_log("Okay, now we need to find an album!");
        $result = json_decode($lidarr->getAlbum(null, $data['mbId']),true);
        write_log("Result: ".json_encode($result));
        foreach ($result as $check) if ($check['foreignAlbumId'] == $data['mbId']) $album = $check;
    }

    if (is_array($album)) {
        write_log("Okay, here's our album: ".json_encode($album));
        $album['monitored'] = true;
        $result = json_decode($lidarr->updateAlbum($album['id'],$album),true);
        write_log("Result: ".json_encode($result));
        $success = $result['monitored'] ?? false;
        $scanId = $album['id'];
    }

    if ($success && $scanId) {
        $success = false;
        write_log("Aight, we're going to trigger a search now...");
        $result = json_decode($lidarr->postCommand(ucfirst($type)."Search",[$type."Ids"=>[$scanId]]),true);
        write_log("Search result: ".json_encode($result));
        $success = isset($result['status']);
    }

    return $success;
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
		$rootPath = $_SESSION['radarrRoot'] ?? json_decode($radarr->getRootFolder(), true)[0]['path'];
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
	write_log("Function fired: ".json_encode($data));
	$exists = $success = false;
	$season = $data['season'] ?? false;
	$episode = $data['episode'] ?? false;
	$sickUri = $_SESSION['sickUri'];
	$sickApiKey = $_SESSION['sickToken'];
	if (!$sickUri || !$sickApiKey) return false;
	$sick = new SickRage($sickUri, $sickApiKey);
	$results = json_decode($sick->shows(), true)['data'];
	foreach ($results as $show) {
        $show['title'] = $show['show_name'];
        $show['tvdbId'] = $show['tvdbid'];

		if (matchVideoMedia($show,$data)) {
			$title = $show['title'];
			write_log("Found $title in the library: " . json_encode($show));
			$exists = true;
			$data = array_merge($show,$data);
			$success = true;
			break;
		}
	}



    $id = $data['tvdbId'] ?? false;
	if (!$id) return false;

    $data['type'] = 'show';

	if (!$exists) {
		$status = ($season && $episode) ? 'skipped' : 'wanted';
		write_log("Show not in list, adding.");
		$result = json_decode($sick->showAddNew($id, null, 'en', null, $status, $_SESSION['sickProfile']), true);
		write_log('Fetch result: ' . json_encode($result));
		$success = ($result['result'] == 'success');
	}

	if ($season) {
	    $success = false;
		if ($episode) {
			write_log("Searching for season $season episode $episode of show with ID of $id");
			$result = json_decode($sick->episodeSetStatus($id, $season, 'wanted', $episode, 1), true);
			if ($result) {
				write_log("Episode search worked, result is " . json_encode($result));
				$success = ($result['result'] === 'success');
			}
		} else {
            $result2 = json_decode($sick->episodeSetStatus($id, $season, 'wanted', null, 1), true);
			$status = strtoupper($result2['result']) . ": " . $result2['message'];
			write_log("Season search status is '$status'");
			if ($result2['result'] === 'success') $success = true;

		}
	}

	if (!$season && $episode) {
	    $success = false;
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
		if (!$winner || $episode == -1) {
			write_log("EpsList: " . json_encode($epsList));
			foreach (array_reverse($epsList) as $episodeItem) {
				if ($episodeItem['aired']) {
					$winner = $episodeItem;
					break;
				}
			}
		}

		if ($winner) {
            write_log("Searching episode: " . json_encode($winner), "INFO");
			$result = $sick->episodeSearch($id, $winner['season'], $winner['episode']);
			$result2 = json_decode($sick->episodeSetStatus($id, $winner['season'], 'wanted', $winner['episode'], 1), true);
			if ($result2) {
				write_log("Episode search worked, result is " . json_encode($result2));
				$success = ($result2['result'] === 'success');
			}
		}
		write_log("Show result: " . json_encode($winner));
	}

	return $success;
}

function downloadSonarr($data) {
    $command = $data['title'];
    $season = $data['season'] ?? false;
    $episode = $data['episode'] ?? false;
	write_log("Function fired, searching for " . $command);
	$exists = $score = $seriesId = $show = $wanted = false;
	$show = $search = $success = false;
	$sonarrUri = $_SESSION['sonarrUri'] ?? false;
	$sonarrApiKey = $_SESSION['sonarrToken'] ?? false;
	if (!$sonarrUri || !$sonarrApiKey) {
	    write_log("Missing Sonarr credentials!","ERROR");
	    return false;
    }

	$sonarr = new Sonarr($sonarrUri, $sonarrApiKey);
    $root = $_SESSION['sonarrRoot'] ?? json_decode($sonarr->getRootFolder(), true)[0]['path'] ?? false;
    if (!$root) return false;
    $seriesArray = json_decode($sonarr->getSeries(), true);

    // See if it's already in the library
    foreach ($seriesArray as $series) {
        if (matchVideoMedia($series,$data)) {
            write_log("This show is already in the library.");
            write_log("SERIES: " . json_encode($series));
            $show = $series;
            $exists = true;
            break;
        }
    }

    $tvdbId = $data['tvdbId'];
    if (!$show) {
        $search = json_decode($sonarr->getSeriesLookup("tvdb:$tvdbId"), true);
        write_log("Search result: " . json_encode($search));
        $show = $search[0] ?? false;
    }

    if (is_array($show)) {
        $show['qualityProfileId'] = intval($_SESSION['sonarrProfile'] ?? 0);
        $show['rootFolderPath'] = $root;
        $skip = ($season || $episode);
        if ($exists || $season && !$episode) {
            $newSeasons = [];
            foreach ($show['seasons'] as $check) {
                $check['monitored'] = ($check['seasonNumber'] == $season);
                array_push($newSeasons, $check);
            }
            $show['seasons'] = $newSeasons;
            unset($show['rootFolderPath']);
            $show['isExisting'] = false;
            write_log("Attempting to update the series " . $show['title'] . ", JSON is: " . json_encode($show));
            $show = json_decode($sonarr->putSeries($show), true);
            write_log("Season add result: " . json_encode($show));
            $success = true;
        } else {
            write_log("Attempting to add the series " . $show['title'] . ", JSON is: " . json_encode($show));
            $show = json_decode($sonarr->postSeries($show, $skip), true);
            $msg = $show['error']['msg'];
            if ($msg == "This series has already been added") write_log("Show is already added.","INFO");
            $success = true;
        }
    } else {
        $success = false;
    }

	// If we want a whole season, send the command to search it.
	if ($show && $season && !$episode) {
		$data = [
			'seasonNumber' => $season,
			'seriesId' => $show['id'],
			'updateScheduledTask' => true
		];
		$result = json_decode($sonarr->postCommand("SeasonSearch", $data), true);
		write_log("Command result: " . json_encode($result));
		$success = ($result['body']['completionMessage'] == "Completed");
		$show['subtitle'] = "Season " . sprintf("%02d", $season);
	}

	// If we want a specific episode, then we need to search it manually.
	if ($episode && $show) {
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
			$success = ($result['body']['completionMessage'] == "Completed");
		} else {
			$success = false;
		}
	}

	return $success;
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
	if ($serviceName === 'device') return false;
    $list = $selected = false;
    $token = $_SESSION[$serviceName . "Token"] ?? "";
    $uri = $_SESSION[$serviceName . "Uri"] ?? "";
    if (trim($token) == "" || trim($uri) == "") {
    	$current = fetchUserData();
    	if (count($current[$serviceName."List"] ?? [])) {
		    updateUserPreferenceArray([$serviceName."List" => json_encode([]),$serviceName."Profile"=>""]);
		    return "";
	    }
    }
    if (!$_SESSION[$serviceName . "Enabled"]) return "";
    switch ($serviceName) {
        case "sick":
            if ($_SESSION['sickList'] ?? false) {
                $list = $_SESSION['sickList'];
            } else {
                testConnection("Sick");
                $list = $_SESSION['sickList'];
            }
            $selected = $_SESSION['sickProfile'];
            break;
        case "ombi":
            if ($_SESSION['ombiList'] ?? false) {
                $list = $_SESSION['ombi'];
            }
            break;
        case "sonarr":
        case "radarr":
        case "lidarr":
            if ($_SESSION[$serviceName . 'List'] ?? false) {
                $list = $_SESSION[$serviceName . 'List'];
            } else {
                testConnection(ucfirst($serviceName));
                $list = $_SESSION[$serviceName . 'List'];
            }
            $selected = $_SESSION[$serviceName . 'Profile'];
            break;
        case "couch":
            if ($_SESSION['couchList'] ?? false) {
                $list = $_SESSION['couchList'];
            } else {
                testConnection("Couch");
                $list = $_SESSION['couchList'];
            }
            $selected = $_SESSION['couchProfile'];
            break;
        case "headphones":
            if ($_SESSION['headphonesList'] ?? false) {
                $list = $_SESSION['headphonesList'];
            } else {
                testConnection("Headphones");
                $list = $_SESSION['headphonesList'];
            }
            $selected = $_SESSION['headphonesProfile'];
            break;
        case "watcher":
            if ($_SESSION['watcherList'] ?? false) {
                $list = $_SESSION['watcherList'];
            } else {
                testConnection("Watcher");
                $list = $_SESSION['watcherList'];
            }
            $selected = $_SESSION['watcherProfile'];
            break;
    }

    return $list;
}

function listFetchers() {
    $fetchers = ['ombi','couch','sonarr','radarr','lidarr','headphones','sick','watcher'];
    $results = [];
    foreach ($fetchers as $fetcher) {
        if ($_SESSION[$fetcher."Enabled"]) array_push($results,$fetcher);
    }
    return $results;
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
	        case 'ombi':
	        	$items = parseOmbi($library);
	        	break;
        }
        foreach($items as $item) array_push($media,$item);
    }
    write_log("Parsed media: ".json_encode($media));
    return $media;
}

function parseOmbi($library) {
	write_log("Lib in: ".json_encode($library));
	$src = 'ombi';
	$items = [];
	foreach($library as $secName => $section) {
		foreach ($section as $item) {
			$out = [
				'title' => $item['title'],
				'year' => $item['info']['year'],
				'imdbId' => $item['info']['imdb'],
				'tmdbId' => $item['info']['tmdb_id'],
				'summary' => $item['info']['plot'],
				'type' => $secName,
				'source' => $src
			];
			array_push($items, $out);
		}
	}
	return $items;
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
        write_log("ITEM: ".json_encode($item));
        $out = [
            "title" => $item['title'],
            "tvdbId" => $item['tvdbId'],
            "source" => $src,
            "year" => $item['year']
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

function scanFetchers($type = false, $id=false, $noCurl=false) {
    $fetchers = $searchArray = [];
    write_log("Function fired, searching for type of $type. Session data is currently: ".json_encode(getSessionData()));
    switch($type) {
        case 'movie':
            if ($_SESSION['couchEnabled'] ?? false) $searchArray['couch'] = scanCouch();
            if ($_SESSION['radarrEnabled'] ?? false) $searchArray['radarr'] = scanRadarr();
            if ($_SESSION['watcherEnabled'] ?? false) $searchArray['watcher'] = scanWatcher();
            if ($_SESSION['ombiEnabled'] ?? false) $searchArray['ombi'] = scanOmbi();
            break;
        case 'show':
        case 'episode':
        case 'show.episode':
            if ($_SESSION['sickEnabled'] ?? false) $searchArray['sick'] = scanSick($id);
            if ($_SESSION['sonarrEnabled'] ?? false) $searchArray['sonarr'] = scanSonarr();
	        if ($_SESSION['ombiEnabled'] ?? false) $searchArray['ombi'] = scanOmbi();
            break;
        case 'music':
        case 'music.track':
        case 'music.album':
        case 'music.artist':
        case 'album':
        case 'artist':
            if ($_SESSION['headphonesEnabled'] ?? false) $searchArray['headphones'] = scanHeadphones();
            if ($_SESSION['lidarrEnabled'] ?? false) $searchArray['lidarr'] = scanLidarr();
            break;
        default:
	        if ($_SESSION['ombiEnabled'] ?? false) $searchArray['ombi'] = scanOmbi();
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
    if ($noCurl) {
        $parsed = false;
    } else {
        $data = new multiCurl($searchArray);
        $data = $data->process();
        $parsed = parseFetchers($data);
    }
    return ['items'=>$parsed,'fetchers'=>$fetchers];
}

function scanOmbi() {
write_log("Function fired!");
    $name = "ombi";
    $result = true;
    $uri = $_SESSION["$name" .'Uri'] ?? false;
    $token = $_SESSION["$name".'Token'] ?? false;
    if ($uri && $token) {

	    //$result = "$uri/api/$token/media.list?type=movie&status=active";
    }
    return $result;
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
function testConnection($serviceName,$returnList=false) {
	$serviceName = ucfirst($serviceName);
	write_log("Testing connection for " . $serviceName,"INFO");
    $msg = "ERROR: Connection to $serviceName failed.";
    $data = $profileList = [];
    $selected = false;
	switch ($serviceName) {

		case "Ombi":
			$ombiUri = $_SESSION['ombiUri'];
			$ombiAuth = $_SESSION['ombiToken'];
			if (($ombiUri) && ($ombiAuth)) {
				$ombi = new Ombi($ombiUri, $ombiAuth);
				$result = json_decode($ombi->getIdentityUsers(),true);
				write_log("Ombi test result: ".json_encode($result));
				$msg = (isset($result[0]['userName']) ? "Connection to $serviceName successful!" : 'ERROR: Server not available.');
			} else {
			    $msg = "ERROR: Missing server parameters.";
            }
			break;

		case "Couch":
			$couchURL = $_SESSION['couchUri'] ?? false;
			$couchToken = $_SESSION['couchToken'] ?? false;

			if ($couchURL && $couchToken) {
				write_log("Got both values.");
				$url = "$couchURL/api/$couchToken/profile.list";
				$result = checkUrl($url,true);
				if ($result[0]) {
					$resultJSON = json_decode($result[1], true);
					$list = $resultJSON['list'] ?? false;
					if ($list) {
                        write_log("Hey, we've got some profiles: " . json_encode($resultJSON));
                        foreach ($resultJSON['list'] as $profile) {
                            $id = $profile['_id'];
                            $name = $profile['label'];
                            $profileList["$id"] = $name;
                            if (!$selected) $selected = $id;
                        }
                        $msg = "Connection to $serviceName successful!";
                    }
				} else {
				    $msg = "ERROR: " . $result[1];
				    $selected = $profileList = false;
                }

			} else {
			    $msg = "ERROR: Missing ". ($couchURL ? "Token." : "URL.");

            }
			$data = ['couchProfile' => $selected, 'couchList' => $profileList];
			write_log("Pushing userdata, what the fuck: ".json_encode($data));
			updateUserPreferenceArray($data);
			break;

		case "Headphones":
			$headphonesURL = $_SESSION['headphonesUri'];
			$headphonesToken = $_SESSION['headphonesToken'];
			if (($headphonesURL) && ($headphonesToken)) {
				$url = "$headphonesURL/api/?apikey=$headphonesToken&cmd=getVersion";
				$result = checkUrl($url,true);
				if ($result[0]) {
					$data = (json_decode($result[1],true)['current_version'] ?? false);
					$msg = ($data ? "Connection Successful!" : "ERROR: Unknown Error");
				} else {
				    $msg = "ERROR: ". $result[1];
                }
			} else {
                $msg = "ERROR: Missing ". ($headphonesURL ? "Token." : "URL.");
            }
			break;

		case "Sonarr":
		case "Radarr":
		case "Lidarr":
			$svc = strtolower($serviceName);
			write_log("Service string should be $svc plus Uri or Token");
			$string1 = $svc . "Uri";
			$string2 = $svc . "Token";
            $root = $_SESSION["$string1"] ?? false;
            $token = $_SESSION["$string2"] ?? false;
            write_log("DARRRRR search $serviceName, uri and token are $root and $token");
			if (($token) && ($root)) {
				if ($serviceName == "Lidarr") {
					$url = "$root/api/v1/qualityprofile?apikey=$token";
					$url2 = "$root/api/v1/rootfolder?apikey=$token";
				} else {
					$url = "$root/api/profile?apikey=$token";
					$url2 = "$root/api/rootfolder?apikey=$token";
				}

				write_log("Request URL: " . $url);
				$result = checkUrl($url,true);
				$result2 = checkUrl($url2,true);
				if ($result[0] && $result2[0]) {
					write_log("Results retrieved.");
					$resultJSON = json_decode($result[1], true);
                    $resultJSON2 = json_decode($result2[1], true);
					foreach ($resultJSON as $profile) {
						if ($profile === "Unauthorized") {
							return "ERROR: Incorrect API Token specified.";
						}
						$selected = ($selected ? $selected : $profile['id']);
						$profileList[$profile['id']] = $profile['name'];
					}
					
					$data = [$svc . 'Profile'=>$selected,$svc . 'List'=>$profileList, $svc . 'Root'=>$resultJSON2[0]['path']];
					updateUserPreferenceArray($data);
					$msg = "Connection to $serviceName successful!";
				} else {
				    $msg = "ERROR:" . ($result[0] ? ($result2[0] ? "Unknown Error" : $result2[1]) : $result[1]);
				    write_log("ERROR Connecting - '$msg'","INFO");
                }
			} else {
			    $msg = "ERROR: Missing server parameters.";
            }
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
					$msg = "ERROR: " . $e;
					break;
				}
				$result = json_decode($result, true);
				write_log("Got some kind of result: " . json_encode($result));
				$list = $result['data']['initial'];
				$count = 0;
				foreach ($list as $profile) {
				    $selected = "0";
					$profileList[$count] = $profile;
					$count++;
				}
				$data = ['sickList'=>$profileList, 'sickProfile'=>$selected];
				$msg = "Connection to Sick successful!";
			} else {
                $msg = "ERROR: Missing ". ($sickURL ? "Token." : "URL.");
            }
			break;
        case "Watcher":
            $url = $_SESSION['watcherUri'] ?? false;
            $token = $_SESSION['watcherToken'] ?? false;
            if ($url && $token) {
                $watcher = new Watcher($url, $token);
                $config = $watcher->getConfig('Quality');
                if ($config) {
                    write_log("Got me a config: ".json_encode($config));
                    $list = $config['Profiles'];
                    $count = 0;
                    foreach ($list as $name => $profile) {
                        $selected = $profile['default'] ? $count : null;
                        $profileList[$count] = $name;
                        $count++;
                    }
                    $selected = ($selected  ? $selected : "0");
                    $data = ['watcherList'=>$profileList,'watcherProfile'=>$selected];
                }
                $msg = (($config) ? 'Connection to Watcher successful!' : 'ERROR: Connection failed.');
            } else {
                $msg = "ERROR: Missing " . ($url ? "token." : "URL.");
            }
            break;

		default:
			$msg = "ERROR: Service name not recognized.";
			break;
	}

	if (count($profileList) && $selected !== false) {
        write_log("$serviceName Profile List Found!: " . json_encode($profileList),"INFO");
    } else {
	    write_log("Unable to fetch profile list...","WARN");
    }

    if (count($data)) {
	    write_log("Updating app data: ".json_encode($data),"INFO");
        updateUserPreferenceArray($data);
    }

	return ($returnList ? $msg : [$msg,$profileList]);
}
