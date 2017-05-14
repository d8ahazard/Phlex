<?php
	require_once dirname(__FILE__) . '/util.php';
	date_default_timezone_set("America/Chicago");
	$config = new Config_Lite('config.ini.php');
	$deviceID = $config->get('general', 'deviceID', false);
	if ($deviceID===false) {
		$deviceID = randomToken(12);
		$config->set("general","deviceID",$deviceID);
		saveConfig($config);
	}
	
	if(isset($_GET['logout'])) {
		$_SESSION['username'] = '';
		if (isset($_SERVER['HTTP_COOKIE'])) {
				$cookies = explode(';', $_SERVER['HTTP_COOKIE']);
				foreach($cookies as $cookie) {
					$parts = explode('=', $cookie);
					$name = trim($parts[0]);
					setcookie($name, '', time()-1000);
					setcookie($name, '', time()-1000, '/');
				}
			}
		$has_session = session_status() == PHP_SESSION_ACTIVE;
		if ($has_session) session_destroy();
		header('Location:  /index.php');
	}

	if(isset($_POST['username'])) {
		write_log("LOGIN.PHP: Posted Username is ".$_POST['username']);
		$token = signIn($_POST['username'],$_POST['password']);
		write_log("LOGIN.PHP: received Token is ".$token);
		if ($token) {
			$username = $_POST['username'];
			$_SESSION['username'] = urlencode($username);
			$_SESSION['plex_token'] = $token;
			$userString = "user-_-".$_SESSION['username'];
			$userpass = base64_encode($_POST['username'] . ":" . $_POST['password']);
			define('USERCONFIG', 'config_'.$_SESSION['username'].'.ini');
			// This is our user's first logon.  Let's make some files and an API key for them.
			$config->set($userString,"plexToken",$token);
			$config->set($userString,"plexCred",$userpass);
			$config->set($userString,"plexUserName",$_SESSION['username']);
			saveConfig($config);
			$apiToken = checkSetApiToken(urlencode($username));
			write_log("ApiToken is ".$apiToken);
			if (! $apiToken) {
				echo "Unable to set API Token, please check write access to Phlex root and try again.";
				die();
			} else {
				$_SESSION['apiToken'] = $apiToken;
			}
			$url = '';
			if(isset($_SESSION['url'])) {
			   $url = $_SESSION['url']; // holds url for last page visited.
			} else { 
			   $url = "login.php"; // default page for 
			}
			header("Location:.");
			write_log('Successfully logged in.');
			exit();
		} else {
			echo 'Error logging in with username of '. $_POST['username'];
			$_SESSION['username'] = '';
			if (isset($_SERVER['HTTP_COOKIE'])) {
				$cookies = explode(';', $_SERVER['HTTP_COOKIE']);
				foreach($cookies as $cookie) {
					$parts = explode('=', $cookie);
					$name = trim($parts[0]);
					setcookie($name, '', time()-1000);
					setcookie($name, '', time()-1000, '/');
				}
			}
			$has_session = session_status() == PHP_SESSION_ACTIVE;
			if ($has_session) session_destroy();
			die();
		}
	} 
	//session_start();
	

echo '<!doctype html>
 
	<head>
		<meta charset="UTF-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<meta name="description" content="Phlex">
		
		<link rel="apple-touch-icon" sizes="57x57" href="./img/apple-icon-57x57.png">
        <link rel="apple-touch-icon" sizes="60x60" href="./img/apple-icon-60x60.png">
        <link rel="apple-touch-icon" sizes="72x72" href="./img/apple-icon-72x72.png">
        <link rel="apple-touch-icon" sizes="76x76" href="./img/apple-icon-76x76.png">
        <link rel="apple-touch-icon" sizes="114x114" href="./img/apple-icon-114x114.png">
        <link rel="apple-touch-icon" sizes="120x120" href="./img/apple-icon-120x120.png">
        <link rel="apple-touch-icon" sizes="144x144" href="./img/apple-icon-144x144.png">
        <link rel="apple-touch-icon" sizes="152x152" href="./img/apple-icon-152x152.png">
        <link rel="apple-touch-icon" sizes="180x180" href="./img/apple-icon-180x180.png">
        <link rel="icon" type="image/png" sizes="192x192"  href="./img/android-icon-192x192.png">
        <link rel="icon" type="image/png" sizes="32x32" href="./img/favicon-32x32.png">
        <link rel="icon" type="image/png" sizes="96x96" href="./img/favicon-96x96.png">
        <link rel="icon" type="image/png" sizes="16x16" href="./img/favicon-16x16.png">
        <link rel="manifest" href="/manifest.json">
        <meta name="msapplication-TileColor" content="#ffffff">
        <meta name="msapplication-TileImage" content="./img/ms-icon-144x144.png">
        <meta name="theme-color" content="#ffffff">
		<link href="./css/bootstrap-reboot.css" rel="stylesheet">
		<link href="./css/bootstrap.min.css" rel="stylesheet">
		<link href="./css/font-awesome.min.css" rel="stylesheet">
		<link rel="stylesheet" type="text/css" href="//fonts.googleapis.com/css?family=Roboto:300,400,500,700">
		<link rel="stylesheet" type="text/css" href="//fonts.googleapis.com/icon?family=Material+Icons">
		<link href="./css/bootstrap-material-design.min.css" rel="stylesheet">
		<link href="./css/bootstrap-dialog.css" rel="stylesheet">
		<link href="./css/ripples.min.css" rel="stylesheet">
		<link href="./css/main.css" rel="stylesheet">
		
		<!--[if lt IE 9]>
			<link href="/css/bootstrap-ie8.css" rel="stylesheet">
			<script src="https://cdn.jsdelivr.net/g/html5shiv@3.7.3,respond@1.4.2"></script>
		<![endif]-->
		
	</head>
	<body>
	<div class="loginBox">
		<div class="form-group login-box">
			<form action="index.php" method="post">
				<label>Welcome to Phlex</label><br>
				<div class="label-floating form-group loginGroup">
					<label id="userLabel" for="username" class="control-label">Plex Username</label>
					<input type="text" class="form-control login-control" id="username" name ="username" autofocus/>
				</div>
				<div class="label-floating form-group loginGroup">
					<label id="userLabel" for="password" class="control-label">Plex Password</label>
					<input type="password" class="form-control login-control" id="password" name="password"/>
				</div>
				<button class="btn btn-raised btn-primary" type="submit">DO IT!</button>
			</form>
		</div>
	</div>
	
		<script type="text/javascript" src="./js/jquery-3.1.1.min.js"></script>
		<script type="text/javascript" src="./js/tether.min.js"></script>
		<script type="text/javascript" src="./js/bootstrap.min.js"></script>
		<script type="text/javascript" src="./js/arrive.min.js"></script>
		<script type="text/javascript" src="./js/material.min.js"></script>
		<script type="text/javascript" src="./js/ripples.min.js"></script>
		<script type="text/javascript" src="./js/main.js"></script>
	</body>
</html>';

function signIn($user, $pass) {
	$url='https://plex.tv/users/sign_in.xml';
	write_log("signIn: URL is ".$url);
	$ch = curl_init();
	// Encode user:password
	$userpass = base64_encode($user . ":" . $pass);
	curl_setopt($ch, CURLOPT_URL,$url);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	$headers = [
		'X-Plex-Client-Identifier: '.$GLOBALS['deviceID'],
				'X-Plex-Device:PhlexWeb',
				'X-Plex-Device-Screen-Resolution:1520x707,1680x1050,1920x1080',
				'X-Plex-Device-Name:Phlex',
				'X-Plex-Platform:Web',
				'X-Plex-Platform-Version:1.0.0',
				'X-Plex-Product:Phlex',
				'X-Plex-Version:1.0.0',
				'X-Plex-Provides:player,controller,sync-target,pubsub-player',
				'Authorization:Basic '.$userpass
	];
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	curl_setopt ($ch, CURLOPT_CAINFO, rtrim(dirname(__FILE__), '/') . "/cert/cacert.pem");
	$result = curl_exec ($ch);
	if (curl_errno($ch)) {
			// this would be your first hint that something went wrong
			write_log("LOGIN: CURL Error while sending command. " . curl_error($ch));
		} else {
			// check the HTTP status code of the request
			$resultStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			if (($resultStatus != 200) && ($resultStatus != 201)) {
				write_log('LOGIN: Request failed, HTTP status code: ' . $resultStatus,"ERROR");
			}
		}
	curl_close ($ch);
	write_log("signIn: Result is ".$result);
	if ($result) {
		$container = new SimpleXMLElement($result);
		$token = (string)$container['authToken'];
		if ($token != "") {
			$_SESSION['plex_token'] = $token;
			write_log("Signin: Valid token received - ".$token);
			return $token;
		}
	}  
	return false;
}
