<?php
require_once dirname(__FILE__) . '/util.php';
date_default_timezone_set("America/Chicago");
$config = new Config_Lite('config.ini.php');
$deviceID = checkSetDeviceID();

if (isset($_GET['logout'])) {
    $_SESSION['username'] = '';
    if (isset($_SERVER['HTTP_COOKIE'])) {
        $cookies = explode(';', $_SERVER['HTTP_COOKIE']);
        foreach ($cookies as $cookie) {
            $parts = explode('=', $cookie);
            $name = trim($parts[0]);
            setcookie($name, '', time() - 1000);
            setcookie($name, '', time() - 1000, '/');
        }
    }
    $has_session = session_status() == PHP_SESSION_ACTIVE;
    if ($has_session) session_destroy();
    header('Location:  /index.php');
}

if ((isset($_POST['username'])) && (isset($_POST['password']))) {
    write_log("LOGIN.PHP: Posted Username is " . $_POST['username']);
    $token = signIn(base64_encode($_POST['username'] . ":" . $_POST['password']));
    write_log("LOGIN.PHP: received Token is " . json_encode($token));
    if ($token) {
        $username = urlencode($token['username']);
        $userString = "user-_-" . $username;
        $userpass = base64_encode($_POST['username'] . ":" . $_POST['password']);
        $authToken = $token['authToken'];
        $email = $token['email'];
        $avatar = $token['thumb'];
        $apiToken = checkSetApiToken($username);

        if (!$apiToken) {
            echo "Unable to set API Token, please check write access to Phlex root and try again.";
            die();
        } else {
            write_log("ApiToken is " . $apiToken);
            $_SESSION['apiToken'] = $apiToken;
            $_SESSION['username'] = $username;
            $_SESSION['plex_token'] = $authToken;
            // This is our user's first logon.  Let's make some files and an API key for them.
            $config->set($userString, "plexToken", $authToken);
            $config->set($userString, "plexEmail", $email);
            $config->set($userString, "plexAvatar", $avatar);
            $config->set($userString, "plexCred", $userpass);
            $config->set($userString, "plexUserName", $username);
            saveConfig($config);
        }
        $url = '';
        if (isset($_SESSION['url'])) {
            $url = $_SESSION['url']; // holds url for last page visited.
        } else {
            $url = "login.php"; // default page for
        }
        header("Location:.");
        write_log('Successfully logged in.');
        exit();
    } else {
        echo 'Error logging in with username of ' . $_POST['username'];
        $_SESSION['username'] = '';
        if (isset($_SERVER['HTTP_COOKIE'])) {
            $cookies = explode(';', $_SERVER['HTTP_COOKIE']);
            foreach ($cookies as $cookie) {
                $parts = explode('=', $cookie);
                $name = trim($parts[0]);
                setcookie($name, '', time() - 1000);
                setcookie($name, '', time() - 1000, '/');
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
	<body background="https://unsplash.it/1920/1080?random">
	<div class="loginBox">
		<div class="login-box">
		<div class="card loginCard">
            <div class="card-block">
                <b><h3 class="loginLabel card-title">Welcome to Phlex!</h3></b>
                <img class="loginLogo" src="./img/phlex_logo.png" alt="Card image">
                <h6 class="loginLabel card-subtitle text-muted">Please log in below to begin.</h6>
            </div>
            
            <div class="card-block">
                <form id="loginForm" action="index.php" method="post">
                    <div class="label-static form-group loginGroup">
                        <label id="userLabel" for="username" class="control-label">Username</label>
                        <input type="text" class="form-control login-control" id="username" name ="username" autofocus/>
                        <span class="bmd-help">Enter your Plex username or email address.</span>
                    </div>
                    <div class="label-static form-group loginGroup">
                        <label id="passLabel" for="password" class="control-label">Password</label>
                        <input type="password" class="form-control login-control" id="password" name="password"/>
                        <span class="bmd-help">Enter your Plex password.</span>
                    </div>
                    <button class="btn btn-raised btn-primary" type="submit">DO IT!</button>
                    <br><br>
                    <a href="http://phlexchat.com/Privacy.html" class="card-link">Privacy Policy</a>
                </form>
            </div>
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


