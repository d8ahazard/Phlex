<?php
/**
 * Require the library
 */
require_once dirname(__FILE__) . '/webApp.php';
require_once dirname(__FILE__) . '/util.php';
require_once dirname(__FILE__) . '/PHPTail.php';
/**
 * Initilize a new instance of PHPTail
 * @var PHPTail
 */
if (!isset($_GET['apiToken'])) {
	//write_log("Unauthorized access detected.");
	die("Unauthorize access detected.");
} else {
    $apiToken = trim($_GET['apiToken']);
    if ($apiToken) {
    	if (isWebApp()) {
    		if ($apiToken !== devToken()) {
    			die("You're not supposed to be here.");
		    }
	    } else {
    	    if (!verifyApiToken($apiToken)) die("Invalid Api Token.");
	    }

    } else {
    	die("No token, sukka.");
    }
}
$logs = [
    "Main" => dirname(__FILE__) . "/logs/Phlex.log.php",
	"Error Log" => dirname(__FILE__)."/logs/Phlex_error.log.php"
];
if (!isset($_SESSION['webApp'])) $logs["Updates"] = dirname(__FILE__)."/logs/Phlex_update.log.php";

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
