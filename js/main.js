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
    Enabled: 'checkbox'
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

// Initialize global variables, special classes
$(function () {
    console.log("Running for jquery Ready.");
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
    console.log("All done processing Jquery ready.");

});

// This fires after the page is completely ready
$(window).on("load", function() {
    console.log("Running window onLoad");
    fetchData();

    // Homebase Init stuff
    // INITIALIZE OFFCANVAS MENU TOGGLES
    $('[data-toggle="offcanvas"]').on('click', function () {
        $('.offcanvas-collapse').toggleClass('open');
        $('.modal-backdrop').toggleClass('fade');
    });

    getServerStatus();
    getCurrentActivityViaPlex();
    getLibraryStats();
    getPopularMovies('30', '5');
    getPopularTvShows('30', '5');
    getTopPlatforms('30', '5');
    getTopContentRatings(['movie', 'show'], [], 6);
    getTopGenres(['movie', 'show'], [], 6);

    // getTopTag() is definitely a work in progress
    //getTopTag('contentRating');
    //getTopTag('genre');
    //getTopTag('year');
});

// Scale the dang diddly-ang slider to the correct width, as it doesn't like to be responsive by itself
$(window).on('resize', function () {
    // TODO: Make sure this isn't needed anymore
    scaleSlider();
    setBackground();
    scaleElements();
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
        console.log("Getting data from " + uri);
        $.get(uri, function (data) {
            if (data !== null) {
                parseData(data);
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
        console.log("DATA: ",data);
        if (data.hasOwnProperty('strings')) javaStrings = data['strings'];
        console.log("Java strings: ",javaStrings);
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
	$(".drawer-list").slideUp(700,"easeOutBounce");
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
    console.log("Drawer item is clicked.");
    var expandDrawer = $(".drawer-list");
    var linkVal = element.data("link");
    var secLabel = $("#sectionLabel");
    if (element.hasClass("active")) {
        console.log("Active item, nothing to do.");
    } else {
        // Handle switching content
        switch (linkVal) {
            case 'expandDrawer':
                var drawerTarget = element.data("target");
                expandDrawer = $('#' + drawerTarget + "Drawer");
                // If clicking the main settings header
                toggleDrawer(expandDrawer);
                break;
            case 'client':
                console.log("Selecting client.");
                var clientId = element.data('id');
                updateDevice('Client', clientId);
                break;
            default:
                var label = element.data("label");
                var activeItem = $('.drawer-item.active');
                if (typeof element.data('src') !== 'undefined') {
                    var frameSrc = element.data('src');
                    console.log("We have a source URL for the frame: ",frameSrc);
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
                console.log("Enabling " + linkVal + " tab.");
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
                console.log("Collapse");
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
                console.log("Device product is " + device['Product']);
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
    	console.log("Generating broadcast device list here...");
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
        }
        if (newDevices.hasOwnProperty("Server")) $('#serverList').html(deviceHtml('Server', newDevices["Server"]));
        if (newDevices.hasOwnProperty("Dvr")) {
            var dvrGroup = $('#dvrGroup');
            console.log("DVR List length is " + newDevices["Dvr"].length);
            if (newDevices["Dvr"].length > 0) {
                console.log("Showing group");
                dvrGroup.show();
            } else {
                console.log("Hiding group");
                dvrGroup.hide();
            }
            $('#dvrList').html(deviceHtml('Dvr', newDevices.Dvr));
            $('.ddLabel').html($('.dd-selected').text());
        }
		devices = JSON.stringify(newDevices);
	}
    colorItems(appColor,$('.dd-selected'));

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
		if (type === 'Client') {
            if (id !== "rescan") {
                console.log("Switching graphics, id is " + id);
                $('.client-item.dd-selected').removeClass('.dd-selected');
                $('.drawer-item.dd-selected').removeClass('.dd-selected');
                var clientDiv = $("div").find("[data-id='" + id + "']");
                clientDiv.addClass('dd-selected');
                $('.ddLabel').html($('.dd-selected').text());
            }
        }
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
                        case 'select':
                            console.log("Need to populate a profile list, ", value);
                            buildList(value, element);
                            profile = false;
                            if (data.hasOwnProperty(propertyName.replace("List","Profile"))) {
                                var profile = data[propertyName.replace("List","Profile")];
                                if (element.find(":selected").val() !== value) {
                                    $('#' + propertyName).val(profile);
                                    //$("#" + propertyName +" option[value='" + profile + "']").attr("selected", true);
                                    //element.val(value).prop('selected', value);
                                    updated = true;
                                }
                            }
                            break;
                        default:
                            if (!propertyName.indexOf('List') == -1) {
                                console.log("Please set a definition for the setting value " + propertyName);
                            }
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
		console.log("No need to show a modal, we're on teh voice tab.");
	} else {
		console.log("Displaying card modal.");
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
    	console.log("Checking", sh, $el.scrollTop(), $el.outerHeight());

        if (sh - st - oh < 1) {
			console.log("bottom");
			npFooter.slideUp();
			npFooter.addClass('reHide');
		} else {
        	npFooter.slideDown();
			npFooter.removeClass('reHide');
    	}
    }
}

function capitalize(str) {
    strVal = '';
    str = str.split(' ');
    for (var chr = 0; chr < str.length; chr++) {
        strVal += str[chr].substring(0, 1).toUpperCase() + str[chr].substring(1, str[chr].length) + ' '
    }
    return strVal
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

function buildList(list, element) {
    for (var item in list) {
        if (list.hasOwnProperty(item)) {
            var opt = $('<option>',{
                text: list[item],
                id: list[item]
            });
            opt.attr('data-index',item);
            console.log("Here's an item: ", item, list[item]);
            element.append(opt);
        }
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
	$.getJSON('https://extreme-ip-lookup.com/json/', function (data) {
		city = data["city"];
		state = data["region"];
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

    $('#cardModalBody').on('click', function() {
    	console.log("You clicked me...");
    	$('#cardModal').modal('hide');
	});

	$("#hamburger").click(function () {
    	openDrawer();
    });

    $('html').on('click', function(e) {
    	if(!$(e.target).hasClass('drawer-item')) {
			closeDrawer();
		}
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

	$("#recentBtn").on('click', function() {
		$("#recent").click();
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
		updateDevice('Client', clientId);
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
	$("#sendBtn").click(function () {
		console.log("Execute clicked!");
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
				$('.load-barz').hide();
				waiting = false;
			});
		}
	});

    $("#smallSendBtn").click(function () {
        console.log("Execute clicked!");
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
                $('.load-barz').hide();
                waiting = false;
            });
        }
    });

	var client = $('.clientBtn');

	client.click(function () {
		var side = $(this).data("position");
		//show the menu directly over the placeholder
		var pc = $("#plexClient");
		if (!pc.hasClass('open')) {
            if (side === "left") {
                console.log("Going left");
                pc.css("left", '0px');
            } else {
                pc.css("right", '5px');
                console.log("Going right");
            }
            pc.slideToggle();
            setTimeout(function () {
                pc.addClass('open');
            }, 200);


        }
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
			$('.sendBtn').each(function(){
			    if ($(this).is(":visible")) $(this).click();
            });
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
    if($("#" + key + "Btn").length) {
        return false;
    }
    if (APP_TITLES.indexOf(key) > -1) {
    	var color = "var(--theme-accent)";
    	if (APP_COLORS.hasOwnProperty(key)) {
    		color = APP_COLORS[key];
		}
        console.log("Trying to add group for " + key);
        var urlString = key + "Uri";
        var frameString = key + "Frame";
        var divString = "#" + key + "Div";
        var url = false;
        if (window.hasOwnProperty(urlString)) {
            console.log("We have urlstring");
            url = window[urlString];
        } else {
        	url = "http://localhost";
		}
		var label = capitalize(key);
        if (APP_DEFAULTS.hasOwnProperty(key)) label = APP_DEFAULTS[key]["Label"];
        if (window.hasOwnProperty(key + "Label")) {
        	if (window[key + "Label"] !== "") {
                label = window[key + "Label"];
            }
        }

        if (url) {
            console.log("We've got the goods for " + key);
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
	console.log("BUILDING PAGES.");



	var drawer = $('#SettingsDrawer');
	var container = $('#results');

	$.each(SETTINGS_SECTIONS, function (key, data) {
		console.log("Creating item for " + key);
		// Create drawer items
        var btnDiv = $('<div>', {
            class: 'drawer-item btn',
            id: key + "SettingBtn"
        });
        btnDiv.data('link', key + "SettingsTab");
        btnDiv.data('label', capitalize(key));

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
        btnDiv.append(capitalize(key));
        console.log("Appending: ",btnDiv);
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
                var label = capitalize(itemKey);
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
	if (drawer.css("left") === '0px') {
		console.log("Drawer");
        drawer.animate({
            left: '-352px'
        }, 200);
	}
    console.log("Clients");
    var clientWrap = $('#plexClient');
    if (clientWrap.hasClass('open')) {
    	clientWrap.slideToggle();
    	clientWrap.removeClass('open');
    }
    //if (clientWrap.is(':visible')) clientWrap.slideToggle();

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



