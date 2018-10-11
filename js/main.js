var action = "play";
var apiToken, appName, bgs, bgWrap, cv, dvr, token, resultDuration, logLevel, itemJSON,
	messageArray, weatherClass, city, state, scrollTimer, direction, progressSlider,
	volumeSlider;

var firstPoll = true;

var cleanLogs=true, couchEnabled=false, lidarrEnabled=false, ombiEnabled=false, sickEnabled=false, sonarrEnabled=false, radarrEnabled=false,
	headphonesEnabled=false, watcherEnabled=false, delugeEnabled=false, downloadstationEnabled=false, sabnzbdEnabled=false, utorrentEnabled=false,
	transmissionEnabled=false, dvrEnabled=false, hook=false, hookPlay=false, polling=false, pollcount=false,
	hookPause=false, hookStop=false, hookCustom=false, hookFetch=false, hookSplit = false, autoUpdate = false, masterUser = false,
	noNewUsers=false, notifyUpdate=false, waiting=false, broadcastDevice="all";

// Show/hide the now playing footer when scrolling
var userScrolled = false;

var clickCount = 0, clickTimer=null;

var appColor = "var(--theme-accent)";
var caches = null;

var forceUpdate = true;

var scrolling = false;
var lastUpdate = [];
var devices = "foo";
var staticCount = 0;
var javaStrings = [];

// A global array of Setting Keys that correlate to an input type
var SETTING_KEYTYPES = {
    Label: 'text',
    Uri: 'text',
    Token: 'text',
    List: 'select',
    Newtab: 'checkbox',
    Search: 'checkbox',
    Enabled: 'checkbox',
    Profile: 'profile'
};

// Settings sections for auto-generation
var SETTINGS_SECTIONS = {
    ombi: {
        icon: 'search',
        items: ['ombi']
    },
    movies: {
        icon: 'movie',
        items: ['couch', 'radarr', 'watcher']
    },
    shows: {
        icon: 'live_tv',
        items: ['sick', 'sonarr']
    },
    music: {
        icon: 'music_note',
        items: ['headphones','lidarr']
    },
    download: {
        icon: 'cloud_download',
        items: ['deluge', 'downloadstation', "nzbhydra", 'sabnzbd', 'transmission', 'utorrent']
    }
};

// Specific elements and properties that an application can have. Only needed when it's also a fetcher, etc.
// Otherwise, we can just specify a name below.
var APP_DEFAULTS = {
    ombi: {
        Token: "Token",
        Label: "Ombi",
        Profile: false,
        Search: true
    },
    couch: {
        Token: "Token",
        Label: "Couchpotato",
        Profile: true,
        Search: true
    },
    radarr: {
        Token: "Token",
        Label: "Radarr",
        Profile: true,
        Search: true
    },
    watcher: {
        Token: "Token",
        Label: "Watcher",
        Profile: true,
        Search: true
    },
    sick: {
        Token: "Token",
        Label: "Sickbeard/Sickrage",
        Profile: true,
        Search: true
    },
    sonarr: {
        Token: "Token",
        Label: "Sonarr",
        Profile: true,
        Search: true
    },
    headphones: {
        Token: "Token",
        Label: "Headphones",
        Profile: false,
        Search: true
    },
    lidarr: {
        Token: "Token",
        Label: "Lidarr",
        Profile: true,
        Search: true
    }
};

// I think this can be supplanted by something else, it was just necessary when I added it
var APP_TITLES = [
    "sonarr",
    "sick",
    "couch",
    "radarr",
    "ombi",
    "headphones",
    "lidarr",
    "watcher",
    "deluge",
    "downloadstation",
    "nzbhydra",
    "sabnzbd",
    "utorrent",
    "transmission"
];

// Self explainatory
var APP_COLORS = {
    "sonarr": "#36c6f4",
    "sick": "#2674b2",
    "downloadstation": "#3c6daf",
    "deluge": "#304663",
    "lidarr": "#00a65b",
    "utorrent": "#76b83f",
    "radarr": "#ffc230",
    "sabnzbd": "#c99907",
    "couch": "#e6521d",
    "ombi": "#a7401c",
    "transmission": "#b90900"
};

// Same as colors
var APP_ICONS = {
    "sonarr": "muximux-sonarr",
    "radarr": "muximux-radarr",
    "sick": "muximux-sick",
    "couch": "muximux-couch",
    "ombi": "muximux-ombi",
    "headphones": "muximux-headphones3",
    "utorrent": "muximux-utorrent",
    "sabnzbd": "muximux-sabnzbd",
    "downloadstation": "muximux-synology",
    "nzbhydra": "muximux-nzbhydra",
    "transmission": "muximux-transmission"
};

var PROFILE_APPS = [
    "sonarr",
    "radarr",
    "lidarr",
    "watcher",
    "couch",
    "headphones"
];

// Initialize global variables, special classes
$(function () {
    $(".select").dropdown({"optionClass": "withripple"});
	$("#mainWrap").css({"top": 0});

	// We do need to embed this in the page, just for the first query back to the server
	apiToken = $('#apiTokenData').data('token');

	bgs = $('.bg');
	bgWrap = $('#bgwrap');
	logLevel = "ALL";

	// Initialize CRITICAL UI Elements
	$('.castArt').show();
	$('#play').addClass('clicked');
    // Hides the loading animation
    $('body').addClass('loaded');

});

// This fires after the page is completely ready
$(window).on("load", function() {
    fetchData();

    getServerStatus();
    getCurrentActivityViaPlex();
    // getLibraryStats();
    // getPopularMovies('30', '5');
    // getPopularTvShows('30', '5');
    // getTopPlatforms('30', '5');
    // getTopContentRatings(['movie', 'show'], [], 6);
    // getTopGenres(['movie', 'show'], [], 6);

    // getTopTag() is definitely a work in progress
    //getTopTag('contentRating');
    //getTopTag('genre');
    //getTopTag('year');
});

// Scale the dang diddly-ang slider to the correct width, as it doesn't like to be responsive by itself
$(window).on('resize', function () {
    scaleSlider();
    setBackground();
    scaleElements();
});

$('#ghostDiv').on('click', function() {
    closeDrawer();
    closeClientList();
});


$(window).on('scroll', function () {
    userScrolled = true;
});

// This is what should fetch data from the Server and build the UI
function fetchData() {
    if (!polling) {
        polling = true;
        pollcount = 1;
        var uri = 'api.php?fetchData&force=' + firstPoll + '&apiToken=' + apiToken;
        $.get(uri, function (data) {
            if (data !== null) {
                parseData(data);
                for (var app in PROFILE_APPS) {
                    app = PROFILE_APPS[app];
                    var list = app + "List";
                    var profile = app + "Profile";
                    var element = $('#' + list);

                    if (window.hasOwnProperty(list) && window.hasOwnProperty(profile)) {
                        console.log("Window has props for " + list + " and " + profile);
                        list = window[list];
                        profile = window[profile];
                        if (list && profile) {
                            var index = 0;
                            if (list.hasOwnProperty(profile)){
                                for(var i = 0; i < list.length; i++) {
                                    for(var key in list[i] ) {
                                        if (key === 'profile') index = i;
                                    }
                                }
                                var value = list[profile];
                                if (element.length) {
                                    var oldVal = element.val();
                                    if (oldVal !== value) {
                                        element.val(value);
                                        console.log("Setting profile for " + app + " to " + value);
                                        console.log("Old value is " + oldVal);
                                    }
                                }
                            }
                        }
                    }
                }
            }
            polling = false;
            firstPoll = false;
        }, "json");

    } else {
        pollcount++;
        if (pollcount >= 10) {
            console.log("Breaking poll wait.");
            polling = false;
        }
    }

}

function parseData(data) {
    var force = (firstPoll !== false);
    if (force) {
        console.log("Parsing data from server: ",data);
        if (data.hasOwnProperty('strings')) javaStrings = data['strings'];
        buildUiDeferred();
        buildSettingsPages(data);
    }



    if (data.hasOwnProperty('userData')) {
        updateUi(data['userData']);
        delete data['userData'];
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
                case 'strings':
                case "ui":
                case "userdata":
                    break;
                default:
                    console.log("Unknown value: " + propertyName);
            }
        }
    }
}

function checkUpdate() {
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
	var revision = data['revision'];
    var html = '<div class="cardHeader">Current revision: ' + revision + '</div>';
	if (data.hasOwnProperty('commits')) {
		if(data['commits'].length > 0) {
            html += "<br><div class='cardHeader'>Missing updates:</div>";
            for (var i = 0, l = data['commits'].length; i < l; i++) {
                var commit = data['commits'][i];
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
	$(".drawer-list").slideUp(500);
	var messages = $('#messages').data('array');
	var IPString = $('#publicAddress').val() + "/api.php?";
	if (IPString.substring(0, 4) !== 'http') {
		IPString = document.location.protocol + '//' + IPString;
	}
	var sayString = IPString + "say&apiToken=" + apiToken + "&command={{TextField}}";
	cv = "";

	$('#sayURL').val(sayString);
	scaleElements();

	setTimeout(function () {
		$('#results').css({"top": "64px", "max-height": "100%"});
        $('.userWrap').show();
        $('.avatar').show();

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
		fetchData();
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

function drawerClick(element) {
    clickCount = 0;
    var expandDrawer = $(".drawer-list");
    var linkVal = element.data("link");
    var secLabel = $("#sectionLabel");
    if (!element.hasClass("active")) {
        // Handle switching content
        switch (linkVal) {
            case 'expandDrawer':
                var drawerTarget = element.data("target");
                expandDrawer = $('#' + drawerTarget + "Drawer");
                // If clicking the main settings header
                toggleDrawer(expandDrawer, element);
                break;
            case 'client':
                var clientId = element.data('id');
                updateDevice('Client', clientId);
                break;
            default:
                var label = element.data("label");
                var activeItem = $('.drawer-item.active');
                if (typeof element.data('src') !== 'undefined') {
                    var frameSrc = element.data('src');
                    var frameTarget = $('#' + element.data('frame'));
                    if (frameTarget.attr('src') !== frameSrc) {
                        frameTarget.attr('src', frameSrc);
                    }
                    $('#refresh').show();
                } else {
                    $('#refresh').hide();
                }
                var color = "var(--theme-accent)";
                if (typeof element.data('color') !== 'undefined') {
                    color = element.data('color');
                }
                appColor = color;
                colorItems(color, element);
                activeItem.removeClass('active');
                element.addClass("active");
                var currentTab = $('.view-tab.active');
                var newTab = $("#" + linkVal);
                currentTab.addClass('fade');
                currentTab.removeClass('active');
                newTab.removeClass('fade');
                newTab.addClass('active');
                // Change label if it's a setting group
                if (!linkVal.includes("SettingsTab")) {
                    secLabel.css("margin-top","15px");
                    secLabel.html(label);
                } else {
                    label = label + "<br><span class='settingLabel'>(Settings)</span>";
                    secLabel.html(label);
                    secLabel.css("margin-top", "4px");
                }
                var frame = $('#logFrame');

                if (linkVal === 'logTab') {
                    apiToken = $('#apiTokenData').data('token');
                    $('.load-barz').show();
                    frame.attr('src',"log.php?noHeader=true&apiToken=" + apiToken);
                } else {
                    $('.load-barz').hide();
                    frame.attr('src',"");
                }
        }
        if (linkVal !== "expandDrawer") {

        } else {

        }
    }
    // Close the drawer if not toggling settings group
    if (linkVal !== "expandDrawer") {
        closeDrawer();
    }
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
            var selected = ((device["Selected"]) ? ((type === 'Client' || type === 'ClientDrawer') ? " dd-selected" : " selected") : "");

            if (type === 'Client') {
                string = "<a class='dropdown-item client-item" + selected + "' data-type='Client' data-id='" + id + "'>" + friendlyName + "</a>";
            } else if (type ==='ClientDrawer') {
                var iconType = "label_important";
                if (device['Product'] === "Cast") iconType = "cast";
                var clientSpan = "<span class='barBtn'><i class='material-icons colorItem barIcon'>" + iconType + "</i></span>" + friendlyName;
                if (device["Selected"]) {
                    $('#clientBtn').html(clientSpan);
                } else {
                    string = "<div class='drawer-item btn"+selected+"' data-link='client' data-id='" + id + "'>" +
                        clientSpan + "</div>";
                }
            } else {
                string = "<option data-type='" + type + "' value='" + id + "'" + selected + ">" + name + "</option>";
            }
            if (device.hasOwnProperty('Product')) {
            	if (device["Product"] !== 'Cast' && type==="Broadcast") {
            		skip = true;
				}
			}
            if (!skip) output += string;
        }
	});
    if (type === 'Broadcast') {
    	var tmp = output;
    	var selected = (broadcastDevice === 'all') ? " selected" : "";
    	output = "<option data-type='Broadcast' value='all'" + selected + ">ALL DEVICES</option>";
        output += tmp;
    } else {
        if (type === 'Client') output += '<a class="dropdown-item client-item" data-id="rescan"><b>rescan devices</b></a>';
        if (type === 'ClientDrawer') output += '<div class="drawer-item btn" data-link="client" data-id="rescan"><b>rescan devices</b></div>';
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
            $('#ClientDrawer').html(deviceHtml('ClientDrawer', newDevices["Client"]));
            $('#broadcastList').html(deviceHtml('Broadcast', newDevices["Client"]));
            var selected = $('.dd-selected');
            $('.ddLabel').html(selected.text());
            colorItems(appColor, selected);
        }
        if (newDevices.hasOwnProperty("Server")) $('#serverList').html(deviceHtml('Server', newDevices["Server"]));
        if (newDevices.hasOwnProperty("Dvr")) {
            var dvrGroup = $('#dvrGroup');
            if (newDevices["Dvr"].length > 0) {
                dvrGroup.show();
            } else {
                dvrGroup.hide();
            }
            $('#dvrList').html(deviceHtml('Dvr', newDevices.Dvr));
        }
		devices = JSON.stringify(newDevices);
	}
}

function updateDevice(type, id) {
    console.log("Setting " + type + " to device with ID " + id);
	var noSocket = true;
	if (noSocket) {
		if (type === 'Client') {
            if (id !== "rescan") {
                $('.client-item.dd-selected').removeClass('.dd-selected');
                $('.drawer-item.dd-selected').removeClass('.dd-selected');
                var clientDiv = $("div").find("[data-id='" + id + "']");
                clientDiv.addClass('dd-selected');
                $('.ddLabel').html($('.dd-selected').text());
            } else {
                $('#loadbar').show();
            }
        }

        apiToken = $('#apiTokenData').data('token');
        $.get('api.php?apiToken=' + apiToken, {
			device: type,
			id: id
		}, function (data) {
			updateDevices(data);
			if (id === 'rescan') {                
                $.snackbar({content: "Device rescan completed."});
                $('#loadbar').hide();
            }

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
	image.src = "https://img.phlexchat.com?new=true&height=" + $(window).height() + "&width=" + $(window).width() + "&v=" + (Math.floor(Math.random() * (1084))) + cv;
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

function updateUi(data) {
    var appItems = {
        ignore: ["plexUserName", "plexEmail", "plexAvatar", "plexPassUser", "lastScan", "appLanguage", "hasPlugin", "masterUser", "alertPlugin", "plexClientId", "plexServerId", "plexDvrId", "ombiUrl", "ombiAuth", "deviceId", "isWebApp", "deviceName", "revision", "updates","quietEnd"],
        num: ["returnItems", "rescanTime", "searchAccuracy", "quietStart", "plexDvrStartOffsetMinutes", "plexDvrEndOffsetMinutes", "quietStop"],
        checkbox: ["plexDvrNewAirings", "darkTheme", "notifyUpdate", "shortAnswers", "autoUpdate", "cleanLogs", "forceSSL", "noNewUsers"],
        text: ["publicAddress"],
        select: ["plexDvrResolution"]
    };
    if (data.length !== 0) {
        console.log("Updating UI Data: ", data);
        for (var propertyName in data) {
            if (data.hasOwnProperty(propertyName)) {
                var value = data[propertyName];
                if (value === 'yes') value = true;
                if (value === 'no') value = false;
                if (value === "true") value = true;
                if (value === "false") value = false;
                var elementType = false;
                for (var keyName in SETTING_KEYTYPES) {
                    if (propertyName.indexOf(keyName) > -1) {
                        elementType = SETTING_KEYTYPES[keyName];
                    }
                }

                if (!elementType) {
                    for (var secType in appItems) {
                        var secNames = appItems[secType];
                        for (var secName in secNames) {
                            if (secNames.hasOwnProperty(secName)) {
                                secName = secNames[secName];
                                if (secName === propertyName) {
                                    elementType = secType;
                                    break;
                                }
                            }
                        }
                        if (elementType) break;
                    }
                }

                if (elementType) {
                    var element = $('#' + propertyName);
                    var updated = false;
                    switch (elementType) {
                        case 'checkbox':
                            if (element.prop('checked') !== value) {
                                element.prop('checked', value);
                                updated = true;
                            }
                            break;
                        case 'text':
                            if (element.val() !== value) {
                                element.val(value);
                                updated = true;
                            }
                            break;
                        case 'num':
                            if (element.val() !== value) {
                                element.val(value);
                                updated = true;
                            }
                            break;
                        case 'ignore':
                            break;
                        case 'profile':
                            break;
                        case 'select':
                            if (value) {
                                buildList(value, element);
                                profile = false;
                                if (data.hasOwnProperty(propertyName.replace("List","Profile"))) {
                                    var profile = data[propertyName.replace("List","Profile")];
                                    if (element.find(":selected").val() !== value) {
                                        element.val(profile);
                                        element.val(value).prop('selected', value);
                                        updated = true;
                                    }
                                }
                            }
                            break;
                        default:
                    }
                    var announce = false;
                    if (window.hasOwnProperty(propertyName)) {
                        if (window[propertyName] !== value) {
                            window[propertyName] = value;
                            announce = true;
                        }
                    } else {
                        window[propertyName] = value;
                    }
                    var force = (forceUpdate !== false);
                    if (!force && announce && updated) {
                        $.snackbar({content: "Value for " + propertyName + " has changed."});
                    }

                } else {
                    console.log("You need to add a handler for " + propertyName);
                }
            }
        }
        toggleGroups();
    }
}

function toggleDrawer(expandDrawer, element) {
    if (expandDrawer.hasClass("collapsed")) {
        element.addClass("opened");
        expandDrawer.removeClass("collapsed");
        expandDrawer.slideDown(500);
    } else {
        element.removeClass('opened');
        expandDrawer.addClass("collapsed");
        expandDrawer.slideUp(500);
    }
}

function toggleClientList() {
    var pc = $("#plexClient");
    if (!pc.hasClass('open')) {
        $('#ghostDiv').show();
        setTimeout(function () {
            pc.addClass('open');
        }, 200);
    } else {
        setTimeout(function () {
            pc.removeClass('open');
        }, 200);
        $('#ghostDiv').hide();
    }
    pc.slideToggle();
}

function closeClientList() {
    var pc = $("#plexClient");
    if (pc.hasClass('open')) {
        pc.removeClass('open');
    }
    pc.slideUp();
    $('#ghostDiv').hide();
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
        "downloadstation": downloadstationEnabled,
        "deluge": delugeEnabled,
        "transmission": transmissionEnabled,
        "utorrent": utorrentEnabled,
        "sabnzbd": sabnzbdEnabled,
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
                addAppGroup(key);
                group.show();
            } else {
                removeAppGroup(key);
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
					displayCardModal(outLine);
				} else {
					$('#resultsInner').append(outLine);
				}
                $('#loadbar').hide();
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
			$('#JSON' + i).on('click', function () {
				var jsonData = decodeURIComponent($(this).attr('data'));
				jsonData = JSON.parse(jsonData);
				jsonData = recurseJSON(jsonData);
				$('#jsonTitle').text('Result JSON');
				$('#jsonBody').html(jsonData);
				$('#jsonModal').modal('show');
			});

			$('#CARDCLOSE' + i).on('click', function () {
				var stamp = $(this).parent().attr("id");
				$(this).parent().slideUp(750, function () {
					$(this).remove();
				});
				apiToken = $('#apiTokenData').data('token');
				console.log("Removing card: ",stamp);
				$.get('api.php?apiToken=' + apiToken + '&card=' + stamp, function (data) {
					lastUpdate = data;
				});
			});

			Swiped.init({
				query: '.resultDiv',
				left: 1000,
				onOpen: function () {
					$('#CARDCLOSE' + i).trigger('click');
				}
			});
		});
	}
}

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

function displayCardModal(card) {
	if ($('#voiceTab').hasClass('active')) {
	} else {
		var cardModal = $('#cardModal');
		var cardModalBody = $('#cardWrap');
		cardModalBody.html("");
		cardModalBody.append(card);
		cardModal.modal('show');
	}
}

function chk_scroll(e) {
	var npFooter = $('.nowPlayingFooter');
	var el = $(e.currentTarget);
    var $el = $(el);
    if (npFooter.hasClass("playing")) {
    	var sh = el[0].scrollHeight;
    	var st = $el.scrollTop();
    	var oh = $el.outerHeight();

        if (sh - st - oh < 1) {
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
    var JSONdiv = '<a href="javascript:void(0)" id="JSON' + i + '" class="JSONPop" data-json="' + encodeURIComponent(JSON.stringify(value, null, 2)) + '" title="Result JSON">{JSON}</a>';
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
	return [htmlResult, cardBg];
}

function buildList(list, element) {
    if (element === undefined) {
        console.log("YOU NEED TO DEFINE AN ELEMENT FOR ",list);
        return false;
    }
    var id = element.attr('id');
    var key = id.replace("List","Profile");
    var selected = false;
    var selVal = false;
    if (window.hasOwnProperty(key)) {
        selected = window[key];
    }

    var i = 0;
    for (var item in list) {
        if (list.hasOwnProperty(item)) {
            var opt = $('<option>',{
                text: list[item],
                id: list[item]
            });
            opt.attr('data-index',item);

                if (!selected && i === 0) selVal = list[item];
                if (selected && item === selected) selVal = list[item];
            element.append(opt);
        }
        i++;
    }
    if (selected) {
        $('#' + id).val(selVal);

    }
}

function animateContent(angle,speed) {
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

function ucFirst(str) {
    var strVal = '';
    str = str.split(' ');
    for (var chr = 0; chr < str.length; chr++) {
        strVal += str[chr].substring(0, 1).toUpperCase() + str[chr].substring(1, str[chr].length) + ' '
    }
    return strVal
}

function notify() {
}

function fetchWeather() {
	var condition = "";
	$.getJSON('https://extreme-ip-lookup.com/json/', function (data) {
		city = data["city"];
		state = data["region"];
		$.simpleWeather({
			location: city + ',' + state,
			woeid: '',
			unit: 'f',
			success: function (weather) {
				setWeather(weather);
			},
			error: function (error) {
				console.log("Error updating weather: ", error);
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

    $('#cardModalBody').on('click', function() {
    	$('#cardModal').modal('hide');
	});

	$("#hamburger").on('click', function () {
    	openDrawer();
    });

    var checkbox = $(':checkbox');
	checkbox.on('change', function () {
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
			var appName = $(this).attr('id');
			var group = $(document.getElementById(appName + 'Group'));
			//var group = $('#'+appName+'Group');
			if (checked) {
				group.show();
			} else {
				group.hide();
			}
			window[appName] = checked;
		}
	});

	$('.avatar').on('click', function() {
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

	$('#logout').on('click', function () {
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

	$("#recentBtn").on('click', function() {
		$("#recent").click();
	});

	$(".btn").on('click', function () {
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
					$.snackbar({content: msg});
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
						 if (dataReg.hasOwnProperty('success')) {
							if (dataReg['success'] === true) {
								msg = "Connection successful!";
							} else {
								msg = dataReg['msg'];
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
		updateDevice('Client', clientId);
		closeClientList();

	});

	$(document).on('click', '.drawer-item', function () {
	    clickCount++;
        if(clickCount === 1) {
            clickTimer = setTimeout(drawerClick, 250, $(this));
        } else {
            clearTimeout(clickTimer);
            clickCount = 0;
            console.log("Reloading frame source.");
            var frame = "#" + $(this).data('frame');
            $('.load-barz').show();
            $(frame,window.parent.document).attr('src',$(frame,window.parent.document).attr('src'));
            $(frame).load(function() {
                $('#loadbar').hide();
            });
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

	$(document).on('click', '#refresh', function () {
	    console.log("Refreshing tab.");
	    var frame = $('.framediv.active').find('iframe');
        $('.load-barz').show();
        $(frame,window.parent.document).attr('src',$(frame,window.parent.document).attr('src'));
        // #TODO: Add an animation to rotate the icon here.
    });

	$(document).on('change', '.profileList', function () {
	    console.log("Profile list changed.");
		var service = $(this).attr('id').replace("List","Profile");
		var index = $(this).find('option:selected').data('index');
		apiToken = $('#apiTokenData').data('token');

		$.get('api.php?apiToken=' + apiToken, {id: service, value: index});
	});

	$("#appLanguage").on('change', function () {
		var lang = $(this).find('option:selected').data('value');
		apiToken = $('#apiTokenData').data('token');

		$.get('api.php?apiToken=' + apiToken, {id: "appLanguage", value: lang});
		$.snackbar({content: "Language changed, reloading page."});
		setTimeout(function () {
			location.reload();
		}, 1000);
	});

	// This handles sending and parsing our result for the web UI.
	$("#sendBtn").on('click', function () {
		$('.load-barz').show();
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
				$('#loadBar').hide();
				waiting = false;
			});
		}
	});

    $("#smallSendBtn").on('click', function () {
        $('.load-barz').show();
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
                $('#loadbar').hide();
                waiting = false;
            });
        }
    });

	
    $('.clientBtn').on('click', function () {
		toggleClientList();
	});


	$(".expandWrap").on('click', function () {
		$(this).children('.expand').slideToggle();
	});

	$("#sendLog").on('click', function () {
		apiToken = $('#apiTokenData').data('token');

		$.get('api.php?sendlog&apiToken=' + apiToken);
	});

	$('#commandTest').on('keypress', function (event) {
		if (event.keyCode === 13) {
			$('.sendBtn').each(function(){
			    if ($(this).is(":visible")) $(this).trigger('click');
            });
		}
	});
	$('#plexServerEnabled').on('change', function () {
		$('#plexGroup').toggle();
	});

	$('#apiEnabled').on('change', function () {
		$('.apiGroup').toggle();
	});


	$('#resolution').on('change', function () {
		apiToken = $('#apiTokenData').data('token');

		var res = $(this).find('option:selected').data('value');
		$.get('api.php?apiToken=' + apiToken, {id: 'plexDvrResolution', value: res});
	});

	$('#checkUpdates').on('click', function () {
		checkUpdate();
	});

	$('#installUpdates').on('click', function () {
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

	$('.controlBtn').on('click', function () {
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

			var appName = $(this).attr('id');
			var group = $(document.getElementById(appName + 'Group'));
			//var group = $('#'+appName+'Group');
			console.log("Toggling ",appName, group);
			if (checked) {
				group.show();
			} else {
				group.hide();
			}

			window[appName] = checked;
			if (value) {
				addAppGroup(id);
            } else {
				removeAppGroup(id);
			}
			id = id + "Enabled";
		}

		if (id.indexOf('Uri') > -1) {
			console.log("IP Address changed for " + id);
			var app = id.replace("Uri","");
			$('#' + app +'Btn').data('src',value);
		}

        if (id.indexOf('Label') > -1) {
        	var appLabel = id.replace("Label","");
            var labelVal = value;
            if (labelVal === "") {
                labelVal = APP_DEFAULTS[appLabel]['Label'];
            }
            console.log("Label changed for " + appLabel + ", new value is " + labelVal);
            var appBtn = $('#' + appLabel +'Btn');
            appBtn[0].childNodes[1].nodeValue = labelVal;
            appBtn.data('label', labelVal);
        }


		apiToken = $('#apiTokenData').data('token');
		$.get('api.php?apiToken=' + apiToken, {id: id, value: value}, function (data) {
			if (data === "valid") {
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

function addAppGroup(key) {
    var container = $("#results");
    var appDrawer = $("#AppzDrawer");
    var btDiv = $("#" + key + "Btn");
    if(btDiv.length) {
        return false;
    }
    if (APP_TITLES.indexOf(key) > -1) {
    	var color = "var(--theme-accent)";
    	if (APP_COLORS.hasOwnProperty(key)) {
    		color = APP_COLORS[key];
		}
        var urlString = key + "Uri";
        var frameString = key + "Frame";
        var divString = "#" + key + "Div";
        var url = false;
        if (window.hasOwnProperty(urlString)) {
            url = window[urlString];
        } else {
        	url = "http://localhost";
		}
		var label = ucFirst(key);
        if (APP_DEFAULTS.hasOwnProperty(key)) label = APP_DEFAULTS[key]["Label"];
        if (window.hasOwnProperty(key + "Label")) {
        	if (window[key + "Label"] !== "") {
                label = window[key + "Label"];
            }
        }

        if (url) {
            var btnDiv = $('<div>', {
                class: 'drawer-item btn',
                id: key + "Btn"
            });

            var btnSpan = $('<span>', {
            	class: 'barBtn',
				id: key + "Span"
			});

            var btnIcon = $('<i>', {
            	class: 'colorItem barIcon ' + APP_ICONS[key] + ' material-icons'
			});
			btnSpan.append(btnIcon);
			btnDiv.append(btnSpan);
			btnDiv.append(label);
            appDrawer.append(btnDiv);

            btnDiv = $("#" + key + "Btn");
            btnDiv.attr('data-src', url);
            btnDiv.attr('data-token', token);
            btnDiv.attr('data-frame', frameString);
            btnDiv.attr('data-link', key + "Div");
            btnDiv.attr('data-label', label);
            btnDiv.attr('data-color', color);

            $('<div>', {
            	class: 'view-tab fade framediv',
				id: key + "Div"
			}).appendTo(container);

            var newDiv = $(divString);
            newDiv.attr("data-uri", url);
            newDiv.attr("data-token", token);
            newDiv.attr("data-target", frameString);
            newDiv.attr("data-label", label);

            $('<iframe>', {
                src: '',
                id:  frameString,
				class: 'appFrame',
                frameborder: 0,
                scrolling: 'yes'
            }).appendTo(newDiv);
        }
    }
}

function removeAppGroup(key) {
    var divItem = $("#" + key + "Div");
    var btnItem = $("#" + key + "Btn");
    if(divItem.length) divItem.remove();
    if(btnItem.length) btnItem.remove();
}

function buildSettingsPages(userData) {
	if (userData.hasOwnProperty('userData')) {
		userData = userData['userData'];
	}

	var drawer = $('#SettingsDrawer');
	var container = $('#results');

	$.each(SETTINGS_SECTIONS, function (key, data) {
		// Create drawer items
        var btnDiv = $('<div>', {
            class: 'drawer-item btn',
            id: key + "SettingBtn"
        });
        btnDiv.data('link', key + "SettingsTab");
        btnDiv.data('label', ucFirst(key));

        var btnSpan = $('<span>', {
            class: 'barBtn',
            id: key + "Span"
        });

        var btnIcon = $('<i>', {
            class: 'colorItem barIcon material-icons',
			text: data.icon
        });
        btnSpan.append(btnIcon);
        btnDiv.append(btnSpan);
        btnDiv.append(ucFirst(key));
        drawer.append(btnDiv);

        // Create settings items (Wheeee!)
		var tabDiv = $('<div>', {
			class: 'view-tab fade settingPage col-md-9 col-lg-10 col-xl-8',
			id: key + "SettingsTab"
		});

		var items = data.items;
		itemClass = "gridBox";
		if (items.length <= 1) {
			var itemClass = "gridBox-1 col-xl-8 col-lg-10 col-sm-12";
		}
		var gB = $('<div>', {
			class: itemClass
		});

        for (var itemKey in items) {
            if (items.hasOwnProperty(itemKey)) {
                itemKey = items[itemKey];
                var label = ucFirst(itemKey);
                var auth = false;
                var list = false;
                var search = false;
                if (APP_DEFAULTS.hasOwnProperty(itemKey)) {
                    auth = APP_DEFAULTS[itemKey].Token;
                    label = APP_DEFAULTS[itemKey].Label;
                    list = APP_DEFAULTS[itemKey].Profile;
                    search = APP_DEFAULTS[itemKey].Search;
                }
                var SETTINGS_INPUTS = {
                    Token: {
                        label: "Token",
                        value: auth,
                        default: ""
                    },
                    Label: {
                        label: "Label",
                        value: label,
                        default: label
                    },
                    List: {
                        label: "Quality Profile",
                        value: list
                    },
                    Search: {
                        label: "Use in searches",
                        value: search
                    },
                    Uri: {
                        label: "Uri",
                        value: true
                    },
                    Newtab: {
                        label: "Open in new tab",
                        value: true
                    }
                };

                var aC = $('<div>', {
                    class: 'appContainer card'
                });

                var cB = $('<div>', {
                    class: 'card-body'
                });

                var h = $('<h4>', {
                    class: 'cardheader',
                    text: label
                });

                var tB = $('<div>', {
                    class: 'togglebutton'
                });

                var tBl = $('<label>', {
                    class: 'appLabel checkLabel',
                    text: 'Enable'
                });

                tBl.attr('for', itemKey);

                var checked = false;
                if (userData.hasOwnProperty(itemKey + 'Enabled')) {
                    checked = userData[itemKey + 'Enabled']
                }

                var iUrl = $('<input>', {
                    id: itemKey,
                    type: 'checkbox',
                    class: 'appInput appToggle',
                    checked: checked
                });

                // Well, this just generates the header and toggle, we still need the settings body...
                tBl.append(iUrl);
                iUrl.data('app', itemKey);
                tB.append(tBl);
                cB.append(h);
                cB.append(tB);

                // Okay, now build the form-group that holds the actual settings...
                // Parent form group
                var pFg = $('<div>', {
                    class: 'form-group appGroup',
                    id: itemKey + "Group"
                });

                $.each(SETTING_KEYTYPES, function (sKey, sType) {
                    if (SETTINGS_INPUTS.hasOwnProperty(sKey)) {
                        if (SETTINGS_INPUTS[sKey]['value']) {
                            var itemLabel = SETTINGS_INPUTS[sKey]['label'];
                            var itemString = itemKey + sKey;

                            var classString = "appLabel";

                            if (sType === 'checkbox') {
                                classString = classString + " appLabel-short";
                            } else {
                                var fG = $('<div>', {
                                    class: 'form-group'
                                });
                            }
                            var sL = $('<label>', {
                                class: classString,
                                text: itemLabel + ":"
                            });
                            sL.attr('for', itemString);
                            var sI;
                            var itemValue = "";
                            if (userData.hasOwnProperty(itemString)) {
                                itemValue = userData[itemString];
                            }

                            if (sType !== 'select') {
                                sI = $('<input>', {
                                    id: itemString,
                                    class: 'appInput form-control appParam ' + itemString,
                                    type: sType,
                                    value: itemValue
                                });
                            } else {
                                sI = $('<select>', {
                                    id: itemString,
                                    class: 'form-control profileList ' + itemString
                                });
                            }
                            sL.append(sI);
                            if (sType === 'checkbox') {
                                pFg.append(sL);
                            } else {
                                fG.append(sL);
                                pFg.append(fG);
                            }
                        }
                    }
                });
                cB.append(pFg);
                aC.append(cB);
                gB.append(aC);
                tabDiv.append(gB);
            }
		}
        container.append(tabDiv);
	});


}

function clearLoadBar() {
	if (waiting) {
		$('.load-barz').hide();
	}
}

function closeDrawer() {
	var drawer = $('#sideMenu');
	$('#ghostDiv').hide();
	if (drawer.css("left") === '0px') {
		drawer.animate({
            left: '-352px'
        }, 200);
	}
}

function openDrawer() {
    var drawer = $('#sideMenu');
    $('#ghostDiv').show();
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

function colorItems(color, element) {
	var items = ['.colorItem', '.dropdown-item', '.JSONPop'];
    for (var i = 0, l = items.length; i < l; i++) {
		$(items[i]).attr('style', 'color: ' + color);
	}
    $('.drawer-item').attr('style','');
	element.attr('style', 'background-color: ' + color + " !important");
    $('.dd-selected').attr('style', 'background-color: ' + color + " !important");
    $('::-webkit-scrollbar').attr('style','background: ' + color);
    $('.colorBg').attr('style','background-color: ' + color);
    $('#commandTest').attr('style','background-image: linear-gradient('+ color +',' + color + '),linear-gradient(#D2D2D2,#D2D2D2)');


}



