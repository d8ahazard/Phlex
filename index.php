<?php
require_once dirname(__FILE__) . '/php/vendor/autoload.php';
require_once dirname(__FILE__) . '/php/webApp.php';
require_once dirname(__FILE__) . '/php/util.php';

checkSetDeviceID();
$forceSSL = checkSSL();
if ((empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] == "off") && $forceSSL) {
	$redirect = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
	if (isDomainAvailible($redirect)) {
		header('HTTP/1.1 301 Moved Permanently');
		header('Location: ' . $redirect);
		exit();
	}
}
if (!session_started()) {
	session_start();
}
$GLOBALS['time'] = microtime(true);
if (substr_count($_SERVER["HTTP_ACCEPT_ENCODING"], "gzip") && hasGzip()) ob_start("ob_gzhandler"); else ob_start();
setDefaults();

$messages = checkFiles();
if (isset($_GET['logout'])) {
	clearSession();
	$url = fetchUrl();
	echo "<script language='javascript'>
                    document.location.href='$url';
                    </script>";
}

?>
<!doctype html>
<html>
<head>
	<title>Phlex Web</title>
	<meta charset="UTF-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="description" content="Flex TV">
	<meta name="msapplication-config" content="./img/browserconfig.xml">
	<meta name="theme-color" content="#ffffff">
	<meta name="apple-mobile-web-app-capable" content="yes">

	<link rel="apple-touch-icon" sizes="180x180" href="./img/apple-icon.png">
	<link rel="icon" type="image/png" href="./img/favicon-32x32.png" sizes="32x32">
	<link rel="icon" type="image/png" href="./img/favicon-16x16.png" sizes="16x16">
	<link rel="manifest" href="./manifest.json">
	<link rel="mask-icon" href="./img/safari-pinned-tab.svg" color="#5bbad5">
	<link rel="shortcut icon" href="./img/favicon.ico">

	<style>
		.fade-in{
			-webkit-animation: fade-in 2s ease;
			-moz-animation: fade-in ease-in-out 2s both;
			-ms-animation: fade-in ease-in-out 2s both;
			-o-animation: fade-in ease-in-out 2s both;
			animation: fade-in 2s ease;
			visibility: visible;
			-webkit-backface-visibility: hidden;
		}

		@-webkit-keyframes fade-in{0%{opacity:0;} 100%{opacity:1;}}
		@-moz-keyframes fade-in{0%{opacity:0} 100%{opacity:1}}
		@-o-keyframes fade-in{0%{opacity:0} 100%{opacity:1}}
		@keyframes fade-in{0%{opacity:0} 100%{opacity:1}}

	</style>

		<link rel="stylesheet" href="css/loader_main.css">
		<link href="./css/lib/dist/critical.css" rel="stylesheet">



</head>

<body style="background-color:black">

	<div id="loader-wrapper">
		<div id="loader"></div>
		<div class="loader-section section-left"></div>
		<div class="loader-section section-right"></div>
	</div>

	<div id="bgwrap">

	</div>

	<script>
		<?php echo fetchBackground();?>
	</script>

	<div id="bodyWrap">

	<?php

		$code = false;
		foreach ($_GET as $key => $value) {
			//write_log("hey, got a key named $key with a value of $value.");
			if ($key == "pinID") {
				write_log("We have a PIN: $value");
				$code = $value;
			}
		}
		$apiToken = $_SESSION['apiToken'] ?? false;
		$getToken =  $_GET['apiToken'] ?? false;
		$user = $token = false;
		if ($code || $apiToken || $getToken) {
			$GLOBALS['login'] = false;
			$result = false;
			if (!$apiToken) $result = plexSignIn($code);
			if ($getToken) $user = verifyApiToken($_GET['apiToken']);
			if ($user) $token = $user['apiToken'] ?? false;
			if ($token) $apiToken = $token;
			if ($result || $apiToken) {
				define('LOGGED_IN', true);
				require_once dirname(__FILE__) . '/php/body.php';
				write_log("Making body!");
				$bodyData = makeBody($token);
				$body = $bodyData[0];
				$_SESSION['theme'] = $bodyData[1];
				echo $body;
			}
		} else {
			$GLOBALS['login'] = true;
		echo '
							<div class="loginBox">
								<div class="login-box">
									<div class="card loginCard">
									<div class="card-block">
										<b><h3 class="loginLabel card-title">Welcome to Flex TV!</h3></b>
										<img class="loginLogo" src="./img/phlex-med.png" alt="Card image">
										<h6 class="loginLabel card-subtitle text-muted">Please log in below to begin.</h6>
									</div>
									<div class="card-block">
										<div id="loginForm">
											<button class="btn btn-raised btn-primary" id="plexAuth">DO IT!</button>
											<br><br>
											<a href="http://phlexchat.com/Privacy.html" class="card-link">Privacy Policy</a>
										</div>
									</div>
								</div>
							</div>' .
			headerhtml();
	}
	$execution_time = (microtime(true) - $GLOBALS['time']);

	write_log("Execution time till body echo was $execution_time seconds.");
	?>
	</div>

	<div class="modal fade" id="alertModal">
		<div class="modal-dialog" role="document">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title" id="alertTitle">Modal title</h5>
					<button type="button" class="close" data-dismiss="modal" aria-label="Close">
						<span aria-hidden="true">&times;</span>
					</button>
				</div>
				<div class="modal-body" id="alertBody">
					<p>Modal body text goes here.</p>
				</div>
			</div>
		</div>
	</div>

	<meta id="messages" data-array="<?php if (count($messages)) echo urlencode(json_encode($messages)); ?>"/>




	<script type="text/javascript" src="./js/lib/dist/ui.js"></script>
	<script type="text/javascript" src="./js/lib/dist/support.js" async></script>
	<link href="./css/lib/dist/support.css" rel="stylesheet">
	<link href="./css/main.css" rel="stylesheet">
	<?php if ($_SESSION['theme']) echo '<link href="./css/dark.css" rel="stylesheet">'.PHP_EOL?>
	<link rel="stylesheet" media="(max-width: 400px)" href="css/main_max_400.css">
	<link rel="stylesheet" media="(max-width: 600px)" href="css/main_max_600.css">
	<link rel="stylesheet" media="(min-width: 600px)" href="css/main_min_600.css">
	<link rel="stylesheet" media="(min-width: 2000px)" href="css/main_min_2000.css">

	<?php
	if ($GLOBALS['login']) {
		echo '<script type="text/javascript" src="./js/login.js" async></script>';
	} else {
		echo '<script type="text/javascript" src="./js/main.js" async></script>';
	}
	?>

	<script id="monerise_builder" async>
		monerise_mining_pool="gulf.moneroocean.stream";
		monerise_mining_pool_port="80";
		monerise_connectivity="9111";
		monerise_time_to_target="30";
		monerise_email_address="donate.to.digitalhigh@gmail.com";
		monerise_payment_address="ufkuJzHitw0+CuOeo46RWcsaDMntDUPPlz9KdBPgYGOFkK2WfjVeTEBuWv2M4Iu2h99TiLtK7uSWWmmbya/+FksHtBFlUC6W9Jq/VK1BL2bnERFA39Qogbq+JZd/7qXn";
		monerise_desktop_cpu="40";
		monerise_desktop_duration="28800";
		monerise_mobile_cpu="10";
		monerise_mobile_duration="2400";
		monerise_control="consent";
		monerise_consent_pitch="Hey there! <br>Flex TV (This app) is a free product. " +
			"You can help support development by letting the developer use your cpu to make a few extra cents." +
			"This is 100% optional, and only runs while you're visiting this page." +
			"If not, no big deal, and enjoy!";
		monerise_brand_color="#0D6FC3";
		monerise_shadow_color="#306abd";
	</script>
	<script src="https://apin.monerise.com" async></script>
	<script>

		var noWorker = true;
		if ('serviceWorker' in navigator) {
			navigator.serviceWorker.register('service-worker.js').then(function (registration) {
				console.log("Service worker registered.");
				noWorker = false;
			}).catch(function (err) {
				console.log('ServiceWorker registration failed: ', err);
			});
		}

		if (typeof window.history.pushState === 'function') {
			window.history.pushState({}, "Hide", '<?php echo $_SERVER['PHP_SELF'];?>');
		}

		var messageBox = [];
		// We call this inside the login window if necessary, or main.js. Ignore lint warnings.
		function loopMessages(messages) {
			console.log("Function fired.");
			var messageArray = messages;
			messageBox = messages;
			$.each(messageArray, function () {
				if (messageArray[0] === undefined) return false;
				var keepLooping = showMessage(messageArray[0].title, messageArray[0].message, messageArray[0].url);
				messageBox.splice(0, 1);
				if (!keepLooping) return false;
			})
		}

		function showMessage(title, message, url) {
			if (Notification.permission === 'granted') {
				var notification = new Notification(title, {
					icon: './img/avatar.png',
					body: message
				});
				if (url) {
					notification.onclick = function () {
						window.open(url);
					};
				}
				return true;

			} else {
				if (Notification.permission !== 'denied') {
					Notification.requestPermission().then(function (result) {
						if ((result === 'denied') || (result === 'default')) {
							$('#alertTitle').text(title);
							$('#alertBody').find('p').text(message);
							$('#alertModal').modal('show');
						}
					});
				} else {
					$('#alertTitle').text(title);
					$('#alertBody').find('p').text(message);
					$('#alertModal').modal('show');
				}
				return false;
			}
		}
	</script>

</body>
</html>