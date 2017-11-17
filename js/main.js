var action = "play";
var appName, autoUpdate, bgs, bgWrap, token, newToken, deviceID, resultDuration, logLevel, lastLog, itemJSON, apiToken,
	messageArray, ombi, couch, sonarr, radarr, sick, publicIP, dvr, weatherClass, city, state, updateAvailable,
	weatherHtml;
var lastDevices = "foo";
var condition = null;
var devices = lastUpdate = [];
var javaStrings;

$(function () {
	$('.snackbar').hide();
	javaStrings = decodeURIComponent($('#strings').data('array'));
	javaStrings = javaStrings.replace(/\+/g, ' ');
	javaStrings = JSON.parse(javaStrings);
	bgs = $('.bg');
	bgWrap = $('#bgwrap');
	var loginBox = $('.login-box');
	if (loginBox.length > 0) {
		console.log("Hiding login box.");
		loginBox.css({"top": "-1000px"});
		$.snackbar({content: "Login successful!"});

	}
	logLevel = "ALL";
	if (bgs.css('display') === 'none') {
		bgs.fadeIn(1000);
	}
	$('#mainwrap').css({"top": 0});
	setTimeout(
		function () {
			$('#results').css({"top": 0, "max-height": "100%", "overflow": "inherit"})
		}, 500);

	$('.castArt').fadeIn(1000);

	dvr = $("#plexDvr").data('enable');
	apiToken = $('#apiTokenData').attr('data');
	token = $('#tokenData').attr('data');
	deviceID = $('#deviceID').attr('data');
	publicIP = $('#publicIP').attr('data');
	newToken = $('#newToken').data('enable') === "true";
	sonarr = $('#sonarr').data('enable') === "true";
	sick = $('#sick').data('enable') === "true";
	couch = $('#couchpotato').data('enable') === "true";
	console.log("COUCH: " + couch);
	radarr = $('#radarr').data('enable') === "true";
	ombi = $('#ombi').data('enable') === "true";
	autoUpdate = $('#autoUpdate').data('enable') === "true";
	updateAvailable = $('#updateAvailable').attr('data');
	$.material.init();
	var Logdata = $('#logData').attr('data');
	if (Logdata !== "") {
		Logdata = decodeURIComponent(Logdata.replace(/\+/g, '%20'));
		var logArray = [];
		try {
			logArray = JSON.parse(Logdata);
		} catch (e) {
			console.log("Error parsing JSON.");
		}
		updateCommands(logArray, false);
	}
	var plexClientURI = $('#clientURI').attr('data');
	$(".select").dropdown({"optionClass": "withripple"});
	$('#play').addClass('clicked');
	var ddText = $('.dd-selected').text();
	$('.ddLabel').html(ddText);
	var progressSlider = document.getElementById('progressSlider');
	noUiSlider.create(progressSlider, {
		start: [20],
		range: {
			'min': 0,
			'max': 100
		}
	});

	$('.formpop').popover();

	var messages = $('#messages').data('array');
	if (messages !== "" && messages !== undefined) {
		messages = messages.replace(/\+/g, '%20');
		messageArray = JSON.parse(decodeURIComponent(messages));
		loopMessages();
	} else {
		messageArray = [];
	}

	$('#alertModal').on('hidden.bs.modal', function () {
		loopMessages();
	});

	if (newToken) {
		var serverAddress = $('#publicAddress').val();
		var regUrl = 'https://phlexserver.cookiehigh.us/api.php?apiToken=' + apiToken + "&serverAddress=" + serverAddress;
		showMessage("New API Token Detected", "A new API Token was created. Click here to re-register your server.", regUrl);
	}

	if (updateAvailable >= 1) {
		showMessage("Updates available!", "You have " + updateAvailable + " update(s) available.", false);
	}
	progressSlider.noUiSlider.on('end', function (values, handle) {
		var value = values[handle];
		var newOffset = Math.round((resultDuration * (value * .01)));
		apiToken = $('#apiTokenData').attr('data');
		var url = plexClientURI + '/player/playback/seekTo?offset=' + newOffset + '&X-Plex-Token=' + token + '&X-Plex-Client-Identifier=' + deviceID;
		$.get(url);
	});

	// Handle our input changes and zap them to PHP for immediate saving
	setListeners();

	$('#logLevel').change(function () {
		logLevel = $(this).val();
		console.log("Log level changed to " + logLevel);
	});

	var checkbox = $(':checkbox');
	checkbox.change(function () {
		var label = $("label[for='" + $(this).attr('id') + "']");
		if ($(this).is(':checked')) {
			label.css("color", "#003792");
		} else {
			label.css("color", "#A1A1A1");
		}
	});

	checkbox.each(function () {
		var label = $("label[for='" + $(this).attr('id') + "']");
		if ($(this).is(':checked')) {
			label.css("color", "#003792");
		} else {
			label.css("color", "#A1A1A1");
		}
	});

	$('#logout').click(function () {
		var bgs = $('.bg');
		$('#results').css({"top": "-2000px", "max-height": 0, "overflow": "hidden"});
		$.snackbar({content: "Logging out."});
		setTimeout(
			function () {
				$('#mainwrap').css({"top": "-200px"});
				bgs.fadeOut(1000);
				$('.castArt').fadeOut(1000);
			}, 500);


	});

	$(".btn").on('click', function () {
		var value, regUrl;
		var serverAddress = $('#publicAddress').val();
		if ($(this).hasClass("copyInput")) {
			value = $(this).val();
			clipboard.copy(value);
			$.snackbar({content: "Successfully copied URL."});
		}

		if ($(this).hasClass("testInput")) {
			value = $(this).data('value');
			apiToken = $('#apiTokenData').attr('data');
			$.get('api.php?test=' + value + '&apiToken=' + apiToken, function (data) {
				var dataArray = [data];
				$.snackbar({content: JSON.stringify(dataArray[0].status).replace(/"/g, "")});
			});
		}
		if ($(this).hasClass("resetInput")) {
			appName = $(this).data('value');
			if (confirm('Are you sure you want to clear settings for ' + appName + '?')) {

			}
		}

		if ($(this).hasClass("setupInput")) {
			appName = $(this).data('value');
			apiToken = $('#apiTokenData').attr('data');
			$.get('api.php?setup&apiToken=' + apiToken, function (data) {
				$.snackbar({content: JSON.stringify(data).replace(/"/g, "")});
			});
			$.snackbar({content: "Setting up API.ai Bot."});
		}

		if ($(this).hasClass("linkBtn")) {
			regUrl = false;
			action = $(this).data('action');
			if (action === 'google') regUrl = 'https://phlexserver.cookiehigh.us/api.php?apiToken=' + apiToken + "&serverAddress=" + serverAddress;
			if (action === 'amazon') regUrl = 'https://phlexchat.com/alexaAuth.php?apiToken=' + apiToken + "&serverAddress=" + serverAddress;
			if (regUrl) {
				newwindow = window.open(regUrl, '');
				if (window.focus) {
					newwindow.focus();
				}
			} else {
				if (action === 'test') {
					regUrl = 'https://phlexserver.cookiehigh.us/api.php?apiToken=' + apiToken + "&serverAddress=" + serverAddress + "&test=true";
					$.get(regUrl, function (data) {
						console.log("Data: " + data);
						$.snackbar({content: data});
					});
				}
			}
		}
	});


	$(document).on('click', '.client-item', function () {
		if ($(this).attr('id') !== "rescan") {
			var clientID = $(this).data('value');
			var clientUri = $(this).data('uri');
			var clientName = $(this).data('name');
			var clientProduct = $(this).data('product');
			apiToken = $('#apiTokenData').attr('data');
			$.get('api.php?apiToken=' + apiToken, {
				device: 'plexClient',
				id: clientID,
				uri: clientUri,
				name: clientName,
				product: clientProduct
			});
			$('.ddLabel').html($(this).attr('name'));
			$('#clientURI').attr('data', decodeURIComponent($(this).data('uri')));
			$(this).siblings().removeClass('dd-selected');
			$(this).addClass('dd-selected');
		} else {
			$.get('api.php?apiToken=' + apiToken, {device: 'plexClient', id: 'rescan'});
			console.log("Rescanning devices.");
		}
	});

	$("#serverList").change(function () {
		var serverID = $(this).val();
		var element = $(this).find('option:selected');
		var serverUri = element.data('uri');
		var serverPublicUri = element.data('publicuri');
		var serverName = element.attr('name');
		var serverToken = element.data('token');
		var serverProduct = element.data('product');
		apiToken = $('#apiTokenData').attr('data');
		$.get('api.php?apiToken=' + apiToken, {
			device: 'plexServer',
			id: serverID,
			uri: serverUri,
			publicUri: serverPublicUri,
			name: serverName,
			token: serverToken,
			product: serverProduct
		});
	});

	$(".profileList").change(function () {
		var service = $(this).attr('id');
		var index = $(this).find('option:selected').data('index');
		apiToken = $('#apiTokenData').attr('data');
		$.get('api.php?apiToken=' + apiToken, {id: service, value: index});
	});

	$("#appLanguage").change(function () {
		var lang = $(this).find('option:selected').data('value');
		apiToken = $('#apiTokenData').attr('data');
		$.get('api.php?apiToken=' + apiToken, {id: "appLanguage", value: lang});
		$.snackbar({content: "Language changed, reloading page."});
		setTimeout(function () {
			location.reload();
		}, 1000);
	});

	$("#dvrList").change(function () {
		console.log("DVR Changed!");
		var serverID = $(this).val();
		var element = $(this).find('option:selected');
		var serverUri = element.data('uri');
		var serverPublicUri = element.data('publicaddress');
		var serverName = element.attr('name');
		var serverToken = element.data('token');
		var serverProduct = element.data('product');
		var serverKey = element.data('key');
		apiToken = $('#apiTokenData').attr('data');
		$.get('api.php?apiToken=' + apiToken, {
			device: 'plexDvr',
			id: serverID,
			uri: serverUri,
			key: serverKey,
			publicUri: serverPublicUri,
			name: serverName,
			token: serverToken,
			product: serverProduct
		});
	});


	// This handles sending and parsing our result for the web UI.
	$("#executeButton").click(function () {
		console.log("Execute clicked!");
		$('.load-bar').show();
		var command = $('#commandTest').val();
		if (command !== '') {
			command = command.replace(/ /g, "+");
			apiToken = $('#apiTokenData').attr('data');
			var url = 'api.php?say&command=' + command + '&apiToken=' + apiToken;
			$.get(url, function () {

			})
				.done(function (data) {
					setTimeout(function () {
						$('.load-bar').hide();
					}, 1500);
					var dataArray = [JSON.parse(data)];
					updateCommands(dataArray, true);
				}, dataType = "json")
				.always(function () {
					setTimeout(function () {
						$('.load-bar').hide();
					}, 1500);

				});
		}

	});

	$('#deviceFab').click(function () {
		var newDev = createStaticDevice();
		$('#deviceBody').append(newDev[0]);
		//setListeners();
		apiToken = $('#apiTokenData').attr('data');
		console.log("Dev1? ", newDev[1]);

		$.get('api.php?apiToken=' + apiToken + '&newDevice=' + JSON.stringify(newDev[1]));

	});

	var client = $('#client');
	client.click(function () {
		console.log("CLICKED CLIENT!");
		var pos = client.position();
		var width = client.outerWidth();

		//show the menu directly over the placeholder
		$("#plexClient").css({
			position: "absolute",
			top: pos.bottom + "px",
			left: (pos.left - width) + "px"
		});
	});


	$(".expandWrap").click(function () {
		$(this).children('.expand').slideToggle();
	});

	$("#sendLog").on('click', function () {
		apiToken = $('#apiTokenData').attr('data');
		$.get('api.php?sendlog&apiToken=' + apiToken);
	});


	$('#commandTest').keypress(function (event) {
		if (event.keyCode === 13) {
			$('#executeButton').click();
		}
	});

	var ombiEnabled = $('#ombiEnabled');
	var couchEnabled = $('#couchEnabled');
	var autoUpdateEnabled = $('#autoUpdate');
	var sonarrEnabled = $('#sonarrEnabled');
	var sickEnabled = $('#sickEnabled');
	var radarrEnabled = $('#radarrEnabled');
	var hookEnabled = $('#hookEnabled');
	var hookSplit = $('#hookSplit');
	var hookPlay = $('#hookPlay');
	var hookPaused = $('#hookPaused');
	var hookStop = $('#hookStop');
	var hookFetch = $('#hookFetch');
	var hookCustom = $('#hookCustom');
	var urlGroup = $('.urlGroup');

	ombiEnabled.prop("checked", ombi);
	couchEnabled.prop("checked", couch);
	sonarrEnabled.prop("checked", sonarr);
	sickEnabled.prop("checked", sick);
	radarrEnabled.prop("checked", radarr);

	if (ombiEnabled.is(':checked')) {
		$('#ombiGroup').show();
	} else {
		$('#ombiGroup').hide();
	}

	if (autoUpdateEnabled.is(':checked')) {
		$('#installUpdates').hide();
	} else {
		$('#installUpdates').show();
	}

	if (couchEnabled.is(':checked')) {
		$('#couchGroup').show();
	} else {
		$('#couchGroup').hide();
	}

	if (sonarrEnabled.is(':checked')) {
		$('#sonarrGroup').show();
	} else {
		$('#sonarrGroup').hide();
	}

	if (sickEnabled.is(':checked')) {
		$('#sickGroup').show();
	} else {
		$('#sickGroup').hide();
	}

	if (radarrEnabled.is(':checked')) {
		$('#radarrGroup').show();
	} else {
		$('#radarrGroup').hide();
	}

	if (hookEnabled.is(':checked')) {
		$('#hookGroup').show();
	} else {
		$('#hookGroup').hide();
	}

	if (hookSplit.is(':checked')) {
		$('#hookUrlGroup').hide();
		$('.hookLabel').show();
		urlGroup.css("height", "");
	} else {
		$('#hookUrlGroup').show();
		$('.hookLabel').hide();
		urlGroup.css("height", "0");
	}

	if (hookPlay.is(':checked')) {
		$('#hookPlayGroup').show();
	} else {
		$('#hookPlayGroup').hide();
	}

	if (hookPaused.is(':checked')) {
		$('#hookPausedGroup').show();
	} else {
		$('#hookPausedGroup').hide();
	}

	if (hookStop.is(':checked')) {
		$('#hookStopGroup').show();
	} else {
		$('#hookStopGroup').hide();
	}

	if (hookFetch.is(':checked')) {
		$('#hookFetchGroup').show();
	} else {
		$('#hookFetchGroup').hide();
	}


	if (hookCustom.is(':checked')) {
		$('#hookCustomPhraseGroup').show();
	} else {
		$('#hookCustomPhraseGroup').hide();
	}

	if (dvr) {
		$('.dvrGroup').show();
	} else {
		$('.dvrGroup').hide();
	}

	$('#plexServerEnabled').change(function () {
		$('#plexGroup').toggle();
	});

	ombiEnabled.change(function () {
		$('#ombiGroup').toggle();
	});

	autoUpdateEnabled.change(function () {
		$('#installUpdates').toggle();
	});

	couchEnabled.change(function () {
		$('#couchGroup').toggle();
	});

	$('#apiEnabled').change(function () {
		$('.apiGroup').toggle();
	});

	sonarrEnabled.change(function () {
		$('#sonarrGroup').toggle();
	});

	sickEnabled.change(function () {
		$('#sickGroup').toggle();
	});

	radarrEnabled.change(function () {
		$('#radarrGroup').toggle();
	});

	hookEnabled.change(function () {
		$('#hookGroup').toggle();
	});

	hookEnabled.change(function () {
		$('#hookGroup').toggle();
	});

	hookSplit.change(function () {
		$('#hookUrlGroup').toggle();
		$('.hookLabel').toggle();
	});

	hookPlay.change(function () {
		$('#hookPlayGroup').toggle();
	});
	hookPaused.change(function () {
		$('#hookPausedGroup').toggle();
	});
	hookStop.change(function () {
		$('#hookStopGroup').toggle();
	});
	hookFetch.change(function () {
		$('#hookFetchGroup').toggle();
	});
	hookCustom.change(function () {
		$('#hookCustomPhraseGroup').toggle();
	});


	$('#resolution').change(function () {
		apiToken = $('#apiTokenData').attr('data');
		var res = $(this).find('option:selected').data('value');
		$.get('api.php?apiToken=' + apiToken, {id: 'plexDvrResolution', value: res});
	});

	$('#checkUpdates').click(function () {
		apiToken = $('#apiTokenData').attr('data');
		$.get('api.php?apiToken=' + apiToken, {checkUpdates: true}, function (data) {
			$('#updateContainer').html(data);
		});
	});

	$('#installUpdates').click(function () {
		apiToken = $('#apiTokenData').attr('data');
		$.get('api.php?apiToken=' + apiToken, {installUpdates: true}, function (data) {
			$('#updateContainer').html(data);
		});
	});

	document.addEventListener('DOMContentLoaded', function () {
		if (!Notification) {
			alert('Desktop notifications not available in your browser. Try Chromium.');
			return;
		}

		if (Notification.permission !== "granted")
			Notification.requestPermission();
	});

	// Update our status every 10 seconds?  Should this be longer?  Shorter?  IDK...
	var IPString = $('#publicAddress').val() + "/api.php?";
	if (IPString.substring(0, 4) !== 'http') {
		IPString = document.location.protocol + '//' + IPString;
	}
	var sayString = IPString + "say&apiToken=" + apiToken + "&command={{TextField}}";
	$('#sayURL').val(sayString);

	var clientList = $.get('api.php?clientList&apiToken=' + apiToken, function (clientData) {
		$('#clientWrapper').html(clientData);
	});

	setInterval(function () {
		updateStatus();
	}, 5000);

	setInterval(function () {
		setBackground();
	}, 1000 * 60);

	setInterval(function () {
		setWeather();
	}, 10 * 1000 * 60);

	$('.controlBtn').click(function () {
		var myId = $(this).attr("id");
		myId = myId.replace("Btn", "");
		console.log("Firing " + myId + " command.");
		if (myId === "play") {
			$('#playBtn').hide();
			$('#pauseBtn').show();
		}
		if (myId === "pause") {
			$('#playBtn').show();
			$('#pauseBtn').hide();
		}
		$.get('api.php?control&noLog=true&command=' + myId + "&apiToken=" + apiToken);
	});
	scaleElements();
});

$(window).resize(function () {
	scaleElements();
});

function scaleElements() {
	var winWidth = $(window).width();
	var commandTest = $('#actionLabel');
	if (winWidth <= 340) commandTest.html(javaStrings[1]);
	if ((winWidth >= 341) && (winWidth <= 400)) commandTest.html(javaStrings[1]);
	if (winWidth >= 401) commandTest.html(javaStrings[0]);
	var sliderWidth = $('.statusWrapper').width() - $('#statusImage').width() - 60;
	$("#progressSlider").css('width', sliderWidth);

}

function setBackground() {
	//Add your images, we'll set the path in the next step
	console.log("Caching background image.");
	var image = new Image();
	image.src = "https://phlexchat.com/img.php?random&height=" + $(document).height() + "&width=" + $(document).width() + "&v=" + (Math.floor(Math.random() * (1084)));
	setTimeout
	(
		function () {
			if (!image.complete || !image.naturalWidth) {
				image.src = "./img/bg/" + ~~(Math.random() * 10) + ".jpg";
				console.log("Returning cached image: " + image.src);
			}
		},
		1000
	);

	$('#bgwrap').append("<div class='bg'></div>");
	bgs = $('.bg');
	var bgWrap = document.getElementById('bgwrap');
	bgs.last().css('background-image', 'url(' + image.src + ')');
	bgs.last().fadeIn(1000);
	setTimeout(
		function () {
			console.log("Removing first background");
			while (bgWrap.childNodes.length > 1) {
				bgWrap.removeChild(bgWrap.firstChild);
			}
			$('.imgHolder').remove();
		}, 1500);
}

function loadImage(url, altUrl) {
	var timer;

	function clearTimer() {
		if (timer) {
			clearTimeout(timer);
			timer = null;
		}
	}

	function handleFail() {
		// kill previous error handlers
		this.onload = this.onabort = this.onerror = function () {
		};
		// stop existing timer
		clearTimer();
		// switch to alternate url
		if (this.src === url) {
			this.src = altUrl;
		}
	}

	var img = new Image();
	img.onerror = img.onabort = handleFail;
	img.onload = function () {
		clearTimer();
	};
	img.src = url;
	timer = setTimeout(function (theImg) {
		return function () {
			handleFail.call(theImg);
		};
	}(img), 1000);
	return (img);
}


function resetApiUrl(newUrl) {
	if (newUrl.substring(0, 4) !== 'http') {
		newUrl = document.location.protocol + '://' + newUrl;
	}
	return newUrl;
}

function fetchClientList(players) {
	var options = "";
	$.each(players, function (key, client) {
		var selected = client.selected;
		var id = client.id;
		var name = client.name;
		var uri = client.uri;
		var product = client.product;
		options += '<a class="dropdown-item client-item' + ((selected) ? ' dd-selected' : '') + '" href="#" data-product="' + product + '" data-value="' + id + '" name="' + name + '" data-uri="' + uri + '">' + name + '</a>';
	});
	options += '<a class="dropdown-item client-item" id="rescan"><b>' + javaStrings[4] + '</b></a>';
	return options;
}

function updateStatus() {
	apiToken = $('#apiTokenData').attr('data');
	var footer = $('.nowPlayingFooter');
	var logLimit = $('#logLimit').find(":selected").val();
	var dataCommands = false;
	$.get('api.php?pollPlayer&apiToken=' + apiToken + '&logLimit=' + logLimit, function (data) {
		if (data.dologout === true) {
			document.getElementById('logout').click();
		}
		if (data.hasOwnProperty("commands")) dataCommands = data.commands.replace(/\+/g, '%20');
		if (dataCommands) {
			try {
				a = JSON.parse(decodeURIComponent(dataCommands));
				updateCommands(a, false);
			} catch (e) {
				//alert(e); // error in the above string (in this case, yes)!
			}
		}
		try {
			var clientHtml = fetchClientList(data.players);
			$('#clientWrapper').html(clientHtml);
			$('#serverList').html(data.servers);
			$('#dvrList').html(data.dvrs);
			$('#updateContainer').html(data.updates);
			$('#logBody').html(formatLog(JSON.parse(data.logs)));
			if (data.hasOwnProperty(updateAvailable)) {
				showMessage("An update is available.", "An update is available for Phlex.  Click here to install it now.", 'api.php?apiToken=' + apiToken + '&installUpdates=true');
			}

			//devices = data.devs;
			var devHtml = "";
			var devCount = devices.length;
			if (JSON.stringify(devices) !== JSON.stringify(lastDevices)) {
				$.each(data.devs, function (id, device) {
					var devString = createStaticDevice(device.Name, device.IP, device.Port, id);
					devHtml += devString[0];
				});
				$('#deviceBody').append(devHtml);
				//setListeners();
				if (devices.length !== devCount) lastDevices = devices;
			}

			ddText = $('.dd-selected').text();
			$('.ddLabel').html(ddText);
			data.playerStatus = JSON.parse(data.playerStatus);
			var TitleString;
			var playBtn = $('#playBtn');
			var pauseBtn = $('#pauseBtn');
			if ((data.playerStatus.status === 'playing') || (data.playerStatus.status === 'paused')) {
				switch (data.playerStatus.status) {
					case 'playing':
						playBtn.hide();
						pauseBtn.show();
						break;
					case 'paused':
						pauseBtn.hide();
						playBtn.show();
						break;
				}
				var mr = data.playerStatus.mediaResult;
				if (hasContent(mr)) {
					var resultTitle = mr.title;
					var resultType = mr.type;
					var resultYear = mr.year;
					var thumbPath = mr.thumb;
					var artPath = mr.art;
					var resultSummary = mr.summary;
					if (resultSummary === "") resultSummary = mr.tagline;
					var resultOffset = data.playerStatus.time;
					resultDuration = mr.duration;
					var progressSlider = document.getElementById('progressSlider');
					TitleString = resultTitle;
					if (resultType === "episode") TitleString = "S" + mr.parentIndex + "E" + mr.index + " - " + resultTitle;
					if (resultType === "track") {
						console.log("The title should be right, fucker.");
						TitleString = mr.grandparentTitle + " - " + resultTitle;
					}
					console.log("Width: " + resultOffset / resultDuration * 100)
					progressSlider.noUiSlider.set((resultOffset / resultDuration) * 100);
					if (thumbPath !== false) {
						$('#statusImage').attr('src', thumbPath).show();
					} else {
						$('#statusImage').hide();
					}
					$('#playerName').html($('.ddLabel').html());
					$('#mediaTitle').html(TitleString);
					$('#mediaSummary').html(resultSummary);
					$('.wrapperArt').css('background-image', 'url(' + artPath + ')');
					if ((!(footer.is(":visible"))) && (!(footer.hasClass('reHide')))) {
						footer.slideDown(1000);
						footer.addClass("playing");
						var sliderWidth = $('.statusWrapper').width() - $('#statusImage').width() - 60;
						$("#progressSlider").css('width', sliderWidth);
					}
				}
			} else {
				if (footer.is(":visible")) {
					footer.slideUp(1000);
					footer.removeClass("playing");
					$('.wrapperArt').css('background-image', '');
				}
			}
		} catch (e) {
			console.error(e, e.stack);
		}

	}, dataType = "json");

}

function updateCommands(data, prepend) {

	if (JSON.stringify(lastUpdate) !== JSON.stringify(data) || prepend) {
		if (!prepend) {
			lastUpdate = data;
			$('#resultsInner').html("");
		}

		$.each(data, function (i, value) {
			if (value === []) return true;
			try {
				var initialCommand = ucFirst(value.initialCommand);
				var timeStamp = ($.inArray('timeStamp', value) ? $.trim(value.timestamp) : '');
				speech = (value.speech ? value.speech : "");
				if ($(window).width() < 700) speech = speech.substring(0, 100);
				var status = (value.mediaStatus ? value.mediaStatus : "");
				itemJSON = value;
				var JSONdiv = '<a href="javascript:void(0)" id="JSON' + i + '" class="JSONPop" data="' + encodeURIComponent(JSON.stringify(value, null, 2)) + '" title="Result JSON">{JSON}</a>';
				var mediaDiv = false;
				// Build our card
				var cardResult = buildCards(value, i);
				mediaDiv = cardResult[0];
				var bgImage = cardResult[1];
				var style = bgImage ? "style='background-image:url(" + bgImage + ");background-size:cover' " : 'background-color:';
				var outLine =
					"<div class='resultDiv card' id='" + timeStamp + "'>" +
					'<button id="CARDCLOSE' + i + '" class="cardClose"><span class="material-icons">close</span></button>' +
					mediaDiv +
					"<div class='cardColors'>" +
					"<div class='cardImg'" + style + "></div>" +
					"<div class='card-img-overlay'></div>"
				"</div>";

				if (prepend) {
					$('#resultsInner').prepend(outLine);
				} else {
					$('#resultsInner').append(outLine);
				}


			} catch (e) {
				console.error(e, e.stack);
			}
			$('#JSON' + i).click(function () {
				var JSON = decodeURIComponent($(this).attr('data'));
				BootstrapDialog.alert({
					title: 'Result JSON',
					message: JSON,
					closable: true,
					buttons: [{
						label: 'Copy JSON',
						title: 'Copy JSON to clipboard',
						cssClass: 'btnAdd',
						action: function (dialogItself) {
							clipboard.copy(JSON);
						}

					}]
				});
			});

			$('#CARDCLOSE' + i).click(function () {
				var id = $(this).attr("id").replace("CARDCLOSE", "");
				var stamp = $(this).parent().attr("id");
				console.log("Removing card with id of " + id);
				$(this).parent().slideUp(750, function () {
					$(this).remove();
				});
				$.get('api.php?apiToken=' + apiToken + '&card=' + stamp, function (data) {
					lastUpdate = data;
				});
			})
			Swiped.init({
				query: '.resultDiv',
				left: 1000,
				onOpen: function () {
					$('#CARDCLOSE' + i).click();
				}
			});
		})

	}
}


function formatLog(logJSON) {
	if (lastLog !== logJSON) {
		var htmlOut = '';
		$.each(logJSON, function (index, line) {
			var skip = false;
			var alertClass;
			switch (line.level) {
				case "DEBUG":
					alertClass = "alert alert-success";
					if (logLevel === "INFO") skip = true;
					if (logLevel === "WARN") skip = true;
					if (logLevel === "ERROR") skip = true;
					break;
				case "INFO":
					alertClass = "alert alert-info";
					if (logLevel === "WARN") skip = true;
					if (logLevel === "ERROR") skip = true;
					break;
				case "WARN":
					alertClass = "alert alert-warning";
					if (logLevel === "ERROR") skip = true;
					break;
				case "ERROR":
					alertClass = "alert alert-danger";
					break;
			}
			if (!skip) {
				var logHTML = "";
				if (line.hasOwnProperty('JSON')) {
					logHTML = "<br>";
					try {
						var logJSON = JSON.parse(line.JSON);
						logHTML = logHTML + recurseJSON(logJSON);
					} catch (err) {

					}

				}
				htmlOut = htmlOut + '<div class="' + alertClass + '">' +
					'<p class="badge badge-custom"><b>' + line.time + '</b></p>' +
					'<p class="badge badge-default">' + line.caller + '</p><br>' +
					'<span>' + line.message + logHTML + '</span></div>';
			}
		});
		if (htmlOut == '') htmlOut = '<div class="alert alert-info">' +
			'<span class="badge badge-default"><b>No records found.</b></span>' +

			'</div>';
		lastLog = logJSON;
		return htmlOut;
	}
}


// Scale the dang diddly-ang slider to the correct width, as it doesn't like to be responsive by itself
$(window).on('resize', function () {
	var sliderWidth = $('.nowPlayingFooter').width() - 30;
	$("#progressSlider").css('width', sliderWidth);
});


// Show/hide the now playing footer when scrolling
var userScrolled = false;

$(window).scroll(function () {
	userScrolled = true;
});


setInterval(function () {
	if (userScrolled) {
		var pos = window.scrollY;
		var divHeight = $(".queryBtnWrap").height();
		var npFooter = $('.nowPlayingFooter');
		if (npFooter.hasClass("playing")) {
			if (pos >= divHeight) {
				npFooter.slideUp();
				npFooter.addClass('reHide');
			} else {
				npFooter.slideDown();
				npFooter.removeClass('reHide');
			}
		}
		userScrolled = false;
	}
}, 50);

function recurseJSON(json) {

	return '<pre class="prettyprint">' + JSON.stringify(json, undefined, 2) + '</pre>';


}

function buildCards(value, i) {
	var cardBg = false;
	var timeStamp = ($.inArray('timeStamp', value) ? $.trim(value.timestamp) : '');
	var title = '';
	var subtitle = '';
	var description = '';
	var initialCommand = ucFirst(value.initialCommand);
	var speech = (value.speech ? value.speech : "");
	var status = (value.mediaStatus ? value.mediaStatus : "");
	var itemJSON = value;
	var JSONdiv = '<a href="javascript:void(0)" id="JSON' + i + '" class="JSONPop" data="' + encodeURIComponent(JSON.stringify(value, null, 2)) + '" title="Result JSON">{JSON}</a>';

	if ($(window).width() < 700) speech = speech.substring(0, 100);

	if (value.hasOwnProperty('card')) {
		if ((value.card.length > 0) && (value.card instanceof Array)) {
			var cardArray = value.card;
			var card = cardArray[0];
			//Get our general variables about this media object
			if (cardArray.length === 1) {
				title = ((card.hasOwnProperty('title') && (card.title !== null)) ? card.title : '');
				subtitle = ((card.hasOwnProperty('subtitle') && (card.subtitle !== null)) ? card.subtitle : '');
				description = ((card.hasOwnProperty('formattedText')) ? card.formattedText : ((card.hasOwnProperty('description')) ? card.description : ''));
			}
			if (cardArray.length >= 2) {
				card = cardArray[Math.floor(Math.random() * cardArray.length - 1)];
			}
			if (card !== undefined) {
				if (card.hasOwnProperty('image')) {
					if (card.image.url !== null) cardBg = card.image.url;
				}
			}
		}
	}


	var htmlResult = '' +
		//<div class="card-img-overlay card-inverse">' +
		'<ul class="card-list">' +
		'<li class="card-timestamp">' + timeStamp + '</li>' +
		'<li class="card-title">' + title + '</li>' +
		'<li class="card-subtitle">' + subtitle + '</li>' +
		'<li class="card-description">' + description + '</li>' +
		'<li class="card-request card-text"><b>' + javaStrings[2] + ' </b>"' + initialCommand + '."</li>' +
		'<li class="card-reply card-text"><b>' + javaStrings[3] + ' </b> "' + speech + '</li>' +
		'<li class="card-json">' + JSONdiv + '</li>' +
		'</ul>' +
		'<br>';
	//'</div>';
	return [htmlResult, cardBg];
}

function hasContent(obj) {
	for (var key in obj) {
		if (obj.hasOwnProperty(key))
			return true;
	}
	return false;
}

function ucFirst(string) {
	if (string !== undefined && string !== null) {
		if (string.length !== 0) {
			return string.charAt(0).toUpperCase() + string.slice(1);
		}
	}
	return '';
}

function notify() {
	console.log("Image loaded: ".imgUrl);
}


function formatAMPM() {
	date = new Date();
	var hours = date.getHours();
	var minutes = date.getMinutes();
	var ampm = hours >= 12 ? 'PM' : 'AM';
	hours = hours % 12;
	hours = hours ? hours : 12; // the hour '0' should be '12'
	minutes = minutes < 10 ? '0' + minutes : minutes;
	var strTime = hours + ':' + minutes + ' ' + ampm;
	return strTime;
}

function fetchWeather() {
	$.get("https://freegeoip.net/json/", function (data) {
		city = data.city;
		state = data.region_name;
		console.log("Data: ", data + "City: " + city + " State: " + state);
		$.simpleWeather({
			location: city + ',' + state,
			woeid: '',
			unit: 'f',
			success: function (weather) {
				weatherHtml = weather.temp + String.fromCharCode(176) + weather.units.temp;
				condition = weather.code;
				console.log("Setting weather for " + city + ", " + state + " of " + condition + " and description " + weatherHtml);
			},
			error: function (error) {
				console.log("Error: ", error);
				condition = "";
			}
		});
	});

}

function setWeather() {
	fetchWeather();
	switch (condition) {
		case "0":
		case "1":
		case "2":
			weatherClass = "windy";
			break;
		case "3":
		case "4":
		case "38":
		case "39":
		case "45":
			weatherClass = "weather_thunderstorm";
			break;
		case "5":
		case "6":
		case "7":
		case "8":
		case "9":
		case "10":
			weatherClass = "weather_wind_rain";
			break;
		case "11":
		case "12":
		case "40":
			weatherClass = "weather_rain";
			break;
		case "13":
		case "14":
		case "15":
		case "16":
		case "41":
		case "42":
		case "43":
		case "46":
			weatherClass = "weather_snow";
			break;
		case "17":
		case "18":
		case "19":
		case "20":
		case "21":
		case "22":
			weatherClass = "weather_windier";
			break;
		case "23":
		case "24":
			weatherClass = "weather_windy";
			break;
		case "25":
			weatherClass = "weather_cold";
			break;
		case "26":
		case "44":
			weatherClass = "weather_cloudy";
			break;
		case "27":
			weatherClass = "weather_cloudy_night";
			break;
		case "28":
			weatherClass = "weather_cloudy_day";
			break;
		case "29":
		case "31":
		case "33":
			weatherClass = "weather_partly_cloudy_night";
			break;
		case "30":
		case "34":
			weatherClass = "weather_partly_cloudy_day";
			break;
		case "32":
		case "36":
			weatherClass = "weather_sunny";
			break;
		case "35":
			weatherClass = "weather_slush";
			break;
		case "37":
		case "47":
			weatherClass = "weather_lightning";
			break;
		default:
			weatherClass = "weather_partly_cloudy_night";
			break;

	}
	$('.weatherIcon').html('<span class="weather ' + weatherClass + '"> </span>');
	$(".timeDiv").text(formatAMPM());
	$(".tempDiv").text(weatherHtml);
}

function imgError(image) {
	image.onerror = "";
	image.src = "https://phlexchat.com/img.php?random&height=" + $(document).height() + "&width=" + $(document).width() + "&v=" + (Math.floor(Math.random() * (1084)));
	return true;
}

function createStaticDevice(name, ip, port, id) {
	if (!id) id = devices.length + 1;
	if (!name) name = "New Device " + id;
	if (!ip) ip = "0.0.0.0";
	if (!port) port = "8009";
	var nameString = 'device_' + id + '_Name';
	var ipString = 'device_' + id + '_IP';
	var portString = 'device_' + id + '_Port';
	var device = {
		'name': name,
		'ip': ip,
		'port': port
	};


	devices.push({id: device});
	device['id'] = id;
	var dataString = "<div class='card'>" +
		"<div class='card-header'>" +
		'<label for="device_' + id + '_Name" class="appLabel">Device Name:' +
		"<input type='text' id='device_" + id + "_Name' value='" + name + "' class='appInput form-control'/>" +
		'</label>' +
		'<label for="device_' + id + '_IP" class="appLabel">IP Address:' +
		"<input type='text' id='device_" + id + "_IP' value='" + ip + "' required pattern=\"^([0-9]{1,3}\\.){3}[0-9]{1,3}$\" class='appInput form-control'/>" +
		'</label>' +
		'<label for="device_' + id + '_Port" class="appLabel">Port:' +
		"<input type='text' id='device_" + id + "_Port' value='" + port + "' required pattern=\"^([0-9]{1,4}|[1-5][0-9]{4}|6[0-4][0-9]{3}|65[0-4][0-9]{2}|655[0-2][0-9]|6553[0-5])$\" class='appInput form-control'/>" +
		'</label>' +
		"</div>" +
		"</div>";
	return [dataString, device];
}

function deleteStaticDevice(id) {

}

function setListeners() {
	$("input").change(function () {
		var id;
		if ($(this).hasClass("appInput")) {
			id = $(this).attr('id');
			var value;
			if (($(this).attr('type') === 'checkbox') || ($(this).attr('type') === 'radio')) {
				value = $(this).is(':checked');
			} else {
				value = $(this).val();
			}
			if ($(this).id === 'publicAddress') {
				value = resetApiUrl($(this).val());
			}

			apiToken = $('#apiTokenData').attr('data');
			$.get('api.php?apiToken=' + apiToken, {id: id, value: value}, function () {
				if (id === 'darkTheme') {
					setTimeout(function () {
						location.reload();
					}, 1000);
					$.snackbar({content: "Theme changed, reloading page."});
				}
			});
			if ($(this).hasClass("appParam")) {
				id = $(this).parent().parent().parent().attr('id').replace("Group", "");
				$.get('api.php?apiToken=' + apiToken + '&fetchList=' + id, function (data) {
					$('#' + id + 'Profile').html(data);
				})
			}
		}
	});
}