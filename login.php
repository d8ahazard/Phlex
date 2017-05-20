<?php
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


echo '
	<div class="loginBox">
		<div class="login-box">
		<div class="card loginCard">
            <div class="card-block">
                <b><h3 class="loginLabel card-title">Welcome to Phlex!</h3></b>
                <img class="loginLogo" src="./img/phlex_logo.png" alt="Card image">
                <h6 class="loginLabel card-subtitle text-muted">Please log in below to begin.</h6>
            </div>
            
            <div class="card-block">
                <form id="loginForm" method="post">
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
                    <button class="btn btn-raised btn-primary" id="post">DO IT!</button>
                    <br><br>
                    <a href="http://phlexchat.com/Privacy.html" class="card-link">Privacy Policy</a>
                </form>
            </div>
        </div>
	</div>
	
	
		<script type="text/javascript" src="./js/login.js"></script>		
';


