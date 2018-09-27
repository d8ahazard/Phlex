<?php

namespace digitalhigh\Headphones;

use GuzzleHttp\Client;

class Ombi
{
    protected $url;
    protected $apiKey;

    public function __construct($url, $apiKey)
    {
        $this->url = rtrim($url, '/\\'); // Example: http://127.0.0.1:8989 (no trailing forward-backward slashes)
        $this->apiKey = $apiKey;
    }

    /**
     * Fetch data from index page. Returns: ArtistName, ArtistSortName, ArtistID, Status, DateAdded,
     * [LatestAlbum, ReleaseDate, AlbumID], HaveTracks, TotalTracks, IncludeExtras, LastUpdated,
     * [ArtworkURL, ThumbURL]: a remote url to the artwork/thumbnail.
     * To get the cached image path, see getArtistArt command.
     * ThumbURL is added/updated when an artist is added/updated.
     * If your using the database method to get the artwork,
     * it's more reliable to use the ThumbURL than the ArtworkURL
     *
     * @return array|object|string
     */
    public function getIndex()
    {
        $uri ='getIndex';

        return $this->processRequest($uri);
    }

    /**
     * Perform artist query on musicbrainz. Returns: url, score, name, uniquename (contains disambiguation info), id)
     *     *
     * @param string $name - The artist name to search
     * @param int $limit - How many results to return
     * @return string
     */
    public function findArtist(string $name, int $limit = 0)
    {
        $name = urlencode($name);
        $uri = "findArtist&name=$name";
        if ($limit !== 0) $uri .= "&limit=$limit";

        return $this->processRequest($uri);
    }

    /**
     * Process requests with Guzzle
     *
     * @param string $uri
     * @return bool|\Psr\Http\Message\ResponseInterface
     */
    protected function _request(string $uri)
    {
        $client = new Client();
        $key = $this->apiKey;
        $url = $this->url . "/api/?apikey=$key&cmd=$uri";
        write_log("URL is $url");
        $headers = ['headers' => ['Authorization' => 'Bearer ' . $this->apiKey]];
        return $client->get($url,$headers);
    }

    /**
     * Process requests, catch exceptions, return json response
     *
     * @param string $uri
     * @return string json encoded response
     */
    protected function processRequest(string $uri)
    {
        try {
            $response = $this->_request($uri);
        } catch (\Exception $e) {
            return json_encode(array(
                'error' => array(
                    'msg' => $e->getMessage(),
                    'code' => $e->getCode(),
                ),
            ));
        }
        return $response->getBody()->getContents();
    }
}
