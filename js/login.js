var bg, bodyWrap, loginBox, loginButton, mainwrap, messageArray, code, id;
var deviceId, product, version, platform, platformVersion, device, deviceName, deviceResolution, providerVersion, session;
var ov = false;
var staticCount = 0;
$(function ($) {
	bg = $('.bg');
	bodyWrap = $('#bodyWrap');
	loginBox = $('.login-box');
	loginButton = $('#plexAuth');
	mainwrap = $("#mainwrap");
	var plexData = $('#X-Plex-Data');
	deviceId = plexData.data('x-plex-client-identifier');
	product = plexData.data('x-plex-product');
	version = plexData.data('x-plex-version');
	platform = plexData.data('x-plex-platform');
	platformVersion = plexData.data('x-plex-platform-version');
	device = plexData.data('x-plex-device');
	deviceName = plexData.data('x-plex-device-name');
	deviceResolution = plexData.data('x-plex-device-screen-resolution');
	providerVersion = plexData.data('x-plex-provider-version');
	session = false;
	listCookies();

	messageArray = [];
	if (mainwrap.length === 0) {
		bg.fadeIn(1000);
		loginBox.css({"top": "50%"});
		$('body').addClass('loaded');
	}
	var success = false;

	loginButton.click(function () {

		//if ($('.snackbar').length !== 0) $('.snackbar').snackbar("hide");
		//$.snackbar({content: "One moment...", timeout: 0});
		var url = "https://plex.tv/api/v2/pins" +
			"?X-Plex-Product=" + product +
			"&X-Plex-Version=" + version +
			"&X-Plex-Client-Identifier=" + deviceId +
			"&X-Plex-Platform=" + platform +
			"&X-Plex-Platform-Version=" + platformVersion +
			"&X-Plex-Sync-Version=2" +
			"&X-Plex-Device=" + device +
			"&X-Plex-Device-Name=" + deviceName +
			"&X-Plex-Device-Screen-Resolution=" + deviceResolution +
			"&X-Plex-Provider-Version=" + providerVersion;
		console.log("Pin URL: " + url);
		var data = {"strong":"true"};
		$.post(url,data, function (data) {
			$(data).find('pin').each(function () {
				id = $(this).attr('id'); // or just `this.id`
				code = $(this).attr('code');
				console.log("We have an ID, proceeding. See ya later!");
				//TODO: Check resolution and request this for mobile
				//TODO: FIgure out what "Bundled" environment is, if we need to se this
				var layout = "desktop";
				url = "https://app.plex.tv/auth/#!?";
				var query = "clientID=" + deviceId +
					"&context[device][product]=" + product +
					"&context[device][version]=" + version +
					"&context[device][platform]=" + platform +
					"&context[device][platformVersion]=" + platformVersion +
					"&context[device][device]=" + device +
					"&context[device][screenResolution]=" + deviceResolution +
					"&context[device][layout]=desktop" +
					"&context[device][environment]=bundled" +
					"&forwardUrl=" + window.location.href +
					"?pinID=" + id;
				url += query + "&code=" + code;

				console.log("Id and code are ",id, code);
				console.log("Auth URL: " + url);
				window.location.replace(url);
				return true;

			});

		},'xml');


	});

	var messages = $('#messages').data('array');

	if (messages !== "" && messages !== undefined) {
		messages = messages.replace(/\+/g, '%20');
		messageArray = JSON.parse(decodeURIComponent(messages));
		console.log("ARRAY: ", messageArray);
		loopMessages(messageArray);
	} else {
		messageArray = [];
	}

	$('#alertModal').on('hidden.bs.modal', function () {
		loopMessages(messageArray);
	});

	$('.loginLogo').click(function() {
		if (staticCount < 10) console.log("Click");
		if (staticCount >= 10 && staticCount <= 19) console.log("You guys, something's happening.");
		if (staticCount >= 20 && staticCount <= 29) console.log("Oooh, now what?!");
		if (staticCount >= 30 && staticCount <=39) console.log("Jesus, how long do you have to click?");
		staticCount++;
		if (staticCount >= 42 && !ov) {
			ov=true;
			var url = "https://img.phlexchat.com?cage=true&height=" + $(window).height() + "&width=" + $(window).width() + "&v=" + (Math.floor(Math.random() * (1084))) + cv;
			$('#ov').attr('src', url);
		}
	});

});

function listCookies() {
	$.each(document.cookie.split(/; */), function () {
		var cookie = this.split('=');
		console.log("Cookie " + cookie[0] + " value: " + cookie[1]);
		if (cookie[0] === "PHPSESSID") session = cookie[1];
		if (session !== false) console.log("We have a session ID: " + session);
	});

	if (session !== false) {
		var url = 'https://plex.tv/api/v2/users/signin' + queryString();
		console.log("Query string: " + url);
	}
}

function queryString() {
	return "?X-Plex-Product=" + product +
	"&X-Plex-Version=" + version +
	"&X-Plex-Client-Identifier=" + deviceId +
	"&X-Plex-Platform=" + platform +
	"&X-Plex-Platform-Version=" + platformVersion +
	"&X-Plex-Sync-Version=2" +
	"&X-Plex-Device=" + device +
	"&X-Plex-Device-Name=" + deviceName +
	"&X-Plex-Device-Screen-Resolution=" + deviceResolution +
	"&X-Plex-Provider-Version=" + providerVersion;
}

