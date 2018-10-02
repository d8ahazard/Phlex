var action = "play";
var apiToken, appName, bgs, bgWrap, cv, dvr, token, newToken, deviceID, resultDuration, logLevel, itemJSON,
	messageArray, publicIP, weatherClass, city, state, updateAvailable, scrollTimer, direction, progressSlider,
	volumeSlider;

var cleanLogs=true, couchEnabled=false, lidarrEnabled=false, ombiEnabled=false, sickEnabled=false, sonarrEnabled=false, radarrEnabled=false,
	headphonesEnabled=false, watcherEnabled=false, dvrEnabled=false, hook=false, hookPlay=false, polling=false, pollcount=false,
	hookPause=false, hookStop=false, hookCustom=false, hookFetch=false, hookSplit = false, autoUpdate = false, masterUser = false,
	noNewUsers=false, notifyUpdate=false, waiting=false, broadcastDevice="all";

var caches = null;

var forceUpdate = true;

var scrolling = false;
var lastUpdate = [];
var devices = "foo";
var staticCount = 0;
var javaStrings;

$(function () {
	// Set up variables
	$(".select").dropdown({"optionClass": "withripple"});
	$("#mainWrap").css({"top": 0});

	apiToken = $('#apiTokenData').data('token');

	bgs = $('.bg');
	bgWrap = $('#bgwrap');
	logLevel = "ALL";

	dvr = $("#plexDvr").data('enable');
	token = $('#tokenData').attr('data');
	deviceID = $('#deviceID').attr('data');
	publicIP = $('#publicIP').attr('data');
	newToken = $('#newToken').data('enable') === "true";
	updateAvailable = $('#updateAvailable').attr('data');
	var ddText = $('.dd-selected').text();

	// Initialize CRITICAL UI Elements
	$('.castArt').show();
	$('#play').addClass('clicked');
	$('.ddLabel').html(ddText);
// Hides the loading animation
});



// Self-explanatory
$(window).on("resize",function () {
	scaleElements();
});

function checkUpdate() {
	console.log("Function fired!!");
    apiToken = $('#apiTokenData').data('token');
    $.get('api.php?apiToken=' + apiToken, {checkUpdates: true}, function (data) {
    	if (data.hasOwnProperty('commits')) {
    		var count = data['commits'].length;
            if (notifyUpdate && !autoUpdate && count >= 1) {
                showMessage("Updates available!", "You have " + count + " update(s) available.", false);
            }
            if (autoUpdate && count >= 1) {
            	installUpdate();
			}
        }
        var formatted = parseUpdates(data);

        $('#updateContainer').html(formatted);
    },'json');
}

function installUpdate() {
	console.log("Installing updates!");
    apiToken = $('#apiTokenData').data('token');
    $.get('api.php?apiToken=' + apiToken, {installUpdates: true}, function (data) {
        var formatted = parseUpdates(data);
        $('#updateContainer').html(formatted);
    },'json');
}

function parseUpdates(data) {
	var tmp = "";
	console.log("Got some data: ",data);
	var revision = data['revision'];
    var html = '<div class="cardHeader">Current revision: ' + revision + '</div>';
	if (data.hasOwnProperty('commits')) {
		if(data['commits'].length > 0) {
            html += "<br><div class='cardHeader'>Missing updates:</div>";
            console.log("We've got some commit messages");
            for (var i = 0, l = data['commits'].length; i < l; i++) {
                var commit = data['commits'][i];
                console.log("Commit: ", commit);
                var short = commit['shortHead'];
                var date = commit['date'];
                var subject = commit['subject'];
                var body = commit['body'];
                tmp = '<div class="panel panel-primary">\n' +
                    '                                <div class="panel-heading cardHeader">\n' +
                    '                                    <div class="panel-title">' + short + ' - ' + date + '</div>\n' +
                    '                                </div>\n' +
                    '                                <div class="panel-body cardHeader">\n' +
                    '                                    <b>' + subject + '</b><br>' + body + '\n' +
                    '                                </div>\n' +
                    '                            </div>';
                html += tmp;
            }
        }
	}
	if (data.hasOwnProperty('last')) {
        html += "<br><div class='cardHeader'>Last Installed:</div>";
        for (var m = 0, n = data['last'].length; m < n; m++) {
            var commit2 = data['last'][m];
            var short2 = commit2['shortHead'];
            var date2 = commit2['date'];
            var subject2 = commit2['subject'];
            var body2 = commit2['body'];
            tmp = '<div class="panel panel-primary">\n' +
                '                                <div class="panel-heading cardHeader">\n' +
                '                                    <div class="panel-title">' + short2 + ' - ' + date2 + '</div>\n' +
                '                                </div>\n' +
                '                                <div class="panel-body cardHeader">\n' +
                '                                    <b>' + subject2 + '</b><br>' + body2 + '\n' +
                '                                </div>\n' +
                '                            </div>';
            html += tmp;
        }
	}
	html += "</div>";
	return html;
}
// Build the UI elements after document load
function buildUiDeferred() {
	$.material.init();
	$(".drawer-list").slideUp(700,"easeOutBounce");
	var messages = $('#messages').data('array');
	var IPString = $('#publicAddress').val() + "/api.php?";
	if (IPString.substring(0, 4) !== 'http') {
		IPString = document.location.protocol + '//' + IPString;
	}
	var sayString = IPString + "say&apiToken=" + apiToken + "&command={{TextField}}";
	cv = "";

	$('#sayURL').val(sayString);

	javaStrings = $('#strings').data('array');
	javaStrings = decodeURIComponent(javaStrings);
	javaStrings = javaStrings.replace(/\+/g, ' ');
	javaStrings = JSON.parse(javaStrings);
	scaleElements();

	setTimeout(function () {
		$('#results').css({"top": "64px", "max-height": "100%"})
	}, 500);

	$('.formpop').popover();

	if (messages !== "" && messages !== undefined) {
		messages = decodeURIComponent(messages.replace(/\+/g, '%20'));
		messageArray = JSON.parse(messages);
		loopMessages(messageArray);
		messageArray = [];
	} else {
		messageArray = [];
	}

	setListeners();
	checkUpdate();
	fetchWeather();

	$(".remove").remove();

	setInterval(function () {
		forceUpdate = false;
		updateStatus();
	}, 5000);

	setInterval(function () {
		setBackground();
	}, 1000 * 60);

	setInterval(function () {
		fetchWeather();
		checkUpdate();
	}, 10 * 1000 * 60);

	setInterval(function() {
		setTime();
	}, 1000)

}

function deviceHtml(type, deviceData) {
	var output = "";
	$.each(deviceData, function (key, device) {
		var skip = false;
	    if (device.hasOwnProperty('Id') && device.hasOwnProperty('Name') && device.hasOwnProperty('Selected')) {
            var string = "";
            var id = device["Id"];
            var name = device["Name"];
            var friendlyName = device["FriendlyName"];
            if (type === 'Broadcast') {
            	if (id === broadcastDevice) device["Selected"] = true;
			}
            var selected = ((device["Selected"]) ? ((type === 'Client') ? " dd-selected" : " selected") : "");

            if (type === 'Client') {
                string = "<a class='dropdown-item client-item" + selected + "' data-type='Client' data-id='" + id + "'>" + friendlyName + "</a>";
            } else {
                string = "<option data-type='" + type + "' value='" + id + "'" + selected + ">" + name + "</option>";
            }
            if (device.hasOwnProperty('Product')) {
            	console.log("Device type present.");
            	if (device["Product"] !== 'Cast' && type==="Broadcast") {
            		console.log("Skip this baby.");
            		skip = true;
				}
			}
            if (!skip) output += string;
        }
	});
    if (type === 'Broadcast') {
    	console.log("Generating broadcast device list here...");
    	var tmp = output;
    	var selected = (broadcastDevice === 'all') ? " selected" : "";
    	output = "<option data-type='Broadcast' value='all'" + selected + ">ALL DEVICES</option>";
        output += tmp;
    } else {
        if (type === 'Client') output += '<a class="dropdown-item client-item" data-id="rescan"><b>rescan devices</b></a>';
    }
	return output;
}

function updateDevices(newDevices) {
	$(".remove").remove();
	var newString = JSON.stringify(newDevices);
	if (newString !== devices) {
		console.log("Device array changed, updating: ", newDevices);
		if (newDevices.hasOwnProperty("Client")) {
			$('#clientWrapper').html(deviceHtml('Client', newDevices["Client"]));
            $('#broadcastList').html(deviceHtml('Broadcast', newDevices["Client"]));
        }
        if (newDevices.hasOwnProperty("Server")) $('#serverList').html(deviceHtml('Server', newDevices["Server"]));
        if (newDevices.hasOwnProperty("Dvr")) {
            if (newDevices["Dvr"].length === 0) $('.dvrGroup').hide(); else ($('.dvrGroup').show());
            $('#dvrList').html(deviceHtml('Dvr', newDevices.Dvr));
            $('.ddLabel').html($('.dd-selected').text());
        }
		devices = JSON.stringify(newDevices);
	}
}

function updateDevice(type, id) {
	var noSocket = true;
	if (noSocket) {
		apiToken = $('#apiTokenData').data('token');

		$.get('api.php?apiToken=' + apiToken, {
			device: type,
			id: id
		}, function (data) {
			updateDevices(data);
		});
	} else {
		// var data = {
		// 	action: 'device',
		// 	data: {
		// 		type: type,
		// 		id: id
		// 	}
		// };
		//doSend(data);
	}
}

function scaleElements() {
	var winWidth = $(window).width();
	var commandTest = $('#actionLabel');
	if (winWidth <= 340) commandTest.html(javaStrings[1]);
	if ((winWidth >= 341) && (winWidth <= 400)) commandTest.html(javaStrings[1]);
	if (winWidth >= 401) commandTest.html(javaStrings[0]);
	$('#logFrame').height(($(window).height()/3) * 2);
}

function setBackground() {
	//Add your images, we'll set the path in the next step
	var image = new Image();
	image.src = "https://img.phlexchat.com?height=" + $(window).height() + "&width=" + $(window).width() + "&v=" + (Math.floor(Math.random() * (1084))) + cv;
	$('#bgwrap').append("<div class='bg hidden'></div>");
	bgs = $('.bg');
	var bgWrap = document.getElementById('bgwrap');
	bgs.last().css('background-image', 'url(' + image.src + ')');
	bgs.last().fadeIn(1000);
	setTimeout(
		function () {
			while (bgWrap.childNodes.length > 1) {
				bgWrap.removeChild(bgWrap.firstChild);
			}
			bgs.last().removeClass('hidden');
			$('.imgHolder').remove();
		}, 1500);
}


function resetApiUrl(newUrl) {
	if (newUrl.substring(0, 4) !== 'http') {
		newUrl = document.location.protocol + '://' + newUrl;
	}
	return newUrl;
}


function updateStatus() {
	apiToken = $('#apiTokenData').data('token');
	var logLimit = $('#logLimit').find(":selected").val();
	if (!polling) {
		polling = true;
		pollcount = 1;
		if (forceUpdate !== false) {
            parseServerData(forceUpdate);
			polling = forceUpdate = false;

		} else {
            $.get('api.php?pollPlayer&apiToken=' + apiToken + '&logLimit=' + logLimit, function (data) {
                if (data !== null) {
                    parseServerData(data);
                }
                polling = false;
            }, "json");
        }
	} else {
		pollcount++;
		if (pollcount >= 10) {
			console.log("Breaking poll wait.");
			polling = false;
		}
	}

}

function parseServerData(data) {
	var force = (forceUpdate !== false);
    if (force) {
        buildUiDeferred();
    }

    if (data.hasOwnProperty('userData') && data.userData) {
        setUiVariables(data.userData);
        delete data.userData;
    }

    if ($('#autoUpdate').is(':checked')) {
        $('#installUpdates').hide();
    } else {
        $('#installUpdates').show();
    }

    if (force) $('.queryBtnGrp').removeClass('show');

    for (var propertyName in data) {

        if (data.hasOwnProperty(propertyName)) {
            if (propertyName !== 'ui' && propertyName !== 'playerStatus') {
                console.log("Received updated " + propertyName + " data:", data[propertyName]);
            }
            var val = data[propertyName];
            switch (propertyName) {
                case "dologout":
                    if (val === true || val === "true") document.getElementById('logout').click();
                    break;
                case "commands":
                    updateCommands(val, !force);
                    break;
                case "messages":
                    for (var i = 0, l = val.length; i < l; i++) {
                        var msg = val[i];
                        showMessage(msg.title,msg.message,msg.url);
                    }
                    break;
                case "updates":
                    $('#updateContainer').html(val);
                    break;
                case "devices":
                    updateDevices(val);
                    break;
                case "playerStatus":
                    updatePlayerStatus(val);
                    break;
                case "ui":
                case "userdata":
                    break;
                default:
                    console.log("Unknown value: " + propertyName);
            }
        }
    }
}

function setUiVariables(data) {
	console.log("Setting UI Variables: ",data);
	for (var propertyName in data) {
		if (data.hasOwnProperty(propertyName)) switch (propertyName) {
			case 'sonarrEnabled':
			case 'sickEnabled':
			case 'couchEnabled':
			case 'radarrEnabled':
			case 'ombiEnabled':
			case 'headphonesEnabled':
			case 'lidarrEnabled':
			case 'watcherEnabled':
			case 'hook':
			case 'hookPlay':
			case 'hookPause':
			case 'hookStop':
			case 'hookFetch':
			case 'hookCustom':
			case 'hookSplit':
			case 'dvrEnabled':
			case 'noNewUsers':
			case 'masterUser':
			case 'cleanLogs':
			case 'autoUpdate':
			case 'notifyUpdate':
			case 'broadcastDevice':
				var value = data[propertyName];
                try {
                    value = JSON.parse(value);
                } catch (SyntaxError) {
                	console.log("Syntax error parsing value.",data[propertyName]);
				}
				if (value === 'yes') value = true;
                if (value === 'no') value = false;
				if(window[propertyName] !== value) {
					window[propertyName] = value;
				}
				break;
			case 'publicAddress':
				value = data[propertyName];
				if(window[propertyName] !== value) {
					window[propertyName] = value;
				}
				break;
			case 'quietStart':
			case 'quietStop':
                value = data[propertyName];
				$('#'+ propertyName).val(value);
				break;
			case 'couchList':
			case 'sonarrList':
			case 'radarrList':
			case 'lidarrList':
			case 'watcherList':
			case 'ombiList':
			case 'sickList':
				var list = data[propertyName];
				$('#' + propertyName).html(list);
		}
	}
	toggleGroups();
}

function toggleDrawer(expandDrawer) {
    if (expandDrawer.hasClass("collapsed")) {
        expandDrawer.removeClass("collapsed");
        expandDrawer.slideDown(700,"easeOutBounce");
    } else {
        console.log("Collapse");
        expandDrawer.addClass("collapsed");
        expandDrawer.slideUp(700,"easeOutBounce");
    }
}

function toggleGroups() {
	var vars = {
		"sonarr": sonarrEnabled,
		"sick": sickEnabled,
		"couch": couchEnabled,
		"radarr": radarrEnabled,
		"ombi": ombiEnabled,
		"watcher": watcherEnabled,
		"headphones": headphonesEnabled,
		"lidarr": lidarrEnabled,
		"hookPlay": hookPlay,
		"hookPause": hookPause,
		"hookStop": hookStop,
		"hookFetch": hookFetch,
		"hookCustom": hookCustom,
		"hookSplit": hookSplit,
		"hook": hook,
		"dvr": dvrEnabled,
		"masterUser": masterUser,
		"autoUpdate": autoUpdate
	};

	for (var key in vars){
		if (vars.hasOwnProperty(key)) {
			var value = vars[key];
			var element = $('#'+key);
			var group = (key === 'hookSplit' || key === 'autoUpdate') ? $('.'+key+'Group') : $('#'+key+'Group');
			group = (value === 'masterUser') ?  $('.noNewUsersGroup') : group;

			if (element.prop('checked') !== value) {
				element.prop('checked', value);
			}
            if (key === 'autoUpdate') value = !value;
			if (value) {
				group.show();
			} else {
				group.hide();
			}
		}
	}
}

function updatePlayerStatus(data) {
	var footer = $('.nowPlayingFooter');
	var TitleString;
	var playBtn = $('#playBtn');
	var pauseBtn = $('#pauseBtn');

	if ((data.status === 'playing') || (data.status === 'paused')) {
		switch (data.status) {
			case 'playing':
				playBtn.hide();
				pauseBtn.show();
				break;
			case 'paused':
				pauseBtn.hide();
				playBtn.show();
				break;
		}
		var mr = data["mediaResult"];
		if (hasContent(mr)) {
			var resultTitle = mr["title"];
			var resultType = mr["type"];
			var thumbPath = mr["thumb"];
			var artPath = mr["art"];
			var resultSummary = mr["summary"];
			var tagline = mr["tagline"];
			var vs = $('#volumeSlider');
			progressSlider = $('#progressSlider').bootstrapSlider();
			volumeSlider = vs.bootstrapSlider({
				reversed : true
			});

			vs.on("change", function() {
				apiToken = $('#apiTokenData').data('token');
				var volume = $(this).val();
				var url = 'api.php?say&command=set+the+volume+to+' + volume + "+percent&apiToken=" + apiToken;
				$.get(url);
			});

			progressSlider.fadeOut();
			volumeSlider.fadeOut();

			TitleString = resultTitle;
			if (resultType === "episode") {
				TitleString = "S" + mr["parentIndex"] + "E" + mr.index + " - " + resultTitle;
				tagline = mr["grandParentTitle"] + " (" + mr.year + ") ";
			}

			if (resultType === "track") {
				TitleString = resultTitle;
				tagline = mr["grandParentTitle"] + " - " + mr["parentTitle"];
			}

			var resultOffset = data["time"];
			var volume = data["volume"];
			resultDuration = mr["duration"];
			var progress = (resultOffset / 1000);
			progressSlider.bootstrapSlider({max: resultDuration / 1000});
			progressSlider.bootstrapSlider('setValue', progress);
			volumeSlider.bootstrapSlider('setValue', parseInt(volume));
			var statusImage = $('.statusImage');
			if (thumbPath !== false) {
				statusImage.attr('src', thumbPath).show();
				scaleSlider();
			} else {
				statusImage.hide();
				scaleSlider();
			}
			$('#playerName').html($('.ddLabel').html());
			$('#mediaTitle').html(TitleString);
			$('#mediaTagline').html(tagline);
			var s1 = $('.scrollContent').height();
			var s2 = $('.scrollContainer').height();
			if ((s1 > s2 + 10) && ((s1 !== 0) && (s2 !== 0))) {
				if (scrolling !== true) startScrolling();
			} else {
				if (scrolling !== false) stopScrolling();
			}
			$('#mediaSummary').html(resultSummary);
			$('.wrapperArt').css('background-image', 'url(' + artPath + ')');
			if ((!(footer.is(":visible"))) && (!(footer.hasClass('reHide')))) {
				footer.slideDown(1000);

				scaleSlider();
				footer.addClass("playing");
			}
		}

	} else {
		if (footer.is(":visible")) {
			footer.slideUp(1000);
			stopScrolling();
			footer.removeClass("playing");
			$('.wrapperArt').css('background-image', '');
		}
	}
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
				var timeStamp = (value.hasOwnProperty('timeStamp') ? $.trim(value.timeStamp) : '');
				itemJSON = value;
				var mediaDiv;
				// Build our card
				var cardResult = buildCards(value, i);
				mediaDiv = cardResult[0];
				var bgImage = cardResult[1];
				var style = bgImage ? "data-src='" + bgImage + "'" : "style='background-color:'";
				var className = bgImage ? " filled" : "";
				var outLine =
					"<div class='resultDiv card col-xl-5 col-lg-5-5 col-md-12 noHeight"+className+"' id='" + timeStamp + "'>" +
					'<button id="CARDCLOSE' + i + '" class="cardClose"><span class="material-icons">close</span></button>' +
					mediaDiv +
					"<div class='cardColors'>" +
					"<div class='cardImg lazy' " + style + "></div>" +
					"<div class='card-img-overlay'></div>" +
				"</div>";

				if (prepend) {
					$('#resultsInner').prepend(outLine);
				} else {
					$('#resultsInner').append(outLine);
				}
				setTimeout(function(){
					var nh = $('.noHeight');
					nh.slideDown();
					nh.css("display", "");
					nh.removeClass('noHeight');
				},700);

				$('.lazy').Lazy();
			} catch (e) {
				console.error(e, e.stack);
			}
			$('#JSON' + i).click(function () {
				var jsonData = decodeURIComponent($(this).attr('data'));
				jsonData = JSON.parse(jsonData);
				jsonData = recurseJSON(jsonData);
				$('#jsonTitle').text('Result JSON');
				$('#jsonBody').html(jsonData);
				$('#jsonModal').modal('show');
			});

			$('#CARDCLOSE' + i).click(function () {
				var stamp = $(this).parent().attr("id");
				$(this).parent().slideUp(750, function () {
					$(this).remove();
				});
				apiToken = $('#apiTokenData').data('token');
				$.get('api.php?apiToken=' + apiToken + '&card=' + stamp, function (data) {
					lastUpdate = data;
				});
			});

			Swiped.init({
				query: '.resultDiv',
				left: 1000,
				onOpen: function () {
					$('#CARDCLOSE' + i).click();
				}
			});
		});
	}
}

// Scale the dang diddly-ang slider to the correct width, as it doesn't like to be responsive by itself
$(window).on('resize', function () {
	// TODO: Make sure this isn't needed anymore
	scaleSlider();
});

// Show/hide the now playing footer when scrolling
var userScrolled = false;

$(window).scroll(function () {
	userScrolled = true;
});


function scaleSlider() {
	var ps = $('#progress');
	var imgWidth = $('.statusImage').width();
	var sliderWidth = $('.nowPlayingFooter').width() - imgWidth;
	if (imgWidth === 0) {
		ps.fadeOut();
	} else {
		ps.css('width', sliderWidth);
		ps.css("left", imgWidth);
		ps.fadeIn();
	}
}

function chk_scroll(e) {
	var npFooter = $('.nowPlayingFooter');
	var el = $(e.currentTarget);
    var $el = $(el);
    if (npFooter.hasClass("playing")) {
    	var sh = el[0].scrollHeight;
    	var st = $el.scrollTop();
    	var oH = $el.outerHeight();
    	console.log("Checking", sh, $el.scrollTop(), $el.outerHeight());

        if (sh - $el.scrollTop() - $el.outerHeight() < 1) {
			console.log("bottom");
			npFooter.slideUp();
			npFooter.addClass('reHide');
		} else {
        	npFooter.slideDown();
			npFooter.removeClass('reHide');
    	}
    }
}

function recurseJSON(json) {
	return '<pre class="prettyprint">' + JSON.stringify(json, undefined, 2) + '</pre>';
}

function buildCards(value, i) {
	var cardBg = false;
	var timeStamp = (value.hasOwnProperty('timeStamp') ? $.trim(value.timeStamp) : '');
	var title = '';
	var subtitle = '';
	var description = '';
	var initialCommand = ucFirst(value["initialCommand"]);
	var speech = (value["speech"] ? value["speech"] : "");
    var JSONdiv = '<a href="javascript:void(0)" id="JSON' + i + '" class="JSONPop" data="' + encodeURIComponent(JSON.stringify(value, null, 2)) + '" title="Result JSON">{JSON}</a>';
	if ($(window).width() < 700) speech = speech.substring(0, 100);

	if (value.hasOwnProperty('cards')) {
		if ((value.cards.length > 0) && (value.cards instanceof Array)) {
			var cardArray = value.cards;
			var card = cardArray[0];
			//Get our general variables about this media object
			if (cardArray.length === 1) {
				title = ((card.hasOwnProperty('title') && (card.title !== null)) ? card.title : '');
				subtitle = ((card.hasOwnProperty('subtitle') && (card.subtitle !== null)) ? card.subtitle : '');
				description = ((card.hasOwnProperty('formattedText')) ? card.formattedText : ((card.hasOwnProperty('description')) ? card.description : ''));
			}
			if (cardArray.length >= 2) {
                		card = cardArray[Math.floor(Math.random()*cardArray.length)];
				console.log("Multiple cards, picked card: ",card);
			}
			if (card !== undefined) {
				if (card.hasOwnProperty('image')) {
					if (card.image.url !== null) cardBg = card.image.url;
				}
				if (card.hasOwnProperty('art') && cardBg === false) {
				    cardBg = card.art;
                }
                if (card.hasOwnProperty('thumb') && cardBg === false) {
					cardBg = card.thumb;
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
		'<li class="card-reply card-text"><b>' + javaStrings[3] + ' </b> "' + speech + '"</li>' +
		'<li class="card-json">' + JSONdiv + '</li>' +
		'</ul>' +
		'<br>';
	//'</div>';
	return [htmlResult, cardBg];
}

function animateContent(angle,speed)
{
	var sc = $('.scrollContent');
	var animationOffset = $('.scrollContainer').height() - sc.height();
	if (angle === 'up') {
		animationOffset = 0;
	}

	sc.animate({"marginTop": (animationOffset)+ "px"}, speed, 'swing',function() {
		scrolling = 'pause';
		direction = (direction ==="up") ? "down" : "up";
	});
}


function startScrolling(){
	if (!scrolling) {
		direction = "down";
		scrollTimer = setInterval(function () {
			if (!scrolling) {
				animateContent(direction, 3000);
				scrolling = true;
			} else {
				if (scrolling === 'pause') {
					scrolling = false;
				}
			}
		}, 5000);
	}
}

function stopScrolling() {
	if (scrolling === true) {
		scrolling = false;
		clearInterval(scrollTimer);
	}
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
			var char1 = string.split("")[0];
			return char1.toUpperCase() + string.slice(1);
		}
	}
	return '';
}

function notify() {
}



function fetchWeather() {
	var condition = "";
	$.getJSON('https://geoip.tools/v1/json', function (data) {
		city = data["city"];
		state = data["region_name"];
		console.log("City and state are " + city + " and " + state);
		$.simpleWeather({
			location: city + ',' + state,
			woeid: '',
			unit: 'f',
			success: function (weather) {
				console.log("SUCCESS!",weather);
				setWeather(weather);
			},
			error: function (error) {
				console.log("Error: ", error);
				setWeather("");
			}
		});
	});
	return condition;

}

function setWeather(weather) {
	var condition;
	if (weather !== "") {
		var cityString = weather.city + ", " + weather.region;
		var weatherHtml = weather.temp + String.fromCharCode(176) + weather.units.temp;
        condition = weather.code;
    } else {
		condition = "";
	}
	console.log("Condition? " + condition);
	console.log("Condition is '" + condition + "'. Weather html is '" + weatherHtml + "'.");
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
	$('.weatherIcon').html('<span id="city">'+cityString+'</span><span class="weather ' + weatherClass + '"> </span>');
	$(".tempDiv").text(weatherHtml);
}

function setTime() {
    var date = new Date();
    var hours = date.getHours();
    var minutes = date.getMinutes();
    var ampm = hours >= 12 ? 'PM' : 'AM';
    hours = hours % 12;
    hours = hours ? hours : 12; // the hour '0' should be '12'
    minutes = minutes < 10 ? '0' + minutes : minutes;
    var time = hours + ':' + minutes + ' ' + ampm;
    var timeDiv = $(".timeDiv");
    if (time !== timeDiv.text()) timeDiv.text(time);
}

function setListeners() {
	var id;

	// Set up other listeners - shouldn't be rendering stuff here

	// progressSlider.noUiSlider.on('end', function (values, handle) {
	// 	var value = values[handle];
	// 	apiToken = $('#apiTokenData').data('token');
	// 	var newOffset = Math.round((resultDuration * (value * .01)));
	// 	var url = 'api.php?control&command=seek&value=' + newOffset + "&apiToken=" + apiToken;
	// 	$.get(url);
	// });
	//

    $('.view-tab').on('scroll', chk_scroll);


    $('#alertModal').on('hidden.bs.modal', function () {
		loopMessages();
	});

	$("#hamburger").click(function () {
    	openDrawer();
    });

    $("#body").click(function() {
    	console.log("CLICK");
    	closeDrawer();
	});

    $("#baseFrame").click(function() {
        console.log("CLICK");
        closeDrawer();
    });


	var checkbox = $(':checkbox');
	checkbox.change(function () {
		var label = $("label[for='" + $(this).attr('id') + "']");
		var checked = ($(this).is(':checked'));
		if ($(this).data('app') === 'autoUpdate') {
            checked = !checked;
		}
		if (checked) {
			label.css("color", "#003792");
		} else {
			label.css("color", "#A1A1A1");
		}
		if ($(this).hasClass('appToggle')) {
			var appName = $(this).data('app');
			var group = $('#'+appName+'Group');
			console.log("Toggling ",appName, group);
			if (checked) {
				group.show();
			} else {
				group.hide();
			}
			window[appName] = checked;
		}
	});

	$('.avatar').click(function() {
		staticCount++;
		if (staticCount >= 14 && cv==="") {
			cv="&cage=true";
			$('#actionLabel').text("You don't say!?!?");
			setBackground();
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
		sessionStorage.clear();
		localStorage.clear();
		if (caches !== null) {
            if (caches.hasOwnProperty('phlex')) del = caches.delete('phlex');
        }
		setCookie('PHPSESSID','',1);
		setTimeout(
			function () {
				$('#mainWrap').css({"top": "-200px"});
				bgs.fadeOut(1000);
				$('.castArt').fadeOut(1000);

			}, 500);
        window.location.href = "?logout";

	});

	$(".btn").on('click', function () {
        apiToken = $('#apiTokenData').data('token');

        var serverAddress = $("#publicAddress").attr('value');
		var value, regUrl;
		if ($(this).hasClass("copyInput")) {
			value = $(this).val();
			copyString(value);
		}

		if ($(this).hasClass("testInput")) {
			var url = "";
			if ($(this).val() === 'broadcast') {
				var msg = encodeURIComponent("Flex TV is the bee's, knees, Mcgee.");
				url = 'api.php?notify=true&message=' + msg + '&apiToken=' + apiToken;
			} else {
                value = encodeURIComponent($(this).val());
                url = 'api.php?test=' + value + '&apiToken=' + apiToken
            }

			$.get(url, function (data) {
				if (data.hasOwnProperty('status')) {
					console.log("We have a msg.",data['status']);
					var msg = data['status'].replace(/"/g,"");
					console.log("Cleaned: ",msg);
					$.snackbar({content: msg});
				}
				if (data.hasOwnProperty('list')) {
					var list = data['list'];
					if (list !== false) {
						var appName = "#" + value.toLowerCase() + "Profile";
                        console.log("We have a list, appending to " + appName,list);
                        $(appName).html(list);
					}
				}

			},"json");
		}

		if ($(this).hasClass("resetInput")) {
			appName = $(this).data('value');
			if (confirm('Are you sure you want to clear settings for ' + appName + '?')) {

			}
		}

		if ($(this).hasClass("hookLnk")) {
			appName = $(this).data('value');
			var string = serverAddress + "api.php?apiToken=" + apiToken + "&notify=true&message=";
			copyString(string);
		}

        if ($(this).hasClass("logBtn")) {
			console.log("Cast logs should be fetching...");
            location.href = 'api.php?castLogs&apiToken=' + apiToken;


        }

		if ($(this).hasClass("setupInput")) {
			appName = $(this).data('value');

			$.get('api.php?setup&apiToken=' + apiToken, function (data) {
				$.snackbar({content: JSON.stringify(data).replace(/"/g, "")});
			});
			$.snackbar({content: "Setting up API.ai Bot."});
		}

		if ($(this).hasClass("linkBtn")) {
			serverAddress = $("#publicAddress").val();
			regUrl = false;
			action = $(this).data('action');
			serverAddress = encodeURIComponent(serverAddress);
			if (action === 'googlev2') regUrl = 'https://phlexchat.com/api.php?apiToken=' + apiToken + "&serverAddress=" + serverAddress;
			if (action === 'amazon') regUrl = 'https://phlexchat.com/alexaAuth.php?apiToken=' + apiToken + "&serverAddress=" + serverAddress;
			if (typeof(regUrl) === "string") {
				var newWindow = window.open(regUrl, '');
				if (window.focus) {
					newWindow.focus();
				}
			} else {
				if (action === 'test') {
					apiToken = $('#apiTokenData').data('token');

					regUrl = 'https://phlexchat.com/api.php?apiToken=' + apiToken + "&serverAddress=" + serverAddress + "&test=true";
					$.get(regUrl, function (dataReg) {
						var msg = false;
						console.log("Message: ", dataReg);
                        if (dataReg.hasOwnProperty('success')) {
							if (dataReg['success'] === true) {
								msg = "Connection successful!";
							} else {
								msg = dataReg['msg'];
								console.log("Message received: " + msg);
							}
						}
						$.snackbar({content: msg});
					},'json');
				}
			}
		}
	});


	$(document).on('click', '.client-item', function () {
		var clientId = $(this).data('id');
		if ($(this).data('id') !== "rescan") {
			$(this).siblings().removeClass('dd-selected');
			$(this).addClass('dd-selected');
			$('.ddLabel').html($('.dd-selected').text());

		} else {
			console.log("Rescanning devices.");
		}
		$("#plexClient").slideToggle();
		updateDevice('Client', clientId);
	});

	$(document).on('click', '.nav-item', function () {
		var frame = $('#logFrame');
		if($(this).hasClass('logNav')) {
			apiToken = $('#apiTokenData').data('token');
			frame.attr('src',"log.php?noHeader=true&apiToken=" + apiToken);
		} else {
			frame.attr('src',"");
		}

	});

	$(".drawer-item").on('click', function () {
		console.log("Drawer item is clicked.");
        var expandDrawer = $(".drawer-list");
        var linkVal = $(this).data("link");
		var secLabel = $("#sectionLabel");
        if ($(this).hasClass("active")) {
			console.log("Active item, nothing to do.");
		} else {
        	// Handle switching content
            if (linkVal !== "expandDrawer") {
            	var label = "<h3>" + $(this).data("label") + "</h3>";

				$('.drawer-item.active').removeClass('active');
				$(this).addClass("active");
                var currentTab = $('.view-tab.active');
                var newTab = $("#" + linkVal);
                console.log("Enabling " + linkVal + " tab.");
                currentTab.addClass('fade');
                currentTab.removeClass('active');
                newTab.removeClass('fade');
                newTab.addClass('active');
                // Collapse settings group if another button is clicked
                if (!linkVal.includes("SettingsTab")) {
                	secLabel.css("top","18px");
                    secLabel.html(label);
                    console.log("Collapse");
                    expandDrawer.addClass("collapsed");
                    expandDrawer.slideUp(700,"easeOutBounce");
                } else {
                	label = label + "(Settings)";
                	secLabel.html(label);
                	secLabel.css("top", "4px");
				}
            } else {
            	var drawerTarget = $(this).data("target");
            	expandDrawer = $('#' + drawerTarget + "Drawer");
                // If clicking the main settings header
				toggleDrawer(expandDrawer);
			}
		}
		// Close the drawer if not toggling settings group
        if (linkVal !== "expandDrawer") {
            closeDrawer();
        }
	});

	$(document).on("click change", "#serverList",function () {
		var serverID = $(this).val();
		apiToken = $('#apiTokenData').data('token');

		$.get('api.php?apiToken=' + apiToken, {
			device: 'Server',
			id: serverID
		});
	});

    $(document).on("click change", "#broadcastList",function () {
        var ID = $(this).val();
        apiToken = $('#apiTokenData').data('token');

        $.get('api.php?apiToken=' + apiToken, {
            device: 'Broadcast',
            id: ID
        });
    });

	$(document).on("click change", "#dvrList", function () {
		var serverID = $(this).val();
		apiToken = $('#apiTokenData').data('token');

		$.get('api.php?apiToken=' + apiToken, {
			device: 'Dvr',
			id: serverID
		});
	});

	$(".profileList").change(function () {
		var service = $(this).attr('id');
		var index = $(this).find('option:selected').data('index');
		apiToken = $('#apiTokenData').data('token');

		$.get('api.php?apiToken=' + apiToken, {id: service, value: index});
	});

	$("#appLanguage").change(function () {
		var lang = $(this).find('option:selected').data('value');
		apiToken = $('#apiTokenData').data('token');

		$.get('api.php?apiToken=' + apiToken, {id: "appLanguage", value: lang});
		$.snackbar({content: "Language changed, reloading page."});
		setTimeout(function () {
			location.reload();
		}, 1000);
	});

	// This handles sending and parsing our result for the web UI.
	$("#executeButton").click(function () {
		console.log("Execute clicked!");
		$('.load-bar').show();
		var command = $('#commandTest').val();
		if (command !== '') {
			command = command.replace(/ /g, "+");
			var url = 'api.php?say&web=true&command=' + command + '&apiToken=' + apiToken;
			apiToken = $('#apiTokenData').data('token');
			waiting = true;
			setTimeout(function()  {
				clearLoadBar();
			},10000);
			$.get(url, function () {
				$('.load-bar').hide();
				waiting = false;
			});
		}
	});

	var client = $('#client');

	client.click(function () {
		var pos = $(this).position();
		var width = $(this).outerWidth();
		var side = $(this).data("position");
		//show the menu directly over the placeholder
		var string3 = "";
		var pc = $("#plexClient");
		if (side === "left") {
		    console.log("Going left");
			pc.css("left", '0px');
		} else {
            pc.css("right", '0px');
            console.log("Going right");
		}
		pc.slideToggle();
	});


	$(".expandWrap").click(function () {
		$(this).children('.expand').slideToggle();
	});

	$("#sendLog").on('click', function () {
		apiToken = $('#apiTokenData').data('token');

		$.get('api.php?sendlog&apiToken=' + apiToken);
	});


	$('#commandTest').keypress(function (event) {
		if (event.keyCode === 13) {
			$('#executeButton').click();
		}
	});
	$('#plexServerEnabled').change(function () {
		$('#plexGroup').toggle();
	});

	$('#apiEnabled').change(function () {
		$('.apiGroup').toggle();
	});


	$('#resolution').change(function () {
		apiToken = $('#apiTokenData').data('token');

		var res = $(this).find('option:selected').data('value');
		$.get('api.php?apiToken=' + apiToken, {id: 'plexDvrResolution', value: res});
	});

	$('#checkUpdates').click(function () {
		checkUpdate();
	});

	$('#installUpdates').click(function () {
		console.log("Trying to install...");
		installUpdate();
	});

	document.addEventListener('DOMContentLoaded', function () {
		if (!Notification) {
			alert('Desktop notifications not available in your browser. Try Chromium.');
			return;
		}

		if (Notification["permission"] !== "granted")
			Notification.requestPermission();
	});

	// Update our status every 10 seconds?  Should this be longer?  Shorter?  IDK...

	$('.controlBtn').click(function () {
		var myId = $(this).attr("id");
		myId = myId.replace("Btn", "");
		if (myId === "play") {
			$('#playBtn').hide();
			$('#pauseBtn').show();
		}
		if (myId === "pause") {
			$('#playBtn').show();
			$('#pauseBtn').hide();
		}
		apiToken = $('#apiTokenData').data('token');

		$.get('api.php?say&noLog=true&command=' + myId + "&apiToken=" + apiToken);
	});

    $(document).on('click', '#autoUpdate', function () {
		var value = $(this).is(':checked');
		if (value) {
			$('#installUpdates').hide();
		} else {
            $('#installUpdates').show();
		}
    });

	$(document).on('change', '.appInput', function () {
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

		if ($(this).hasClass('appToggle') && id !== 'autoUpdate') {
			id = id + "Enabled";
		}

		apiToken = $('#apiTokenData').data('token');
		$.get('api.php?apiToken=' + apiToken, {id: id, value: value}, function (data) {
			console.log("No, really...");
			if (data === "valid") {
				if (window.hasOwnProperty(id)) {
					console.log("Hey, this has a global variable, changing it from " + window[id]);
					window[id] = value;
				}
				console.log("SUCCESS!");
				$.snackbar({content: "Value saved successfully."});
			} else {
				$.snackbar({content: "Invalid entry specified for " + id + "."});
				$(this).val("");
			}

			if (id === 'darkTheme') {
				setTimeout(function () {
					location.reload();
				}, 1000);
				$.snackbar({content: "Theme changed, reloading page."});
			}
		});

	});
}

function clearLoadBar() {
	if (waiting) {
		$('.load-bar').hide();
	}
}

function closeDrawer() {
	var drawer = $('#sideMenu');
	if (drawer.css("left") === '0px') {
        drawer.animate({
            left: '-352px'
        }, 200);
	}
	var clientWrap = $('#plexClient');
	if (clientWrap.is(':visible')) clientWrap.slideToggle();

}

function openDrawer() {
    var drawer = $('#sideMenu');
    if (drawer.css("left") === '-352px') {
        drawer.animate({
            left: '0'
        }, 200);
    }
}

function setCookie(key, value, days) {
    var expires = new Date();
    if (days) {
        expires.setTime(expires.getTime() + (days * 24 * 60 * 60 * 1000));
        document.cookie = key + '=' + value + ';expires=' + expires.toUTCString();
    } else {
        document.cookie = key + '=' + value + ';expires=Fri, 30 Dec 9999 23:59:59 GMT;';
    }
}


$(window).on("load",function () {
	$('body').addClass('loaded');
	var uiData = $('#uiData').data('default');

	if ('requestIdleCallback' in window) {
		forceUpdate = uiData;
		requestIdleCallback(updateStatus);
	} else {
		setTimeout(updateStatus, 1);
	}

});

function copyString(data) {
    var dummy = document.createElement("input");
    document.body.appendChild(dummy);
    dummy.setAttribute("id", "dummy_id");
    document.getElementById("dummy_id").value=JSON.stringify(data);
    dummy.select();
    document.execCommand("copy");
    document.body.removeChild(dummy);
    $.snackbar({content: "Successfully copied data."});
}