<?php
require_once dirname(__FILE__) . '/vendor/autoload.php';
use Cz\Git\GitRepository;
$GLOBALS['config'] = new Config_Lite('config.ini.php', LOCK_EX);

function checkFiles() {
	if (isset($_SESSION['webApp'])) return [];
	$messages = [];
	$extensions = [
		'curl',
		'xml'
	];

	$logDir = file_build_path(dirname(__FILE__), "logs");

	$logPath = file_build_path($logDir, "Phlex.log.php");
	$errorLogPath = file_build_path($logDir, "Phlex_error.log.php");
	$updateLogPath = file_build_path($logDir, "Phlex_update.log.php");
	$configPath = file_build_path(dirname(__FILE__), "config.ini.php");
	$cmdPath = file_build_path(dirname(__FILE__), "commands.php");

	$old = [
		file_build_path($logDir, "PhlexUpdate.log"),
		file_build_path($logDir, "Phlex.log"),
		file_build_path($logDir, "Phlex.log.old"),
		file_build_path($logDir, "Phlex_error.log"),
		file_build_path($logDir, "Phlex_update.log")
	];

	foreach ($old as $delete) {
		if (file_exists($delete)) {
			write_log("Deleting insecure file $delete", "INFO");
			unlink($delete);
		}
	}

	$files = [
		$logPath,
		$errorLogPath,
		$updateLogPath,
		$configPath,
		$cmdPath
	];

	$secureString = "'; <?php die('Access denied'); ?>";
	if (!file_exists($logDir)) {
		if (!mkdir($logDir, 0777, true)) {
			$message = "Unable to create log folder directory, please check permissions and try again.";
			$error = [
				'title' => 'Permission error.',
				'message' => $message,
				'url' => false
			];
			array_push($messages, $error);
		}
	}
	foreach ($files as $file) {
		if (!file_exists($file)) {
			mkdir(dirname($file), 0777, true);
			touch($file);
			chmod($file, 0777);
			file_put_contents($file, $secureString);
		}
		if ((file_exists($file) && (!is_writable(dirname($file)) || !is_writable($file))) || !is_writable(dirname($file))) { // If file exists, check both file and directory writeable, else check that the directory is writeable.
			$message = 'Either the file ' . $file . ' and/or it\'s parent directory is not writable by the PHP process. Check the permissions & ownership and try again.';
			$url = '';
			if (PHP_SHLIB_SUFFIX === "so") { //Check for POSIX systems.
				$message .= "  Current permission mode of " . $file . " is " . decoct(fileperms($file) & 0777);
				$message .= "  Current owner of " . $file . " is " . posix_getpwuid(fileowner($file))['name'];
				$message .= "  Refer to the README on instructions how to change permissions on the aforementioned files.";
				$url = 'http://www.computernetworkingnotes.com/ubuntu-12-04-tips-and-tricks/how-to-fix-permission-of-htdocs-in-ubuntu.html';
			} else if (PHP_SHLIB_SUFFIX === "dll") {
				$message .= "  Detected Windows system, refer to guides on how to set appropriate permissions."; //Can't get fileowner in a trivial manner.
				$url = 'https://stackoverflow.com/questions/32017161/xampp-on-windows-8-1-cant-edit-files-in-htdocs';
			}
			write_log($message, "ERROR");
			$error = [
				'title' => 'File error.',
				'message' => $message,
				'url' => $url
			];
			array_push($messages, $error);
		}
	}


	foreach ($extensions as $extension) {
		if (!extension_loaded($extension)) {
			$message = "The " . $extension . " PHP extension, which is required for Phlex to work correctly, is not loaded." . " Please enable it in php.ini, restart your webserver, and then reload this page to continue.";
			write_log($message, "ERROR");
			$url = "http://php.net/manual/en/book.$extension.php";
			$error = [
				'title' => 'PHP Extension not loaded.',
				'message' => $message,
				'url' => $url
			];
			array_push($messages, $error);
		}
	}

	# TODO: Only do this if not server-only
	try {
		new Config_Lite('config.ini.php');
	} catch (Config_Lite_Exception_Runtime $e) {
		$message = "An exception occurred trying to load config.ini.php.  Please check that the directory and file are writeable by your webserver application and try again.";
		$error = [
			'title' => 'Config error.',
			'message' => $message,
			'url' => false
		];
		array_push($messages, $error);
	};
	//$testMessage = ['title'=>'Test message.','message'=>"This is a test of the emergency alert system. If this were a real emergency, you'd be screwed.",'url'=>'https://www.google.com'];
	//array_push($messages,$testMessage);
	return $messages;
}

