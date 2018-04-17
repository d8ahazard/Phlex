<?php

namespace digitalhigh\Headphones;

use GuzzleHttp\Client;
use digitalhigh\Radarr\Exceptions\InvalidException;

class Headphones
{
    protected $url;
    protected $apiKey;

    public function __construct($url, $apiKey)
    {
        $this->url = rtrim($url, '/\\'); // Example: http://127.0.0.1:8989 (no trailing forward-backward slashes)
        $this->apiKey = $apiKey;
    }

    /**
     * Gets upcoming artist, if start/end are not supplied artist airing today and tomorrow will be returned
     * When supplying start and/or end date you must supply date in format yyyy-mm-dd
     * Example: $radarr->getCalendar('2015-01-25', '2016-01-15');
     * 'start' and 'end' not required. You may supply, one or both.
     *
     * @return array|object|string
     * @throws InvalidException
     */
    public function getIndex()
    {
        try {
            $response = $this->_request(
                [
                    'uri' => 'getIndex',
                    'type' => 'get'
                ]
            );
        } catch ( \Exception $e ) {
            throw new InvalidException($e->getMessage());
        }

        return $response->getBody()->getContents();
    }

    /**
     * Queries the status of a previously started command, or all currently started commands.
     * Fetch artist data. returns the artist object (see above) and album info:
     * Status, AlbumASIN, DateAdded, AlbumTitle, ArtistName, ReleaseDate, AlbumID, ArtistID,
     * Type, ArtworkURL: hosted image path. For cached image, see getAlbumArt command)
     * @param $artistid string ID to retrieve
     * @return array|object|string
     * @throws InvalidException
     */
    public function getArtist($artistid)
    {

        try {
            $response = $this->_request(
                [
                    'uri' => 'getArtist',
                    'type' => 'get',
                    'data' => $artistid
                ]
            );
        } catch ( \Exception $e ) {
            throw new InvalidException($e->getMessage());
        }

        return $response->getBody()->getContents();
    }

    /**
     * Publish a new command for Radarr to run.
     * These commands are executed asynchronously; use GET to retrieve the current status.
     *
     * Commands and their parameters can be found here:
     * https://github.com/Radarr/Radarr/wiki
     *
     * @param $name
     * @param array|null $params
     * @return string
     * @throws InvalidException
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

        try {
            $response = $this->_request(
                [
                    'uri' => $uri,
                    'type' => 'post',
                    'data' => $uriData
                ]
            );
        } catch ( \Exception $e ) {
            throw new InvalidException($e->getMessage());
        }

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
	 * Returns all artist, or a single artist if ID or title is specified
	 *
	 * @param null|string $id
	 * @param null|string $title
	 * @return array|object|string
	 * @throws InvalidException
	 * @internal param $artistId
	 */

    public function getartist2($id = null)
    {
	    $uri = ($id) ? 'artist/' . $id : 'artist';

        try {
            $response = $this->_request(
                [
                    'uri' => $uri,
                    'type' => 'get'
                ]
            );
        } catch ( \Exception $e ) {
            throw new InvalidException($e->getMessage());
        }

        return $response->getBody()->getContents();
    }

	/**
	 * Searches for new shows on trakt
	 * Search by name or tvdbid
	 * Example: 'The Blacklist' or 'tvdb:266189'
	 *
	 * @param string $searchTerm query string for the search (Use tvdb:12345 to lookup TVDB ID 12345)
	 * @return string
	 */
	public function getartistLookup($searchTerm)
	{
		$uri = 'artist/lookup';
		$uriData = [
			'term' => $searchTerm
		];

		$response = [
			'uri' => $uri,
			'type' => 'get',
			'data' => $uriData
		];

		return $this->processRequest($response);
	}

	/**
	 * Adds a new artist to your collection
	 *
	 * NOTE: if you do not add the required params, then the artist wont function.
	 * Some of these without the others can indeed make a "artist". But it wont function properly in Radarr.
	 *
	 * Required: tmdbId (int) title (string) qualityProfileId (int) titleSlug (string) seasons (array)
	 * See GET output for format
	 *
	 * path (string) - full path to the artist on disk or rootFolderPath (string)
	 * Full path will be created by combining the rootFolderPath with the artist title
	 *
	 * Optional: tvRageId (int) seasonFolder (bool) monitored (bool)
	 *
	 * @param array $data
	 * @param bool|true $onlyFutureartist It can be used to control which episodes Radarr monitors
	 * after adding the artist, setting to true (default) will only monitor future episodes.
	 *
	 * @return array|object|string
	 */
    public function postartist(array $data)
    {
        $uri = 'artist';

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
     * Update the given artist, currently only monitored is changed, all other modifications are ignored.
     *
     * Required: All parameters (you should perform a GET/{id} and submit the full body with the changes
     * and submit the full body with the changes, as other values may be editable in the future.
     *
     * @param array $data
     * @return string
     * @throws InvalidException
     */
    public function updateartist(array $data)
    {
        $uri = 'artist';

        try {
            $response = $this->_request(
                [
                    'uri' => $uri,
                    'type' => 'put',
                    'data' => $data
                ]
            );
        } catch ( \Exception $e ) {
            throw new InvalidException($e->getMessage());
        }

        return $response->getBody()->getContents();
    }

	/**
	 * Delete the given artist file
	 *
	 * @param $id - TMDB ID of the artist to remove
	 * @param bool $deleteFiles - Optional, delete files along with remove from Radarr.
	 * @return string
	 * @throws InvalidException
	 */
    public function deleteartist($id,$deleteFiles=false)
    {
        $uri = 'artist';

        try {
            $response = $this->_request(
                [
                    'uri' => $uri . '/' . $id,
                    'type' => 'delete',
                    'deleteFiles' => $deleteFiles
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
     * @param string $sortKey 'artist.title' or 'date'
     * @param string $sortDir 'asc' or 'desc'
     * @return array|object|string
     * @throws InvalidException
     */
    public function getHistory($page = 1, $pageSize = 10, $sortKey = 'artist.title', $sortDir = 'asc')
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
     * Gets all quality profiles
     *
     * @return array|object|string
     * @throws InvalidException
     */
    public function getProfiles()
    {
        $uri = 'profile';

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
	 *
	 * Returns the banner for the specified artist
	 *
	 * @param $artistId
	 * @return string
	 * @throws InvalidException
	 */
	public function getBanner($artistId) {
	    $uri = 'MediaCover/'.$artistId.'/banner.jpg';
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
     * Gets root folder
     *
     * @return array|object|string
     * @throws InvalidException
     */
    public function getRootFolder()
    {
        $uri = 'rootfolder';

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
     * Process requests with Guzzle
     *
     * @param array $params
     * @return bool|\Psr\Http\Message\ResponseInterface
     */
    protected function _request(array $params)
    {
        $client = new Client();

        if ( $params['type'] == 'get' ) {
            $url = $this->url . '/api/?apiKey=' . $this->apiKey . "&cmd=" . $params['uri'];

            return $client->get($url);
        }

        if ( $params['type'] == 'put' ) {
            $url = $this->url . '/api/' . $params['uri'];
            $options['json'] = $params['data'];
            
            return $client->put($url, $options);
        }

        if ( $params['type'] == 'post' ) {
            $url = $this->url . '/api/' . $params['uri'];
            $options['json'] = $params['data'];
            
            return $client->post($url, $options);
        }

        if ( $params['type'] == 'delete' ) {
            $url = $this->url . '/api/' . $params['uri'] . '?' . http_build_query($params['data']);

            return $client->delete($url);
        }
        return false;
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
			return json_encode(array(
				'error' => array(
					'msg' => $e->getMessage(),
					'code' => $e->getCode(),
				),
			));
		}

		return $response->getBody()->getContents();
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
