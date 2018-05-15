<?php

namespace digitalhigh\Headphones;

use GuzzleHttp\Client;

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
     * Fetch artist data. returns the artist object (see above) and album info:
     * Status, AlbumASIN, DateAdded, AlbumTitle, ArtistName, ReleaseDate, AlbumID, ArtistID, Type,
     * ArtworkURL: hosted image path. For cached image, see getAlbumArt command)
     * @param string $id - Artist ID to retrieve. Use findArtist to get ID by name.
     * @return array|object|string
     */
    public function getArtist(string $id)
    {
        $uri ="getArtist&id=$id";

        return $this->processRequest($uri);
    }


    /**
     * Fetch data from album page. Returns the album object, a description object and a tracks object.
     * Tracks contain: AlbumASIN, AlbumTitle, TrackID, Format, TrackDuration (ms), ArtistName, TrackTitle,
     * AlbumID, ArtistID, Location, TrackNumber, CleanName (stripped of punctuation /styling), BitRate
     *
     * @param string $id - The album ID to look for. Use findAlbum to get ID by name.
     * @return string
     */
    public function getAlbum(string $id)
    {
        $uri = "getAlbum&id=$id";

        return $this->processRequest($uri);
    }


    /**
     * Returns: Status, AlbumASIN, DateAdded, AlbumTitle, ArtistName, ReleaseDate, AlbumID, ArtistID, Type
     *
     * @return array|object|string
     */
    public function getUpcoming()
    {
        $uri = 'getUpcoming';

        return $this->processRequest($uri);
    }

    /**
     * Returns: Status, AlbumASIN, DateAdded, AlbumTitle, ArtistName, ReleaseDate, AlbumID, ArtistID, Type
     *
     * @return array|object|string
     */
    public function getWanted()
    {
        $uri = 'getWanted';

        return $this->processRequest($uri);
    }

    /**
     * Returns similar artists - with a higher "Count" being more likely to be similar. Returns: Count,
     * ArtistName, ArtistID
     *
     * @return array|object|string
     */
    public function getSimilar()
    {
        $uri = 'getSimilar';

        return $this->processRequest($uri);
    }


    /**
     * Returns: Status, DateAdded, Title, URL (nzb), FolderName, AlbumID, Size (bytes)
     *
     * @return array|object|string
     */
    public function getHistory()
    {
        $uri = 'getSimilar';

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
     * Perform album query on musicbrainz. Returns: title, url (artist), id (artist), albumurl, albumid, score, uniquename (artist - with disambiguation)
     *
     * @param string $name - The artist name to search
     * @param int $limit - How many results to return
     * @return string
     */
    public function findAlbum(string $name, int $limit = 0)
    {
        $name = urlencode($name);
        $uri = "findAlbum&name=$name";
        if ($limit !== 0) $uri .= "&limit=$limit";

        return $this->processRequest($uri);
    }

    /**
     * Add an artist to the db by artistid
     *
     * @param string $id
     * @return array|object|string
     */
    public function addArtist(string $id)
    {
        $uri = "addArtist&id=$id";

        return $this->processRequest($uri);
    }


    /**
     * Add an artist to the db by artistid
     *
     * @param string $id
     * @return array|object|string
     */
    public function addAlbum(string $id)
    {
        $uri = "addAlbum&id=$id";

        return $this->processRequest($uri);
    }


    /**
     * Delete artist from db by artistid
     *
     * @param $id - artistid of the artist to remove
     * @return string
     */
    public function delArtist($id)
    {
        $uri = "delArtist&id=$id";

        return $this->processRequest($uri);
    }


    /**
     * Pause an artist in db
     *
     * @param $id - artistid of the artist to pause
     * @return string
     */
    public function pauseArtist($id)
    {
        $uri = "pauseArtist&id=$id";

        

        return $this->processRequest($uri);
    }

    /**
     * Refresh info for artist in db from musicbrainz
     *
     * @param $id - artistid of the artist to refresh
     * @return string
     */
    public function refreshArtist($id)
    {
        $uri = "refreshArtist&id=$id";

        

        return $this->processRequest($uri);
    }

    /**
     * Resume an artist in db
     *
     * @param $id - artistid of the artist to remove
     * @return string
     */
    public function resumeArtist($id)
    {
        $uri = "resumeArtist&id=$id";

        

        return $this->processRequest($uri);
    }


    /**
     * Mark an album as wanted and start the searcher.
     * Optional paramters: 'new' looks for new versions, 'lossless' looks only for lossless versions
     *
     * @param string $id - album ID of the album to queue
     * @param bool $new - Optional. Look for new versions. Defaults to true.
     * @param bool $lossless - Optional. Looks only for lossless versions. Defaults to true.
     * @return string
     */
    public function queueAlbum(string $id, bool $new = true, bool $lossless = true)
    {
        $uri = "refreshArtist&id=$id&new=$new&lossless=$lossless";

        

        return $this->processRequest($uri);
    }

    /**
     * Unmark album as wanted / i.e. mark as skipped
     *
     * @param $id - artistid of the artist to pause
     * @return string
     */
    public function unqueueAlbum($id)
    {
        $uri = "unqueueAlbum&id=$id";

        

        return $this->processRequest($uri);
    }

    /**
     * force search for wanted albums - not launched in a separate thread so it may take a bit to complete
     *
     * @return string
     */
    public function forceSearch()
    {
        $uri = "forceSearch";

        

        return $this->processRequest($uri);
    }


    /**
     * Force post process albums in download directory - also not launched in a separate thread
     *
     * @param string|bool $dir - Optional path to process downloads in (Defaults to ??)
     * @return string
     */
    public function forceProcess($dir = false)
    {
        $uri = "forceSearch" . ($dir ? "&dir=$dir" : "");

        

        return $this->processRequest($uri);
    }


    /**
     * force Active Artist Update - also not launched in a separate thread
     *
     * @return string
     */
    public function forceActiveArtistsUpdate()
    {
        $uri = "forceActiveArtistsUpdate";

        

        return $this->processRequest($uri);
    }


    /**
     * Returns some version information: git_path, install_type, current_version, installed_version, commits_behind
     *
     * @return string
     */
    public function getVersion()
    {
        $uri = "getVersion";

        

        return $this->processRequest($uri);
    }

    /**
     * Updates the version information above and returns getVersion data
     *
     * @return string
     */
    public function checkGithub()
    {
        $uri = "CheckGithub";

        

        return $this->processRequest($uri);
    }

    /**
     * Shut down headphones
     *
     * @return string
     */
    public function shutdown()
    {
        $uri = "shutdown";

        

        return $this->processRequest($uri);
    }


    /**
     * Restart headphones
     *
     * @return string
     */
    public function restart()
    {
        $uri = "restart";

        

        return $this->processRequest($uri);
    }


    /**
     * Update headphones - you may want to check the install type in get version and not allow this if type==exe
     *
     * @return string
     */
    public function update()
    {
        $uri = "update";

        

        return $this->processRequest($uri);
    }

    /**
     * Returns either a relative path to the cached image, or a remote url if the image can't be saved to the cache dir
     *
     * @param $id - Id to fetch. Use getartist/getalbum to retrieve the appropriate ID
     * @return string
     */
    public function getArtistArt($id)
    {
        $uri = "getArtistArt&id=$id";

        

        return $this->processRequest($uri);
    }


    /**
     * Returns either a relative path to the cached image, or a remote url if the image can't be saved to the cache dir
     *
     * @param $id - Id to fetch. Use getartist/getalbum to retrieve the appropriate ID
     * @return string
     */
    public function getAlbumArt($id)
    {
        $uri = "getAlbumArt&id=$id";

        

        return $this->processRequest($uri);
    }


    /**
     * Returns Summary and Content, both formatted in html.
     *
     * @param $id - Id to fetch. Use getartist/getalbum to retrieve the appropriate ID
     * @return string
     */
    public function getArtistInfo($id)
    {
        $uri = "getArtistInfo&id=$id";

        

        return $this->processRequest($uri);
    }


    /**
     * Returns Summary and Content, both formatted in html.
     *
     * @param $id - Id to fetch. Use getartist/getalbum to retrieve the appropriate ID
     * @return string
     */
    public function getAlbumInfo($id)
    {
        $uri = "getAlbumInfo&id=$id";

        return $this->processRequest($uri);
    }


    /**
     * Returns either a relative path to the cached thumbnail artist image,
     * or an http:// address if the cache dir can't be written to.
     *
     * @param $id - Id to fetch. Use getartist/getalbum to retrieve the appropriate ID
     * @return string
     */
    public function getArtistThumb($id)
    {
        $uri = "getArtistThumb&id=$id";

        return $this->processRequest($uri);
    }


    /**
     * Returns either a relative path to the cached thumbnail artist image,
     * or an http:// address if the cache dir can't be written to.
     *
     * @param $id - Id to fetch. Use getartist/getalbum to retrieve the appropriate ID
     * @return string
     */
    public function getAlbumThumb($id)
    {
        $uri = "getAlbumThumb&id=$id";

        return $this->processRequest($uri);
    }

    /**
     * Gives you a list of results from searcher.searchforalbum().
     * Basically runs a normal search, but rather than sorting them and downloading the best result,
     * it dumps the data, which you can then pass on to download_specific_release().
     *
     * Returns a list of dictionaries with params: title, size, url, provider & kind -
     * all of these values must be passed back to download_specific_release.
     *
     * @param $id - Album Id to fetch. Use getalbum to retrieve the appropriate ID.
     * @return string
     */
    public function choose_specific_download($id)
    {
        $uri = "choose_specific_download&id=$id";

        return $this->processRequest($uri);
    }


    /**
     * Allows you to manually pass a choose_specific_download release back to searcher.send_to_downloader()
     *
     * @param $id - Album Id to fetch. Use getalbum to retrieve the appropriate ID.
     * @param $title - Get this from choose_specific_download
     * @param $size - Get this from choose_specific_download
     * @param $url - Get this from choose_specific_download
     * @param $provider - Get this from choose_specific_download
     * @param $kind - Get this from choose_specific_download
     * @return string
     */
    public function download_specific_release($id, $title, $size, $url, $provider, $kind)
    {
        $uri = "download_specific_release&id=$id&title=$title&size=$size&url=$url&provider=$provider&kind=$kind";
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
        return $client->get($url);

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
