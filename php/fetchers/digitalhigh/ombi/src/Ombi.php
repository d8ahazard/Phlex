<?php

namespace digitalhigh\Headphones;

use GuzzleHttp\Client;

class Ombi {
	protected $url;
	protected $apiKey;

	public function __construct($url, $apiKey) {
		$this->url = rtrim($url, '/\\'); // Example: http://127.0.0.1:8989 (no trailing forward-backward slashes)
		$this->apiKey = $apiKey;
	}


	/**
	 * postCouchPotatoprofile
	 *
	 *
	 * @param  $settings - (optional) ({"enabled":"boolean","apiKey":"string","defaultProfileId":"string","username":"string","password":"string","ssl":"boolean","subDir":"string","ip":"string","port":"integer","id":"integer"})
	 *
	 * @return array
	 */
	function postCouchPotatoprofile($settings = false) {
		$uri = "CouchPotato/profile";
		$method = "post";
		return $this->processRequest($uri, $settings, $method);
	}

	/**
	 * postCouchPotatoapikey
	 *
	 *
	 * @param  $settings - (optional) ({"enabled":"boolean","apiKey":"string","defaultProfileId":"string","username":"string","password":"string","ssl":"boolean","subDir":"string","ip":"string","port":"integer","id":"integer"})
	 *
	 * @return array
	 */
	function postCouchPotatoapikey($settings = false) {
		$uri = "CouchPotato/apikey";
		$method = "post";
		return $this->processRequest($uri, $settings, $method);
	}

	/**
	 * postEmby
	 * Signs into the Emby Api
	 *
	 * @param  $request - (optional) The request.({"enable":"boolean","servers":"array","id":"integer"})
	 *
	 * @return array
	 */
	function postEmby($request = false) {
		$uri = "Emby";
		$method = "post";
		return $this->processRequest($uri, $request, $method);
	}

	/**
	 * getEmbyusers
	 * Gets the emby users.
	 *
	 *
	 * @return array
	 */
	function getEmbyusers() {
		$uri = "Emby/users";
		return $this->processRequest($uri);
	}

	/**
	 * getIdentityUsers
	 * Gets all users.
	 *
	 *
	 * @return array
	 */
	function getIdentityUsers() {
		$uri = "Identity/Users";
		return $this->processRequest($uri);
	}

	/**
	 * putIdentity
	 * Updates the user.
	 *
	 * @param  $ui - (optional) The user.({"id":"string","userName":"string","alias":"string","claims":"array","emailAddress":"string","password":"string","lastLoggedIn":"string","hasLoggedIn":"boolean","userType":"string","movieRequestLimit":"integer","episodeRequestLimit":"integer","episodeRequestQuota":null,"movieRequestQuota":null,"musicRequestQuota":null,"musicRequestLimit":"integer","userQualityProfiles":null})
	 *
	 * @return array
	 */
	function putIdentity($ui = false) {
		$uri = "Identity";
		$method = "put";
		return $this->processRequest($uri, $ui, $method);
	}

	/**
	 * getIdentityUser
	 * Gets the user by the user id.
	 *
	 * @param string $id - (required) ([])
	 *
	 * @return array
	 */
	function getIdentityUser($id) {
		$uri = "Identity/User/$id";
		return $this->processRequest($uri);
	}

	/**
	 * putIdentitylocal
	 * This is for the local user to change their details.
	 *
	 * @param  $ui - (optional) ({"currentPassword":"string","confirmNewPassword":"string","id":"string","userName":"string","alias":"string","claims":"array","emailAddress":"string","password":"string","lastLoggedIn":"string","hasLoggedIn":"boolean","userType":"string","movieRequestLimit":"integer","episodeRequestLimit":"integer","episodeRequestQuota":null,"movieRequestQuota":null,"musicRequestQuota":null,"musicRequestLimit":"integer","userQualityProfiles":null})
	 *
	 * @return array
	 */
	function putIdentitylocal($ui = false) {
		$uri = "Identity/local";
		$method = "put";
		return $this->processRequest($uri, $ui, $method);
	}

	/**
	 * deleteIdentity
	 * Deletes the user.
	 *
	 * @param string $userId - (required) The user.([])
	 *
	 * @return array
	 */
	function deleteIdentity($userId) {
		$uri = "Identity/$userId";
		$method = "delete";
		return $this->processRequest($uri, $userId, $method);
	}

	/**
	 * getIdentityclaims
	 * Gets all available claims in the system.
	 *
	 *
	 * @return array
	 */
	function getIdentityclaims() {
		$uri = "Identity/claims";
		return $this->processRequest($uri);
	}

	/**
	 * postIdentitywelcomeEmail
	 *
	 *
	 * @param  $user - (optional) ({"id":"string","userName":"string","alias":"string","claims":"array","emailAddress":"string","password":"string","lastLoggedIn":"string","hasLoggedIn":"boolean","userType":"string","movieRequestLimit":"integer","episodeRequestLimit":"integer","episodeRequestQuota":null,"movieRequestQuota":null,"musicRequestQuota":null,"musicRequestLimit":"integer","userQualityProfiles":null})
	 *
	 * @return array
	 */
	function postIdentitywelcomeEmail($user = false) {
		$uri = "Identity/welcomeEmail";
		$method = "post";
		return $this->processRequest($uri, $user, $method);
	}

	/**
	 * getIdentitynotificationpreferences
	 *
	 *
	 * @param bool | string $userId - (required) ([])
	 *
	 * @return array
	 */
	function getIdentitynotificationpreferences($userId = false) {
		if ($userId) {
			$uri = "Identity/notificationpreferences/$userId";
		} else {
			$uri = "Identity/notificationpreferences";
		}
		return $this->processRequest($uri);
	}

	/**
	 * postIdentityNotificationPreferences
	 *
	 *
	 * @param  $preferences - (optional) ([])
	 *
	 * @return array
	 */
	function postIdentityNotificationPreferences($preferences = false) {
		$uri = "Identity/NotificationPreferences";
		$method = "post";
		return $this->processRequest($uri, $preferences, $method);
	}

	/**
	 * getImagestv
	 *
	 *
	 * @param int $tvdbid - (required) ([])
	 *
	 * @return array
	 */
	function getImagestv($tvdbid) {
		$uri = "Images/tv/$tvdbid";
		return $this->processRequest($uri);
	}

	/**
	 * getImagespostermovie
	 *
	 *
	 * @param string $movieDbId - (required) ([])
	 *
	 * @return array
	 */
	function getImagespostermovie($movieDbId) {
		$uri = "Images/poster/movie/$movieDbId";
		return $this->processRequest($uri);
	}

	/**
	 * getImagespostertv
	 *
	 *
	 * @param int $tvdbid - (required) ([])
	 *
	 * @return array
	 */
	function getImagespostertv($tvdbid) {
		$uri = "Images/poster/tv/$tvdbid";
		return $this->processRequest($uri);
	}

	/**
	 * getImagesbackgroundmovie
	 *
	 *
	 * @param string $movieDbId - (required) ([])
	 *
	 * @return array
	 */
	function getImagesbackgroundmovie($movieDbId) {
		$uri = "Images/background/movie/$movieDbId";
		return $this->processRequest($uri);
	}

	/**
	 * getImagesbackgroundtv
	 *
	 *
	 * @param int $tvdbid - (required) ([])
	 *
	 * @return array
	 */
	function getImagesbackgroundtv($tvdbid) {
		$uri = "Images/background/tv/$tvdbid";
		return $this->processRequest($uri);
	}

	/**
	 * getImagesbackground
	 *
	 *
	 *
	 * @return array
	 */
	function getImagesbackground() {
		$uri = "Images/background";
		return $this->processRequest($uri);
	}

	/**
	 * postIssuescategories
	 * Creates a new category
	 *
	 * @param  $cat - (optional) ({"value":"string","id":"integer"})
	 *
	 * @return array
	 */
	function postIssuescategories($cat = false) {
		$uri = "Issues/categories";
		$method = "post";
		return $this->processRequest($uri, $cat, $method);
	}

	/**
	 * deleteIssuescategories
	 * Deletes a Category
	 *
	 * @param int $catId - (required) ([])
	 *
	 * @return array
	 */
	function deleteIssuescategories($catId) {
		$uri = "Issues/categories/$catId";
		$method = "delete";
		return $this->processRequest($uri, $catId, $method);
	}

	/**
	 * postIssues
	 * Create Movie Issue
	 *
	 * @param  $i - (optional) ({"title":"string","requestType":"string","providerId":"string","requestId":"integer","subject":"string","description":"string","issueCategoryId":"integer","issueCategory":null,"status":"string","resovledDate":"string","userReportedId":"string","userReported":null,"comments":"array","id":"integer"})
	 *
	 * @return array
	 */
	function postIssues($i = false) {
		$uri = "Issues";
		$method = "post";
		return $this->processRequest($uri, $i, $method);
	}

	/**
	 * getAllIssues
	 * Returns all the issues
	 *
	 * @param int $take - (required) ([])
	 * @param int $skip - (required) ([])
	 * @param string $status - (required) ([])
	 *
	 * @return array
	 */
	function getIssues($take, $skip, $status) {
		$uri = "Issues/$take/$skip/$status";
		return $this->processRequest($uri);
	}

	/**
	 * getIssuescount
	 * Returns all the issues count
	 *
	 *
	 * @return array
	 */
	function getIssuescount() {
		$uri = "Issues/count";
		return $this->processRequest($uri);
	}

	/**
	 * getIssue
	 * Returns the issue by Id
	 *
	 * @param int $id - (required) ([])
	 *
	 * @return array
	 */
	function getIssue($id) {
		$uri = "Issues/$id";
		return $this->processRequest($uri);
	}

	/**
	 * getIssuescomments
	 * Get's all the issue comments by id
	 *
	 * @param int $id - (required) ([])
	 *
	 * @return array
	 */
	function getIssuescomments($id) {
		$uri = "Issues/$id/comments";
		return $this->processRequest($uri);
	}

	/**
	 * postIssuescomments
	 * Adds a comment on an issue
	 *
	 * @param  $comment - (optional) ({"comment":"string","issueId":"integer"})
	 *
	 * @return array
	 */
	function postIssuescomments($comment = false) {
		$uri = "Issues/comments";
		$method = "post";
		return $this->processRequest($uri, $comment, $method);
	}

	/**
	 * deleteIssuescomments
	 * Deletes a comment on a issue
	 *
	 * @param int $id - (required) ([])
	 *
	 * @return array
	 */
	function deleteIssuescomments($id) {
		$uri = "Issues/comments/$id";
		$method = "delete";
		return $this->processRequest($uri, $id, $method);
	}

	/**
	 * postIssuesstatus
	 *
	 *
	 * @param  $model - (optional) ({"issueId":"integer","status":"string"})
	 *
	 * @return array
	 */
	function postIssuesstatus($model = false) {
		$uri = "Issues/status";
		$method = "post";
		return $this->processRequest($uri, $model, $method);
	}

	/**
	 * postJobupdate
	 * Runs the update job
	 *
	 *
	 * @return array
	 */
	function postJobupdate() {
		$uri = "Job/update";
		$method = "post";
		return $this->processRequest($uri, false, $method);
	}

	/**
	 * postJobplexuserimporter
	 * Runs the Plex User importer
	 *
	 *
	 * @return array
	 */
	function postJobplexuserimporter() {
		$uri = "Job/plexuserimporter";
		$method = "post";
		return $this->processRequest($uri, false, $method);
	}

	/**
	 * postJobembyuserimporter
	 * Runs the Emby User importer
	 *
	 *
	 * @return array
	 */
	function postJobembyuserimporter() {
		$uri = "Job/embyuserimporter";
		$method = "post";
		return $this->processRequest($uri, false, $method);
	}

	/**
	 * postJobplexcontentcacher
	 * Runs the Plex Content Cacher
	 *
	 *
	 * @return array
	 */
	function postJobplexcontentcacher() {
		$uri = "Job/plexcontentcacher";
		$method = "post";
		return $this->processRequest($uri, false, $method);
	}

	/**
	 * postJobplexrecentlyadded
	 * Runs a smaller version of the content cacher
	 *
	 *
	 * @return array
	 */
	function postJobplexrecentlyadded() {
		$uri = "Job/plexrecentlyadded";
		$method = "post";
		return $this->processRequest($uri, false, $method);
	}

	/**
	 * postJobembycontentcacher
	 * Runs the Emby Content Cacher
	 *
	 *
	 * @return array
	 */
	function postJobembycontentcacher() {
		$uri = "Job/embycontentcacher";
		$method = "post";
		return $this->processRequest($uri, false, $method);
	}

	/**
	 * postJobnewsletter
	 * Runs the newsletter
	 *
	 *
	 * @return array
	 */
	function postJobnewsletter() {
		$uri = "Job/newsletter";
		$method = "post";
		return $this->processRequest($uri, false, $method);
	}

	/**
	 * getLandingPage
	 *
	 *
	 *
	 * @return array
	 */
	function getLandingPage() {
		$uri = "LandingPage";
		return $this->processRequest($uri);
	}

	/**
	 * postLidarrProfiles
	 * Gets the Lidarr profiles.
	 *
	 * @param  $settings - (optional) The settings.({"enabled":"boolean","apiKey":"string","defaultQualityProfile":"string","defaultRootPath":"string","albumFolder":"boolean","languageProfileId":"integer","metadataProfileId":"integer","addOnly":"boolean","ssl":"boolean","subDir":"string","ip":"string","port":"integer","id":"integer"})
	 *
	 * @return array
	 */
	function postLidarrProfiles($settings = false) {
		$uri = "Lidarr/Profiles";
		$method = "post";
		return $this->processRequest($uri, $settings, $method);
	}

	/**
	 * postLidarrRootFolders
	 * Gets the Lidarr root folders.
	 *
	 * @param  $settings - (optional) The settings.({"enabled":"boolean","apiKey":"string","defaultQualityProfile":"string","defaultRootPath":"string","albumFolder":"boolean","languageProfileId":"integer","metadataProfileId":"integer","addOnly":"boolean","ssl":"boolean","subDir":"string","ip":"string","port":"integer","id":"integer"})
	 *
	 * @return array
	 */
	function postLidarrRootFolders($settings = false) {
		$uri = "Lidarr/RootFolders";
		$method = "post";
		return $this->processRequest($uri, $settings, $method);
	}

	/**
	 * postLidarrMetadata
	 * Gets the Lidarr metadata profiles.
	 *
	 * @param  $settings - (optional) The settings.({"enabled":"boolean","apiKey":"string","defaultQualityProfile":"string","defaultRootPath":"string","albumFolder":"boolean","languageProfileId":"integer","metadataProfileId":"integer","addOnly":"boolean","ssl":"boolean","subDir":"string","ip":"string","port":"integer","id":"integer"})
	 *
	 * @return array
	 */
	function postLidarrMetadata($settings = false) {
		$uri = "Lidarr/Metadata";
		$method = "post";
		return $this->processRequest($uri, $settings, $method);
	}

	/**
	 * postLidarrLangauges
	 * Gets the Lidarr Langauge profiles.
	 *
	 * @param  $settings - (optional) The settings.({"enabled":"boolean","apiKey":"string","defaultQualityProfile":"string","defaultRootPath":"string","albumFolder":"boolean","languageProfileId":"integer","metadataProfileId":"integer","addOnly":"boolean","ssl":"boolean","subDir":"string","ip":"string","port":"integer","id":"integer"})
	 *
	 * @return array
	 */
	function postLidarrLangauges($settings = false) {
		$uri = "Lidarr/Langauges";
		$method = "post";
		return $this->processRequest($uri, $settings, $method);
	}

	/**
	 * postLogging
	 *
	 *
	 * @param  $l - (optional) ({"level":"string","description":"string","id":"integer","location":"string","stackTrace":"string","dateTime":"string"})
	 *
	 * @return array
	 */
	function postLogging($l = false) {
		$uri = "Logging";
		$method = "post";
		return $this->processRequest($uri, $l, $method);
	}

	/**
	 * getrequestmusic
	 * Gets album requests.
	 *
	 * @param int $count - (required) The count of items you want to return.([])
	 * @param int $position - (required) The position.([])
	 * @param int $orderType - (required) The way we want to order.([])
	 * @param int $statusType - (required) ([])
	 * @param int $availabilityType - (required) ([])
	 *
	 * @return array
	 */
	function getrequestmusic($count, $position, $orderType, $statusType, $availabilityType) {
		$uri = "request/music/$count/$position/$orderType/$statusType/$availabilityType";
		return $this->processRequest($uri);
	}

	/**
	 * getrequestmusictotal
	 * Gets the total amount of album requests.
	 *
	 *
	 * @return array
	 */
	function getrequestmusictotal() {
		$uri = "request/music/total";
		return $this->processRequest($uri);
	}

	/**
	 * postrequestmusic
	 * Requests a album.
	 *
	 * @param  $album - (optional) The album.({"foreignAlbumId":"string"})
	 *
	 * @return array
	 */
	function postrequestmusic($album = false) {
		$uri = "request/music";
		$method = "post";
		return $this->processRequest($uri, $album, $method);
	}

	/**
	 * getrequestmusicsearch
	 * Searches for a specific album request
	 *
	 * @param string $searchTerm - (required) The search term.([])
	 *
	 * @return array
	 */
	function getrequestmusicsearch($searchTerm) {
		$uri = "request/music/search/$searchTerm";
		return $this->processRequest($uri);
	}

	/**
	 * deleterequestmusic
	 * Deletes the specified album request.
	 *
	 * @param int $requestId - (required) The request identifier.([])
	 *
	 * @return array
	 */
	function deleterequestmusic($requestId) {
		$uri = "request/music/$requestId";
		$method = "delete";
		return $this->processRequest($uri, $requestId, $method);
	}

	/**
	 * postrequestmusicapprove
	 * Approves the specified album request.
	 *
	 * @param  $model - (optional) The albums's ID({"id":"integer"})
	 *
	 * @return array
	 */
	function postrequestmusicapprove($model = false) {
		$uri = "request/music/approve";
		$method = "post";
		return $this->processRequest($uri, $model, $method);
	}

	/**
	 * postrequestmusicavailable
	 * Set's the specified album as available
	 *
	 * @param  $model - (optional) The album's ID({"id":"integer"})
	 *
	 * @return array
	 */
	function postrequestmusicavailable($model = false) {
		$uri = "request/music/available";
		$method = "post";
		return $this->processRequest($uri, $model, $method);
	}

	/**
	 * postrequestmusicunavailable
	 * Set's the specified album as unavailable
	 *
	 * @param  $model - (optional) The album's ID({"id":"integer"})
	 *
	 * @return array
	 */
	function postrequestmusicunavailable($model = false) {
		$uri = "request/music/unavailable";
		$method = "post";
		return $this->processRequest($uri, $model, $method);
	}

	/**
	 * putrequestmusicdeny
	 * Denies the specified album request.
	 *
	 * @param  $model - (optional) The album's ID({"id":"integer"})
	 *
	 * @return array
	 */
	function putrequestmusicdeny($model = false) {
		$uri = "request/music/deny";
		$method = "put";
		return $this->processRequest($uri, $model, $method);
	}

	/**
	 * getrequestmusicremaining
	 * Gets model containing remaining number of music requests.
	 *
	 *
	 * @return array
	 */
	function getrequestmusicremaining() {
		$uri = "request/music/remaining";
		return $this->processRequest($uri);
	}

	/**
	 * postNotificationsmassemail
	 *
	 *
	 * @param  $model - (optional) ({"subject":"string","body":"string","users":"array"})
	 *
	 * @return array
	 */
	function postNotificationsmassemail($model = false) {
		$uri = "Notifications/massemail";
		$method = "post";
		return $this->processRequest($uri, $model, $method);
	}

	/**
	 * postPlex
	 * Signs into the Plex API.
	 *
	 * @param  $request - (optional) The request.({"login":"string","password":"string"})
	 *
	 * @return array
	 */
	function postPlex($request = false) {
		$uri = "Plex";
		$method = "post";
		return $this->processRequest($uri, $request, $method);
	}

	/**
	 * postPlexLibraries
	 * Gets the plex libraries.
	 *
	 * @param  $settings - (optional) The settings.({"name":"string","plexAuthToken":"string","machineIdentifier":"string","episodeBatchSize":"integer","plexSelectedLibraries":"array","ssl":"boolean","subDir":"string","ip":"string","port":"integer","id":"integer"})
	 *
	 * @return array
	 */
	function postPlexLibraries($settings = false) {
		$uri = "Plex/Libraries";
		$method = "post";
		return $this->processRequest($uri, $settings, $method);
	}

	/**
	 * getPlexLibraries
	 *
	 *
	 * @param string $machineId - (required) ([])
	 *
	 * @return array
	 */
	function getPlexLibraries($machineId) {
		$uri = "Plex/Libraries/$machineId";
		return $this->processRequest($uri);
	}

	/**
	 * postPlexuser
	 *
	 *
	 * @param  $user - (optional) ({"username":"string","machineIdentifier":"string","libsSelected":"array"})
	 *
	 * @return array
	 */
	function postPlexuser($user = false) {
		$uri = "Plex/user";
		$method = "post";
		return $this->processRequest($uri, $user, $method);
	}

	/**
	 * postPlexservers
	 * Gets the plex servers.
	 *
	 * @param  $u - (optional) The u.({"login":"string","password":"string"})
	 *
	 * @return array
	 */
	function postPlexservers($u = false) {
		$uri = "Plex/servers";
		$method = "post";
		return $this->processRequest($uri, $u, $method);
	}

	/**
	 * getPlexfriends
	 * Gets the plex friends.
	 *
	 *
	 * @return array
	 */
	function getPlexfriends() {
		$uri = "Plex/friends";
		return $this->processRequest($uri);
	}

	/**
	 * postPlexoauth
	 *
	 *
	 * @param  $wizard - (optional) ({"wizard":"boolean","pin":null})
	 *
	 * @return array
	 */
	function postPlexoauth($wizard = false) {
		$uri = "Plex/oauth";
		$method = "post";
		return $this->processRequest($uri, $wizard, $method);
	}

	/**
	 * postRadarrProfiles
	 * Gets the Radarr profiles.
	 *
	 * @param  $settings - (optional) The settings.({"enabled":"boolean","apiKey":"string","defaultQualityProfile":"string","defaultRootPath":"string","addOnly":"boolean","minimumAvailability":"string","ssl":"boolean","subDir":"string","ip":"string","port":"integer","id":"integer"})
	 *
	 * @return array
	 */
	function postRadarrProfiles($settings = false) {
		$uri = "Radarr/Profiles";
		$method = "post";
		return $this->processRequest($uri, $settings, $method);
	}

	/**
	 * postRadarrRootFolders
	 * Gets the Radarr root folders.
	 *
	 * @param  $settings - (optional) The settings.({"enabled":"boolean","apiKey":"string","defaultQualityProfile":"string","defaultRootPath":"string","addOnly":"boolean","minimumAvailability":"string","ssl":"boolean","subDir":"string","ip":"string","port":"integer","id":"integer"})
	 *
	 * @return array
	 */
	function postRadarrRootFolders($settings = false) {
		$uri = "Radarr/RootFolders";
		$method = "post";
		return $this->processRequest($uri, $settings, $method);
	}

	/**
	 * getRecentlyAddedmovies
	 * Returns the recently added movies for the past 7 days
	 *
	 *
	 * @return array
	 */
	function getRecentlyAddedmovies() {
		$uri = "RecentlyAdded/movies";
		return $this->processRequest($uri);
	}

	/**
	 * getRecentlyAddedtv
	 * Returns the recently added tv shows for the past 7 days
	 *
	 *
	 * @return array
	 */
	function getRecentlyAddedtv() {
		$uri = "RecentlyAdded/tv";
		return $this->processRequest($uri);
	}

	/**
	 * getRecentlyAddedtvgrouped
	 * Returns the recently added tv shows for the past 7 days and groups them by season
	 *
	 *
	 * @return array
	 */
	function getRecentlyAddedtvgrouped() {
		$uri = "RecentlyAdded/tv/grouped";
		return $this->processRequest($uri);
	}

	/**
	 * getRequestmovie
	 * Gets movie requests.
	 *
	 * @param int $count - (required) The count of items you want to return.([])
	 * @param int $position - (required) The position.([])
	 * @param int $orderType - (required) The way we want to order.([])
	 * @param int $statusType - (required) ([])
	 * @param int $availabilityType - (required) ([])
	 *
	 * @return array
	 */
	function getRequestmovie($count, $position, $orderType, $statusType, $availabilityType) {
		$uri = "Request/movie/$count/$position/$orderType/$statusType/$availabilityType";
		return $this->processRequest($uri);
	}

	/**
	 * getRequestmovietotal
	 * Gets the total amount of movie requests.
	 *
	 *
	 * @return array
	 */
	function getRequestmovietotal() {
		$uri = "Request/movie/total";
		return $this->processRequest($uri);
	}

	/**
	 * putRequestmovie
	 * Updates the specified movie request.
	 *
	 * @param  $model - (optional) The Movie's ID({"theMovieDbId":"integer","issueId":"integer","issues":"array","subscribed":"boolean","showSubscribe":"boolean","rootPathOverride":"integer","qualityOverride":"integer","imdbId":"string","overview":"string","posterPath":"string","releaseDate":"string","digitalReleaseDate":"string","status":"string","background":"string","released":"boolean","digitalRelease":"boolean","title":"string","approved":"boolean","markedAsApproved":"string","requestedDate":"string","available":"boolean","markedAsAvailable":"string","requestedUserId":"string","denied":"boolean","markedAsDenied":"string","deniedReason":"string","requestType":"string","requestedUser":null,"canApprove":"boolean","id":"integer"})
	 *
	 * @return array
	 */
	function putRequestmovie($model = false) {
		$uri = "Request/movie";
		$method = "put";
		return $this->processRequest($uri, $model, $method);
	}

	/**
	 * getRequestmoviesearch
	 * Searches for a specific movie request
	 *
	 * @param string $searchTerm - (required) The search term.([])
	 *
	 * @return array
	 */
	function getRequestmoviesearch($searchTerm) {
		$uri = "Request/movie/search/$searchTerm";
		return $this->processRequest($uri);
	}

	/**
	 * deleteRequestmovie
	 * Deletes the specified movie request.
	 *
	 * @param int $requestId - (required) The request identifier.([])
	 *
	 * @return array
	 */
	function deleteRequestmovie($requestId) {
		$uri = "Request/movie/$requestId";
		$method = "delete";
		return $this->processRequest($uri, $requestId, $method);
	}

	/**
	 * postRequestmovieapprove
	 * Approves the specified movie request.
	 *
	 * @param  $model - (optional) The Movie's ID({"id":"integer"})
	 *
	 * @return array
	 */
	function postRequestmovieapprove($model = false) {
		$uri = "Request/movie/approve";
		$method = "post";
		return $this->processRequest($uri, $model, $method);
	}

	/**
	 * postRequestmovieavailable
	 * Set's the specified Movie as available
	 *
	 * @param  $model - (optional) The Movie's ID({"id":"integer"})
	 *
	 * @return array
	 */
	function postRequestmovieavailable($model = false) {
		$uri = "Request/movie/available";
		$method = "post";
		return $this->processRequest($uri, $model, $method);
	}

	/**
	 * postRequestmovieunavailable
	 * Set's the specified Movie as unavailable
	 *
	 * @param  $model - (optional) The Movie's ID({"id":"integer"})
	 *
	 * @return array
	 */
	function postRequestmovieunavailable($model = false) {
		$uri = "Request/movie/unavailable";
		$method = "post";
		return $this->processRequest($uri, $model, $method);
	}

	/**
	 * putRequestmoviedeny
	 * Denies the specified movie request.
	 *
	 * @param  $model - (optional) The Movie's ID({"id":"integer"})
	 *
	 * @return array
	 */
	function putRequestmoviedeny($model = false) {
		$uri = "Request/movie/deny";
		$method = "put";
		return $this->processRequest($uri, $model, $method);
	}

	/**
	 * getRequesttvtotal
	 * Gets the total amount of TV requests.
	 *
	 *
	 * @return array
	 */
	function getRequesttvtotal() {
		$uri = "Request/tv/total";
		return $this->processRequest($uri);
	}

	/**
	 * getRequesttv
	 * Gets the tv requests.
	 *
	 * @param int $count - (required) The count of items you want to return.([])
	 * @param int $position - (required) The position.([])
	 * @param int $orderType - (required) ([])
	 * @param bool | int $statusType - (optional) ([])
	 * @param bool | int $availabilityType - (optional) ([])
	 * @param string $statusFilterType - (required) ([])
	 * @param string $availabilityFilterType - (required) ([])
	 *
	 * @return array
	 */
	function getRequesttv($count, $position, $orderType, $statusFilterType, $availabilityFilterType, $statusType = false, $availabilityType = false) {
		$uri = "Request/tv/$count/$position/$orderType/$statusFilterType/$availabilityFilterType";
		if ($statusType) {
			$uri .= "?statusType=$statusType";
		}
		if ($availabilityType) {
			$uri .= "?availabilityType=$availabilityType";
		}
		return $this->processRequest($uri);
	}

	/**
	 * getRequesttvlite
	 * Gets the tv requests lite.
	 *
	 * @param int $count - (required) The count of items you want to return.([])
	 * @param int $position - (required) The position.([])
	 * @param int $orderType - (required) ([])
	 * @param bool | int $statusType - (optional) ([])
	 * @param bool | int $availabilityType - (optional) ([])
	 * @param string $statusFilterType - (required) ([])
	 * @param string $availabilityFilterType - (required) ([])
	 *
	 * @return array
	 */
	function getRequesttvlite($count, $position, $orderType, $statusFilterType, $availabilityFilterType, $statusType = false, $availabilityType = false) {
		$uri = "Request/tvlite/$count/$position/$orderType/$statusFilterType/$availabilityFilterType";
		if ($statusType) {
			$uri .= "?statusType=$statusType";
		}
		if ($availabilityType) {
			$uri .= "?availabilityType=$availabilityType";
		}

		return $this->processRequest($uri);
	}

	/**
	 * putRequesttv
	 * Updates the a specific tv request
	 *
	 * @param  $model - (optional) The model.({"tvDbId":"integer","imdbId":"string","qualityOverride":"integer","rootFolder":"integer","overview":"string","title":"string","posterPath":"string","background":"string","releaseDate":"string","status":"string","totalSeasons":"integer","childRequests":"array","id":"integer"})
	 *
	 * @return array
	 */
	function putRequesttv($model = false) {
		$uri = "Request/tv";
		$method = "put";
		return $this->processRequest($uri, $model, $method);
	}

	/**
	 * getRequesttvlite
	 * Gets the tv requests without the whole object graph (Does not include seasons/episodes).
	 *
	 *
	 * @return array
	 */
	function getRequeststvlite() {
		$uri = "Request/tvlite";
		return $this->processRequest($uri);
	}

	/**
	 * deleteRequesttv
	 * Deletes the a specific tv request
	 *
	 * @param int $requestId - (required) The request identifier.([])
	 *
	 * @return array
	 */
	function deleteRequesttv($requestId) {
		$uri = "Request/tv/$requestId";
		$method = "delete";
		return $this->processRequest($uri, $requestId, $method);
	}

	/**
	 * getRequesttvsearch
	 * Searches for a specific tv request
	 *
	 * @param string $searchTerm - (required) The search term.([])
	 *
	 * @return array
	 */
	function getRequesttvsearch($searchTerm) {
		$uri = "Request/tv/search/$searchTerm";
		return $this->processRequest($uri);
	}

	/**
	 * putRequesttvroot
	 * Updates the root path for this tv show
	 *
	 * @param int $requestId - (required) ([])
	 * @param int $rootFolderId - (required) ([])
	 *
	 * @return array
	 */
	function putRequesttvroot($requestId, $rootFolderId) {
		$uri = "Request/tv/root/$requestId/$rootFolderId";
		$method = "put";
		return $this->processRequest($uri, $requestId, $method);
	}

	/**
	 * putRequesttvquality
	 * Updates the quality profile for this tv show
	 *
	 * @param int $requestId - (required) ([])
	 * @param int $qualityId - (required) ([])
	 *
	 * @return array
	 */
	function putRequesttvquality($requestId, $qualityId) {
		$uri = "Request/tv/quality/$requestId/$qualityId";
		$method = "put";
		return $this->processRequest($uri, $requestId, $method);
	}

	/**
	 * putRequesttvchild
	 * Updates the a specific child request
	 *
	 * @param  $child - (optional) The model.({"parentRequest":null,"parentRequestId":"integer","issueId":"integer","seriesType":"string","subscribed":"boolean","showSubscribe":"boolean","issues":"array","seasonRequests":"array","title":"string","approved":"boolean","markedAsApproved":"string","requestedDate":"string","available":"boolean","markedAsAvailable":"string","requestedUserId":"string","denied":"boolean","markedAsDenied":"string","deniedReason":"string","requestType":"string","requestedUser":null,"canApprove":"boolean","id":"integer"})
	 *
	 * @return array
	 */
	function putRequesttvchild($child = false) {
		$uri = "Request/tv/child";
		$method = "put";
		return $this->processRequest($uri, $child, $method);
	}

	/**
	 * putRequesttvdeny
	 * Denies the a specific child request
	 *
	 * @param  $model - (optional) This is the child request's ID({"id":"integer"})
	 *
	 * @return array
	 */
	function putRequesttvdeny($model = false) {
		$uri = "Request/tv/deny";
		$method = "put";
		return $this->processRequest($uri, $model, $method);
	}

	/**
	 * postRequesttvavailable
	 * Set's the specified tv child as available
	 *
	 * @param  $model - (optional) The Movie's ID({"id":"integer"})
	 *
	 * @return array
	 */
	function postRequesttvavailable($model = false) {
		$uri = "Request/tv/available";
		$method = "post";
		return $this->processRequest($uri, $model, $method);
	}

	/**
	 * postRequesttvunavailable
	 * Set's the specified tv child as unavailable
	 *
	 * @param  $model - (optional) The Movie's ID({"id":"integer"})
	 *
	 * @return array
	 */
	function postRequesttvunavailable($model = false) {
		$uri = "Request/tv/unavailable";
		$method = "post";
		return $this->processRequest($uri, $model, $method);
	}

	/**
	 * postRequesttvapprove
	 * Updates the a specific child request
	 *
	 * @param  $model - (optional) This is the child request's ID({"id":"integer"})
	 *
	 * @return array
	 */
	function postRequesttvapprove($model = false) {
		$uri = "Request/tv/approve";
		$method = "post";
		return $this->processRequest($uri, $model, $method);
	}

	/**
	 * deleteRequesttvchild
	 * Deletes the a specific tv request
	 *
	 * @param int $requestId - (required) The model.([])
	 *
	 * @return array
	 */
	function deleteRequesttvchild($requestId) {
		$uri = "Request/tv/child/$requestId";
		$method = "delete";
		return $this->processRequest($uri, $requestId, $method);
	}

	/**
	 * getRequesttvchild
	 * Retuns all children requests for the request id
	 *
	 * @param int $requestId - (required) The Request Id([])
	 *
	 * @return array
	 */
	function getRequesttvchild($requestId) {
		$uri = "Request/tv/$requestId/child";
		return $this->processRequest($uri);
	}

	/**
	 * getRequestcount
	 * Gets the count of total requests
	 *
	 *
	 * @return array
	 */
	function getRequestcount() {
		$uri = "Request/count";
		return $this->processRequest($uri);
	}

	/**
	 * getRequestuserhasrequest
	 * Checks if the passed in user has a request
	 *
	 * @param string $userId
	 *
	 * @return array
	 */
	function getRequestuserhasrequest($userId) {
		$uri = "Request/userhasrequest?userId=$userId";
		return $this->processRequest($uri);
	}

	/**
	 * postRequestmoviesubscribe
	 * Subscribes for notifications to a movie request
	 *
	 * @param int $requestId - (required) ([])
	 *
	 * @return array
	 */
	function postRequestmoviesubscribe($requestId) {
		$uri = "Request/movie/subscribe/$requestId";
		$method = "post";
		return $this->processRequest($uri, $requestId, $method);
	}

	/**
	 * postRequesttvsubscribe
	 * Subscribes for notifications to a TV request
	 *
	 * @param int $requestId - (required) ([])
	 *
	 * @return array
	 */
	function postRequesttvsubscribe($requestId) {
		$uri = "Request/tv/subscribe/$requestId";
		$method = "post";
		return $this->processRequest($uri, $requestId, $method);
	}

	/**
	 * postRequestmovieunsubscribe
	 * UnSubscribes for notifications to a movie request
	 *
	 * @param int $requestId - (required) ([])
	 *
	 * @return array
	 */
	function postRequestmovieunsubscribe($requestId) {
		$uri = "Request/movie/unsubscribe/$requestId";
		$method = "post";
		return $this->processRequest($uri, $requestId, $method);
	}

	/**
	 * postRequesttvunsubscribe
	 * UnSubscribes for notifications to a TV request
	 *
	 * @param int $requestId - (required) ([])
	 *
	 * @return array
	 */
	function postRequesttvunsubscribe($requestId) {
		$uri = "Request/tv/unsubscribe/$requestId";
		$method = "post";
		return $this->processRequest($uri, $requestId, $method);
	}

	/**
	 * getRequestmovieremaining
	 * Gets model containing remaining number of movie requests.
	 *
	 *
	 * @return array
	 */
	function getRequestmovieremaining() {
		$uri = "Request/movie/remaining";
		return $this->processRequest($uri);
	}

	/**
	 * getRequesttvremaining
	 * Gets model containing remaining number of tv requests.
	 *
	 *
	 * @return array
	 */
	function getRequesttvremaining() {
		$uri = "Request/tv/remaining";
		return $this->processRequest($uri);
	}

	/**
	 * getSearchmovie
	 * Searches for a movie.
	 *
	 * @param string $searchTerm - (required) The search term.([])
	 *
	 * @return array
	 */
	function getSearchmovie($searchTerm) {
		$uri = "Search/movie/$searchTerm";
		return $this->processRequest($uri);
	}

	/**
	 * getSearchmovieinfo
	 * Gets extra information on the movie e.g. IMDBId
	 *
	 * @param int $theMovieDbId - (required) The movie database identifier.([])
	 *
	 * @return array
	 */
	function getSearchmovieinfo($theMovieDbId) {
		$uri = "Search/movie/info/$theMovieDbId";
		return $this->processRequest($uri);
	}

	/**
	 * getSearchmoviesimilar
	 * Returns similar movies to the movie id passed in
	 *
	 * @param int $theMovieDbId - (required) ID of the movie([])
	 *
	 * @return array
	 */
	function getSearchmoviesimilar($theMovieDbId) {
		$uri = "Search/movie/$theMovieDbId/similar";
		return $this->processRequest($uri);
	}

	/**
	 * getSearchmoviepopular
	 * Returns Popular Movies
	 *
	 *
	 * @return array
	 */
	function getSearchmoviepopular() {
		$uri = "Search/movie/popular";
		return $this->processRequest($uri);
	}

	/**
	 * getSearchmovienowplaying
	 * Retuns Now Playing Movies
	 *
	 *
	 * @return array
	 */
	function getSearchmovienowplaying() {
		$uri = "Search/movie/nowplaying";
		return $this->processRequest($uri);
	}

	/**
	 * getSearchmovietoprated
	 * Returns top rated movies.
	 *
	 *
	 * @return array
	 */
	function getSearchmovietoprated() {
		$uri = "Search/movie/toprated";
		return $this->processRequest($uri);
	}

	/**
	 * getSearchmovieupcoming
	 * Returns Upcoming movies.
	 *
	 *
	 * @return array
	 */
	function getSearchmovieupcoming() {
		$uri = "Search/movie/upcoming";
		return $this->processRequest($uri);
	}

	/**
	 * getSearchtv
	 * Searches for a Tv Show.
	 *
	 * @param string $searchTerm - (required) The search term.([])
	 *
	 * @return array
	 */
	function getSearchtv($searchTerm) {
		$uri = "Search/tv/$searchTerm";
		return $this->processRequest($uri);
	}

	/**
	 * getSearchtvinfo
	 * Gets extra show information.
	 *
	 * @param int $tvdbId - (required) The TVDB identifier.([])
	 *
	 * @return array
	 */
	function getSearchtvinfo($tvdbId) {
		$uri = "Search/tv/info/$tvdbId";
		return $this->processRequest($uri);
	}

	/**
	 * getSearchtvpopular
	 * Returns Popular Tv Shows
	 *
	 *
	 * @return array
	 */
	function getSearchtvpopular() {
		$uri = "Search/tv/popular";
		return $this->processRequest($uri);
	}

	/**
	 * getSearchtvanticipated
	 * Returns most Anticiplateds tv shows.
	 *
	 *
	 * @return array
	 */
	function getSearchtvanticipated() {
		$uri = "Search/tv/anticipated";
		return $this->processRequest($uri);
	}

	/**
	 * getSearchtvmostwatched
	 * Returns Most watched shows.
	 *
	 *
	 * @return array
	 */
	function getSearchtvmostwatched() {
		$uri = "Search/tv/mostwatched";
		return $this->processRequest($uri);
	}

	/**
	 * getSearchtvtrending
	 * Returns trending shows
	 *
	 *
	 * @return array
	 */
	function getSearchtvtrending() {
		$uri = "Search/tv/trending";
		return $this->processRequest($uri);
	}

	/**
	 * getSearchmusicartist
	 * Returns the artist information we searched for
	 *
	 * @param string $searchTerm - (required) ([])
	 *
	 * @return array
	 */
	function getSearchmusicartist($searchTerm) {
		$uri = "Search/music/artist/$searchTerm";
		return $this->processRequest($uri);
	}

	/**
	 * getSearchmusicalbum
	 * Returns the album information we searched for
	 *
	 * @param string $searchTerm - (required) ([])
	 *
	 * @return array
	 */
	function getSearchmusicalbum($searchTerm) {
		$uri = "Search/music/album/$searchTerm";
		return $this->processRequest($uri);
	}

	/**
	 * getSearchmusicartistalbum
	 * Returns all albums for the artist using the ForeignArtistId
	 *
	 * @param string $foreignArtistId - (required) ([])
	 *
	 * @return array
	 */
	function getSearchmusicartistalbum($foreignArtistId) {
		$uri = "Search/music/artist/album/$foreignArtistId";
		return $this->processRequest($uri);
	}

	/**
	 * postSettingsombi
	 * Save the Ombi settings.
	 *
	 * @param  $ombi - (optional) The ombi.({"baseUrl":"string","collectAnalyticData":"boolean","wizard":"boolean","apiKey":"string","ignoreCertificateErrors":"boolean","doNotSendNotificationsForAutoApprove":"boolean","hideRequestsUsers":"boolean","id":"integer"})
	 *
	 * @return array
	 */
	function postSettingsombi($ombi = false) {
		$uri = "Settings/ombi";
		$method = "post";
		return $this->processRequest($uri, $ombi, $method);
	}

	/**
	 * getSettingsabout
	 *
	 *
	 *
	 * @return array
	 */
	function getSettingsabout() {
		$uri = "Settings/about";
		return $this->processRequest($uri);
	}

	/**
	 * postSettingsombiresetApi
	 *
	 *
	 *
	 * @return array
	 */
	function postSettingsombiresetApi() {
		$uri = "Settings/ombi/resetApi";
		$method = "post";
		return $this->processRequest($uri, false, $method);
	}

	/**
	 * postSettingsplex
	 * Save the Plex settings.
	 *
	 * @param  $plex - (optional) The plex.({"enable":"boolean","installId":"string","servers":"array","id":"integer"})
	 *
	 * @return array
	 */
	function postSettingsplex($plex = false) {
		$uri = "Settings/plex";
		$method = "post";
		return $this->processRequest($uri, $plex, $method);
	}

	/**
	 * getSettingsclientid
	 *
	 *
	 *
	 * @return array
	 */
	function getSettingsclientid() {
		$uri = "Settings/clientid";
		return $this->processRequest($uri);
	}

	/**
	 * postSettingsemby
	 * Save the Emby settings.
	 *
	 * @param  $emby - (optional) The emby.({"enable":"boolean","servers":"array","id":"integer"})
	 *
	 * @return array
	 */
	function postSettingsemby($emby = false) {
		$uri = "Settings/emby";
		$method = "post";
		return $this->processRequest($uri, $emby, $method);
	}

	/**
	 * postSettingslandingpage
	 * Save the Landing Page settings.
	 *
	 * @param  $settings - (optional) The settings.({"enabled":"boolean","noticeEnabled":"boolean","noticeText":"string","timeLimit":"boolean","startDateTime":"string","endDateTime":"string","expired":"boolean","id":"integer"})
	 *
	 * @return array
	 */
	function postSettingslandingpage($settings = false) {
		$uri = "Settings/landingpage";
		$method = "post";
		return $this->processRequest($uri, $settings, $method);
	}

	/**
	 * postSettingscustomization
	 * Save the Customization settings.
	 *
	 * @param  $settings - (optional) The settings.({"applicationName":"string","applicationUrl":"string","customCssLink":"string","enableCustomDonations":"boolean","customDonationUrl":"string","customDonationMessage":"string","logo":"string","presetThemeName":"string","presetThemeContent":"string","recentlyAddedPage":"boolean","presetThemeVersion":"string","presetThemeDisplayName":"string","hasPresetTheme":"boolean","id":"integer"})
	 *
	 * @return array
	 */
	function postSettingscustomization($settings = false) {
		$uri = "Settings/customization";
		$method = "post";
		return $this->processRequest($uri, $settings, $method);
	}

	/**
	 * getSettingsthemes
	 * Get's the preset themes available
	 *
	 *
	 * @return array
	 */
	function getSettingsthemes() {
		$uri = "Settings/themes";
		return $this->processRequest($uri);
	}

	/**
	 * getSettingsthemecontent
	 * Gets the content of the theme available
	 *
	 * @param string $url
	 *
	 * @return array
	 */
	function getSettingsthemecontent($url) {
		$uri = "Settings/themecontent?url=$url";
		return $this->processRequest($uri);
	}

	/**
	 * postSettingssonarr
	 * Save the Sonarr settings.
	 *
	 * @param  $settings - (optional) The settings.({"enabled":"boolean","apiKey":"string","qualityProfile":"string","seasonFolders":"boolean","rootPath":"string","qualityProfileAnime":"string","rootPathAnime":"string","addOnly":"boolean","ssl":"boolean","subDir":"string","ip":"string","port":"integer","id":"integer"})
	 *
	 * @return array
	 */
	function postSettingssonarr($settings = false) {
		$uri = "Settings/sonarr";
		$method = "post";
		return $this->processRequest($uri, $settings, $method);
	}

	/**
	 * postSettingsradarr
	 * Save the Radarr settings.
	 *
	 * @param  $settings - (optional) The settings.({"enabled":"boolean","apiKey":"string","defaultQualityProfile":"string","defaultRootPath":"string","addOnly":"boolean","minimumAvailability":"string","ssl":"boolean","subDir":"string","ip":"string","port":"integer","id":"integer"})
	 *
	 * @return array
	 */
	function postSettingsradarr($settings = false) {
		$uri = "Settings/radarr";
		$method = "post";
		return $this->processRequest($uri, $settings, $method);
	}

	/**
	 * postSettingslidarr
	 * Save the Lidarr settings.
	 *
	 * @param  $settings - (optional) The settings.({"enabled":"boolean","apiKey":"string","defaultQualityProfile":"string","defaultRootPath":"string","albumFolder":"boolean","languageProfileId":"integer","metadataProfileId":"integer","addOnly":"boolean","ssl":"boolean","subDir":"string","ip":"string","port":"integer","id":"integer"})
	 *
	 * @return array
	 */
	function postSettingslidarr($settings = false) {
		$uri = "Settings/lidarr";
		$method = "post";
		return $this->processRequest($uri, $settings, $method);
	}

	/**
	 * getSettingslidarrenabled
	 * Gets the Lidarr Settings.
	 *
	 *
	 * @return array
	 */
	function getSettingslidarrenabled() {
		$uri = "Settings/lidarrenabled";
		return $this->processRequest($uri);
	}

	/**
	 * postSettingsauthentication
	 * Save the Authentication settings.
	 *
	 * @param  $settings - (optional) The settings.({"allowNoPassword":"boolean","requireDigit":"boolean","requiredLength":"integer","requireLowercase":"boolean","requireNonAlphanumeric":"boolean","requireUppercase":"boolean","enableOAuth":"boolean","id":"integer"})
	 *
	 * @return array
	 */
	function postSettingsauthentication($settings = false) {
		$uri = "Settings/authentication";
		$method = "post";
		return $this->processRequest($uri, $settings, $method);
	}

	/**
	 * postSettingsUpdate
	 * Save the Update settings.
	 *
	 * @param  $settings - (optional) The settings.({"autoUpdateEnabled":"boolean","username":"string","password":"string","processName":"string","useScript":"boolean","scriptLocation":"string","windowsServiceName":"string","windowsService":"boolean","testMode":"boolean","id":"integer"})
	 *
	 * @return array
	 */
	function postSettingsUpdate($settings = false) {
		$uri = "Settings/Update";
		$method = "post";
		return $this->processRequest($uri, $settings, $method);
	}

	/**
	 * postSettingsUserManagement
	 * Save the UserManagement settings.
	 *
	 * @param  $settings - (optional) The settings.({"importPlexAdmin":"boolean","importPlexUsers":"boolean","importEmbyUsers":"boolean","movieRequestLimit":"integer","episodeRequestLimit":"integer","defaultRoles":"array","bannedPlexUserIds":"array","bannedEmbyUserIds":"array","id":"integer"})
	 *
	 * @return array
	 */
	function postSettingsUserManagement($settings = false) {
		$uri = "Settings/UserManagement";
		$method = "post";
		return $this->processRequest($uri, $settings, $method);
	}

	/**
	 * postSettingsCouchPotato
	 * Save the CouchPotatoSettings settings.
	 *
	 * @param  $settings - (optional) The settings.({"enabled":"boolean","apiKey":"string","defaultProfileId":"string","username":"string","password":"string","ssl":"boolean","subDir":"string","ip":"string","port":"integer","id":"integer"})
	 *
	 * @return array
	 */
	function postSettingsCouchPotato($settings = false) {
		$uri = "Settings/CouchPotato";
		$method = "post";
		return $this->processRequest($uri, $settings, $method);
	}

	/**
	 * postSettingsDogNzb
	 * Save the DogNzbSettings settings.
	 *
	 * @param  $settings - (optional) The settings.({"enabled":"boolean","apiKey":"string","movies":"boolean","tvShows":"boolean","id":"integer"})
	 *
	 * @return array
	 */
	function postSettingsDogNzb($settings = false) {
		$uri = "Settings/DogNzb";
		$method = "post";
		return $this->processRequest($uri, $settings, $method);
	}

	/**
	 * postSettingsSickRage
	 * Save the SickRage settings.
	 *
	 * @param  $settings - (optional) The settings.({"enabled":"boolean","apiKey":"string","qualityProfile":"string","qualities":"array","ssl":"boolean","subDir":"string","ip":"string","port":"integer","id":"integer"})
	 *
	 * @return array
	 */
	function postSettingsSickRage($settings = false) {
		$uri = "Settings/SickRage";
		$method = "post";
		return $this->processRequest($uri, $settings, $method);
	}

	/**
	 * postSettingsjobs
	 * Save the JobSettings settings.
	 *
	 * @param  $settings - (optional) The settings.({"embyContentSync":"string","sonarrSync":"string","radarrSync":"string","plexContentSync":"string","plexRecentlyAddedSync":"string","couchPotatoSync":"string","automaticUpdater":"string","userImporter":"string","sickRageSync":"string","refreshMetadata":"string","newsletter":"string","lidarrArtistSync":"string","id":"integer"})
	 *
	 * @return array
	 */
	function postSettingsjobs($settings = false) {
		$uri = "Settings/jobs";
		$method = "post";
		return $this->processRequest($uri, $settings, $method);
	}

	/**
	 * postSettingstestcron
	 *
	 *
	 * @param  $body - (optional) ({"expression":"string"})
	 *
	 * @return array
	 */
	function postSettingstestcron($body = false) {
		$uri = "Settings/testcron";
		$method = "post";
		return $this->processRequest($uri, $body, $method);
	}

	/**
	 * postSettingsIssues
	 * Save the Issues settings.
	 *
	 * @param  $settings - (optional) The settings.({"enabled":"boolean","enableInProgress":"boolean","id":"integer"})
	 *
	 * @return array
	 */
	function postSettingsIssues($settings = false) {
		$uri = "Settings/Issues";
		$method = "post";
		return $this->processRequest($uri, $settings, $method);
	}

	/**
	 * getSettingsissuesenabled
	 *
	 *
	 *
	 * @return array
	 */
	function getSettingsissuesenabled() {
		$uri = "Settings/issuesenabled";
		return $this->processRequest($uri);
	}

	/**
	 * postSettingsnotificationsemail
	 * Saves the email notification settings.
	 *
	 * @param  $model - (optional) The model.({"notificationTemplates":"array","enabled":"boolean","host":"string","password":"string","port":"integer","senderName":"string","senderAddress":"string","username":"string","authentication":"boolean","adminEmail":"string","disableTLS":"boolean","disableCertificateChecking":"boolean","id":"integer"})
	 *
	 * @return array
	 */
	function postSettingsnotificationsemail($model = false) {
		$uri = "Settings/notifications/email";
		$method = "post";
		return $this->processRequest($uri, $model, $method);
	}

	/**
	 * getSettingsnotificationsemailenabled
	 * Gets the Email Notification Settings.
	 *
	 *
	 * @return array
	 */
	function getSettingsnotificationsemailenabled() {
		$uri = "Settings/notifications/email/enabled";
		return $this->processRequest($uri);
	}

	/**
	 * postSettingsnotificationsdiscord
	 * Saves the discord notification settings.
	 *
	 * @param  $model - (optional) The model.({"notificationTemplates":"array","enabled":"boolean","webhookUrl":"string","username":"string","id":"integer"})
	 *
	 * @return array
	 */
	function postSettingsnotificationsdiscord($model = false) {
		$uri = "Settings/notifications/discord";
		$method = "post";
		return $this->processRequest($uri, $model, $method);
	}

	/**
	 * postSettingsnotificationstelegram
	 * Saves the telegram notification settings.
	 *
	 * @param  $model - (optional) The model.({"notificationTemplates":"array","enabled":"boolean","botApi":"string","chatId":"string","parseMode":"string","id":"integer"})
	 *
	 * @return array
	 */
	function postSettingsnotificationstelegram($model = false) {
		$uri = "Settings/notifications/telegram";
		$method = "post";
		return $this->processRequest($uri, $model, $method);
	}

	/**
	 * postSettingsnotificationspushbullet
	 * Saves the pushbullet notification settings.
	 *
	 * @param  $model - (optional) The model.({"notificationTemplates":"array","enabled":"boolean","accessToken":"string","channelTag":"string","id":"integer"})
	 *
	 * @return array
	 */
	function postSettingsnotificationspushbullet($model = false) {
		$uri = "Settings/notifications/pushbullet";
		$method = "post";
		return $this->processRequest($uri, $model, $method);
	}

	/**
	 * postSettingsnotificationspushover
	 * Saves the pushover notification settings.
	 *
	 * @param  $model - (optional) The model.({"notificationTemplates":"array","enabled":"boolean","accessToken":"string","userToken":"string","priority":"integer","sound":"string","id":"integer"})
	 *
	 * @return array
	 */
	function postSettingsnotificationspushover($model = false) {
		$uri = "Settings/notifications/pushover";
		$method = "post";
		return $this->processRequest($uri, $model, $method);
	}

	/**
	 * postSettingsnotificationsslack
	 * Saves the slack notification settings.
	 *
	 * @param  $model - (optional) The model.({"notificationTemplates":"array","enabled":"boolean","webhookUrl":"string","channel":"string","username":"string","iconEmoji":"string","iconUrl":"string","id":"integer"})
	 *
	 * @return array
	 */
	function postSettingsnotificationsslack($model = false) {
		$uri = "Settings/notifications/slack";
		$method = "post";
		return $this->processRequest($uri, $model, $method);
	}

	/**
	 * postSettingsnotificationsmattermost
	 * Saves the Mattermost notification settings.
	 *
	 * @param  $model - (optional) The model.({"notificationTemplates":"array","webhookUrl":"string","channel":"string","username":"string","iconUrl":"string","enabled":"boolean","id":"integer"})
	 *
	 * @return array
	 */
	function postSettingsnotificationsmattermost($model = false) {
		$uri = "Settings/notifications/mattermost";
		$method = "post";
		return $this->processRequest($uri, $model, $method);
	}

	/**
	 * postSettingsnotificationsmobile
	 * Saves the Mobile notification settings.
	 *
	 * @param  $model - (optional) The model.({"notificationTemplates":"array","id":"integer"})
	 *
	 * @return array
	 */
	function postSettingsnotificationsmobile($model = false) {
		$uri = "Settings/notifications/mobile";
		$method = "post";
		return $this->processRequest($uri, $model, $method);
	}

	/**
	 * postSettingsnotificationsnewsletter
	 * Saves the Newsletter notification settings.
	 *
	 * @param  $model - (optional) The model.({"notificationTemplate":null,"disableTv":"boolean","disableMovies":"boolean","disableMusic":"boolean","enabled":"boolean","externalEmails":"array","id":"integer"})
	 *
	 * @return array
	 */
	function postSettingsnotificationsnewsletter($model = false) {
		$uri = "Settings/notifications/newsletter";
		$method = "post";
		return $this->processRequest($uri, $model, $method);
	}

	/**
	 * postSonarrProfiles
	 * Gets the Sonarr profiles.
	 *
	 * @param  $settings - (optional) The settings.({"enabled":"boolean","apiKey":"string","qualityProfile":"string","seasonFolders":"boolean","rootPath":"string","qualityProfileAnime":"string","rootPathAnime":"string","addOnly":"boolean","ssl":"boolean","subDir":"string","ip":"string","port":"integer","id":"integer"})
	 *
	 * @return array
	 */
	function postSonarrProfiles($settings = false) {
		$uri = "Sonarr/Profiles";
		$method = "post";
		return $this->processRequest($uri, $settings, $method);
	}

	/**
	 * postSonarrRootFolders
	 * Gets the Sonarr root folders.
	 *
	 * @param  $settings - (optional) The settings.({"enabled":"boolean","apiKey":"string","qualityProfile":"string","seasonFolders":"boolean","rootPath":"string","qualityProfileAnime":"string","rootPathAnime":"string","addOnly":"boolean","ssl":"boolean","subDir":"string","ip":"string","port":"integer","id":"integer"})
	 *
	 * @return array
	 */
	function postSonarrRootFolders($settings = false) {
		$uri = "Sonarr/RootFolders";
		$method = "post";
		return $this->processRequest($uri, $settings, $method);
	}

	/**
	 * getStats
	 *
	 *
	 * @param bool | string $from - (optional) ([])
	 * @param bool | string $to - (optional) ([])
	 *
	 * @return array
	 */
	function getStats($from = false, $to = false) {
		$uri = "Stats";
		$queries = [];
		if ($from) $queries['from'] = $from;
		if ($to) $queries['to'] = $to;
		$queryString = (count($queries)) ? "?" . http_build_query($queries) : "";
		$uri .= $queryString;
		return $this->processRequest($uri);
	}

	/**
	 * getStatus
	 * Gets the status of Ombi.
	 *
	 *
	 * @return array
	 */
	function getStatus() {
		$uri = "Status";
		return $this->processRequest($uri);
	}

	/**
	 * getStatusinfo
	 * Returns information about this ombi instance
	 *
	 *
	 * @return array
	 */
	function getStatusinfo() {
		$uri = "Status/info";
		return $this->processRequest($uri);
	}

	/**
	 * postTesterdiscord
	 * Sends a test message to discord using the provided settings
	 *
	 * @param  $settings - (optional) The settings.({"enabled":"boolean","webhookUrl":"string","username":"string","id":"integer"})
	 *
	 * @return array
	 */
	function postTesterdiscord($settings = false) {
		$uri = "Tester/discord";
		$method = "post";
		return $this->processRequest($uri, $settings, $method);
	}

	/**
	 * postTesterpushbullet
	 * Sends a test message to Pushbullet using the provided settings
	 *
	 * @param  $settings - (optional) The settings.({"enabled":"boolean","accessToken":"string","channelTag":"string","id":"integer"})
	 *
	 * @return array
	 */
	function postTesterpushbullet($settings = false) {
		$uri = "Tester/pushbullet";
		$method = "post";
		return $this->processRequest($uri, $settings, $method);
	}

	/**
	 * postTesterpushover
	 * Sends a test message to Pushover using the provided settings
	 *
	 * @param  $settings - (optional) The settings.({"enabled":"boolean","accessToken":"string","userToken":"string","priority":"integer","sound":"string","id":"integer"})
	 *
	 * @return array
	 */
	function postTesterpushover($settings = false) {
		$uri = "Tester/pushover";
		$method = "post";
		return $this->processRequest($uri, $settings, $method);
	}

	/**
	 * postTestermattermost
	 * Sends a test message to mattermost using the provided settings
	 *
	 * @param  $settings - (optional) The settings.({"webhookUrl":"string","channel":"string","username":"string","iconUrl":"string","enabled":"boolean","id":"integer"})
	 *
	 * @return array
	 */
	function postTestermattermost($settings = false) {
		$uri = "Tester/mattermost";
		$method = "post";
		return $this->processRequest($uri, $settings, $method);
	}

	/**
	 * postTesterslack
	 * Sends a test message to Slack using the provided settings
	 *
	 * @param  $settings - (optional) The settings.({"enabled":"boolean","webhookUrl":"string","channel":"string","username":"string","iconEmoji":"string","iconUrl":"string","id":"integer"})
	 *
	 * @return array
	 */
	function postTesterslack($settings = false) {
		$uri = "Tester/slack";
		$method = "post";
		return $this->processRequest($uri, $settings, $method);
	}

	/**
	 * postTesteremail
	 * Sends a test message via email to the admin email using the provided settings
	 *
	 * @param  $settings - (optional) The settings.({"enabled":"boolean","host":"string","password":"string","port":"integer","senderName":"string","senderAddress":"string","username":"string","authentication":"boolean","adminEmail":"string","disableTLS":"boolean","disableCertificateChecking":"boolean","id":"integer"})
	 *
	 * @return array
	 */
	function postTesteremail($settings = false) {
		$uri = "Tester/email";
		$method = "post";
		return $this->processRequest($uri, $settings, $method);
	}

	/**
	 * postTesterplex
	 * Checks if we can connect to Plex with the provided settings
	 *
	 * @param  $settings - (optional) ({"name":"string","plexAuthToken":"string","machineIdentifier":"string","episodeBatchSize":"integer","plexSelectedLibraries":"array","ssl":"boolean","subDir":"string","ip":"string","port":"integer","id":"integer"})
	 *
	 * @return array
	 */
	function postTesterplex($settings = false) {
		$uri = "Tester/plex";
		$method = "post";
		return $this->processRequest($uri, $settings, $method);
	}

	/**
	 * postTesteremby
	 * Checks if we can connect to Emby with the provided settings
	 *
	 * @param  $settings - (optional) ({"name":"string","apiKey":"string","administratorId":"string","enableEpisodeSearching":"boolean","ssl":"boolean","subDir":"string","ip":"string","port":"integer","id":"integer"})
	 *
	 * @return array
	 */
	function postTesteremby($settings = false) {
		$uri = "Tester/emby";
		$method = "post";
		return $this->processRequest($uri, $settings, $method);
	}

	/**
	 * postTesterradarr
	 * Checks if we can connect to Radarr with the provided settings
	 *
	 * @param  $settings - (optional) ({"enabled":"boolean","apiKey":"string","defaultQualityProfile":"string","defaultRootPath":"string","addOnly":"boolean","minimumAvailability":"string","ssl":"boolean","subDir":"string","ip":"string","port":"integer","id":"integer"})
	 *
	 * @return array
	 */
	function postTesterradarr($settings = false) {
		$uri = "Tester/radarr";
		$method = "post";
		return $this->processRequest($uri, $settings, $method);
	}

	/**
	 * postTestersonarr
	 * Checks if we can connect to Sonarr with the provided settings
	 *
	 * @param  $settings - (optional) ({"enabled":"boolean","apiKey":"string","qualityProfile":"string","seasonFolders":"boolean","rootPath":"string","qualityProfileAnime":"string","rootPathAnime":"string","addOnly":"boolean","ssl":"boolean","subDir":"string","ip":"string","port":"integer","id":"integer"})
	 *
	 * @return array
	 */
	function postTestersonarr($settings = false) {
		$uri = "Tester/sonarr";
		$method = "post";
		return $this->processRequest($uri, $settings, $method);
	}

	/**
	 * postTestercouchpotato
	 * Checks if we can connect to Sonarr with the provided settings
	 *
	 * @param  $settings - (optional) ({"enabled":"boolean","apiKey":"string","defaultProfileId":"string","username":"string","password":"string","ssl":"boolean","subDir":"string","ip":"string","port":"integer","id":"integer"})
	 *
	 * @return array
	 */
	function postTestercouchpotato($settings = false) {
		$uri = "Tester/couchpotato";
		$method = "post";
		return $this->processRequest($uri, $settings, $method);
	}

	/**
	 * postTestertelegram
	 * Sends a test message to Telegram using the provided settings
	 *
	 * @param  $settings - (optional) The settings.({"enabled":"boolean","botApi":"string","chatId":"string","parseMode":"string","id":"integer"})
	 *
	 * @return array
	 */
	function postTestertelegram($settings = false) {
		$uri = "Tester/telegram";
		$method = "post";
		return $this->processRequest($uri, $settings, $method);
	}

	/**
	 * postTestersickrage
	 * Sends a test message to Slack using the provided settings
	 *
	 * @param  $settings - (optional) The settings.({"enabled":"boolean","apiKey":"string","qualityProfile":"string","qualities":"array","ssl":"boolean","subDir":"string","ip":"string","port":"integer","id":"integer"})
	 *
	 * @return array
	 */
	function postTestersickrage($settings = false) {
		$uri = "Tester/sickrage";
		$method = "post";
		return $this->processRequest($uri, $settings, $method);
	}

	/**
	 * postTesternewsletter
	 *
	 *
	 * @param  $settings - (optional) ({"notificationTemplate":null,"disableTv":"boolean","disableMovies":"boolean","disableMusic":"boolean","enabled":"boolean","externalEmails":"array","id":"integer"})
	 *
	 * @return array
	 */
	function postTesternewsletter($settings = false) {
		$uri = "Tester/newsletter";
		$method = "post";
		return $this->processRequest($uri, $settings, $method);
	}

	/**
	 * postTestermobile
	 *
	 *
	 * @param  $settings - (optional) ({"userId":"string","settings":null})
	 *
	 * @return array
	 */
	function postTestermobile($settings = false) {
		$uri = "Tester/mobile";
		$method = "post";
		return $this->processRequest($uri, $settings, $method);
	}

	/**
	 * postTesterlidarr
	 *
	 *
	 * @param  $settings - (optional) ({"enabled":"boolean","apiKey":"string","defaultQualityProfile":"string","defaultRootPath":"string","albumFolder":"boolean","languageProfileId":"integer","metadataProfileId":"integer","addOnly":"boolean","ssl":"boolean","subDir":"string","ip":"string","port":"integer","id":"integer"})
	 *
	 * @return array
	 */
	function postTesterlidarr($settings = false) {
		$uri = "Tester/lidarr";
		$method = "post";
		return $this->processRequest($uri, $settings, $method);
	}

	/**
	 * postToken
	 * Gets the token.
	 *
	 * @param  $model - (optional) The model.({"username":"string","password":"string","rememberMe":"boolean","usePlexAdminAccount":"boolean","usePlexOAuth":"boolean","plexTvPin":null})
	 *
	 * @return array
	 */
	function postToken($model = false) {
		$uri = "Token";
		$method = "post";
		return $this->processRequest($uri, $model, $method);
	}

	/**
	 * getToken
	 *
	 *
	 * @param int $pinId - (required) ([])
	 *
	 * @return array
	 */
	function getToken($pinId) {
		$uri = "Token/$pinId";
		return $this->processRequest($uri);
	}

	/**
	 * postTokenrefresh
	 * Refreshes the token.
	 *
	 * @param  $token - (optional) The model.({"token":"string","userename":"string"})
	 *
	 * @return array
	 */
	function postTokenrefresh($token = false) {
		$uri = "Token/refresh";
		$method = "post";
		return $this->processRequest($uri, $token, $method);
	}

	/**
	 * postTokenrequirePassword
	 *
	 *
	 * @param  $model - (optional) ({"username":"string","password":"string","rememberMe":"boolean","usePlexAdminAccount":"boolean","usePlexOAuth":"boolean","plexTvPin":null})
	 *
	 * @return array
	 */
	function postTokenrequirePassword($model = false) {
		$uri = "Token/requirePassword";
		$method = "post";
		return $this->processRequest($uri, $model, $method);
	}

	/**
	 * getUpdate
	 *
	 *
	 * @param string $branch - (required) ([])
	 *
	 * @return array
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
		if ($body) $options['body'] = json_encode($body);
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
	 * @return array - A response
	 */
	protected function processRequest($uri, $body = false, $type = "get") {
		try {
			$response = $this->_request($uri, $body, $type);
		} catch (\Exception $e) {
			return ['error' => array(
					'msg' => $e->getMessage(),
					'code' => $e->getCode())
			];
		}
		return json_decode($response->getBody()->getContents(),true);
	}
}
