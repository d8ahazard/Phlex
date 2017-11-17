<?php

// Demonstrates class to send pictures/videos to Chromecast
// using reverse engineered Castv2 protocol.
//
// Note: To work from internet you must open a route to port 8009 on
// your chromecast through your firewall. Preferably with port forwarding
// from a different port address.

require_once("Chromecast.php");

// Create Chromecast object and give IP and Port
$cc = new Chromecast("192.168.178.28", "8009");

//$cc->launch("9AC194DC");

//sleep(4);

$cc->cc_connect();
$cc->getStatus();

// Connect to the Application
$cc->connect();
echo "Connected";

$cc->getStatus();

// load media namespace is: urn:x-cast:com.google.cast.media
// plex is: urn:x-cast:plex

$cc->sendMessage("urn:x-cast:plex", '{"type":"SETQUALITY","bitrate":1}');
sleep(3);
echo "Pausing";
$cc->sendMessage("urn:x-cast:plex", '{"type":"PLAY"}');
sleep(3);
$cc->sendMessage("urn:x-cast:plex", '{"type":"PAUSE"}');
echo "Paused";

// Keep the connection alive with heartbeat
//while (1==1) {
//	$cc->pingpong();
//	sleep(10);
//}

?>
