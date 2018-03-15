<?php
/**
 * Require the library
 */
require_once dirname(__FILE__) . '/php/webApp.php';
require_once dirname(__FILE__) . '/php/util.php';
require_once dirname(__FILE__) . '/PHPTail.php';
/**
 * Initilize a new instance of PHPTail
 * @var PHPTail
 */
if (!isset($_GET['apiToken'])) {
	//write_log("Unauthorized access detected.");
	die("Unauthorize access detected.");
} else {
	$apiToken = $_GET['apiToken'];
	if (!verifyApiToken($apiToken)) {
		write_log("Invalid API Token used for logfile access.");
		die("Invalid API Token");
	}
}
$logs = array(
	"Main" => dirname(__FILE__)."/logs/Phlex.log.php",
	"Updates" => dirname(__FILE__)."/logs/Phlex_update.log.php",
	"Error Log" => dirname(__FILE__)."/logs/Phlex_error.log.php",

);

$tail = new PHPTail($logs,1000,2097152,$apiToken);

/**
 * We're getting an AJAX call
 */
if(isset($_GET['ajax']))  {
    echo $tail->getNewLines($_GET['file'], $_GET['lastsize'], $_GET['grep'], $_GET['invert'], intval($_GET['count']));
    die();
}

/**
 * Regular GET/POST call, print out the GUI
 */
$tail->generateGUI();
