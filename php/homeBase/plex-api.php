<?php
require_once("../../api.php");
require_once("../util.php");
$serverId = $_SESSION['plexServerId'] ?? false;
$server = findDevice('Id', $serverId, 'Server');
$plexUrl = $server['Uri'];
$plexToken = $server['Token'];
$post = $_POST['postData'];
$headers = headerRequestArray(plexHeaders($server));
array_push($headers, "Accept: application/json");
$url = $plexUrl . $post;
$resultstr = curlGet($url, $headers, 4, false);

echo $resultstr;

// EOF
