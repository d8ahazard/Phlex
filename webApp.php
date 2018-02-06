<?php
require_once dirname(__FILE__) . '/vendor/autoload.php';
use Cz\Git\GitRepository;
$configDir = dirname(__FILE__) . "/rw/config.ini.php";
$GLOBALS['config'] = new Config_Lite($configDir, LOCK_EX);

function checkFiles() {
	if (isset($_SESSION['webApp'])) return [];
	$messages = [];
	$extensions = [
		'curl',
		'xml'
	];

	$logDir = file_build_path(dirname(__FILE__), "logs");
	$logPath = file_build_path($logDir, "Phlex.log.php");
	$rwDir = file_build_path(dirname(__FILE__),"rw");
	$errorLogPath = file_build_path($logDir, "Phlex_error.log.php");
	$updateLogPath = file_build_path($logDir, "Phlex_update.log.php");
	$configPath = file_build_path($rwDir, "config.ini.php");
	$cmdPath = file_build_path($rwDir, "commands.php");

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
	if (!file_exists($rwDir)) {
		if (!mkdir($rwDir, 0777, true)) {
			$message = "Unable to create secure storage directory, please check permissions and try again.";
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

if (!function_exists("checkUpdates")) {
	function checkUpdates($install = false) {
		write_log("Function fired." . ($install ? " Install requested." : ""));
		$installed = $pp = $result = false;
		$html = '';
		$autoUpdate = $_SESSION['autoUpdate'];
		write_log("Auto Update is " . ($autoUpdate ? " on " : "off"));
		if (checkGit()) {
			write_log("This is a repo and GIT is available, checking for updates.");
			try {
				$repo = new GitRepository(dirname(__FILE__));
				if ($repo) {
					$repo->fetch('origin');
					$result = $repo->readLog('origin', 'HEAD');
					$revision = $repo->getRev();
					$logHistory = readUpdate();
					if ($revision) {
						$config = $GLOBALS['config'] ?? new Config_Lite('config.ini.php', LOCK_EX);
						$old = $config->get('general', 'revision', false);
						if ($old !== $revision) {
							$config->set('general', 'revision', $revision);
							saveConfig($config);
						}
					}
					if (count($logHistory)) $installed = $logHistory[0]['installed'];
					$header = '<div class="cardHeader">
							Current revision: ' . substr($revision, 0, 7) . '<br>
							' . ($installed ? "Last Update: " . $installed : '') . '
						</div>';
					// This never works right when you're developing the app...
					if (1 == 2) {
						$html = $header . '<div class="cardHeader">
								Status: ERROR: Local file conflicts exist.<br><br>
							</div><br>';
						write_log("LOCAL CHANGES DETECTED.", "ERROR");
						return $html;
					}

					if (count($result)) {
						$log = $result;
						$_SESSION['updateAvailable'] = count($log);
						$html = parseLog($log);
						$html = $header . '<div class="cardHeader">
								Status: ' . count($log) . ' commit(s) behind.<br><br>
								Missing Update(s):' . $html . '</div>';
						if (($install) || ($autoUpdate)) {
							if (isset($_SESSION['pollPlayer'])) {
								$pp = true;
								unset($_SESSION['pollPlayer']);
							}
							backupConfig();
							write_log("Updating from repository - " . ($install ? 'Manually triggered.' : 'Automatically triggered.'), "INFO");
							$repo->pull('origin');
							//write_log("Pull result: ".$result);
							if ($pp) $_SESSION['pollPlayer'] = true;
							logUpdate($log);

						}
					} else {
						write_log("No changes detected.");
						if (count($logHistory)) {
							$html = parseLog($logHistory[0]['commits']);
							$installed = $logHistory[0]['installed'];
						} else {
							$html = parseLog($repo->readLog("origin/master", 0));
						}
						$html = $header . '<div class="cardHeader">
								Status: Up-to-date<br><br>
								' . ($installed ? "Installed: " . $installed : '') . '
							Last Update:' . $html . '</div><br>';
					}
				} else {
					write_log("Couldn't initialize git.", "ERROR");
				}
			} catch (\Cz\Git\GitException $e) {
				write_log("An exception has occurred: " . $e, "ERROR");
			}
		} else {
			write_log("Doesn't appear to be a cloned repository or git not available.", "INFO");
		}
		return $html;
	}
}

