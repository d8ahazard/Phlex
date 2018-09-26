<?php

// For example: http://localhost:32400
define('PLEX_URL', 'http://localhost:32400');

// tautulli/settings#tab_tabs-plex_media_server
define('PLEX_TOKEN', '');

$post = $_POST['postData'];

$defaults = array(
  CURLOPT_URL 		        => PLEX_URL . $post,
  CURLOPT_AUTOREFERER     => true,
  CURLOPT_RETURNTRANSFER  => true,
  CURLOPT_CONNECTTIMEOUT  => 30,
  CURLOPT_TIMEOUT         => 30,
  CURLOPT_FAILONERROR     => true,
  CURLOPT_FOLLOWLOCATION  => true,
  CURLOPT_HTTPHEADER      => [
    'X-Plex-Platform: Web Server',
    'X-Plex-Platform-Version: 1.0',
    'X-Plex-Provides: controller',
    'X-Plex-Client-Identifier: 923A3BBB-98AF-53CD-8916-D72BE92DA7E4',
    'X-Plex-Product: Homebase (for Plex)',
    'X-Plex-Version: 1.0',
    'X-Plex-Device: Web Server',
    'X-Plex-Device-Name: Homebase Web Server',
    'X-Plex-Token: ' . PLEX_TOKEN,
  	'Accept: application/json',
  ],
);

$ch = curl_init();
curl_setopt_array($ch, $defaults);
$resultstr = curl_exec($ch);
curl_close($ch);

echo $resultstr;

// EOF
