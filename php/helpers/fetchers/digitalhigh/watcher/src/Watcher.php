<?php

namespace digitalhigh\Watcher;

use GuzzleHttp\Client;

class Watcher
{
    protected $url;
    protected $apiKey;
    protected $httpAuthUsername;
    protected $httpAuthPassword;

    public function __construct($url, $apiKey)
    {
        $this->url = rtrim($url, '/\\'); // Example: http://127.0.0.1:8989 (no trailing forward-backward slashes)
        $this->apiKey = $apiKey;
    }


    /**
     * addmovie
     *
     * Adds a movie to the user's library
     * Accepts imdb and tmdb id #s
     * Imdb id must include 'tt'
     * Will add using Default quality profile unless specified otherwise
     *
     * @param string $id - Either the IMDB or TMDB ID of the movie to add
     * @param string $source - Either "imdb" or "tmdb"
     * @param string $quality - Optional
     * @return array|bool An associative array of info, or false if error.
     */
    public function addMovie(string $id = "", string $source = "tmdb", $quality='Default')
    {
        if (!$id) return false;
        $params = [strtolower($source)."id"=>$id, "quality"=>$quality];
        $response = $this->processRequest('addmovie',$params);
        return $response;
    }

    /**
     * getconfig
     *
     * Returns a dump of the user's config
     *
     * @param string $item
     * @return array|bool An associative array of info, or false if error.
     */
    public function getConfig($item = "")
    {
        $response = $this->processRequest('getconfig');
        $response = $response['config'] ?? $response;
        if ($item && is_array($response)) $response = $response["$item"] ?? $response;
        return $response;
    }

    /**
     * liststatus
     *
     * Effectively a database dump of the MOVIES table
     * Additional params (besides mode and apikey) will be applied as filters to the movie database
     * For example, passing &imdbid=tt1234557 will only return movies with the imdbid tt1234567.
     * Multiple filters can be applied using the columns described in core.sql/sqldb.py
     *   https://github.com/nosmokingbandit/Watcher3/blob/master/core/sqldb.py
     *
     * @param array $filters
     *   An Associative array of key/value filters to search for.
     *   Examples are imdbid, title, added_date, year, score, tmdbid
     *   Need to look at DB for full list of possible filters.
     * @return array|bool An associative array of info, or false if error.
     */
    public function listStatus(array $filters = [])
    {
        $response = $this->processRequest('liststatus',$filters);
        return $response;
    }

    #TODO: Verify which ID can be passed for removal
    /**
     * removemovie
     *
     * Removes movie from user's library
     * Does not remove movie files, only removes entry from Watcher
     *
     * @param string $id - Either the IMDB or TMDB ID of the movie to add
     * @param string $source - Either "imdb" or "tmdb"
     *
     * @return array|bool An associative array of info, or false if error.
     */
    public function removeMovie(string $id = "",string $source = "")
    {
        $params = [strtolower($source)=>$id];
        $response = $this->processRequest('removemovie',$params);
        return $response;
    }

    /**
     * server_restart
     *
     * Gracefully restart Watcher server and child processes.
     * Restart may be instant or delayed to wait for threaded tasks to finish.
     * Returns confirmation that request was received.
     *
     * @return array|bool An associative array of info, or false if error.
     */
    public function serverRestart()
    {
        $response = $this->processRequest('restart');
        return $response;
    }

    /**
     * server_shutdown
     *
     * Gracefully terminate Watcher server and child processes.
     * Shutdown may be instant or delayed to wait for threaded tasks to finish.
     * Returns confirmation that request was received.
     *
     * @return array|bool An associative array of info, or false if error.
     */
    public function serverShutdown()
    {
        $response = $this->processRequest('shutdown');
        return $response;
    }

    /**
     * version
     *
     * Returns API version and current git hash of Watcher
     *
     * @return array|bool An associative array of info, or false if error.
     */
    public function version()
    {
        $response = $this->processRequest('version');
        return $response;
    }


    /**
     * Send requests with Guzzle
     *
     * @param string $mode - The function to call in Watcher
     * @param array $params - An optional array of values to add to the URL
     * @return \Psr\Http\Message\ResponseInterface | bool
     */
    protected function _request(string $mode, array $params = [])
    {
        $client = new Client();
        $key = $this->apiKey;
        $url = $this->url;
        $url = "$url/api/?apikey=$key&mode=$mode";
        if (count($params)) foreach ($params as $key=>$value) {
            $url .= "&".urlencode($key) . "=" . urlencode($value);
        }
        write_log("URL is $url");
        return $client->get($url);

    }

    /**
     * Process requests, catch exceptions, return array response
     *
     * @param string $mode - The function to call in Watcher
     * @param array $params - An optional array of values to add to the URL
     * @return array | bool - associative array or false if error
     */
	protected function processRequest(string $mode, array $params = [])
	{

		try {
			$response = $this->_request($mode, $params);
		} catch ( \Exception $e ) {
			return [
				'error' => [
					'msg' => $e->getMessage(),
					'code' => $e->getCode(),
				]
            ];
		}

		$response = $response ? $response->getBody()->getContents() : $response;
		write_log("Response: ".$response);
		return json_decode($response,true);
	}
}
