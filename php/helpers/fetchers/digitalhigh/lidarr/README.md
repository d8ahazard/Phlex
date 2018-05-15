# Lidarr
PHP Wrapper for Lidarr https://lidarr.video/

Here is the Lidarr API Documentation that this package implements: https://github.com/Lidarr/Lidarr/wiki/API

## Installation
```ruby
composer require digitalhigh/lidarr
```

## Example Usage
```php
use digitalhigh\Lidarr\Lidarr;
```
```php
public function addMovie()
{
    $lidarr = new Lidarr('http://127.0.0.1:8989', 'cf7544f71b6c4efcbb84b49011fc965c'); // URL and API Key
    
    return $lidarr->postMovie([
        'tmdbId' => 121856,
        'title' => 'Assassin's Creed',
        'qualityProfileId' => 3, // HD-720p
        'rootFolderPath' => '/volume1/Plex/Movies'
    ]);
}
```
### HTTP Auth
If your site requires HTTP Auth username and password you may supply it like this. Please note, if you are using HTTP Auth without SSL you are sending your username and password unprotected across the internet.
```php
$lidarr = new Lidarr('http://127.0.0.1:8989', 'cf7544f71b6c4efcbb84b49011fc965c', 'my-username', 'my-password');
```

### Output
```json
{
  "title": "Assassin's Creed",
  "sortTitle": "assassins creed",
  "sizeOnDisk": 0,
  "status": "released",
  "overview": "Lynch discovers he is a descendant of the secret Assassins society through unlocked genetic memories that allow him to relive the adventures of his ancestor, Aguilar, in 15th Century Spain. After gaining incredible knowledge and skills he’s poised to take on the oppressive Knights Templar in the present day.",
  "inCinemas": "2016-12-21T00:00:00Z",
  "images": [
    {
      "coverType": "poster",
      "url": "/lidarr/MediaCover/1/poster.jpg?lastWrite=636200219330000000"
    },
    {
      "coverType": "banner",
      "url": "/lidarr/MediaCover/1/banner.jpg?lastWrite=636200219340000000"
    }
  ],
  "website": "https://www.ubisoft.com/en-US/",
  "downloaded": false,
  "year": 2016,
  "hasFile": false,
  "youTubeTrailerId": "pgALJgMjXN4",
  "studio": "20th Century Fox",
  "path": "/path/to/Assassin's Creed (2016)",
  "profileId": 6,
  "monitored": true,
  "minimumAvailability": "preDb",
  "runtime": 115,
  "lastInfoSync": "2017-01-23T22:05:32.365337Z",
  "cleanTitle": "assassinscreed",
  "imdbId": "tt2094766",
  "tmdbId": 121856,
  "titleSlug": "assassins-creed-121856",
  "genres": [
    "Action",
    "Adventure",
    "Fantasy",
    "Science Fiction"
  ],
  "tags": [],
  "added": "2017-01-14T20:18:52.938244Z",
  "ratings": {
    "votes": 711,
    "value": 5.2
  },
  "alternativeTitles": [
    "Assassin's Creed: The IMAX Experience"
  ],
  "qualityProfileId": 6,
  "id": 1
}
```

For available methods reference included [Lidarr::class](src/Lidarr.php)

Note: when posting data with key => value pairs, keys are case-sensitive.
