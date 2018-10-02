<?php

use GuzzleHttp\Client;
use Guzzle\Common\Exception\MultiTransferException;

class Ombi {
	protected $url;
	protected $apiKey;

	public function __construct($url, $apiKey) {
		$this->url = rtrim($url, "/"); // Example: http://127.0.0.1:8989 (no trailing forward-backward slashes)
		$this->apiKey = $apiKey;
	}

	/**
	 * postCouchPotatoProfile
	 *
	 *
	 * @param bool | array $settings - (optional)
	 * {
	 *     "enabled": "bool",
	 *     "apiKey": "string",
	 *     "defaultProfileId": "string",
	 *     "username": "string",
	 *     "password": "string",
	 *     "ssl": "bool",
	 *     "subDir": "string",
	 *     "ip": "string",
	 *     "port": "int",
	 *     "id": "int"
	 * }
	 *
	 * @return string (application/json)
	 * {
	 *     "list": "array - []",
	 *     "success": "bool"
	 * }
	 */
	function postCouchPotatoProfile($settings=false) {
		$uri = "CouchPotato/profile";
		$method = "post";
		return $this->processRequest($uri, $settings, $method);
	}

	/**
	 * postCouchPotatoApikey
	 *
	 *
	 * @param bool | array $settings - (optional)
	 * {
	 *     "enabled": "bool",
	 *     "apiKey": "string",
	 *     "defaultProfileId": "string",
	 *     "username": "string",
	 *     "password": "string",
	 *     "ssl": "bool",
	 *     "subDir": "string",
	 *     "ip": "string",
	 *     "port": "int",
	 *     "id": "int"
	 * }
	 *
	 * @return string (application/json)
	 * {
	 *     "success": "bool",
	 *     "api_key": "string"
	 * }
	 */
	function postCouchPotatoApikey($settings=false) {
		$uri = "CouchPotato/apikey";
		$method = "post";
		return $this->processRequest($uri, $settings, $method);
	}

	/**
	 * postEmby
	 * Signs into the Emby Api
	 *
	 * @param bool | array $request - (optional) The request.
	 * {
	 *     "enable": "bool",
	 *     "servers": "array - []",
	 *     "id": "int"
	 * }
	 *
	 * @return string (application/json)
	 * {
	 *     "enable": "bool",
	 *     "servers": "array - []",
	 *     "id": "int"
	 * }
	 */
	function postEmby($request=false) {
		$uri = "Emby";
		$method = "post";
		return $this->processRequest($uri, $request, $method);
	}

	/**
	 * getEmbyUsers
	 * Gets the emby users.
	 *
	 *
	 * @return string (application/json)
	 */
	function getEmbyUsers() {
		$uri = "Emby/users";
		return $this->processRequest($uri);
	}

	/**
	 * getIdentityUsers
	 * Gets all users.
	 *
	 *
	 * @return string (application/json)
	 */
	function getIdentityUsers() {
		$uri = "Identity/Users";
		return $this->processRequest($uri);
	}

	/**
	 * putIdentity
	 * Updates the user.
	 *
	 * @param bool | array $ui - (optional) The user.
	 * {
	 *     "id": "string",
	 *     "userName": "string",
	 *     "alias": "string",
	 *     "claims": "array - []",
	 *     "emailAddress": "string",
	 *     "password": "string",
	 *     "lastLoggedIn": "string",
	 *     "hasLoggedIn": "bool",
	 *     "userType": "string",
	 *     "movieRequestLimit": "int",
	 *     "episodeRequestLimit": "int",
	 *     "episodeRequestQuota": null,
	 *     "movieRequestQuota": null,
	 *     "musicRequestQuota": null,
	 *     "musicRequestLimit": "int",
	 *     "userQualityProfiles": null
	 * }
	 *
	 * @return string (application/json)
	 * {
	 *     "errors": "array - []",
	 *     "successful": "bool"
	 * }
	 */
	function putIdentity($ui=false) {
		$uri = "Identity";
		$method = "put";
		return $this->processRequest($uri, $ui, $method);
	}

	/**
	 * getIdentityUser
	 * Gets the user by the user id.
	 *
	 * @param string $id - (required)
	 *
	 * @return string (application/json)
	 * {
	 *     "id": "string",
	 *     "userName": "string",
	 *     "alias": "string",
	 *     "claims": "array - []",
	 *     "emailAddress": "string",
	 *     "password": "string",
	 *     "lastLoggedIn": "string",
	 *     "hasLoggedIn": "bool",
	 *     "userType": "string",
	 *     "movieRequestLimit": "int",
	 *     "episodeRequestLimit": "int",
	 *     "episodeRequestQuota": null,
	 *     "movieRequestQuota": null,
	 *     "musicRequestQuota": null,
	 *     "musicRequestLimit": "int",
	 *     "userQualityProfiles": null
	 * }
	 */
	function getIdentityUser($id) {
		$uri = "Identity/User/$id";
		return $this->processRequest($uri);
	}

	/**
	 * putIdentityLocal
	 * This is for the local user to change their details.
	 *
	 * @param bool | array $ui - (optional)
	 * {
	 *     "currentPassword": "string",
	 *     "confirmNewPassword": "string",
	 *     "id": "string",
	 *     "userName": "string",
	 *     "alias": "string",
	 *     "claims": "array - []",
	 *     "emailAddress": "string",
	 *     "password": "string",
	 *     "lastLoggedIn": "string",
	 *     "hasLoggedIn": "bool",
	 *     "userType": "string",
	 *     "movieRequestLimit": "int",
	 *     "episodeRequestLimit": "int",
	 *     "episodeRequestQuota": null,
	 *     "movieRequestQuota": null,
	 *     "musicRequestQuota": null,
	 *     "musicRequestLimit": "int",
	 *     "userQualityProfiles": null
	 * }
	 *
	 * @return string (application/json)
	 * {
	 *     "errors": "array - []",
	 *     "successful": "bool"
	 * }
	 */
	function putIdentityLocal($ui=false) {
		$uri = "Identity/local";
		$method = "put";
		return $this->processRequest($uri, $ui, $method);
	}

	/**
	 * deleteIdentity
	 * Deletes the user.
	 *
	 * @param string $userId - (required) The user.
	 *
	 * @return string (application/json)
	 * {
	 *     "errors": "array - []",
	 *     "successful": "bool"
	 * }
	 */
	function deleteIdentity($userId) {
		$uri = "Identity/$userId";
		$method = "delete";
		return $this->processRequest($uri, $userId, $method);
	}

	/**
	 * getIdentityClaims
	 * Gets all available claims in the system.
	 *
	 *
	 * @return string (application/json)
	 */
	function getIdentityClaims() {
		$uri = "Identity/claims";
		return $this->processRequest($uri);
	}

	/**
	 * postIdentityWelcomeEmail
	 *
	 *
	 * @param bool | array $user - (optional)
	 * {
	 *     "id": "string",
	 *     "userName": "string",
	 *     "alias": "string",
	 *     "claims": "array - []",
	 *     "emailAddress": "string",
	 *     "password": "string",
	 *     "lastLoggedIn": "string",
	 *     "hasLoggedIn": "bool",
	 *     "userType": "string",
	 *     "movieRequestLimit": "int",
	 *     "episodeRequestLimit": "int",
	 *     "episodeRequestQuota": null,
	 *     "movieRequestQuota": null,
	 *     "musicRequestQuota": null,
	 *     "musicRequestLimit": "int",
	 *     "userQualityProfiles": null
	 * }
	 *
	 * @return string
	 */
	function postIdentityWelcomeEmail($user=false) {
		$uri = "Identity/welcomeEmail";
		$method = "post";
		return $this->processRequest($uri, $user, $method);
	}

	/**
	 * getIdentityNotificationpreferences
	 *
	 *
	 *
	 * @return string (application/json)
	 */
	function getIdentityNotificationpreferences() {
		$uri = "Identity/notificationpreferences";
		return $this->processRequest($uri);
	}

	/**
	 * getIdentityNotificationpreferences
	 *
	 *
	 * @param string $userId - (required)
	 *
	 * @return string (application/json)
	 */
	function getIdentityNotificationpreference($userId) {
		$uri = "Identity/notificationpreferences/$userId";
		return $this->processRequest($uri);
	}

	/**
	 * postIdentityNotificationPreferences
	 *
	 *
	 * @param bool | array $preferences - (optional)
	 *
	 * @return string
	 */
	function postIdentityNotificationPreferences($preferences=false) {
		$uri = "Identity/NotificationPreferences";
		$method = "post";
		return $this->processRequest($uri, $preferences, $method);
	}

	/**
	 * getImagesTv
	 *
	 *
	 * @param int $tvdbid - (required)
	 *
	 * @return string (application/json)
	 */
	function getImagesTv($tvdbid) {
		$uri = "Images/tv/$tvdbid";
		return $this->processRequest($uri);
	}

	/**
	 * getImagesPosterMovie
	 *
	 *
	 * @param string $movieDbId - (required)
	 *
	 * @return string (application/json)
	 */
	function getImagesPosterMovie($movieDbId) {
		$uri = "Images/poster/movie/$movieDbId";
		return $this->processRequest($uri);
	}

	/**
	 * getImagesPosterTv
	 *
	 *
	 * @param int $tvdbid - (required)
	 *
	 * @return string (application/json)
	 */
	function getImagesPosterTv($tvdbid) {
		$uri = "Images/poster/tv/$tvdbid";
		return $this->processRequest($uri);
	}

	/**
	 * getImagesBackgroundMovie
	 *
	 *
	 * @param string $movieDbId - (required)
	 *
	 * @return string (application/json)
	 */
	function getImagesBackgroundMovie($movieDbId) {
		$uri = "Images/background/movie/$movieDbId";
		return $this->processRequest($uri);
	}

	/**
	 * getImagesBackgroundTv
	 *
	 *
	 * @param int $tvdbid - (required)
	 *
	 * @return string (application/json)
	 */
	function getImagesBackgroundTv($tvdbid) {
		$uri = "Images/background/tv/$tvdbid";
		return $this->processRequest($uri);
	}

	/**
	 * getImagesBackground
	 *
	 *
	 *
	 * @return string
	 */
	function getImagesBackground() {
		$uri = "Images/background";
		return $this->processRequest($uri);
	}

	/**
	 * postIssuesCategories
	 * Creates a new category
	 *
	 * @param bool | array $cat - (optional)
	 * {
	 *     "value": "string",
	 *     "id": "int"
	 * }
	 *
	 * @return string (application/json)
	 */
	function postIssuesCategories($cat=false) {
		$uri = "Issues/categories";
		$method = "post";
		return $this->processRequest($uri, $cat, $method);
	}

	/**
	 * deleteIssuesCategories
	 * Deletes a Category
	 *
	 * @param int $catId - (required)
	 *
	 * @return string (application/json)
	 */
	function deleteIssuesCategories($catId) {
		$uri = "Issues/categories/$catId";
		$method = "delete";
		return $this->processRequest($uri, $catId, $method);
	}

	/**
	 * postIssues
	 * Create Movie Issue
	 *
	 * @param bool | array $i - (optional)
	 * {
	 *     "title": "string",
	 *     "requestType": "string",
	 *     "providerId": "string",
	 *     "requestId": "int",
	 *     "subject": "string",
	 *     "description": "string",
	 *     "issueCategoryId": "int",
	 *     "issueCategory": null,
	 *     "status": "string",
	 *     "resovledDate": "string",
	 *     "userReportedId": "string",
	 *     "userReported": null,
	 *     "comments": "array - []",
	 *     "id": "int"
	 * }
	 *
	 * @return string (application/json)
	 */
	function postIssues($i=false) {
		$uri = "Issues";
		$method = "post";
		return $this->processRequest($uri, $i, $method);
	}

	/**
	 * getIssues
	 * Returns all the issues
	 *
	 * @param int $take - (required)
	 * @param int $skip - (required)
	 * @param string $status - (required)  'Pending | InProgress | Resolved'
	 *
	 * @return string (application/json)
	 */
	function getIssues($take, $skip, $status) {
		$uri = "Issues/$take/$skip/$status";
		return $this->processRequest($uri);
	}

	/**
	 * getIssuesCount
	 * Returns all the issues count
	 *
	 *
	 * @return string (application/json)
	 * {
	 *     "pending": "int",
	 *     "inProgress": "int",
	 *     "resolved": "int"
	 * }
	 */
	function getIssuesCount() {
		$uri = "Issues/count";
		return $this->processRequest($uri);
	}

	/**
	 * getIssues
	 * Returns the issue by Id
	 *
	 * @param int $id - (required)
	 *
	 * @return string (application/json)
	 * {
	 *     "title": "string",
	 *     "requestType": "string",
	 *     "providerId": "string",
	 *     "requestId": "int",
	 *     "subject": "string",
	 *     "description": "string",
	 *     "issueCategoryId": "int",
	 *     "issueCategory": null,
	 *     "status": "string",
	 *     "resovledDate": "string",
	 *     "userReportedId": "string",
	 *     "userReported": null,
	 *     "comments": "array - []",
	 *     "id": "int"
	 * }
	 */
	function getIssue($id) {
		$uri = "Issues/$id";
		return $this->processRequest($uri);
	}

	/**
	 * getIssuesComments
	 * Get's all the issue comments by id
	 *
	 * @param int $id - (required)
	 *
	 * @return string (application/json)
	 */
	function getIssuesComments($id) {
		$uri = "Issues/$id/comments";
		return $this->processRequest($uri);
	}

	/**
	 * postIssuesComments
	 * Adds a comment on an issue
	 *
	 * @param bool | array $comment - (optional)
	 * {
	 *     "comment": "string",
	 *     "issueId": "int"
	 * }
	 *
	 * @return string (application/json)
	 * {
	 *     "userId": "string",
	 *     "comment": "string",
	 *     "issuesId": "int",
	 *     "date": "string",
	 *     "issues": null,
	 *     "user": null,
	 *     "id": "int"
	 * }
	 */
	function postIssuesComments($comment=false) {
		$uri = "Issues/comments";
		$method = "post";
		return $this->processRequest($uri, $comment, $method);
	}

	/**
	 * deleteIssuesComments
	 * Deletes a comment on a issue
	 *
	 * @param int $id - (required)
	 *
	 * @return string (application/json)
	 */
	function deleteIssuesComments($id) {
		$uri = "Issues/comments/$id";
		$method = "delete";
		return $this->processRequest($uri, $id, $method);
	}

	/**
	 * postIssuesStatus
	 *
	 *
	 * @param bool | array $model - (optional)
	 * {
	 *     "issueId": "int",
	 *     "status": "string"
	 * }
	 *
	 * @return string (application/json)
	 */
	function postIssuesStatus($model=false) {
		$uri = "Issues/status";
		$method = "post";
		return $this->processRequest($uri, $model, $method);
	}

	/**
	 * postJobUpdate
	 * Runs the update job
	 *
	 *
	 * @return string (application/json)
	 */
	function postJobUpdate() {
		$uri = "Job/update";
		$method = "post";
		return $this->processRequest($uri, false, $method);
	}

	/**
	 * postJobPlexuserimporter
	 * Runs the Plex User importer
	 *
	 *
	 * @return string (application/json)
	 */
	function postJobPlexuserimporter() {
		$uri = "Job/plexuserimporter";
		$method = "post";
		return $this->processRequest($uri, false, $method);
	}

	/**
	 * postJobEmbyuserimporter
	 * Runs the Emby User importer
	 *
	 *
	 * @return string (application/json)
	 */
	function postJobEmbyuserimporter() {
		$uri = "Job/embyuserimporter";
		$method = "post";
		return $this->processRequest($uri, false, $method);
	}

	/**
	 * postJobPlexcontentcacher
	 * Runs the Plex Content Cacher
	 *
	 *
	 * @return string (application/json)
	 */
	function postJobPlexcontentcacher() {
		$uri = "Job/plexcontentcacher";
		$method = "post";
		return $this->processRequest($uri, false, $method);
	}

	/**
	 * postJobPlexrecentlyadded
	 * Runs a smaller version of the content cacher
	 *
	 *
	 * @return string (application/json)
	 */
	function postJobPlexrecentlyadded() {
		$uri = "Job/plexrecentlyadded";
		$method = "post";
		return $this->processRequest($uri, false, $method);
	}

	/**
	 * postJobEmbycontentcacher
	 * Runs the Emby Content Cacher
	 *
	 *
	 * @return string (application/json)
	 */
	function postJobEmbycontentcacher() {
		$uri = "Job/embycontentcacher";
		$method = "post";
		return $this->processRequest($uri, false, $method);
	}

	/**
	 * postJobNewsletter
	 * Runs the newsletter
	 *
	 *
	 * @return string (application/json)
	 */
	function postJobNewsletter() {
		$uri = "Job/newsletter";
		$method = "post";
		return $this->processRequest($uri, false, $method);
	}

	/**
	 * getLandingPage
	 *
	 *
	 *
	 * @return string (application/json)
	 * {
	 *     "serversAvailable": "int",
	 *     "serversUnavailable": "int",
	 *     "partiallyDown": "bool",
	 *     "completelyDown": "bool",
	 *     "fullyAvailable": "bool",
	 *     "totalServers": "int"
	 * }
	 */
	function getLandingPage() {
		$uri = "LandingPage";
		return $this->processRequest($uri);
	}

	/**
	 * postLidarrProfiles
	 * Gets the Lidarr profiles.
	 *
	 * @param bool | array $settings - (optional) The settings.
	 * {
	 *     "enabled": "bool",
	 *     "apiKey": "string",
	 *     "defaultQualityProfile": "string",
	 *     "defaultRootPath": "string",
	 *     "albumFolder": "bool",
	 *     "languageProfileId": "int",
	 *     "metadataProfileId": "int",
	 *     "addOnly": "bool",
	 *     "ssl": "bool",
	 *     "subDir": "string",
	 *     "ip": "string",
	 *     "port": "int",
	 *     "id": "int"
	 * }
	 *
	 * @return string (application/json)
	 */
	function postLidarrProfiles($settings=false) {
		$uri = "Lidarr/Profiles";
		$method = "post";
		return $this->processRequest($uri, $settings, $method);
	}

	/**
	 * postLidarrRootFolders
	 * Gets the Lidarr root folders.
	 *
	 * @param bool | array $settings - (optional) The settings.
	 * {
	 *     "enabled": "bool",
	 *     "apiKey": "string",
	 *     "defaultQualityProfile": "string",
	 *     "defaultRootPath": "string",
	 *     "albumFolder": "bool",
	 *     "languageProfileId": "int",
	 *     "metadataProfileId": "int",
	 *     "addOnly": "bool",
	 *     "ssl": "bool",
	 *     "subDir": "string",
	 *     "ip": "string",
	 *     "port": "int",
	 *     "id": "int"
	 * }
	 *
	 * @return string (application/json)
	 */
	function postLidarrRootFolders($settings=false) {
		$uri = "Lidarr/RootFolders";
		$method = "post";
		return $this->processRequest($uri, $settings, $method);
	}

	/**
	 * postLidarrMetadata
	 * Gets the Lidarr metadata profiles.
	 *
	 * @param bool | array $settings - (optional) The settings.
	 * {
	 *     "enabled": "bool",
	 *     "apiKey": "string",
	 *     "defaultQualityProfile": "string",
	 *     "defaultRootPath": "string",
	 *     "albumFolder": "bool",
	 *     "languageProfileId": "int",
	 *     "metadataProfileId": "int",
	 *     "addOnly": "bool",
	 *     "ssl": "bool",
	 *     "subDir": "string",
	 *     "ip": "string",
	 *     "port": "int",
	 *     "id": "int"
	 * }
	 *
	 * @return string (application/json)
	 */
	function postLidarrMetadata($settings=false) {
		$uri = "Lidarr/Metadata";
		$method = "post";
		return $this->processRequest($uri, $settings, $method);
	}

	/**
	 * postLidarrLangauges
	 * Gets the Lidarr Langauge profiles.
	 *
	 * @param bool | array $settings - (optional) The settings.
	 * {
	 *     "enabled": "bool",
	 *     "apiKey": "string",
	 *     "defaultQualityProfile": "string",
	 *     "defaultRootPath": "string",
	 *     "albumFolder": "bool",
	 *     "languageProfileId": "int",
	 *     "metadataProfileId": "int",
	 *     "addOnly": "bool",
	 *     "ssl": "bool",
	 *     "subDir": "string",
	 *     "ip": "string",
	 *     "port": "int",
	 *     "id": "int"
	 * }
	 *
	 * @return string (application/json)
	 */
	function postLidarrLangauges($settings=false) {
		$uri = "Lidarr/Langauges";
		$method = "post";
		return $this->processRequest($uri, $settings, $method);
	}

	/**
	 * postLogging
	 *
	 *
	 * @param bool | array $l - (optional)
	 * {
	 *     "level": "string",
	 *     "description": "string",
	 *     "id": "int",
	 *     "location": "string",
	 *     "stackTrace": "string",
	 *     "dateTime": "string"
	 * }
	 *
	 * @return string
	 */
	function postLogging($l=false) {
		$uri = "Logging";
		$method = "post";
		return $this->processRequest($uri, $l, $method);
	}

	/**
	 * getRequestMusic
	 * Gets album requests.
	 *
	 * @param int $count - (required) The count of items you want to return.
	 * @param int $position - (required) The position.
	 * @param int $orderType - (required) The way we want to order.
	 * @param int $statusType - (required)
	 * @param int $availabilityType - (required)
	 *
	 * @return string (application/json)
	 * {
	 *     "collection": "array - []",
	 *     "total": "int"
	 * }
	 */
	function getRequestMusic($count, $position, $orderType, $statusType, $availabilityType) {
		$uri = "request/music/$count/$position/$orderType/$statusType/$availabilityType";
		return $this->processRequest($uri);
	}

	/**
	 * getRequestMusicTotal
	 * Gets the total amount of album requests.
	 *
	 *
	 * @return string (application/json)
	 */
	function getRequestMusicTotal() {
		$uri = "request/music/total";
		return $this->processRequest($uri);
	}

	/**
	 * postRequestMusic
	 * Requests a album.
	 *
	 * @param bool | array $album - (optional) The album.
	 * {
	 *     "foreignAlbumId": "string"
	 * }
	 *
	 * @return string (application/json)
	 * {
	 *     "result": "bool",
	 *     "message": "string",
	 *     "isError": "bool",
	 *     "errorMessage": "string"
	 * }
	 */
	function postRequestMusic($album=false) {
		$uri = "request/music";
		$method = "post";
		return $this->processRequest($uri, $album, $method);
	}

	/**
	 * getRequestMusicSearch
	 * Searches for a specific album request
	 *
	 * @param string $searchTerm - (required) The search term.
	 *
	 * @return string (application/json)
	 */
	function getRequestMusicSearch($searchTerm) {
		$uri = "request/music/search/$searchTerm";
		return $this->processRequest($uri);
	}

	/**
	 * deleteRequestMusic
	 * Deletes the specified album request.
	 *
	 * @param int $requestId - (required) The request identifier.
	 *
	 * @return string
	 */
	function deleteRequestMusic($requestId) {
		$uri = "request/music/$requestId";
		$method = "delete";
		return $this->processRequest($uri, $requestId, $method);
	}

	/**
	 * postRequestMusicApprove
	 * Approves the specified album request.
	 *
	 * @param bool | array $model - (optional) The albums's ID
	 * {
	 *     "id": "int"
	 * }
	 *
	 * @return string (application/json)
	 * {
	 *     "result": "bool",
	 *     "message": "string",
	 *     "isError": "bool",
	 *     "errorMessage": "string"
	 * }
	 */
	function postRequestMusicApprove($model=false) {
		$uri = "request/music/approve";
		$method = "post";
		return $this->processRequest($uri, $model, $method);
	}

	/**
	 * postRequestMusicAvailable
	 * Set's the specified album as available
	 *
	 * @param bool | array $model - (optional) The album's ID
	 * {
	 *     "id": "int"
	 * }
	 *
	 * @return string (application/json)
	 * {
	 *     "result": "bool",
	 *     "message": "string",
	 *     "isError": "bool",
	 *     "errorMessage": "string"
	 * }
	 */
	function postRequestMusicAvailable($model=false) {
		$uri = "request/music/available";
		$method = "post";
		return $this->processRequest($uri, $model, $method);
	}

	/**
	 * postRequestMusicUnavailable
	 * Set's the specified album as unavailable
	 *
	 * @param bool | array $model - (optional) The album's ID
	 * {
	 *     "id": "int"
	 * }
	 *
	 * @return string (application/json)
	 * {
	 *     "result": "bool",
	 *     "message": "string",
	 *     "isError": "bool",
	 *     "errorMessage": "string"
	 * }
	 */
	function postRequestMusicUnavailable($model=false) {
		$uri = "request/music/unavailable";
		$method = "post";
		return $this->processRequest($uri, $model, $method);
	}

	/**
	 * putRequestMusicDeny
	 * Denies the specified album request.
	 *
	 * @param bool | array $model - (optional) The album's ID
	 * {
	 *     "id": "int"
	 * }
	 *
	 * @return string (application/json)
	 * {
	 *     "result": "bool",
	 *     "message": "string",
	 *     "isError": "bool",
	 *     "errorMessage": "string"
	 * }
	 */
	function putRequestMusicDeny($model=false) {
		$uri = "request/music/deny";
		$method = "put";
		return $this->processRequest($uri, $model, $method);
	}

	/**
	 * getRequestMusicRemaining
	 * Gets model containing remaining number of music requests.
	 *
	 *
	 * @return string (application/json)
	 * {
	 *     "hasLimit": "bool",
	 *     "limit": "int",
	 *     "remaining": "int",
	 *     "nextRequest": "string"
	 * }
	 */
	function getRequestMusicRemaining() {
		$uri = "request/music/remaining";
		return $this->processRequest($uri);
	}

	/**
	 * postNotificationsMassemail
	 *
	 *
	 * @param bool | array $model - (optional)
	 * {
	 *     "subject": "string",
	 *     "body": "string",
	 *     "users": "array - []"
	 * }
	 *
	 * @return string (application/json)
	 */
	function postNotificationsMassemail($model=false) {
		$uri = "Notifications/massemail";
		$method = "post";
		return $this->processRequest($uri, $model, $method);
	}

	/**
	 * postPlex
	 * Signs into the Plex API.
	 *
	 * @param bool | array $request - (optional) The request.
	 * {
	 *     "login": "string",
	 *     "password": "string"
	 * }
	 *
	 * @return string (application/json)
	 * {
	 *     "user": null
	 * }
	 */
	function postPlex($request=false) {
		$uri = "Plex";
		$method = "post";
		return $this->processRequest($uri, $request, $method);
	}

	/**
	 * postPlexLibraries
	 * Gets the plex libraries.
	 *
	 * @param bool | array $settings - (optional) The settings.
	 * {
	 *     "name": "string",
	 *     "plexAuthToken": "string",
	 *     "machineIdentifier": "string",
	 *     "episodeBatchSize": "int",
	 *     "plexSelectedLibraries": "array - []",
	 *     "ssl": "bool",
	 *     "subDir": "string",
	 *     "ip": "string",
	 *     "port": "int",
	 *     "id": "int"
	 * }
	 *
	 * @return string (application/json)
	 * {
	 *     "data": null,
	 *     "successful": "bool",
	 *     "message": "string"
	 * }
	 */
	function postPlexLibraries($settings=false) {
		$uri = "Plex/Libraries";
		$method = "post";
		return $this->processRequest($uri, $settings, $method);
	}

	/**
	 * getPlexLibraries
	 *
	 *
	 * @param string $machineId - (required)
	 *
	 * @return string (application/json)
	 * {
	 *     "data": "array - []",
	 *     "successful": "bool",
	 *     "message": "string"
	 * }
	 */
	function getPlexLibraries($machineId) {
		$uri = "Plex/Libraries/$machineId";
		return $this->processRequest($uri);
	}

	/**
	 * postPlexUser
	 *
	 *
	 * @param bool | array $user - (optional)
	 * {
	 *     "username": "string",
	 *     "machineIdentifier": "string",
	 *     "libsSelected": "array - []"
	 * }
	 *
	 * @return string
	 */
	function postPlexUser($user=false) {
		$uri = "Plex/user";
		$method = "post";
		return $this->processRequest($uri, $user, $method);
	}

	/**
	 * postPlexServers
	 * Gets the plex servers.
	 *
	 * @param bool | array $u - (optional) The u.
	 * {
	 *     "login": "string",
	 *     "password": "string"
	 * }
	 *
	 * @return string (application/json)
	 * {
	 *     "success": "bool",
	 *     "message": "string",
	 *     "servers": null
	 * }
	 */
	function postPlexServers($u=false) {
		$uri = "Plex/servers";
		$method = "post";
		return $this->processRequest($uri, $u, $method);
	}

	/**
	 * getPlexFriends
	 * Gets the plex friends.
	 *
	 *
	 * @return string (application/json)
	 */
	function getPlexFriends() {
		$uri = "Plex/friends";
		return $this->processRequest($uri);
	}

	/**
	 * postPlexOauth
	 *
	 *
	 * @param bool | array $wizard - (optional)
	 * {
	 *     "wizard": "bool",
	 *     "pin": null
	 * }
	 *
	 * @return string
	 */
	function postPlexOauth($wizard=false) {
		$uri = "Plex/oauth";
		$method = "post";
		return $this->processRequest($uri, $wizard, $method);
	}

	/**
	 * postRadarrProfiles
	 * Gets the Radarr profiles.
	 *
	 * @param bool | array $settings - (optional) The settings.
	 * {
	 *     "enabled": "bool",
	 *     "apiKey": "string",
	 *     "defaultQualityProfile": "string",
	 *     "defaultRootPath": "string",
	 *     "addOnly": "bool",
	 *     "minimumAvailability": "string",
	 *     "ssl": "bool",
	 *     "subDir": "string",
	 *     "ip": "string",
	 *     "port": "int",
	 *     "id": "int"
	 * }
	 *
	 * @return string (application/json)
	 */
	function postRadarrProfiles($settings=false) {
		$uri = "Radarr/Profiles";
		$method = "post";
		return $this->processRequest($uri, $settings, $method);
	}

	/**
	 * postRadarrRootFolders
	 * Gets the Radarr root folders.
	 *
	 * @param bool | array $settings - (optional) The settings.
	 * {
	 *     "enabled": "bool",
	 *     "apiKey": "string",
	 *     "defaultQualityProfile": "string",
	 *     "defaultRootPath": "string",
	 *     "addOnly": "bool",
	 *     "minimumAvailability": "string",
	 *     "ssl": "bool",
	 *     "subDir": "string",
	 *     "ip": "string",
	 *     "port": "int",
	 *     "id": "int"
	 * }
	 *
	 * @return string (application/json)
	 */
	function postRadarrRootFolders($settings=false) {
		$uri = "Radarr/RootFolders";
		$method = "post";
		return $this->processRequest($uri, $settings, $method);
	}

	/**
	 * getRecentlyAddedMovies
	 * Returns the recently added movies for the past 7 days
	 *
	 *
	 * @return string (application/json)
	 */
	function getRecentlyAddedMovies() {
		$uri = "RecentlyAdded/movies";
		return $this->processRequest($uri);
	}

	/**
	 * getRecentlyAddedTv
	 * Returns the recently added tv shows for the past 7 days
	 *
	 *
	 * @return string (application/json)
	 */
	function getRecentlyAddedTv() {
		$uri = "RecentlyAdded/tv";
		return $this->processRequest($uri);
	}

	/**
	 * getRecentlyAddedTvGrouped
	 * Returns the recently added tv shows for the past 7 days and groups them by season
	 *
	 *
	 * @return string (application/json)
	 */
	function getRecentlyAddedTvGrouped() {
		$uri = "RecentlyAdded/tv/grouped";
		return $this->processRequest($uri);
	}

	/**
	 * getRequestMovie
	 * Gets movie requests.
	 *
	 * @param int $count - (required) The count of items you want to return.
	 * @param int $position - (required) The position.
	 * @param int $orderType - (required) The way we want to order.
	 * @param int $statusType - (required)
	 * @param int $availabilityType - (required)
	 *
	 * @return string (application/json)
	 * {
	 *     "collection": "array - []",
	 *     "total": "int"
	 * }
	 */
	function getRequestMovie($count, $position, $orderType, $statusType, $availabilityType) {
		$uri = "Request/movie/$count/$position/$orderType/$statusType/$availabilityType";
		return $this->processRequest($uri);
	}

	/**
	 * getRequestMovieTotal
	 * Gets the total amount of movie requests.
	 *
	 *
	 * @return string (application/json)
	 */
	function getRequestMovieTotal() {
		$uri = "Request/movie/total";
		return $this->processRequest($uri);
	}

	/**
	 * putRequestMovie
	 * Updates the specified movie request.
	 *
	 * @param bool | array $model - (optional) The Movie's ID
	 * {
	 *     "theMovieDbId": "int",
	 *     "issueId": "int",
	 *     "issues": "array - []",
	 *     "subscribed": "bool",
	 *     "showSubscribe": "bool",
	 *     "rootPathOverride": "int",
	 *     "qualityOverride": "int",
	 *     "imdbId": "string",
	 *     "overview": "string",
	 *     "posterPath": "string",
	 *     "releaseDate": "string",
	 *     "digitalReleaseDate": "string",
	 *     "status": "string",
	 *     "background": "string",
	 *     "released": "bool",
	 *     "digitalRelease": "bool",
	 *     "title": "string",
	 *     "approved": "bool",
	 *     "markedAsApproved": "string",
	 *     "requestedDate": "string",
	 *     "available": "bool",
	 *     "markedAsAvailable": "string",
	 *     "requestedUserId": "string",
	 *     "denied": "bool",
	 *     "markedAsDenied": "string",
	 *     "deniedReason": "string",
	 *     "requestType": "string",
	 *     "requestedUser": null,
	 *     "canApprove": "bool",
	 *     "id": "int"
	 * }
	 *
	 * @return string (application/json)
	 * {
	 *     "theMovieDbId": "int",
	 *     "issueId": "int",
	 *     "issues": "array - []",
	 *     "subscribed": "bool",
	 *     "showSubscribe": "bool",
	 *     "rootPathOverride": "int",
	 *     "qualityOverride": "int",
	 *     "imdbId": "string",
	 *     "overview": "string",
	 *     "posterPath": "string",
	 *     "releaseDate": "string",
	 *     "digitalReleaseDate": "string",
	 *     "status": "string",
	 *     "background": "string",
	 *     "released": "bool",
	 *     "digitalRelease": "bool",
	 *     "title": "string",
	 *     "approved": "bool",
	 *     "markedAsApproved": "string",
	 *     "requestedDate": "string",
	 *     "available": "bool",
	 *     "markedAsAvailable": "string",
	 *     "requestedUserId": "string",
	 *     "denied": "bool",
	 *     "markedAsDenied": "string",
	 *     "deniedReason": "string",
	 *     "requestType": "string",
	 *     "requestedUser": null,
	 *     "canApprove": "bool",
	 *     "id": "int"
	 * }
	 */
	function putRequestMovie($model=false) {
		$uri = "Request/movie";
		$method = "put";
		return $this->processRequest($uri, $model, $method);
	}

	/**
	 * getRequestMovieSearch
	 * Searches for a specific movie request
	 *
	 * @param string $searchTerm - (required) The search term.
	 *
	 * @return string (application/json)
	 */
	function getRequestMovieSearch($searchTerm) {
		$uri = "Request/movie/search/$searchTerm";
		return $this->processRequest($uri);
	}

	/**
	 * deleteRequestMovie
	 * Deletes the specified movie request.
	 *
	 * @param int $requestId - (required) The request identifier.
	 *
	 * @return string
	 */
	function deleteRequestMovie($requestId) {
		$uri = "Request/movie/$requestId";
		$method = "delete";
		return $this->processRequest($uri, $requestId, $method);
	}

	/**
	 * postRequestMovieApprove
	 * Approves the specified movie request.
	 *
	 * @param bool | array $model - (optional) The Movie's ID
	 * {
	 *     "id": "int"
	 * }
	 *
	 * @return string (application/json)
	 * {
	 *     "result": "bool",
	 *     "message": "string",
	 *     "isError": "bool",
	 *     "errorMessage": "string"
	 * }
	 */
	function postRequestMovieApprove($model=false) {
		$uri = "Request/movie/approve";
		$method = "post";
		return $this->processRequest($uri, $model, $method);
	}

	/**
	 * postRequestMovieAvailable
	 * Set's the specified Movie as available
	 *
	 * @param bool | array $model - (optional) The Movie's ID
	 * {
	 *     "id": "int"
	 * }
	 *
	 * @return string (application/json)
	 * {
	 *     "result": "bool",
	 *     "message": "string",
	 *     "isError": "bool",
	 *     "errorMessage": "string"
	 * }
	 */
	function postRequestMovieAvailable($model=false) {
		$uri = "Request/movie/available";
		$method = "post";
		return $this->processRequest($uri, $model, $method);
	}

	/**
	 * postRequestMovieUnavailable
	 * Set's the specified Movie as unavailable
	 *
	 * @param bool | array $model - (optional) The Movie's ID
	 * {
	 *     "id": "int"
	 * }
	 *
	 * @return string (application/json)
	 * {
	 *     "result": "bool",
	 *     "message": "string",
	 *     "isError": "bool",
	 *     "errorMessage": "string"
	 * }
	 */
	function postRequestMovieUnavailable($model=false) {
		$uri = "Request/movie/unavailable";
		$method = "post";
		return $this->processRequest($uri, $model, $method);
	}

	/**
	 * putRequestMovieDeny
	 * Denies the specified movie request.
	 *
	 * @param bool | array $model - (optional) The Movie's ID
	 * {
	 *     "id": "int"
	 * }
	 *
	 * @return string (application/json)
	 * {
	 *     "result": "bool",
	 *     "message": "string",
	 *     "isError": "bool",
	 *     "errorMessage": "string"
	 * }
	 */
	function putRequestMovieDeny($model=false) {
		$uri = "Request/movie/deny";
		$method = "put";
		return $this->processRequest($uri, $model, $method);
	}

	/**
	 * getRequestTvTotal
	 * Gets the total amount of TV requests.
	 *
	 *
	 * @return string (application/json)
	 */
	function getRequestTvTotal() {
		$uri = "Request/tv/total";
		return $this->processRequest($uri);
	}

	/**
	 * getRequestTv
	 * Gets the tv requests.
	 *
	 * @param int $count - (required) The count of items you want to return.
	 * @param int $position - (required) The position.
	 * @param int $orderType - (required)
	 * @param string $statusFilterType - (required)
	 * @param string $availabilityFilterType - (required)
	 * @param bool | int $statusType - (optional)
	 * @param bool | int $availabilityType - (optional)
	 *
	 * @return string (application/json)
	 * {
	 *     "collection": "array - []",
	 *     "total": "int"
	 * }
	 */
	function getRequestTv($count, $position, $orderType, $statusFilterType, $availabilityFilterType, $statusType=false, $availabilityType=false) {
		$uri = "Request/tv/$count/$position/$orderType/$statusFilterType/$availabilityFilterType?$statusType&$availabilityType";
		return $this->processRequest($uri);
	}

	/**
	 * getRequestTvlite
	 * Gets the tv requests lite.
	 *
	 * @param int $count - (required) The count of items you want to return.
	 * @param int $position - (required) The position.
	 * @param int $orderType - (required)
	 * @param string $statusFilterType - (required)
	 * @param string $availabilityFilterType - (required)
	 * @param bool | int $statusType - (optional)
	 * @param bool | int $availabilityType - (optional)
	 *
	 * @return string (application/json)
	 * {
	 *     "collection": "array - []",
	 *     "total": "int"
	 * }
	 */
	function getRequestTvlite($count, $position, $orderType, $statusFilterType, $availabilityFilterType, $statusType=false, $availabilityType=false) {
		$uri = "Request/tvlite/$count/$position/$orderType/$statusFilterType/$availabilityFilterType?$statusType&$availabilityType";
		return $this->processRequest($uri);
	}

	/**
	 * putRequestTv
	 * Updates the a specific tv request
	 *
	 * @param bool | array $model - (optional) The model.
	 * {
	 *     "tvDbId": "int",
	 *     "imdbId": "string",
	 *     "qualityOverride": "int",
	 *     "rootFolder": "int",
	 *     "overview": "string",
	 *     "title": "string",
	 *     "posterPath": "string",
	 *     "background": "string",
	 *     "releaseDate": "string",
	 *     "status": "string",
	 *     "totalSeasons": "int",
	 *     "childRequests": "array - []",
	 *     "id": "int"
	 * }
	 *
	 * @return string (application/json)
	 * {
	 *     "tvDbId": "int",
	 *     "imdbId": "string",
	 *     "qualityOverride": "int",
	 *     "rootFolder": "int",
	 *     "overview": "string",
	 *     "title": "string",
	 *     "posterPath": "string",
	 *     "background": "string",
	 *     "releaseDate": "string",
	 *     "status": "string",
	 *     "totalSeasons": "int",
	 *     "childRequests": "array - []",
	 *     "id": "int"
	 * }
	 */
	function putRequestTv($model=false) {
		$uri = "Request/tv";
		$method = "put";
		return $this->processRequest($uri, $model, $method);
	}

	/**
	 * getRequestsTvlite
	 * Gets the tv requests without the whole object graph (Does not include seasons/episodes).
	 *
	 *
	 * @return string (application/json)
	 */
	function getRequestsTvlite() {
		$uri = "Request/tvlite";
		return $this->processRequest($uri);
	}

	/**
	 * deleteRequestTv
	 * Deletes the a specific tv request
	 *
	 * @param int $requestId - (required) The request identifier.
	 *
	 * @return string
	 */
	function deleteRequestTv($requestId) {
		$uri = "Request/tv/$requestId";
		$method = "delete";
		return $this->processRequest($uri, $requestId, $method);
	}

	/**
	 * getRequestTvSearch
	 * Searches for a specific tv request
	 *
	 * @param string $searchTerm - (required) The search term.
	 *
	 * @return string (application/json)
	 */
	function getRequestTvSearch($searchTerm) {
		$uri = "Request/tv/search/$searchTerm";
		return $this->processRequest($uri);
	}

	/**
	 * putRequestTvRoot
	 * Updates the root path for this tv show
	 *
	 * @param int $requestId - (required)
	 * @param int $rootFolderId - (required)
	 *
	 * @return string (application/json)
	 */
	function putRequestTvRoot($requestId, $rootFolderId) {
		$uri = "Request/tv/root/$requestId/$rootFolderId";
		$method = "put";
		return $this->processRequest($uri, $requestId, $method);
	}

	/**
	 * putRequestTvQuality
	 * Updates the quality profile for this tv show
	 *
	 * @param int $requestId - (required)
	 * @param int $qualityId - (required)
	 *
	 * @return string (application/json)
	 */
	function putRequestTvQuality($requestId, $qualityId) {
		$uri = "Request/tv/quality/$requestId/$qualityId";
		$method = "put";
		return $this->processRequest($uri, $requestId, $method);
	}

	/**
	 * putRequestTvChild
	 * Updates the a specific child request
	 *
	 * @param bool | array $child - (optional) The model.
	 * {
	 *     "parentRequest": null,
	 *     "parentRequestId": "int",
	 *     "issueId": "int",
	 *     "seriesType": "string",
	 *     "subscribed": "bool",
	 *     "showSubscribe": "bool",
	 *     "issues": "array - []",
	 *     "seasonRequests": "array - []",
	 *     "title": "string",
	 *     "approved": "bool",
	 *     "markedAsApproved": "string",
	 *     "requestedDate": "string",
	 *     "available": "bool",
	 *     "markedAsAvailable": "string",
	 *     "requestedUserId": "string",
	 *     "denied": "bool",
	 *     "markedAsDenied": "string",
	 *     "deniedReason": "string",
	 *     "requestType": "string",
	 *     "requestedUser": null,
	 *     "canApprove": "bool",
	 *     "id": "int"
	 * }
	 *
	 * @return string (application/json)
	 * {
	 *     "parentRequest": null,
	 *     "parentRequestId": "int",
	 *     "issueId": "int",
	 *     "seriesType": "string",
	 *     "subscribed": "bool",
	 *     "showSubscribe": "bool",
	 *     "issues": "array - []",
	 *     "seasonRequests": "array - []",
	 *     "title": "string",
	 *     "approved": "bool",
	 *     "markedAsApproved": "string",
	 *     "requestedDate": "string",
	 *     "available": "bool",
	 *     "markedAsAvailable": "string",
	 *     "requestedUserId": "string",
	 *     "denied": "bool",
	 *     "markedAsDenied": "string",
	 *     "deniedReason": "string",
	 *     "requestType": "string",
	 *     "requestedUser": null,
	 *     "canApprove": "bool",
	 *     "id": "int"
	 * }
	 */
	function putRequestTvChild($child=false) {
		$uri = "Request/tv/child";
		$method = "put";
		return $this->processRequest($uri, $child, $method);
	}

	/**
	 * putRequestTvDeny
	 * Denies the a specific child request
	 *
	 * @param bool | array $model - (optional) This is the child request's ID
	 * {
	 *     "id": "int"
	 * }
	 *
	 * @return string (application/json)
	 * {
	 *     "result": "bool",
	 *     "message": "string",
	 *     "isError": "bool",
	 *     "errorMessage": "string"
	 * }
	 */
	function putRequestTvDeny($model=false) {
		$uri = "Request/tv/deny";
		$method = "put";
		return $this->processRequest($uri, $model, $method);
	}

	/**
	 * postRequestTvAvailable
	 * Set's the specified tv child as available
	 *
	 * @param bool | array $model - (optional) The Movie's ID
	 * {
	 *     "id": "int"
	 * }
	 *
	 * @return string (application/json)
	 * {
	 *     "result": "bool",
	 *     "message": "string",
	 *     "isError": "bool",
	 *     "errorMessage": "string"
	 * }
	 */
	function postRequestTvAvailable($model=false) {
		$uri = "Request/tv/available";
		$method = "post";
		return $this->processRequest($uri, $model, $method);
	}

	/**
	 * postRequestTvUnavailable
	 * Set's the specified tv child as unavailable
	 *
	 * @param bool | array $model - (optional) The Movie's ID
	 * {
	 *     "id": "int"
	 * }
	 *
	 * @return string (application/json)
	 * {
	 *     "result": "bool",
	 *     "message": "string",
	 *     "isError": "bool",
	 *     "errorMessage": "string"
	 * }
	 */
	function postRequestTvUnavailable($model=false) {
		$uri = "Request/tv/unavailable";
		$method = "post";
		return $this->processRequest($uri, $model, $method);
	}

	/**
	 * postRequestTvApprove
	 * Updates the a specific child request
	 *
	 * @param bool | array $model - (optional) This is the child request's ID
	 * {
	 *     "id": "int"
	 * }
	 *
	 * @return string (application/json)
	 * {
	 *     "result": "bool",
	 *     "message": "string",
	 *     "isError": "bool",
	 *     "errorMessage": "string"
	 * }
	 */
	function postRequestTvApprove($model=false) {
		$uri = "Request/tv/approve";
		$method = "post";
		return $this->processRequest($uri, $model, $method);
	}

	/**
	 * deleteRequestTvChild
	 * Deletes the a specific tv request
	 *
	 * @param int $requestId - (required) The model.
	 *
	 * @return string (application/json)
	 */
	function deleteRequestTvChild($requestId) {
		$uri = "Request/tv/child/$requestId";
		$method = "delete";
		return $this->processRequest($uri, $requestId, $method);
	}

	/**
	 * getRequestTvChild
	 * Retuns all children requests for the request id
	 *
	 * @param int $requestId - (required) The Request Id
	 *
	 * @return string (application/json)
	 */
	function getRequestTvChild($requestId) {
		$uri = "Request/tv/$requestId/child";
		return $this->processRequest($uri);
	}

	/**
	 * getRequestCount
	 * Gets the count of total requests
	 *
	 *
	 * @return string (application/json)
	 * {
	 *     "pending": "int",
	 *     "approved": "int",
	 *     "available": "int"
	 * }
	 */
	function getRequestCount() {
		$uri = "Request/count";
		return $this->processRequest($uri);
	}

	function postRequestMovie($tmdbId) {
		$uri = "Request/Movie";
		$method = "post";
		$body = ["theMovieDbId"=>$tmdbId];

		return $this->processRequest($uri, $body, $method);
	}

	/**
	 * getRequestUserhasrequest
	 * Checks if the passed in user has a request
	 *
	 * @param bool | string $userId - (optional)
	 *
	 * @return string (application/json)
	 */
	function getRequestUserhasrequest($userId=false) {
		$uri = "Request/userhasrequest?$userId";
		return $this->processRequest($uri);
	}

	/**
	 * postRequestMovieSubscribe
	 * Subscribes for notifications to a movie request
	 *
	 * @param int $requestId - (required)
	 *
	 * @return string (application/json)
	 */
	function postRequestMovieSubscribe($requestId) {
		$uri = "Request/movie/subscribe/$requestId";
		$method = "post";
		return $this->processRequest($uri, $requestId, $method);
	}

	function postRequestTv($tvdbId, $seasons) {
		$uri = "Request/TV";
		$method = "post";
		foreach($seasons as &$season) {
			$season = ['seasonNumber'=>$season['seasonNumber'], 'episodes'=>[]];
		}
		$body = [
			"firstSeason"=>false,
			"latestSeason"=>false,
			"requestAll"=>true,
			"tvDbId"=>intval($tvdbId),
			"seasons"=>$seasons
		];

		return $this->processRequest($uri,$body,$method);
	}

	/**
	 * postRequestTvSubscribe
	 * Subscribes for notifications to a TV request
	 *
	 * @param int $requestId - (required)
	 *
	 * @return string (application/json)
	 */
	function postRequestTvSubscribe($requestId) {
		$uri = "Request/tv/subscribe/$requestId";
		$method = "post";
		return $this->processRequest($uri, $requestId, $method);
	}

	/**
	 * postRequestMovieUnsubscribe
	 * UnSubscribes for notifications to a movie request
	 *
	 * @param int $requestId - (required)
	 *
	 * @return string (application/json)
	 */
	function postRequestMovieUnsubscribe($requestId) {
		$uri = "Request/movie/unsubscribe/$requestId";
		$method = "post";
		return $this->processRequest($uri, $requestId, $method);
	}

	/**
	 * postRequestTvUnsubscribe
	 * UnSubscribes for notifications to a TV request
	 *
	 * @param int $requestId - (required)
	 *
	 * @return string (application/json)
	 */
	function postRequestTvUnsubscribe($requestId) {
		$uri = "Request/tv/unsubscribe/$requestId";
		$method = "post";
		return $this->processRequest($uri, $requestId, $method);
	}

	/**
	 * getRequestMovieRemaining
	 * Gets model containing remaining number of movie requests.
	 *
	 *
	 * @return string (application/json)
	 * {
	 *     "hasLimit": "bool",
	 *     "limit": "int",
	 *     "remaining": "int",
	 *     "nextRequest": "string"
	 * }
	 */
	function getRequestMovieRemaining() {
		$uri = "Request/movie/remaining";
		return $this->processRequest($uri);
	}

	/**
	 * getRequestTvRemaining
	 * Gets model containing remaining number of tv requests.
	 *
	 *
	 * @return string (application/json)
	 * {
	 *     "hasLimit": "bool",
	 *     "limit": "int",
	 *     "remaining": "int",
	 *     "nextRequest": "string"
	 * }
	 */
	function getRequestTvRemaining() {
		$uri = "Request/tv/remaining";
		return $this->processRequest($uri);
	}

	/**
	 * getSearchMovie
	 * Searches for a movie.
	 *
	 * @param string $searchTerm - (required) The search term.
	 *
	 * @return string (application/json)
	 */
	function getSearchMovie($searchTerm) {
		$uri = "Search/movie/$searchTerm";
		return $this->processRequest($uri);
	}

	function getSearchMulti($searchTerm) {
		$uri = [];
		$searchTerm = urlencode($searchTerm);
		$uri["movie"] = ["uri"=>"Search/movie/$searchTerm"];
		$uri["tv"] = ["uri"=>"Search/tv/$searchTerm"];
		return $this->processRequests($uri);
	}

	/**
	 * getSearchMovieInfo
	 * Gets extra information on the movie e.g. IMDBId
	 *
	 * @param int $theMovieDbId - (required) The movie database identifier.
	 *
	 * @return string (application/json)
	 * {
	 *     "adult": "bool",
	 *     "backdropPath": "string",
	 *     "genreIds": "array - []",
	 *     "originalLanguage": "string",
	 *     "originalTitle": "string",
	 *     "overview": "string",
	 *     "popularity": "number",
	 *     "posterPath": "string",
	 *     "releaseDate": "string",
	 *     "title": "string",
	 *     "video": "bool",
	 *     "voteAverage": "number",
	 *     "voteCount": "int",
	 *     "alreadyInCp": "bool",
	 *     "trailer": "string",
	 *     "homepage": "string",
	 *     "rootPathOverride": "int",
	 *     "qualityOverride": "int",
	 *     "type": "string",
	 *     "releaseDates": null,
	 *     "digitalReleaseDate": "string",
	 *     "id": "int",
	 *     "approved": "bool",
	 *     "requested": "bool",
	 *     "requestId": "int",
	 *     "available": "bool",
	 *     "plexUrl": "string",
	 *     "embyUrl": "string",
	 *     "quality": "string",
	 *     "imdbId": "string",
	 *     "theTvDbId": "string",
	 *     "theMovieDbId": "string",
	 *     "subscribed": "bool",
	 *     "showSubscribe": "bool"
	 * }
	 */
	function getSearchMovieInfo($theMovieDbId) {
		$uri = "Search/movie/info/$theMovieDbId";
		return $this->processRequest($uri);
	}

	/**
	 * getSearchMovieSimilar
	 * Returns similar movies to the movie id passed in
	 *
	 * @param int $theMovieDbId - (required) ID of the movie
	 *
	 * @return string (application/json)
	 */
	function getSearchMovieSimilar($theMovieDbId) {
		$uri = "Search/movie/$theMovieDbId/similar";
		return $this->processRequest($uri);
	}

	/**
	 * getSearchMoviePopular
	 * Returns Popular Movies
	 *
	 *
	 * @return string (application/json)
	 */
	function getSearchMoviePopular() {
		$uri = "Search/movie/popular";
		return $this->processRequest($uri);
	}

	/**
	 * getSearchMovieNowplaying
	 * Retuns Now Playing Movies
	 *
	 *
	 * @return string (application/json)
	 */
	function getSearchMovieNowplaying() {
		$uri = "Search/movie/nowplaying";
		return $this->processRequest($uri);
	}

	/**
	 * getSearchMovieToprated
	 * Returns top rated movies.
	 *
	 *
	 * @return string (application/json)
	 */
	function getSearchMovieToprated() {
		$uri = "Search/movie/toprated";
		return $this->processRequest($uri);
	}

	/**
	 * getSearchMovieUpcoming
	 * Returns Upcoming movies.
	 *
	 *
	 * @return string (application/json)
	 */
	function getSearchMovieUpcoming() {
		$uri = "Search/movie/upcoming";
		return $this->processRequest($uri);
	}

	/**
	 * getSearchTv
	 * Searches for a Tv Show.
	 *
	 * @param string $searchTerm - (required) The search term.
	 *
	 * @return string (application/json)
	 */
	function getSearchTv($searchTerm) {
		$uri = "Search/tv/$searchTerm";
		return $this->processRequest($uri);
	}

	/**
	 * getSearchTvInfo
	 * Gets extra show information.
	 *
	 * @param int $tvdbId - (required) The TVDB identifier.
	 *
	 * @return string (application/json)
	 * {
	 *     "title": "string",
	 *     "aliases": "array - []",
	 *     "banner": "string",
	 *     "seriesId": "int",
	 *     "status": "string",
	 *     "firstAired": "string",
	 *     "network": "string",
	 *     "networkId": "string",
	 *     "runtime": "string",
	 *     "genre": "array - []",
	 *     "overview": "string",
	 *     "lastUpdated": "int",
	 *     "airsDayOfWeek": "string",
	 *     "airsTime": "string",
	 *     "rating": "string",
	 *     "siteRating": "int",
	 *     "trailer": "string",
	 *     "homepage": "string",
	 *     "seasonRequests": "array - []",
	 *     "requestAll": "bool",
	 *     "firstSeason": "bool",
	 *     "latestSeason": "bool",
	 *     "fullyAvailable": "bool",
	 *     "partlyAvailable": "bool",
	 *     "type": "string",
	 *     "id": "int",
	 *     "approved": "bool",
	 *     "requested": "bool",
	 *     "requestId": "int",
	 *     "available": "bool",
	 *     "plexUrl": "string",
	 *     "embyUrl": "string",
	 *     "quality": "string",
	 *     "imdbId": "string",
	 *     "theTvDbId": "string",
	 *     "theMovieDbId": "string",
	 *     "subscribed": "bool",
	 *     "showSubscribe": "bool"
	 * }
	 */
	function getSearchTvInfo($tvdbId) {
		$uri = "Search/tv/info/$tvdbId";
		return $this->processRequest($uri);
	}

	/**
	 * getSearchTvPopular
	 * Returns Popular Tv Shows
	 *
	 *
	 * @return string (application/json)
	 */
	function getSearchTvPopular() {
		$uri = "Search/tv/popular";
		return $this->processRequest($uri);
	}

	/**
	 * getSearchTvAnticipated
	 * Returns most Anticiplateds tv shows.
	 *
	 *
	 * @return string (application/json)
	 */
	function getSearchTvAnticipated() {
		$uri = "Search/tv/anticipated";
		return $this->processRequest($uri);
	}

	/**
	 * getSearchTvMostwatched
	 * Returns Most watched shows.
	 *
	 *
	 * @return string (application/json)
	 */
	function getSearchTvMostwatched() {
		$uri = "Search/tv/mostwatched";
		return $this->processRequest($uri);
	}

	/**
	 * getSearchTvTrending
	 * Returns trending shows
	 *
	 *
	 * @return string (application/json)
	 */
	function getSearchTvTrending() {
		$uri = "Search/tv/trending";
		return $this->processRequest($uri);
	}

	/**
	 * getSearchMusicArtist
	 * Returns the artist information we searched for
	 *
	 * @param string $searchTerm - (required)
	 *
	 * @return string (application/json)
	 */
	function getSearchMusicArtist($searchTerm) {
		$uri = "Search/music/artist/$searchTerm";
		return $this->processRequest($uri);
	}

	/**
	 * getSearchMusicAlbum
	 * Returns the album information we searched for
	 *
	 * @param string $searchTerm - (required)
	 *
	 * @return string (application/json)
	 */
	function getSearchMusicAlbum($searchTerm) {
		$uri = "Search/music/album/$searchTerm";
		return $this->processRequest($uri);
	}

	/**
	 * getSearchMusicArtistAlbum
	 * Returns all albums for the artist using the ForeignArtistId
	 *
	 * @param string $foreignArtistId - (required)
	 *
	 * @return string (application/json)
	 */
	function getSearchMusicArtistAlbum($foreignArtistId) {
		$uri = "Search/music/artist/album/$foreignArtistId";
		return $this->processRequest($uri);
	}

	/**
	 * postSettingsOmbi
	 * Save the Ombi settings.
	 *
	 * @param bool | array $ombi - (optional) The ombi.
	 * {
	 *     "baseUrl": "string",
	 *     "collectAnalyticData": "bool",
	 *     "wizard": "bool",
	 *     "apiKey": "string",
	 *     "ignoreCertificateErrors": "bool",
	 *     "doNotSendNotificationsForAutoApprove": "bool",
	 *     "hideRequestsUsers": "bool",
	 *     "id": "int"
	 * }
	 *
	 * @return string (application/json)
	 */
	function postSettingsOmbi($ombi=false) {
		$uri = "Settings/ombi";
		$method = "post";
		return $this->processRequest($uri, $ombi, $method);
	}

	/**
	 * getSettingsAbout
	 *
	 *
	 *
	 * @return string (application/json)
	 * {
	 *     "version": "string",
	 *     "branch": "string",
	 *     "frameworkDescription": "string",
	 *     "osArchitecture": "string",
	 *     "osDescription": "string",
	 *     "processArchitecture": "string",
	 *     "applicationBasePath": "string"
	 * }
	 */
	function getSettingsAbout() {
		$uri = "Settings/about";
		return $this->processRequest($uri);
	}

	/**
	 * postSettingsOmbiResetApi
	 *
	 *
	 *
	 * @return string (application/json)
	 */
	function postSettingsOmbiResetApi() {
		$uri = "Settings/ombi/resetApi";
		$method = "post";
		return $this->processRequest($uri, false, $method);
	}

	/**
	 * postSettingsPlex
	 * Save the Plex settings.
	 *
	 * @param bool | array $plex - (optional) The plex.
	 * {
	 *     "enable": "bool",
	 *     "installId": "string",
	 *     "servers": "array - []",
	 *     "id": "int"
	 * }
	 *
	 * @return string (application/json)
	 */
	function postSettingsPlex($plex=false) {
		$uri = "Settings/plex";
		$method = "post";
		return $this->processRequest($uri, $plex, $method);
	}

	/**
	 * getSettingsClientid
	 *
	 *
	 *
	 * @return string (application/json)
	 */
	function getSettingsClientid() {
		$uri = "Settings/clientid";
		return $this->processRequest($uri);
	}

	/**
	 * postSettingsEmby
	 * Save the Emby settings.
	 *
	 * @param bool | array $emby - (optional) The emby.
	 * {
	 *     "enable": "bool",
	 *     "servers": "array - []",
	 *     "id": "int"
	 * }
	 *
	 * @return string (application/json)
	 */
	function postSettingsEmby($emby=false) {
		$uri = "Settings/emby";
		$method = "post";
		return $this->processRequest($uri, $emby, $method);
	}

	/**
	 * postSettingsLandingpage
	 * Save the Landing Page settings.
	 *
	 * @param bool | array $settings - (optional) The settings.
	 * {
	 *     "enabled": "bool",
	 *     "noticeEnabled": "bool",
	 *     "noticeText": "string",
	 *     "timeLimit": "bool",
	 *     "startDateTime": "string",
	 *     "endDateTime": "string",
	 *     "expired": "bool",
	 *     "id": "int"
	 * }
	 *
	 * @return string (application/json)
	 */
	function postSettingsLandingpage($settings=false) {
		$uri = "Settings/landingpage";
		$method = "post";
		return $this->processRequest($uri, $settings, $method);
	}

	/**
	 * postSettingsCustomization
	 * Save the Customization settings.
	 *
	 * @param bool | array $settings - (optional) The settings.
	 * {
	 *     "applicationName": "string",
	 *     "applicationUrl": "string",
	 *     "customCssLink": "string",
	 *     "enableCustomDonations": "bool",
	 *     "customDonationUrl": "string",
	 *     "customDonationMessage": "string",
	 *     "logo": "string",
	 *     "presetThemeName": "string",
	 *     "presetThemeContent": "string",
	 *     "recentlyAddedPage": "bool",
	 *     "presetThemeVersion": "string",
	 *     "presetThemeDisplayName": "string",
	 *     "hasPresetTheme": "bool",
	 *     "id": "int"
	 * }
	 *
	 * @return string (application/json)
	 */
	function postSettingsCustomization($settings=false) {
		$uri = "Settings/customization";
		$method = "post";
		return $this->processRequest($uri, $settings, $method);
	}

	/**
	 * getSettingsThemes
	 * Get's the preset themes available
	 *
	 *
	 * @return string (application/json)
	 */
	function getSettingsThemes() {
		$uri = "Settings/themes";
		return $this->processRequest($uri);
	}

	/**
	 * getSettingsThemecontent
	 * Gets the content of the theme available
	 *
	 * @param bool | string $url - (optional)
	 *
	 * @return string
	 */
	function getSettingsThemecontent($url=false) {
		$uri = "Settings/themecontent?$url";
		return $this->processRequest($uri);
	}

	/**
	 * postSettingsSonarr
	 * Save the Sonarr settings.
	 *
	 * @param bool | array $settings - (optional) The settings.
	 * {
	 *     "enabled": "bool",
	 *     "apiKey": "string",
	 *     "qualityProfile": "string",
	 *     "seasonFolders": "bool",
	 *     "rootPath": "string",
	 *     "qualityProfileAnime": "string",
	 *     "rootPathAnime": "string",
	 *     "addOnly": "bool",
	 *     "ssl": "bool",
	 *     "subDir": "string",
	 *     "ip": "string",
	 *     "port": "int",
	 *     "id": "int"
	 * }
	 *
	 * @return string (application/json)
	 */
	function postSettingsSonarr($settings=false) {
		$uri = "Settings/sonarr";
		$method = "post";
		return $this->processRequest($uri, $settings, $method);
	}

	/**
	 * postSettingsRadarr
	 * Save the Radarr settings.
	 *
	 * @param bool | array $settings - (optional) The settings.
	 * {
	 *     "enabled": "bool",
	 *     "apiKey": "string",
	 *     "defaultQualityProfile": "string",
	 *     "defaultRootPath": "string",
	 *     "addOnly": "bool",
	 *     "minimumAvailability": "string",
	 *     "ssl": "bool",
	 *     "subDir": "string",
	 *     "ip": "string",
	 *     "port": "int",
	 *     "id": "int"
	 * }
	 *
	 * @return string (application/json)
	 */
	function postSettingsRadarr($settings=false) {
		$uri = "Settings/radarr";
		$method = "post";
		return $this->processRequest($uri, $settings, $method);
	}

	/**
	 * postSettingsLidarr
	 * Save the Lidarr settings.
	 *
	 * @param bool | array $settings - (optional) The settings.
	 * {
	 *     "enabled": "bool",
	 *     "apiKey": "string",
	 *     "defaultQualityProfile": "string",
	 *     "defaultRootPath": "string",
	 *     "albumFolder": "bool",
	 *     "languageProfileId": "int",
	 *     "metadataProfileId": "int",
	 *     "addOnly": "bool",
	 *     "ssl": "bool",
	 *     "subDir": "string",
	 *     "ip": "string",
	 *     "port": "int",
	 *     "id": "int"
	 * }
	 *
	 * @return string (application/json)
	 */
	function postSettingsLidarr($settings=false) {
		$uri = "Settings/lidarr";
		$method = "post";
		return $this->processRequest($uri, $settings, $method);
	}

	/**
	 * getSettingsLidarrenabled
	 * Gets the Lidarr Settings.
	 *
	 *
	 * @return string (application/json)
	 */
	function getSettingsLidarrenabled() {
		$uri = "Settings/lidarrenabled";
		return $this->processRequest($uri);
	}

	/**
	 * postSettingsAuthentication
	 * Save the Authentication settings.
	 *
	 * @param bool | array $settings - (optional) The settings.
	 * {
	 *     "allowNoPassword": "bool",
	 *     "requireDigit": "bool",
	 *     "requiredLength": "int",
	 *     "requireLowercase": "bool",
	 *     "requireNonAlphanumeric": "bool",
	 *     "requireUppercase": "bool",
	 *     "enableOAuth": "bool",
	 *     "id": "int"
	 * }
	 *
	 * @return string (application/json)
	 */
	function postSettingsAuthentication($settings=false) {
		$uri = "Settings/authentication";
		$method = "post";
		return $this->processRequest($uri, $settings, $method);
	}

	/**
	 * postSettingsUpdate
	 * Save the Update settings.
	 *
	 * @param bool | array $settings - (optional) The settings.
	 * {
	 *     "autoUpdateEnabled": "bool",
	 *     "username": "string",
	 *     "password": "string",
	 *     "processName": "string",
	 *     "useScript": "bool",
	 *     "scriptLocation": "string",
	 *     "windowsServiceName": "string",
	 *     "windowsService": "bool",
	 *     "testMode": "bool",
	 *     "id": "int"
	 * }
	 *
	 * @return string (application/json)
	 */
	function postSettingsUpdate($settings=false) {
		$uri = "Settings/Update";
		$method = "post";
		return $this->processRequest($uri, $settings, $method);
	}

	/**
	 * postSettingsUserManagement
	 * Save the UserManagement settings.
	 *
	 * @param bool | array $settings - (optional) The settings.
	 * {
	 *     "importPlexAdmin": "bool",
	 *     "importPlexUsers": "bool",
	 *     "importEmbyUsers": "bool",
	 *     "movieRequestLimit": "int",
	 *     "episodeRequestLimit": "int",
	 *     "defaultRoles": "array - []",
	 *     "bannedPlexUserIds": "array - []",
	 *     "bannedEmbyUserIds": "array - []",
	 *     "id": "int"
	 * }
	 *
	 * @return string (application/json)
	 */
	function postSettingsUserManagement($settings=false) {
		$uri = "Settings/UserManagement";
		$method = "post";
		return $this->processRequest($uri, $settings, $method);
	}

	/**
	 * postSettingsCouchPotato
	 * Save the CouchPotatoSettings settings.
	 *
	 * @param bool | array $settings - (optional) The settings.
	 * {
	 *     "enabled": "bool",
	 *     "apiKey": "string",
	 *     "defaultProfileId": "string",
	 *     "username": "string",
	 *     "password": "string",
	 *     "ssl": "bool",
	 *     "subDir": "string",
	 *     "ip": "string",
	 *     "port": "int",
	 *     "id": "int"
	 * }
	 *
	 * @return string (application/json)
	 */
	function postSettingsCouchPotato($settings=false) {
		$uri = "Settings/CouchPotato";
		$method = "post";
		return $this->processRequest($uri, $settings, $method);
	}

	/**
	 * postSettingsDogNzb
	 * Save the DogNzbSettings settings.
	 *
	 * @param bool | array $settings - (optional) The settings.
	 * {
	 *     "enabled": "bool",
	 *     "apiKey": "string",
	 *     "movies": "bool",
	 *     "tvShows": "bool",
	 *     "id": "int"
	 * }
	 *
	 * @return string (application/json)
	 */
	function postSettingsDogNzb($settings=false) {
		$uri = "Settings/DogNzb";
		$method = "post";
		return $this->processRequest($uri, $settings, $method);
	}

	/**
	 * postSettingsSickRage
	 * Save the SickRage settings.
	 *
	 * @param bool | array $settings - (optional) The settings.
	 * {
	 *     "enabled": "bool",
	 *     "apiKey": "string",
	 *     "qualityProfile": "string",
	 *     "qualities": "array - []",
	 *     "ssl": "bool",
	 *     "subDir": "string",
	 *     "ip": "string",
	 *     "port": "int",
	 *     "id": "int"
	 * }
	 *
	 * @return string (application/json)
	 */
	function postSettingsSickRage($settings=false) {
		$uri = "Settings/SickRage";
		$method = "post";
		return $this->processRequest($uri, $settings, $method);
	}

	/**
	 * postSettingsJobs
	 * Save the JobSettings settings.
	 *
	 * @param bool | array $settings - (optional) The settings.
	 * {
	 *     "embyContentSync": "string",
	 *     "sonarrSync": "string",
	 *     "radarrSync": "string",
	 *     "plexContentSync": "string",
	 *     "plexRecentlyAddedSync": "string",
	 *     "couchPotatoSync": "string",
	 *     "automaticUpdater": "string",
	 *     "userImporter": "string",
	 *     "sickRageSync": "string",
	 *     "refreshMetadata": "string",
	 *     "newsletter": "string",
	 *     "lidarrArtistSync": "string",
	 *     "id": "int"
	 * }
	 *
	 * @return string (application/json)
	 * {
	 *     "result": "bool",
	 *     "message": "string"
	 * }
	 */
	function postSettingsJobs($settings=false) {
		$uri = "Settings/jobs";
		$method = "post";
		return $this->processRequest($uri, $settings, $method);
	}

	/**
	 * postSettingsTestcron
	 *
	 *
	 * @param bool | array $body - (optional)
	 * {
	 *     "expression": "string"
	 * }
	 *
	 * @return string (application/json)
	 * {
	 *     "success": "bool",
	 *     "message": "string",
	 *     "schedule": "array - []"
	 * }
	 */
	function postSettingsTestcron($body=false) {
		$uri = "Settings/testcron";
		$method = "post";
		return $this->processRequest($uri, $body, $method);
	}

	/**
	 * postSettingsIssues
	 * Save the Issues settings.
	 *
	 * @param bool | array $settings - (optional) The settings.
	 * {
	 *     "enabled": "bool",
	 *     "enableInProgress": "bool",
	 *     "id": "int"
	 * }
	 *
	 * @return string (application/json)
	 */
	function postSettingsIssues($settings=false) {
		$uri = "Settings/Issues";
		$method = "post";
		return $this->processRequest($uri, $settings, $method);
	}

	/**
	 * getSettingsIssuesenabled
	 *
	 *
	 *
	 * @return string (application/json)
	 */
	function getSettingsIssuesenabled() {
		$uri = "Settings/issuesenabled";
		return $this->processRequest($uri);
	}

	/**
	 * postSettingsNotificationsEmail
	 * Saves the email notification settings.
	 *
	 * @param bool | array $model - (optional) The model.
	 * {
	 *     "notificationTemplates": "array - []",
	 *     "enabled": "bool",
	 *     "host": "string",
	 *     "password": "string",
	 *     "port": "int",
	 *     "senderName": "string",
	 *     "senderAddress": "string",
	 *     "username": "string",
	 *     "authentication": "bool",
	 *     "adminEmail": "string",
	 *     "disableTLS": "bool",
	 *     "disableCertificateChecking": "bool",
	 *     "id": "int"
	 * }
	 *
	 * @return string (application/json)
	 */
	function postSettingsNotificationsEmail($model=false) {
		$uri = "Settings/notifications/email";
		$method = "post";
		return $this->processRequest($uri, $model, $method);
	}

	/**
	 * getSettingsNotificationsEmailEnabled
	 * Gets the Email Notification Settings.
	 *
	 *
	 * @return string (application/json)
	 */
	function getSettingsNotificationsEmailEnabled() {
		$uri = "Settings/notifications/email/enabled";
		return $this->processRequest($uri);
	}

	/**
	 * postSettingsNotificationsDiscord
	 * Saves the discord notification settings.
	 *
	 * @param bool | array $model - (optional) The model.
	 * {
	 *     "notificationTemplates": "array - []",
	 *     "enabled": "bool",
	 *     "webhookUrl": "string",
	 *     "username": "string",
	 *     "id": "int"
	 * }
	 *
	 * @return string (application/json)
	 */
	function postSettingsNotificationsDiscord($model=false) {
		$uri = "Settings/notifications/discord";
		$method = "post";
		return $this->processRequest($uri, $model, $method);
	}

	/**
	 * postSettingsNotificationsTelegram
	 * Saves the telegram notification settings.
	 *
	 * @param bool | array $model - (optional) The model.
	 * {
	 *     "notificationTemplates": "array - []",
	 *     "enabled": "bool",
	 *     "botApi": "string",
	 *     "chatId": "string",
	 *     "parseMode": "string",
	 *     "id": "int"
	 * }
	 *
	 * @return string (application/json)
	 */
	function postSettingsNotificationsTelegram($model=false) {
		$uri = "Settings/notifications/telegram";
		$method = "post";
		return $this->processRequest($uri, $model, $method);
	}

	/**
	 * postSettingsNotificationsPushbullet
	 * Saves the pushbullet notification settings.
	 *
	 * @param bool | array $model - (optional) The model.
	 * {
	 *     "notificationTemplates": "array - []",
	 *     "enabled": "bool",
	 *     "accessToken": "string",
	 *     "channelTag": "string",
	 *     "id": "int"
	 * }
	 *
	 * @return string (application/json)
	 */
	function postSettingsNotificationsPushbullet($model=false) {
		$uri = "Settings/notifications/pushbullet";
		$method = "post";
		return $this->processRequest($uri, $model, $method);
	}

	/**
	 * postSettingsNotificationsPushover
	 * Saves the pushover notification settings.
	 *
	 * @param bool | array $model - (optional) The model.
	 * {
	 *     "notificationTemplates": "array - []",
	 *     "enabled": "bool",
	 *     "accessToken": "string",
	 *     "userToken": "string",
	 *     "priority": "int",
	 *     "sound": "string",
	 *     "id": "int"
	 * }
	 *
	 * @return string (application/json)
	 */
	function postSettingsNotificationsPushover($model=false) {
		$uri = "Settings/notifications/pushover";
		$method = "post";
		return $this->processRequest($uri, $model, $method);
	}

	/**
	 * postSettingsNotificationsSlack
	 * Saves the slack notification settings.
	 *
	 * @param bool | array $model - (optional) The model.
	 * {
	 *     "notificationTemplates": "array - []",
	 *     "enabled": "bool",
	 *     "webhookUrl": "string",
	 *     "channel": "string",
	 *     "username": "string",
	 *     "iconEmoji": "string",
	 *     "iconUrl": "string",
	 *     "id": "int"
	 * }
	 *
	 * @return string (application/json)
	 */
	function postSettingsNotificationsSlack($model=false) {
		$uri = "Settings/notifications/slack";
		$method = "post";
		return $this->processRequest($uri, $model, $method);
	}

	/**
	 * postSettingsNotificationsMattermost
	 * Saves the Mattermost notification settings.
	 *
	 * @param bool | array $model - (optional) The model.
	 * {
	 *     "notificationTemplates": "array - []",
	 *     "webhookUrl": "string",
	 *     "channel": "string",
	 *     "username": "string",
	 *     "iconUrl": "string",
	 *     "enabled": "bool",
	 *     "id": "int"
	 * }
	 *
	 * @return string (application/json)
	 */
	function postSettingsNotificationsMattermost($model=false) {
		$uri = "Settings/notifications/mattermost";
		$method = "post";
		return $this->processRequest($uri, $model, $method);
	}

	/**
	 * postSettingsNotificationsMobile
	 * Saves the Mobile notification settings.
	 *
	 * @param bool | array $model - (optional) The model.
	 * {
	 *     "notificationTemplates": "array - []",
	 *     "id": "int"
	 * }
	 *
	 * @return string (application/json)
	 */
	function postSettingsNotificationsMobile($model=false) {
		$uri = "Settings/notifications/mobile";
		$method = "post";
		return $this->processRequest($uri, $model, $method);
	}

	/**
	 * postSettingsNotificationsNewsletter
	 * Saves the Newsletter notification settings.
	 *
	 * @param bool | array $model - (optional) The model.
	 * {
	 *     "notificationTemplate": null,
	 *     "disableTv": "bool",
	 *     "disableMovies": "bool",
	 *     "disableMusic": "bool",
	 *     "enabled": "bool",
	 *     "externalEmails": "array - []",
	 *     "id": "int"
	 * }
	 *
	 * @return string (application/json)
	 */
	function postSettingsNotificationsNewsletter($model=false) {
		$uri = "Settings/notifications/newsletter";
		$method = "post";
		return $this->processRequest($uri, $model, $method);
	}

	/**
	 * postSonarrProfiles
	 * Gets the Sonarr profiles.
	 *
	 * @param bool | array $settings - (optional) The settings.
	 * {
	 *     "enabled": "bool",
	 *     "apiKey": "string",
	 *     "qualityProfile": "string",
	 *     "seasonFolders": "bool",
	 *     "rootPath": "string",
	 *     "qualityProfileAnime": "string",
	 *     "rootPathAnime": "string",
	 *     "addOnly": "bool",
	 *     "ssl": "bool",
	 *     "subDir": "string",
	 *     "ip": "string",
	 *     "port": "int",
	 *     "id": "int"
	 * }
	 *
	 * @return string (application/json)
	 */
	function postSonarrProfiles($settings=false) {
		$uri = "Sonarr/Profiles";
		$method = "post";
		return $this->processRequest($uri, $settings, $method);
	}

	/**
	 * postSonarrRootFolders
	 * Gets the Sonarr root folders.
	 *
	 * @param bool | array $settings - (optional) The settings.
	 * {
	 *     "enabled": "bool",
	 *     "apiKey": "string",
	 *     "qualityProfile": "string",
	 *     "seasonFolders": "bool",
	 *     "rootPath": "string",
	 *     "qualityProfileAnime": "string",
	 *     "rootPathAnime": "string",
	 *     "addOnly": "bool",
	 *     "ssl": "bool",
	 *     "subDir": "string",
	 *     "ip": "string",
	 *     "port": "int",
	 *     "id": "int"
	 * }
	 *
	 * @return string (application/json)
	 */
	function postSonarrRootFolders($settings=false) {
		$uri = "Sonarr/RootFolders";
		$method = "post";
		return $this->processRequest($uri, $settings, $method);
	}

	/**
	 * getStats
	 *
	 *
	 * @param bool | string $from - (optional)
	 * @param bool | string $to - (optional)
	 *
	 * @return string (application/json)
	 * {
	 *     "totalRequests": "int",
	 *     "totalMovieRequests": "int",
	 *     "totalTvRequests": "int",
	 *     "totalIssues": "int",
	 *     "completedRequestsMovies": "int",
	 *     "completedRequestsTv": "int",
	 *     "completedRequests": "int",
	 *     "mostRequestedUserMovie": null,
	 *     "mostRequestedUserTv": null
	 * }
	 */
	function getStats($from=false, $to=false) {
		$uri = "Stats?$from&$to";
		return $this->processRequest($uri);
	}

	/**
	 * getStatus
	 * Gets the status of Ombi.
	 *
	 *
	 * @return string (application/json)
	 */
	function getStatus() {
		$uri = "Status";
		return $this->processRequest($uri);
	}

	/**
	 * getStatusInfo
	 * Returns information about this ombi instance
	 *
	 *
	 * @return string (application/json)
	 */
	function getStatusInfo() {
		$uri = "Status/info";
		return $this->processRequest($uri);
	}

	/**
	 * postTesterDiscord
	 * Sends a test message to discord using the provided settings
	 *
	 * @param bool | array $settings - (optional) The settings.
	 * {
	 *     "enabled": "bool",
	 *     "webhookUrl": "string",
	 *     "username": "string",
	 *     "id": "int"
	 * }
	 *
	 * @return string (application/json)
	 */
	function postTesterDiscord($settings=false) {
		$uri = "Tester/discord";
		$method = "post";
		return $this->processRequest($uri, $settings, $method);
	}

	/**
	 * postTesterPushbullet
	 * Sends a test message to Pushbullet using the provided settings
	 *
	 * @param bool | array $settings - (optional) The settings.
	 * {
	 *     "enabled": "bool",
	 *     "accessToken": "string",
	 *     "channelTag": "string",
	 *     "id": "int"
	 * }
	 *
	 * @return string (application/json)
	 */
	function postTesterPushbullet($settings=false) {
		$uri = "Tester/pushbullet";
		$method = "post";
		return $this->processRequest($uri, $settings, $method);
	}

	/**
	 * postTesterPushover
	 * Sends a test message to Pushover using the provided settings
	 *
	 * @param bool | array $settings - (optional) The settings.
	 * {
	 *     "enabled": "bool",
	 *     "accessToken": "string",
	 *     "userToken": "string",
	 *     "priority": "int",
	 *     "sound": "string",
	 *     "id": "int"
	 * }
	 *
	 * @return string (application/json)
	 */
	function postTesterPushover($settings=false) {
		$uri = "Tester/pushover";
		$method = "post";
		return $this->processRequest($uri, $settings, $method);
	}

	/**
	 * postTesterMattermost
	 * Sends a test message to mattermost using the provided settings
	 *
	 * @param bool | array $settings - (optional) The settings.
	 * {
	 *     "webhookUrl": "string",
	 *     "channel": "string",
	 *     "username": "string",
	 *     "iconUrl": "string",
	 *     "enabled": "bool",
	 *     "id": "int"
	 * }
	 *
	 * @return string (application/json)
	 */
	function postTesterMattermost($settings=false) {
		$uri = "Tester/mattermost";
		$method = "post";
		return $this->processRequest($uri, $settings, $method);
	}

	/**
	 * postTesterSlack
	 * Sends a test message to Slack using the provided settings
	 *
	 * @param bool | array $settings - (optional) The settings.
	 * {
	 *     "enabled": "bool",
	 *     "webhookUrl": "string",
	 *     "channel": "string",
	 *     "username": "string",
	 *     "iconEmoji": "string",
	 *     "iconUrl": "string",
	 *     "id": "int"
	 * }
	 *
	 * @return string (application/json)
	 */
	function postTesterSlack($settings=false) {
		$uri = "Tester/slack";
		$method = "post";
		return $this->processRequest($uri, $settings, $method);
	}

	/**
	 * postTesterEmail
	 * Sends a test message via email to the admin email using the provided settings
	 *
	 * @param bool | array $settings - (optional) The settings.
	 * {
	 *     "enabled": "bool",
	 *     "host": "string",
	 *     "password": "string",
	 *     "port": "int",
	 *     "senderName": "string",
	 *     "senderAddress": "string",
	 *     "username": "string",
	 *     "authentication": "bool",
	 *     "adminEmail": "string",
	 *     "disableTLS": "bool",
	 *     "disableCertificateChecking": "bool",
	 *     "id": "int"
	 * }
	 *
	 * @return string (application/json)
	 */
	function postTesterEmail($settings=false) {
		$uri = "Tester/email";
		$method = "post";
		return $this->processRequest($uri, $settings, $method);
	}

	/**
	 * postTesterPlex
	 * Checks if we can connect to Plex with the provided settings
	 *
	 * @param bool | array $settings - (optional)
	 * {
	 *     "name": "string",
	 *     "plexAuthToken": "string",
	 *     "machineIdentifier": "string",
	 *     "episodeBatchSize": "int",
	 *     "plexSelectedLibraries": "array - []",
	 *     "ssl": "bool",
	 *     "subDir": "string",
	 *     "ip": "string",
	 *     "port": "int",
	 *     "id": "int"
	 * }
	 *
	 * @return string (application/json)
	 */
	function postTesterPlex($settings=false) {
		$uri = "Tester/plex";
		$method = "post";
		return $this->processRequest($uri, $settings, $method);
	}

	/**
	 * postTesterEmby
	 * Checks if we can connect to Emby with the provided settings
	 *
	 * @param bool | array $settings - (optional)
	 * {
	 *     "name": "string",
	 *     "apiKey": "string",
	 *     "administratorId": "string",
	 *     "enableEpisodeSearching": "bool",
	 *     "ssl": "bool",
	 *     "subDir": "string",
	 *     "ip": "string",
	 *     "port": "int",
	 *     "id": "int"
	 * }
	 *
	 * @return string (application/json)
	 */
	function postTesterEmby($settings=false) {
		$uri = "Tester/emby";
		$method = "post";
		return $this->processRequest($uri, $settings, $method);
	}

	/**
	 * postTesterRadarr
	 * Checks if we can connect to Radarr with the provided settings
	 *
	 * @param bool | array $settings - (optional)
	 * {
	 *     "enabled": "bool",
	 *     "apiKey": "string",
	 *     "defaultQualityProfile": "string",
	 *     "defaultRootPath": "string",
	 *     "addOnly": "bool",
	 *     "minimumAvailability": "string",
	 *     "ssl": "bool",
	 *     "subDir": "string",
	 *     "ip": "string",
	 *     "port": "int",
	 *     "id": "int"
	 * }
	 *
	 * @return string (application/json)
	 */
	function postTesterRadarr($settings=false) {
		$uri = "Tester/radarr";
		$method = "post";
		return $this->processRequest($uri, $settings, $method);
	}

	/**
	 * postTesterSonarr
	 * Checks if we can connect to Sonarr with the provided settings
	 *
	 * @param bool | array $settings - (optional)
	 * {
	 *     "enabled": "bool",
	 *     "apiKey": "string",
	 *     "qualityProfile": "string",
	 *     "seasonFolders": "bool",
	 *     "rootPath": "string",
	 *     "qualityProfileAnime": "string",
	 *     "rootPathAnime": "string",
	 *     "addOnly": "bool",
	 *     "ssl": "bool",
	 *     "subDir": "string",
	 *     "ip": "string",
	 *     "port": "int",
	 *     "id": "int"
	 * }
	 *
	 * @return string (application/json)
	 */
	function postTesterSonarr($settings=false) {
		$uri = "Tester/sonarr";
		$method = "post";
		return $this->processRequest($uri, $settings, $method);
	}

	/**
	 * postTesterCouchpotato
	 * Checks if we can connect to Sonarr with the provided settings
	 *
	 * @param bool | array $settings - (optional)
	 * {
	 *     "enabled": "bool",
	 *     "apiKey": "string",
	 *     "defaultProfileId": "string",
	 *     "username": "string",
	 *     "password": "string",
	 *     "ssl": "bool",
	 *     "subDir": "string",
	 *     "ip": "string",
	 *     "port": "int",
	 *     "id": "int"
	 * }
	 *
	 * @return string (application/json)
	 */
	function postTesterCouchpotato($settings=false) {
		$uri = "Tester/couchpotato";
		$method = "post";
		return $this->processRequest($uri, $settings, $method);
	}

	/**
	 * postTesterTelegram
	 * Sends a test message to Telegram using the provided settings
	 *
	 * @param bool | array $settings - (optional) The settings.
	 * {
	 *     "enabled": "bool",
	 *     "botApi": "string",
	 *     "chatId": "string",
	 *     "parseMode": "string",
	 *     "id": "int"
	 * }
	 *
	 * @return string (application/json)
	 */
	function postTesterTelegram($settings=false) {
		$uri = "Tester/telegram";
		$method = "post";
		return $this->processRequest($uri, $settings, $method);
	}

	/**
	 * postTesterSickrage
	 * Sends a test message to Slack using the provided settings
	 *
	 * @param bool | array $settings - (optional) The settings.
	 * {
	 *     "enabled": "bool",
	 *     "apiKey": "string",
	 *     "qualityProfile": "string",
	 *     "qualities": "array - []",
	 *     "ssl": "bool",
	 *     "subDir": "string",
	 *     "ip": "string",
	 *     "port": "int",
	 *     "id": "int"
	 * }
	 *
	 * @return string (application/json)
	 */
	function postTesterSickrage($settings=false) {
		$uri = "Tester/sickrage";
		$method = "post";
		return $this->processRequest($uri, $settings, $method);
	}

	/**
	 * postTesterNewsletter
	 *
	 *
	 * @param bool | array $settings - (optional)
	 * {
	 *     "notificationTemplate": null,
	 *     "disableTv": "bool",
	 *     "disableMovies": "bool",
	 *     "disableMusic": "bool",
	 *     "enabled": "bool",
	 *     "externalEmails": "array - []",
	 *     "id": "int"
	 * }
	 *
	 * @return string (application/json)
	 */
	function postTesterNewsletter($settings=false) {
		$uri = "Tester/newsletter";
		$method = "post";
		return $this->processRequest($uri, $settings, $method);
	}

	/**
	 * postTesterMobile
	 *
	 *
	 * @param bool | array $settings - (optional)
	 * {
	 *     "userId": "string",
	 *     "settings": null
	 * }
	 *
	 * @return string (application/json)
	 */
	function postTesterMobile($settings=false) {
		$uri = "Tester/mobile";
		$method = "post";
		return $this->processRequest($uri, $settings, $method);
	}

	/**
	 * postTesterLidarr
	 *
	 *
	 * @param bool | array $settings - (optional)
	 * {
	 *     "enabled": "bool",
	 *     "apiKey": "string",
	 *     "defaultQualityProfile": "string",
	 *     "defaultRootPath": "string",
	 *     "albumFolder": "bool",
	 *     "languageProfileId": "int",
	 *     "metadataProfileId": "int",
	 *     "addOnly": "bool",
	 *     "ssl": "bool",
	 *     "subDir": "string",
	 *     "ip": "string",
	 *     "port": "int",
	 *     "id": "int"
	 * }
	 *
	 * @return string (application/json)
	 */
	function postTesterLidarr($settings=false) {
		$uri = "Tester/lidarr";
		$method = "post";
		return $this->processRequest($uri, $settings, $method);
	}

	/**
	 * postToken
	 * Gets the token.
	 *
	 * @param bool | array $model - (optional) The model.
	 * {
	 *     "username": "string",
	 *     "password": "string",
	 *     "rememberMe": "bool",
	 *     "usePlexAdminAccount": "bool",
	 *     "usePlexOAuth": "bool",
	 *     "plexTvPin": null
	 * }
	 *
	 * @return string
	 */
	function postToken($model=false) {
		$uri = "Token";
		$method = "post";
		return $this->processRequest($uri, $model, $method);
	}

	/**
	 * getToken
	 *
	 *
	 * @param int $pinId - (required)
	 *
	 * @return string
	 */
	function getToken($pinId) {
		$uri = "Token/$pinId";
		return $this->processRequest($uri);
	}

	/**
	 * postTokenRefresh
	 * Refreshes the token.
	 *
	 * @param bool | array $token - (optional) The model.
	 * {
	 *     "token": "string",
	 *     "userename": "string"
	 * }
	 *
	 * @return string
	 */
	function postTokenRefresh($token=false) {
		$uri = "Token/refresh";
		$method = "post";
		return $this->processRequest($uri, $token, $method);
	}

	/**
	 * postTokenRequirePassword
	 *
	 *
	 * @param bool | array $model - (optional)
	 * {
	 *     "username": "string",
	 *     "password": "string",
	 *     "rememberMe": "bool",
	 *     "usePlexAdminAccount": "bool",
	 *     "usePlexOAuth": "bool",
	 *     "plexTvPin": null
	 * }
	 *
	 * @return string (application/json)
	 */
	function postTokenRequirePassword($model=false) {
		$uri = "Token/requirePassword";
		$method = "post";
		return $this->processRequest($uri, $model, $method);
	}

	/**
	 * getUpdate
	 *
	 *
	 * @param string $branch - (required)
	 *
	 * @return string (application/json)
	 * {
	 *     "updateVersionString": "string",
	 *     "updateVersion": "int",
	 *     "updateDate": "string",
	 *     "changeLogs": "array - []",
	 *     "downloads": "array - []"
	 * }
	 */
	function getUpdate($branch) {
		$uri = "Update/$branch";
		return $this->processRequest($uri);
	}

	/**
	 * Process requests with Guzzle
	 *
	 * @param string $uri
	 * @param string $type - Default is "get", also accepts post/put/delete
	 * @param bool | array $body - A JSON array of key/values to submit on POST/PUT
	 * @return bool|\Psr\Http\Message\ResponseInterface
	 */
	protected function _request(string $uri, $body, $type) {
		$client = new Client();
		$url = $this->url . "/api/v1/$uri";
		write_log("URL is $url");
		$options = [];
		$options['headers'] = ['apiKey' => $this->apiKey];
		if ($body) $options['json'] = $body;
		write_log("Options for $type: ".json_encode($options));
		switch ($type) {
			case "get":
				return $client->get($url, $options);
				break;
			case "post":
				return $client->post($url, $options);
				break;
			case "put":
				return $client->put($url, $options);
				break;
			case "delete":
				return $client->delete($url, $options);
				break;
		}
		return false;
	}

	/**
	 * Process requests, catch exceptions, return json response
	 *
	 * @param string $uri
	 * @param bool | array $body - A JSON array of key/values to submit on POST/PUT
	 * @param string $type
	 * @return string - A JSON response
	 */
	protected function processRequest($uri, $body = false, $type = "get") {
		try {
			$response = $this->_request($uri, $body, $type);
		} catch (\Exception $e) {
			return json_encode(['error' => array(
				'msg' => $e->getMessage(),
				'code' => $e->getCode())
			]);
		}
		return $response->getBody()->getContents();
	}


	protected function processRequests($uris) {
		$response = [];
		try {
			$response = $this->_multiRequest($uris);
		} catch (\Exception $e) {

		}
		return $response;
	}



	protected function _multiRequest($uris) {
		$responses = [];
		$options = [];
		$results = [];
		$options['headers'] = ['apiKey' => $this->apiKey];

		$base = $this->url . "/api/v1/";
		write_log("We have ". count($uris). " uris in request...");
		$newClient = new  \GuzzleHttp\Client(['base_uri' => $base]);
		$requestArr = [];
		foreach($uris as $name => $data){
			$uri = $data['uri'];
			$requestArr[$name] = $newClient->getAsync($uri, $options);
		}

		try {
			$responses = \GuzzleHttp\Promise\unwrap($requestArr);
		} catch (Throwable $e) {
			write_log("I threw up: $e","ERROR");
		}

		foreach($responses as $name=>$response) {
			$res = $response->getBody()->getContents();
			write_log("REs: ".json_encode($res));
			$results[$name] = $res;
		}
		return $results;
	}
}

