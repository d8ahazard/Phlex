<?php

namespace digitalhigh\Lidarr;

use GuzzleHttp\Client;
use digitalhigh\Lidarr\Exceptions\InvalidException;

class Lidarr
{
    protected $url;
    protected $apiKey;
    protected $httpAuthUsername;
    protected $httpAuthPassword;

    public function __construct($url, $apiKey, $httpAuthUsername = null, $httpAuthPassword = null)
    {
        $this->url = rtrim($url, '/\\'); // Example: http://127.0.0.1:8989 (no trailing forward-backward slashes)
        $this->apiKey = $apiKey;
        $this->httpAuthUsername = $httpAuthUsername;
        $this->httpAuthPassword = $httpAuthPassword;
    }

    /**
     * Gets upcoming artist, if start/end are not supplied artist airing today and tomorrow will be returned
     * When supplying start and/or end date you must supply date in format yyyy-mm-dd
     * Example: $Lidarr->getCalendar('2015-01-25', '2016-01-15');
     * 'start' and 'end' not required. You may supply, one or both.
     *
     * @param string|null $start
     * @param string|null $end
     * @return array|object|string
     * @throws InvalidException
     */
    public function getCalendar($start = null, $end = null)
    {
        $uriData = [];

        if ( $start ) {
            if ( $this->validateDate($start) ) {
                $uriData['start'] = $start;
            } else {
                throw new InvalidException('Start date string was not recognized as a valid DateTime. Format must be yyyy-mm-dd.');
            }
        }
        if ( $end ) {
            if ( $this->validateDate($end) ) {
                $uriData['end'] = $end;
            } else {
                throw new InvalidException('End date string was not recognized as a valid DateTime. Format must be yyyy-mm-dd.');
            }
        }

        try {
            $response = $this->_request(
                [
                    'uri' => 'calendar',
                    'type' => 'get',
                    'data' => $uriData
                ]
            );
        } catch ( \Exception $e ) {
            throw new InvalidException($e->getMessage());
        }

        return $response->getBody()->getContents();
    }

    /**
     * Queries the status of a previously started command, or all currently started commands.
     *
     * @param null $id Unique ID of the command
     * @return array|object|string
     * @throws InvalidException
     */
    public function getCommand($id = null)
    {
        $uri = ($id) ? 'command/' . $id : 'command';

        try {
            $response = $this->_request(
                [
                    'uri' => $uri,
                    'type' => 'get',
                    'data' => []
                ]
            );
        } catch ( \Exception $e ) {
            throw new InvalidException($e->getMessage());
        }

        return $response->getBody()->getContents();
    }

    /**
     * Publish a new command for Lidarr to run.
     * These commands are executed asynchronously; use GET to retrieve the current status.
     *
     * Commands and their parameters can be found here:
     * https://github.com/Lidarr/Lidarr/wiki
     *
     * @param $name
     * @param array|null $params
     * @return string
     */
    public function postCommand($name, array $params = null)
    {
        $uri = 'command';
        $uriData = [
            'name' => $name
        ];

        if ($params != null) {
        	foreach ($params as $key=>$value) {
        		$uriData[$key]= $value;
	        }
        }

        $response = $this->_request(
            [
                'uri' => $uri,
                'type' => 'post',
                'data' => $uriData
            ]
        );

        return $response->getBody()->getContents();
    }

    /**
     * Gets Diskspace
     *
     * @return array|object|string
     * @throws InvalidException
     */
    public function getDiskspace()
    {
        $uri = 'diskspace';

        try {
            $response = $this->_request(
                [
                    'uri' => $uri,
                    'type' => 'get',
                    'data' => []
                ]
            );
        } catch ( \Exception $e ) {
            throw new InvalidException($e->getMessage());
        }

        return $response->getBody()->getContents();
    }



    /**
     * Gets history (grabs/failures/completed).
     *
     * @param int $page Page Number
     * @param int $pageSize Results per Page
     * @param string $sortKey 'artist.name' or 'date'
     * @param string $sortDir 'asc' or 'desc'
     * @return array|object|string
     * @throws InvalidException
     */
    public function getHistory($page = 1, $pageSize = 10, $sortKey = 'artist.name', $sortDir = 'asc')
    {
        $uri = 'history';

        try {
            $response = $this->_request(
                [
                    'uri' => $uri,
                    'type' => 'get',
                    'data' => [
                        'page' => $page,
                        'pageSize' => $pageSize,
                        'sortKey' => $sortKey,
                        'sortDir' => $sortDir
                    ]
                ]
            );
        } catch ( \Exception $e ) {
            throw new InvalidException($e->getMessage());
        }

        return $response->getBody()->getContents();
    }


    /**
     *
     * Returns the image for the specified artist/album
     *
     * @param string $id - The artist or album ID the image belongs to
     * @param int $size - Optional. See below for sizes.
     *
     * Available Sizes
     * Posters: 500, 250
     * Banners: 70,35
     * Fanart: 360, 180
     * Cover: 500, 250
     *
     * @return string
     * @throws InvalidException
     */
    public function getImages(string $id,int $size = 0) {
        $uri = "MediaCover/$id/poster.jpg";
        if ($size) $uri = "MediaCover/$id/poster-$size.jpg";
        try {
            $response = $this->_request(
                [
                    'uri' => $uri,
                    'type' => 'get',
                    'data' => []
                ]
            );
        }catch ( \Exception $e ) {
            throw new InvalidException($e->getMessage());
        }
        return $response->getBody()->getContents();
    }


    /**
     * Returns albums that are missing from disk.
     *
     * @param int $page Page Number
     * @param int $pageSize Results per Page
     * @param string $sortKey 'releaseDate', 'albumTitle', or 'artist.sortName'
     * @param string $sortDir 'asc' or 'desc'
     * @param bool $includeArtist - Defaults to false
     * @param bool $monitored - Default is true
     * @return array|object|string
     * @throws InvalidException
     */
    public function getWantedMissing($page = 1, $pageSize = 10, $sortKey = 'releaseDate',
                                     $sortDir = 'asc', $includeArtist = false, $monitored=true)
    {
        $uri = 'wanted/missing';

        try {
            $response = $this->_request(
                [
                    'uri' => $uri,
                    'type' => 'get',
                    'data' => [
                        'page' => $page,
                        'pageSize' => $pageSize,
                        'sortKey' => $sortKey,
                        'sortDir' => $sortDir,
                        'includeArtist' => $includeArtist,
                        'monitored' => $monitored
                    ]
                ]
            );
        } catch ( \Exception $e ) {
            throw new InvalidException($e->getMessage());
        }

        return $response->getBody()->getContents();
    }


    /**
     * Returns albums that have files on disk that do not meet the profile cutoff
     *
     * @param int $page Page Number
     * @param int $pageSize Results per Page
     * @param string $sortKey 'releaseDate', 'albumTitle', or 'artist.sortName'
     * @param string $sortDir 'asc' or 'desc'
     * @param bool $includeArtist - Defaults to false
     * @param bool $monitored - Default is true
     * @return array|object|string
     * @throws InvalidException
     */
    public function getWantedCutoff($page = 1, $pageSize = 10, $sortKey = 'releaseDate',
                                     $sortDir = 'asc', $includeArtist = false, $monitored=true)
    {
        $uri = 'wanted/cutoff';

        try {
            $response = $this->_request(
                [
                    'uri' => $uri,
                    'type' => 'get',
                    'data' => [
                        'page' => $page,
                        'pageSize' => $pageSize,
                        'sortKey' => $sortKey,
                        'sortDir' => $sortDir,
                        'includeArtist' => $includeArtist,
                        'monitored' => $monitored
                    ]
                ]
            );
        } catch ( \Exception $e ) {
            throw new InvalidException($e->getMessage());
        }

        return $response->getBody()->getContents();
    }


    /**
     * Gets item in activity queue
     *
     * @param int $page Page Number
     * @param int $pageSize Results per Page
     * @param string $sortKey 'estimatedCompletionTime', 'timeleft', 'artist.sortName', 'album.title',
     * 'progress', 'quality' - Default: timeleft
     * @param string $sortDir 'asc' or 'desc'
     * @param bool $includeArtist - Defaults to false
     * @param bool $includeAlbum - Default is false
     * @return array|object|string
     * @throws InvalidException
     */
    public function getQueue($page = 1, $pageSize = 10, $sortKey = 'timeleft',
                                    $sortDir = 'asc', $includeArtist = false, $includeAlbum=false)
    {
        $uri = 'wanted/cutoff';

        try {
            $response = $this->_request(
                [
                    'uri' => $uri,
                    'type' => 'get',
                    'data' => [
                        'page' => $page,
                        'pageSize' => $pageSize,
                        'sortKey' => $sortKey,
                        'sortDir' => $sortDir,
                        'includeArtist' => $includeArtist,
                        'monitored' => $includeAlbum
                    ]
                ]
            );
        } catch ( \Exception $e ) {
            throw new InvalidException($e->getMessage());
        }

        return $response->getBody()->getContents();
    }


    /**
     * Gets all quality profiles
     *
     * @param string $type - 'quality', 'language', or 'metadata'
     * @return array|object|string
     * @throws InvalidException
     */
    public function getProfiles($type = 'quality')
    {
        $uri = $type.'profile';

        try {
            $response = $this->_request(
                [
                    'uri' => $uri,
                    'type' => 'get',
                    'data' => []
                ]
            );
        } catch ( \Exception $e ) {
            throw new InvalidException($e->getMessage());
        }

        return $response->getBody()->getContents();
    }


    /**
     * Gets all releases for a given album
     *
     * @param string|null $albumId
     * @return array|object|string
     * @throws InvalidException
     */

    public function getRelease($albumId = null)
    {
        $uri = 'release';
        if ($albumId == null) throw new InvalidException();
        try {
            $response = $this->_request(
                [
                    'uri' => $uri,
                    'type' => 'get',
                    'data' => [
                        'id' => $albumId
                    ]
                ]
            );
        } catch ( \Exception $e ) {
            throw new InvalidException($e->getMessage());
        }

        return $response->getBody()->getContents();
    }


    /**
     * Download a release - use getRelease to get listings.
     *
     * @param string $title - Release title
     * @param string $downloadUrl - URL to download
     * @param string $protocol - 'Usenet' or 'Torrent'
     * @param string $publishDate - Valid ISO8601 date string
     * @return array|object|string
     * @throws InvalidException
     */

    public function postRelease(string $title, string $downloadUrl, string $protocol = 'Torrent', string $publishDate)
    {
        $uri = 'release';
        if (!$this->validateDate($publishDate)) throw new InvalidException("Invalid date.");

        try {
            $response = $this->_request(
                [
                    'uri' => $uri,
                    'type' => 'post',
                    'data' => [
                        'title' => $title,
                        'downloadUrl' => $downloadUrl,
                        'protocol' => $protocol,
                        'publishDate' => $publishDate
                    ]
                ]
            );
        } catch ( \Exception $e ) {
            throw new InvalidException($e->getMessage());
        }

        return $response->getBody()->getContents();
    }


    /**
     * Gets root folder
     *
     * @return array|object|string
     */
    public function getRootFolder()
    {
        $uri = 'rootfolder';
         $response = $this->_request(
                [
                    'uri' => $uri,
                    'type' => 'get',
                    'data' => []
                ]
            );
        return $response->getBody()->getContents();
    }


    /**
	 * Returns all artists, or a single artist if ID or title is specified
	 *
	 * @param null|string $id
	 * @return array|object|string
     * @internal param $artistId
	 */

    public function getArtist($id = null)
    {
	    $uri = ($id) ? "artist/$id" : 'artist';


        $response = $this->_request(
            [
                'uri' => $uri,
                'type' => 'get'
            ]
        );

        return $response->getBody()->getContents();
    }


    /**
     * Delete the artist with the given ID
     *
     * @param $id - Artist ID to remove
     * @param bool $deleteFiles - Optional, if true the artist folder and all files will be deleted.
     * @return string
     * @throws InvalidException
     */
    public function deleteArtist($id,$deleteFiles=false)
    {
        $uri = 'artist';

        try {
            $response = $this->_request(
                [
                    'uri' => $uri . '/' . $id,
                    'type' => 'delete',
                    'data' => [
                        'deleteFiles' => $deleteFiles
                    ]
                ]
            );
        } catch ( \Exception $e ) {
            throw new InvalidException($e->getMessage());
        }

        return $response->getBody()->getContents();
    }




	/**
	 * Adds a new artist to your collection
	 *
	 * NOTE: if you do not add the required params, then the artist wont function.
	 * Some of these without the others can indeed make a "artist". But it wont function properly in Lidarr.
	 *
	 * Required: All the stuff in an artist object
     * QualityProfileId in data array CANNOT be 0
	 * See GET output for format
	 *
	 * path (string) - full path to the artist on disk or rootFolderPath (string)
	 * Full path will be created by combining the rootFolderPath with the artist title
	 *	 *
	 * @param array $data
	 *
	 * @return array|object|string
	 */
    public function postArtist(array $data)
    {
        $uri = 'artist';

        $response = $this->_request(
            [
            'uri' => $uri,
            'type' => 'post',
            'data' => $data
            ]
	    );

	    return $response->getBody()->getContents();
    }

    /**
     * Update the given artist, currently only monitored is changed, all other modifications are ignored.
     *
     * Required: All parameters (you should perform a GET/{id} and submit the full body with the changes
     * and submit the full body with the changes, as other values may be editable in the future.
     *
     * @param array $data
     * @return string
     */
    public function updateArtist(array $data)
    {
        $uri = 'artist';


            $response = $this->_request(
                [
                    'uri' => $uri,
                    'type' => 'put',
                    'data' => $data
                ]
            );

        return $response->getBody()->getContents();
    }




    /**
     * Searches for new artists on Lidarr API
     *
     * @param string $term - Either the Artist's Name, or 'lidarr:artistId'
     * @return string
     */
    public function getArtistLookup($term)
    {
        $uri = 'artist/lookup';
        $term = preg_match("/lidarr/",$term) ? $term : urlencode($term);
        $uriData = [
            'term' => $term
        ];

        $response = [
            'uri' => $uri,
            'type' => 'get',
            'data' => $uriData
        ];

        return $this->processRequest($response);
    }


    /**
     * Returns all albums, or a single album if ID is specified
     * If a MusicBrainz ID is given, a search will be performed
     *
     * @param null|string $id
     * @param null $foreignAlbumId
     * @return array|object|string
     * @internal param $albumId
     */

    public function getAlbum($id = null, $foreignAlbumId = null)
    {
        $uri = ($id) ? "album/$id" : 'album';
        $data = $foreignAlbumId == null ? [] : ['foreignAlbumId'=>$foreignAlbumId];

        $response = $this->_request(
            [
                'uri' => $uri,
                'type' => 'get',
                'data' => $data
            ]
        );

        return $response->getBody()->getContents();
    }


    /**
     * Delete the album with the given ID
     *
     * @param $id - Album ID to remove
     * @param bool $deleteFiles - Optional, if true the album folder and all files will be deleted.
     * @return string
     * @throws InvalidException
     */
    public function deleteAlbum($id,$deleteFiles=false)
    {
        $uri = 'album';

        try {
            $response = $this->_request(
                [
                    'uri' => $uri . '/' . $id,
                    'type' => 'delete',
                    'data' => [
                        'deleteFiles' => $deleteFiles
                    ]
                ]
            );
        } catch ( \Exception $e ) {
            throw new InvalidException($e->getMessage());
        }

        return $response->getBody()->getContents();
    }




    /**
     * Adds a new album to your collection
     *
     * NOTE: if you do not add the required params, then the album wont function.
     * Some of these without the others can indeed make a "album". But it wont function properly in Lidarr.
     *
     * Required: All the stuff in an album object
     * See GET output for format
     *
     * path (string) - full path to the album on disk or rootFolderPath (string)
     * Full path will be created by combining the rootFolderPath with the album title
     *	 *
     * @param array $data
     *
     * @return array|object|string
     */
    public function postAlbum(array $data)
    {
        $uri = 'album';

        try {
            $response = $this->_request(
                [
                    'uri' => $uri,
                    'type' => 'post',
                    'data' => $data
                ]
            );
        } catch ( \Exception $e ) {
            return $e->getMessage();
        }

        return $response->getBody()->getContents();
    }

    /**
     * Update the given album, currently only monitored is changed, all other modifications are ignored.
     *
     * Required: All parameters (you should perform a GET/{id} and submit the full body with the changes
     * and submit the full body with the changes, as other values may be editable in the future.
     *
     * @param $id - The album ID to update
     * @param array $data
     * @return string
     */
    public function updateAlbum($id, array $data)
    {
        $uri = "album/$id";

        $response = $this->_request(
            [
                'uri' => $uri,
                'type' => 'put',
                'data' => $data
            ]
        );

        return $response->getBody()->getContents();
    }




    /**
     * Searches for new albums on Lidarr API
     *
     * @param string $term - Either the Album's Name, or 'lidarr:albumId'
     * @return string
     */
    public function getAlbumLookup($term)
    {
        $uri = 'Album/lookup';
        $term = preg_match("/lidarr/",$term) ? $term : urlencode($term);
        $uriData = [
            'term' => $term
        ];

        $response = [
            'uri' => $uri,
            'type' => 'get',
            'data' => $uriData
        ];

        return $this->processRequest($response);
    }


    /**
     * Gets all track for an artist or album
     *
     * @param string $id - The Album or artistID to search by
     * @param string $type - Either 'artistId' or 'albumId'
     * @return string
     */
    public function getTracks($id,$type)
    {
        $uri = 'track';
        $uriData = [
            "$type" => $id
        ];

        $response = [
            'uri' => $uri,
            'type' => 'get',
            'data' => $uriData
        ];

        return $this->processRequest($response);
    }


    /**
     * Gets a track by id
     *
     * @param string $id - The track ID
     * @return string
     */
    public function getTrack($id)
    {
        $uri = "track/$id";

        $response = [
            'uri' => $uri,
            'type' => 'get',
            'data'=> []
        ];

        return $this->processRequest($response);
    }


    /**
     * Returns all track files for the given artist or album
     *
     * @param string $id - The Album or artistID to search by
     * @param string $type - Either 'artistId' or 'albumId'
     * @return string
     */
    public function getTrackFiles($id,$type)
    {
        $uri = 'track';
        $uriData = [
            "$type" => $id
        ];

        $response = [
            'uri' => $uri,
            'type' => 'get',
            'data' => $uriData
        ];

        return $this->processRequest($response);
    }


    /**
     * Returns the track file with a given id
     *
     * @param string $id - The track ID
     * @return string
     */
    public function getTrackFile($id)
    {
        $uri = "track/$id";

        $response = [
            'uri' => $uri,
            'type' => 'get',
            'data'=> []
        ];

        return $this->processRequest($response);
    }

    /**
     * Delete the given track file
     *
     * @param string $id - The track ID
     * @return string
     */
    public function deleteTrackFile($id)
    {
        $uri = "track/$id";

        $response = [
            'uri' => $uri,
            'type' => 'delete',
            'data'=> []
        ];

        return $this->processRequest($response);
    }




    /**
     * Get System Status
     *
     * @return string
     * @throws InvalidException
     */
    public function getSystemStatus()
    {
        $uri = 'system/status';

        try {
            $response = $this->_request(
                [
                    'uri' => $uri,
                    'type' => 'get',
                    'data' => []
                ]


            );
        } catch ( \Exception $e ) {
            throw new InvalidException($e->getMessage());
        }

        return $response->getBody()->getContents();
    }

    /**
     * Returns the list of available backups
     *
     * @return string
     * @throws InvalidException
     */
    public function getSystemBackup()
    {
        $uri = 'system/backup';

        try {
            $response = $this->_request(
                [
                    'uri' => $uri,
                    'type' => 'get',
                    'data' => []
                ]


            );
        } catch ( \Exception $e ) {
            throw new InvalidException($e->getMessage());
        }

        return $response->getBody()->getContents();
    }


    /**
     * Returns the list of available backups
     *
     * @param string $id - The ID of the backup to restore (use getBackup for a list of available ID's)
     * @return string
     * @throws InvalidException
     */
    public function postSystemBackup(string $id)
    {
        $uri = 'system/backup';

        try {
            $response = $this->_request(
                [
                    'uri' => $uri,
                    'type' => 'post',
                    'data' => ['id' => $id]
                ]


            );
        } catch ( \Exception $e ) {
            throw new InvalidException($e->getMessage());
        }

        return $response->getBody()->getContents();
    }

    /**
     * Process requests with Guzzle
     *
     * @param array $params
     * @return \Psr\Http\Message\ResponseInterface
     */
    protected function _request(array $params)
    {
        $client = new Client();
        $options = [];
        $data = ["apikey" => $this->apiKey];
        $url = $this->url . '/api/v1/' . $params['uri'] . '?' . http_build_query($data);

        if ( $params['type'] == 'get' ) {
            $data = array_merge(["apikey" => $this->apiKey],$params['data'] ?? []);
            $url = $this->url . '/api/v1/' . $params['uri'] . '?' . http_build_query($data);

            write_log("Url: ".$url);
            return $client->get($url, $options);
        }

        if ( $params['type'] == 'put' ) {
            $options['json'] = $params['data'];
            return $client->put($url, $options);
        }

        if ( $params['type'] == 'post' ) {
            $options['json'] = $params['data'];
            return $client->post($url, $options);
        }

        if ( $params['type'] == 'delete' ) {
            return $client->delete($url, $options);
        }
        return json_encode(array(
            'error' => array(
                'msg' => "WTF",
                'code' => 000,
            ),
        ));
    }

	/**
	 * Process requests, catch exceptions, return json response
	 *
	 * @param array $request uri, type, data from method
	 * @return string json encoded response
	 */
	protected function processRequest(array $request)
	{
		try {
			$response = $this->_request(
				[
					'uri' => $request['uri'],
					'type' => $request['type'],
					'data' => $request['data']
				]
			);
		} catch ( \Exception $e ) {
		    write_log("Exception: ".json_encode($e),"ERROR");
			return json_encode(array(
				'error' => array(
					'msg' => $e->getMessage(),
					'code' => $e->getCode(),
				),
			));
		}
		$content = $response->getBody()->getContents();
        //write_log("Response: ".$content,"ALERT");
		return $content;
	}

	/**
     * Verify date is in proper format
     *
     * @param $date
     * @param string $format
     * @return bool
     */
    private function validateDate($date, $format = 'Y-m-d')
    {
        $d = \DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) == $date;
    }
}
