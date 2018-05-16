<?php

namespace digitalhigh\Radarr;

use GuzzleHttp\Client;
use digitalhigh\Radarr\Exceptions\InvalidException;

class Radarr
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
     * Gets upcoming movies, if start/end are not supplied movies airing today and tomorrow will be returned
     * When supplying start and/or end date you must supply date in format yyyy-mm-dd
     * Example: $radarr->getCalendar('2015-01-25', '2016-01-15');
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
	 * Returns all movies, or a single movie if ID or title is specified
	 *
	 * @param null|string $id
	 * @param null|string $title
	 * @return array|object|string
	 * @throws InvalidException
	 * @internal param $movieId
	 */

    public function getMovies($id = null)
    {
	    $uri = ($id) ? 'movie/' . $id : 'movie';

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
	public function getMoviesLookup($searchTerm)
	{
		$uri = 'movie/lookup';
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
	 * Adds a new movie to your collection
	 *
	 * NOTE: if you do not add the required params, then the movie wont function.
	 * Some of these without the others can indeed make a "movie". But it wont function properly in Radarr.
	 *
	 * Required: tmdbId (int) title (string) qualityProfileId (int) titleSlug (string) seasons (array)
	 * See GET output for format
	 *
	 * path (string) - full path to the movie on disk or rootFolderPath (string)
	 * Full path will be created by combining the rootFolderPath with the movie title
	 *
	 * Optional: tvRageId (int) seasonFolder (bool) monitored (bool)
	 *
	 * @param array $data
	 * @param bool|true $onlyFutureMovies It can be used to control which episodes Radarr monitors
	 * after adding the movie, setting to true (default) will only monitor future episodes.
	 *
	 * @return array|object|string
	 */
    public function postMovie(array $data)
    {
        $uri = 'movie';

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
     * Update the given movies, currently only monitored is changed, all other modifications are ignored.
     *
     * Required: All parameters (you should perform a GET/{id} and submit the full body with the changes
     * and submit the full body with the changes, as other values may be editable in the future.
     *
     * @param array $data
     * @return string
     * @throws InvalidException
     */
    public function updateMovie(array $data)
    {
        $uri = 'movie';

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
	 * Delete the given movie file
	 *
	 * @param $id - TMDB ID of the movie to remove
	 * @param bool $deleteFiles - Optional, delete files along with remove from Radarr.
	 * @return string
	 * @throws InvalidException
	 */
    public function deleteMovie($id,$deleteFiles=false)
    {
        $uri = 'movie';

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
     * @param string $sortKey 'movie.title' or 'date'
     * @param string $sortDir 'asc' or 'desc'
     * @return array|object|string
     * @throws InvalidException
     */
    public function getHistory($page = 1, $pageSize = 10, $sortKey = 'movie.title', $sortDir = 'asc')
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
	 * Returns the banner for the specified movie
	 *
	 * @param $movieId
	 * @return string
	 * @throws InvalidException
	 */
	public function getBanner($movieId) {
	    $uri = 'MediaCover/'.$movieId.'/banner.jpg';
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
     * @return \Psr\Http\Message\ResponseInterface
     */
    protected function _request(array $params)
    {
        $client = new Client();
        $options = [
            'headers' => [
                'X-Api-Key' => $this->apiKey    
            ]    
        ];
        
        if ( $this->httpAuthUsername && $this->httpAuthPassword ) {
            $options['auth'] = [
                $this->httpAuthUsername,
                $this->httpAuthPassword
            ];
        }

        if ( $params['type'] == 'get' ) {
            $url = $this->url . '/api/' . $params['uri'] . '?' . http_build_query($params['data']);

            return $client->get($url, $options);
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

            return $client->delete($url, $options);
        }
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

			exit();
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
