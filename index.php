<?php
	require_once dirname(__FILE__) . '/vendor/autoload.php';
	require_once dirname(__FILE__) . '/util.php';
	session_start();
	ini_set("log_errors", 1);
	$errfilename = 'Phlex_error.log';
	ini_set("error_log", $errfilename);
	date_default_timezone_set("America/Chicago");
	if (!(isset($_SESSION['plex_token'])) || isset($_GET['logout'])) {
		include('login.php');
		die();
	} else {
		define('USERNAME', $_SESSION['username']);
		$_SESSION['url'] = $_SERVER['REQUEST_URI'];
		$config = new Config_Lite('config.ini.php');
		require ('api.php');
		$apiToken = checkSetApiToken($_SESSION['username']);
		if (! $apiToken) {
			echo "Unable to set API Token, please check write access to Phlex root and try again.";
			die();
		} else {
			$_SESSION['apiToken'] = $apiToken;
		}
		
	
	// Check our config file exists
	
	$_SESSION['enable_couch'] = $config->get('user-_-'.$_SESSION['username'], 'couchEnabled', false);
	$_SESSION['enable_ombi'] = $config->get('user-_-'.$_SESSION['username'], 'ombiEnabled', false);
	$_SESSION['enable_sonarr'] = $config->get('user-_-'.$_SESSION['username'], 'sonarrEnabled', false);
	$_SESSION['enable_sick'] = $config->get('user-_-'.$_SESSION['username'], 'sickEnabled', false);
	$_SESSION['enable_radarr'] = $config->get('user-_-'.$_SESSION['username'], 'radarrEnabled', false);
	$_SESSION['enable_apiai'] = $config->get('user-_-'.$_SESSION['username'], 'apiEnabled', false);
	
	$_SESSION['ip_couch'] = $config->get('user-_-'.$_SESSION['username'], 'couchIP', 'localhost');
	$_SESSION['ip_ombi'] = $config->get('user-_-'.$_SESSION['username'], 'ombiUrl', 'localhost');
	$_SESSION['ip_sonarr'] = $config->get('user-_-'.$_SESSION['username'], 'sonarrIP', 'localhost');
	$_SESSION['ip_sick'] = $config->get('user-_-'.$_SESSION['username'], 'sickIP', 'localhost');
	$_SESSION['ip_radarr'] = $config->get('user-_-'.$_SESSION['username'], 'radarrIP', 'localhost');
	
	$_SESSION['port_couch'] = $config->get('user-_-'.$_SESSION['username'], 'couchPort', '5050');
	$_SESSION['port_ombi'] = $config->get('user-_-'.$_SESSION['username'], 'ombiPort', '3579');
	$_SESSION['port_sonarr'] = $config->get('user-_-'.$_SESSION['username'], 'sonarrPort', '8989');
	$_SESSION['port_sick'] = $config->get('user-_-'.$_SESSION['username'], 'sickPort', '8083');
	$_SESSION['port_radarr'] = $config->get('user-_-'.$_SESSION['username'], 'radarrPort', '7878');
	
	$_SESSION['auth_couch'] = $config->get('user-_-'.$_SESSION['username'], 'couchAuth', '');
	$_SESSION['auth_sonarr'] = $config->get('user-_-'.$_SESSION['username'], 'sonarrAuth', '');
	$_SESSION['auth_sick'] = $config->get('user-_-'.$_SESSION['username'], 'sickAuth', '');
	$_SESSION['auth_radarr'] = $config->get('user-_-'.$_SESSION['username'], 'radarrAuth', '');
	
	$_SESSION['apiai_client_token'] = $config->get('user-_-'.$_SESSION['username'], 'apiClientToken', '');
	$_SESSION['apiai_dev_token'] = $config->get('user-_-'.$_SESSION['username'], 'apiDevToken', '');
	
	$_SESSION['use_cast'] = $config->getBool('user-_-'.$_SESSION['username'], 'useCast', false);
	
	
	$url = 'https://plex.tv/pms/:/ip';
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL,$url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt ($ch, CURLOPT_CAINFO, dirname(__FILE__) . "/cert/cacert.pem");
	$realIP = curl_exec($ch);
	curl_close ($ch);
	$ipString = (((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')	|| $_SERVER['SERVER_PORT'] == 443) ? 'https://' : 'http://') . $realIP . '/Phlex';
	$_SESSION['publicAddress'] = $config->get('user-_-'.$_SESSION['username'], 'publicAddress', $ipString);
	}
?>
<!doctype html>
	<head>
		<meta charset="UTF-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<meta name="description" content="Phlex">
		<link rel="apple-touch-icon" sizes="180x180" href="/img/apple-touch-icon.png">
		<link rel="icon" type="image/png" href="/img/favicon-32x32.png" sizes="32x32">
		<link rel="icon" type="image/png" href="/img/favicon-16x16.png" sizes="16x16">
		<link rel="manifest" href="/img/manifest.json">
		<link rel="mask-icon" href="/img/safari-pinned-tab.svg" color="#5bbad5">
		<link rel="shortcut icon" href="/img/favicon.ico">
		<meta name="msapplication-config" content="/img/browserconfig.xml">
		<meta name="theme-color" content="#ffffff">
		<meta name="apple-mobile-web-app-capable" content="yes">
		<link href="css/bootstrap-reboot.css" rel="stylesheet">
		<link href="css/bootstrap.min.css" rel="stylesheet">
		<link href="css/font-awesome.min.css" rel="stylesheet">
		<link rel="stylesheet" type="text/css" href="//fonts.googleapis.com/css?family=Roboto:300,400,500,700">
		<link rel="stylesheet" type="text/css" href="//fonts.googleapis.com/icon?family=Material+Icons">
		<link href="css/material.css" rel="stylesheet">
		<link href="css/snackbar.min.css" rel="stylesheet">
		<link href="css/bootstrap-material-design.min.css" rel="stylesheet">
		<link href="css/bootstrap-dialog.css" rel="stylesheet">
		<link href="css/ripples.min.css" rel="stylesheet">
		<link href="css/main.css" rel="stylesheet">
		
		<!--[if lt IE 9]>
			<link href="/css/bootstrap-ie8.css" rel="stylesheet">
			<script src="https://cdn.jsdelivr.net/g/html5shiv@3.7.3,respond@1.4.2"></script>
		<![endif]-->
		
	</head>
	<body>
		<div class="wrapper">
			<div class="queryWrap col-xs-12">
				<div class="col-xs-12 col-md-8 col-md-offset-1 query">
					<div class="card">
						<div class="btn-toolbar">
							<div class="col-xs-2 col-sm-1">
								<div class="btn btn-primary" id="executeButton"><i class="material-icons sendBtn">message</i></div>
							</div>
							<div class="queryGroup form-group label-floating col-xs-9 col-md-10 col-lg-5 col-xl-6">
								<label id="actionLabel" for="commandTest" class="control-label">"I want to watch"</label>
								<input type="text" class="form-control" id="commandTest">
							</div>
							<div class="queryBtnWrap">
								<div class="queryBtnGrp">
									<div class="dropdown show btn btn-sm" id="cmdDD">
										<a class="dropdown-toggle" href="" id="dropdownMenuLink" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
											<i class="material-icons barBtn" id="commandIcon">queue_play_next</i>
										</a>
										<div class="dropdown-menu"  id="plexServerClient" aria-labelledby="dropdownMenuButton">
											<a href="javascript:void(0)" id="play" class="btn btn-sm cmdBtn"><i class="material-icons barBtn">queue_play_next</i></a>
											<a href="javascript:void(0)" id="control" class="btn btn-sm cmdBtn"><i class="material-icons barBtn">settings_remote</i></a>
											<a href="javascript:void(0)" id="fetch" class="btn btn-sm cmdBtn"><i class="material-icons barBtn">get_app</i></a>
										</div>
									</div>
									<div class="dropdown show btn btn-sm" id="castDD">
										<i class="ddLabel"> </i><br>
										<a class="dropdown-toggle clientMenu" href="javascript:void(0)" id="client" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
										<i class="material-icons barBtn clientIcon">cast</i>
										</a>

										<div class="dropdown-menu" id="plexClient" aria-labelledby="dropdownMenuLink">
											<h6 class="dropdown-header">Select a player to control.</h6>
											<div id="clientWrapper">
												
											</div>
										</div>
									</div>
									<a href="" id="settings" class="btn btn-sm" data-toggle="modal" data-target="#settingsModal"><i class="material-icons barBtn">settings</i></a>
									<a href="?logout" id="logout" class="btn btn-sm"><i class="material-icons barBtn">power_settings_new</i></a>
									
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div id="results" class="queryWrap col-xs-12">
				<div id="resultsInner"  class="col-xs-8 col-xs-offset-1 query"></div>
			</div>
			<div class="modal fade" id="settingsModal">
				<div class="modal-dialog" role="document">
					<div class="modal-content">
						<div class="modal-header">
							<h5 class="modal-title">Settings</h5>
							<button type="button" class="close" data-dismiss="modal" aria-label="Close">
								<span aria-hidden="true">&times;</span>
							</button>
						</div>
						<div class="modal-body">
							<div class="appContainer card">	
								<div class="card-body">
								<h4>General</h4>
									<div class="form-group">
										<div class="form-group">
											<label for="apiToken" class="appLabel">API Key:
												<input id="apiToken" class="appInput form-control" type="text" value="<?php echo $_SESSION['apiToken'] ?>" readonly="readonly"/>
											</label>
										</div>
									</div>
									<div class="form-group">
										<div class="form-group">
											<label for="publicAddress" class="appLabel">Public Address:
												<input id="publicAddress" class="appInput form-control formpop" type="text" value="<?php echo $_SESSION['publicAddress'] ?>" />
											</label>
										</div>
									</div>
									<div class="form-group text-center">
										<div class="form-group">
											<label for="linkAccount">Google Action Account Linking:</label><br>
											<button id="linkAccount" class="btn btn-raised linkBtn btn-primary" type="button"/>Link Account</button>
										</div>
									</div>
									<div class="text-center">
										<div class="form-group btn-group">
											<label for="sel1">Click to copy IFTTT URL:</label><br>
											<button id="playURL" class="copyInput btn btn-raised btn-primary btn-70" type="button"><i class="material-icons">queue_play_next</i></button>
											<button id="controlURL" class="copyInput btn btn-raised btn-info btn-70" type="button"><i class="material-icons">settings_remote</i></button>
											<button id="fetchURL" class="copyInput btn btn-raised btn-success btn-70" type="button"><i class="material-icons">get_app</i></button>
										</div>
									</div>
									
								</div>
							</div>
							<div class="appContainer card">	
								<div class="card-body">
								<h4>Plex</h4>
									<div class="form-group">
										<div class="form-group">
											<label for="sel1">Playback Server:</label>
											<select class="form-control" id="serverList">
												
											</select>
											<br>
											<div class="togglebutton">
												<label class="appLabel">Use Cast Devices
													<input id="useCast" type="checkbox" class="appInput"<?php echo ($_SESSION['use_cast'] ? 'checked' : '') ?>/>
												</label>
											</div>
										</div>
										<div class="text-center">
											<div class="form-group btn-group">
												<button value="Plex" class="testInput btn btn-raised btn-info btn-100" type="button">Test</button>
											</div>
										</div>
									</div>
								</div>
							</div>
							<div class="appContainer card">	
								<div class="card-body">
								<h4>CouchPotato</h4>
									<div class="togglebutton">
										<label class="appLabel">Enable
											<input id="couchEnabled" type="checkbox" class="appInput"/>
										</label>
									</div>
									<div class="form-group" id="couchGroup">
										<div class="form-group">
											<label for="couchIP" class="appLabel">Couchpotato IP/URL:
												<input id="couchIP" class="appInput form-control CouchPotato appParam" type="text" value="<?php echo $_SESSION['ip_couch'] ?>"/>
											</label>
										</div>
										<div class="form-group">
											<label for="couchPort" class="appLabel">Couchpotato Port:
												<input id="couchPort" class="appInput form-control CouchPotato appParam" type="text" value="<?php echo $_SESSION['port_couch'] ?>"/>
											</label>
										</div>
										<div class="form-group">
											<label for="couchAuth" class="appLabel">Couchpotato Token:
												<input id="couchAuth" class="appInput form-control CouchPotato appParam" type="text" value="<?php echo $_SESSION['auth_couch'] ?>"/>
											</label>
										</div>
										<div class="form-group">
											<label for="sel1">Quality Profile:</label>
											<select class="form-control profileList" id="couchProfile">
												<?php echo fetchList("couch") ?>
											</select>
										</div>
										<div class="text-center">
											<div class="form-group btn-group">
												<button value="CouchPotato" class="testInput btn btn-raised btn-info" type="button">Test</button>
												<button id="resetCouch" value="CouchPotato" class="resetInput btn btn-raised btn-danger btn-100" type="button">Reset</button>
											</div>
										</div>
									</div>
								</div>
							</div>
							
							<div class="appContainer card ombiGroup">	
								<div class="card-body">
								<h4>Ombi</h4>
									<div class="togglebutton">
										<label class="appLabel">Enable
											<input id="ombiEnabled" type="checkbox" class="appInput"/>
										</label>
									</div>
									<div class="form-group" id="ombiGroup">
										<div class="form-group">
										<label for="ombiUrl" class="appLabel">Ombi IP/URL:
											<input id="ombiUrl" class="appInput form-control ombiUrl appParam" type="text"  value="<?php echo $_SESSION['ip_ombi'] ?>" />
										</label>
										</div>
										<div class="form-group">
											<label for="ombiPort" class="appLabel">Ombi Port:
												<input id="ombiPort" class="appInput form-control Ombi appParam" type="text" value="<?php echo $_SESSION['port_ombi'] ?>"/>
											</label>
										</div>
										<div class="form-group">
											<label for="sel1">Quality Profile:</label>
											<select class="form-control profileList" id="ombi">
												
											</select>
										</div>
										<div class="text-center">
											<div class="form-group btn-group">
												<button value="Ombi" class="testInput btn btn-raised btn-info btn-100" type="button">Test</button>
												<button id="resetOmbi" value="Ombi" class="resetInput btn btn-raised btn-danger btn-100" type="button">Reset</button>
											</div>
										</div>
									</div>
								</div>
							</div>
							
							<div class="appContainer card">	
								<div class="card-body">
									<h4>Radarr</h4>
									<div class="togglebutton">
										<label class="appLabel">Enable
											<input id="radarrEnabled" type="checkbox" class="appInput"/>
										</label>
									</div>
									<div class="form-group" id="radarrGroup">
										<div class="form-group">
											<label for="radarrIP" class="appLabel">Radarr IP/URL:
												<input id="radarrIP" class="appInput form-control Radarr appParam" type="text" value="<?php echo $_SESSION['ip_radarr'] ?>"/>
											</label>
										</div>
										<div class="form-group">
											<label for="radarrPort" class="appLabel">Radarr Port:
												<input id="radarrPort" class="appInput form-control Radarr appParam" type="text" value="<?php echo $_SESSION['port_radarr'] ?>"/>
											</label>
										</div>
										<div class="form-group">
											<label for="radarrAuth" class="appLabel">Radarr Token:
												<input id="radarrAuth" class="appInput form-control Radarr appParam" type="text" value="<?php echo $_SESSION['auth_radarr'] ?>"/>
											</label>
										</div>
										<div class="form-group">
											<label for="sel1">Quality Profile:</label>
											<select class="form-control profileList" id="radarrProfile">
												<?php echo fetchList("radarr") ?>
											</select>
										</div>
										<div class="text-center">
											<div class="form-group btn-group">
												<button value="Radarr" class="testInput btn btn-raised btn-info btn-100" type="button">Test</button>
												<button id="resetRadarr" value="Radarr" class="resetInput btn btn-raised btn-danger btn-100" type="button">Reset</button>
											</div>
										</div>
									</div>
								</div>
							</div>
							
							<div class="appContainer card">	
								<div class="card-body">
									<h4>Sickbeard/SickRage</h4>
									<div class="togglebutton">
										<label class="appLabel">Enable
											<input id="sickEnabled" type="checkbox" class="appInput"/>
										</label>
									</div>
									<div class="form-group" id="sickGroup">
										<div class="form-group">
											<label for="sickIP" class="appLabel">Sick IP/URL:
												<input id="sickIP" class="appInput form-control Sick appParam" type="text" value="<?php echo $_SESSION['ip_sick'] ?>"/>
											</label>
										</div>
										<div class="form-group">
											<label for="sickPort" class="appLabel">Sick Port:
												<input id="sickPort" class="appInput form-control Sick appParam" type="text" value="<?php echo $_SESSION['port_sick'] ?>"/>
											</label>
										</div>
										<div class="form-group">
											<label for="sickAuth" class="appLabel">Sick Token:
												<input id="sickAuth" class="appInput form-control Sick appParam" type="text" value="<?php echo $_SESSION['auth_sick'] ?>"/>
											</label>
										</div>
										<div class="form-group">
											<label for="sel1">Quality Profile:</label>
											<select class="form-control appInput profileList" id="sickProfile">
												<?php echo fetchList("sick") ?>
											</select>
										</div>
										<div class="text-center">
											<div class="form-group btn-group">
												<button value="Sick" class="testInput btn btn-raised btn-info btn-100" type="button">Test</button>
												<button id="resetSick" value="Sick" class="resetInput btn btn-raised btn-danger btn-100" type="button">Reset</button>
											</div>
										</div>
									</div>
								</div>
							</div>
														
							<div class="appContainer card">	
								<div class="card-body">
									<h4>Sonarr</h4>
									<div class="togglebutton">
										<label class="appLabel">Enable
											<input id="sonarrEnabled" type="checkbox" class="appInput"/>
										</label>
									</div>
									<div class="form-group" id="sonarrGroup">
										<div class="form-group">
											<label for="sonarrIP" class="appLabel">Sonarr IP/URL:
												<input id="sonarrIP" class="appInput form-control Sonarr appParam" type="text" value="<?php echo $_SESSION['ip_sonarr'] ?>"/>
											</label>
										</div>
										<div class="form-group">
											<label for="sonarrPort" class="appLabel">Sonarr Port:
												<input id="sonarrPort" class="appInput form-control Sonarr appParam" type="text" value="<?php echo $_SESSION['port_sonarr'] ?>"/>
											</label>
										</div>
										<div class="form-group">
											<label for="sonarrAuth" class="appLabel">Sonarr Token:
												<input id="sonarrAuth" class="appInput form-control Sonarr appParam" type="text" value="<?php echo $_SESSION['auth_sonarr'] ?>"/>
											</label>
										</div>
										<div class="form-group">
											<label for="sel1">Quality Profile:</label>
											<select class="form-control profileList" id="sonarrProfile">
												<?php echo fetchList("sonarr") ?>
											</select>
										</div>
										<div class="text-center">
											<div class="form-group btn-group">
												<button value="Sonarr" class="testInput btn btn-raised btn-info btn-100" type="button">Test</button>
												<button id="resetSonarr" value="Sonarr" class="resetInput btn btn-raised btn-danger btn-100" type="button">Reset</button>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
						<div class="modal-footer">
							<button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
						</div>
					</div>
				</div>
			</div>
			
			<div id="inputs" class="col-xs-12 col-sm-5 col-md-3">
				
			</div>
			
			<div id="log">
				<div id="logInner">
				</div>
			</div>
			<div class="nowPlayingFooter">
				<div class="statusWrapper">
					<img id="statusImage" src=""></img>
					<div class="statusText">
						<h6>Now Playing on <span id="playerName"></span>: </h6>
						<h4><span id="mediaTitle"></span> (<span id="mediaYear"></span>)</h4>
						<span id="mediaSummary"></span>
						<div id="progressSlider" class="slider shor slider-material-orange"></div>
					</div>
				</div>
				
			</div>
			
			<div class="wrapperArt"></div>
			<iframe id="backArt" class="backArt" src="cc.html"></iframe>
			<div id="metaTags"><?PHP echo '<meta id="apiTokenData" data="'.$_SESSION['apiToken'].'"></meta>' . metaTags();?></div>
		</div>
		<script type="text/javascript" src="js/jquery-3.1.1.min.js"></script>
		<script type="text/javascript" src="js/tether.min.js"></script>
		<script type="text/javascript" src="js/bootstrap.min.js"></script>
		<script type="text/javascript" src="js/arrive.min.js"></script>
		<script type="text/javascript" src="js/material.min.js"></script>
		<script type="text/javascript" src="js/ripples.min.js"></script>
		<script type="text/javascript" src="js/nouislider.min.js"></script>
		<script type="text/javascript" src="js/main.js"></script>
		<script type="text/javascript" src="js/snackbar.min.js"></script>
		<script type="text/javascript" src="js/bootstrap-dialog.js"></script>
		<script type="text/javascript" src="js/clipboard.min.js"></script>

    
	</body>
</html>
<?php
	foreach ($_SERVER as $key => $value) {
    if (strpos($key, 'HTTP_') === 0) {
        $chunks = explode('_', $key);
        $header = '';
        for ($i = 1; $y = sizeof($chunks) - 1, $i < $y; $i++) {
            $header .= ucfirst(strtolower($chunks[$i])).'-';
        }
        $header .= ucfirst(strtolower($chunks[$i])).': '.$value;
        write_reqlog("Headers: ".$header);
    }
}
	function write_reqlog($text,$level=null) {
		if ($level === null) {
			$level = 'I';	
		}
		$filename = 'PhlexREQ.log';
		$text = $level .'/'. date(DATE_RFC2822) . ': ' . $text . PHP_EOL;
		if (!file_exists($filename)) { touch($filename); chmod($filename, 0666); }
		if (filesize($filename) > 2*1024*1024) {
			$filename2 = "$filename.old";
			if (file_exists($filename2)) unlink($filename2);
			rename($filename, $filename2);
			touch($filename); chmod($filename,0666);
		}
		if (!is_writable($filename)) die;
		if (!$handle = fopen($filename, 'a+')) die;
		if (fwrite($handle, $text) === FALSE) die;
		fclose($handle);
		
	}
	//setupBot();
?>