var action = "play";
var appName, token, deviceID, resultDuration, lastUpdate, itemJSON, apiToken, ombi, couch, sonarr, radarr, sick, publicIP, dvr, resolution, bgImg, weatherClass, city, state, weatherHtml;
var condition = null;

jQuery(document).ready(function($) {
    var bgDiv = $('.bg');
    if (bgDiv.css('display') === 'none') {
        bgDiv.fadeIn(2000);
    }
    $('#mainwrap').show("slide", { direction: "up" }, 750);
    $('.castArt').fadeIn(2000);
    dvr = $("#plexdvr").attr('enable') === 'true';
	apiToken = $('#apiTokenData').attr('data');
	token = $('#tokenData').attr('data');
	deviceID = $('#deviceID').attr('data');
	publicIP = $('#publicIP').attr('data');
    sonarr = $('#sonarr').attr('enable') === 'true';
    sick = $('#sick').attr('enable') === 'true';
    couch = $('#couchpotato').attr('enable') === 'true';
    radarr = $('#radarr').attr('enable') === 'true';
    ombi = $('#ombi').attr('enable') === 'true';
	$.material.init();
    var Logdata = $('#logData').attr('data');
	if (Logdata !== "") {
		Logdata = decodeURIComponent(Logdata.replace(/\+/g, '%20'));
		updateCommands(JSON.parse(Logdata),false);
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


    progressSlider.noUiSlider.on('end', function(values, handle){
		var value = values[handle];
		var newOffset = Math.round((resultDuration * (value * .01)));
		apiToken = $('#apiTokenData').attr('data');
		var url = plexClientURI + '/player/playback/seekTo?offset='+newOffset + '&X-Plex-Token='+ token+'&X-Plex-Client-Identifier=' + deviceID;
		$.get(url);
	});
			
	// Handle our input changes and zap them to PHP for immediate saving
	$("input").change(function(){
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
				resetApiUrl($(this).val());
			}
			apiToken = $('#apiTokenData').attr('data');
			$.get('api.php?apiToken=' + apiToken, {id:id, value:value});
			if ($(this).hasClass("appParam")) {
				id = $(this).parent().parent().parent().attr('id').replace("Group","");
				$.get('api.php?apiToken=' + apiToken + '&fetchList=' + id, function(data){
					$('#'+id + 'Profile').html(data);
				})
			}
		}
	});

	var checkbox = $(':checkbox'); 
    checkbox.change(function() {
    	var label = $("label[for='"+$(this).attr('id')+"']");
           if ($(this).is(':checked')) {
           		label.css("color", "#003792");
            } else {
                label.css("color", "#A1A1A1");
            }
    });

    checkbox.each(function(){
        var label = $("label[for='"+$(this).attr('id')+"']");
        if ($(this).is(':checked')) {
            label.css("color", "#003792");
        } else {
            label.css("color", "#A1A1A1");
        }
    });



    $(".btn").on('click', function() {
		var value;
		if ($(this).hasClass("copyInput")) {
			value = $(this).val();
			clipboard.copy(value);
			$.snackbar({content: "Successfully copied URL."});
		}

		if ($(this).hasClass("testInput")) {
			value = $(this).attr('value');
			apiToken = $('#apiTokenData').attr('data');
			$.get('api.php?test='+ value+'&apiToken=' + apiToken,function(data) {
				var dataArray =[data];
				$.snackbar({content: JSON.stringify(dataArray[0].status).replace(/"/g, "")});
			},dataType="json");
		}
		if ($(this).hasClass("resetInput")) {
			appName = $(this).attr('value');
			if (confirm('Are you sure you want to clear settings for ' + appName + '?')) {

			}  
		}

		if ($(this).hasClass("setupInput")) {
			appName = $(this).attr('value');
			apiToken = $('#apiTokenData').attr('data');
			$.get('api.php?setup&apiToken=' + apiToken,function(data) {
				$.snackbar({content: JSON.stringify(data).replace(/"/g, "")});
			},dataType="json");
			$.snackbar({content: "Setting up API.ai Bot."});
		}
		
		if ($(this).hasClass("linkBtn")) {
			localStorage.setItem("apiToken", apiToken);
			clipboard.copy(apiToken);
			var serverAddress = $('#publicAddress').val();
			var regUrl = 'https://phlexserver.cookiehigh.us/api.php?apiToken='+apiToken+"&serverAddress="+serverAddress;
			newwindow=window.open(regUrl,'');
			if (window.focus) {
				newwindow.focus();
			}
			
		}
	});
	
	
	
	$(document).on('click', '.client-item' , function() {
		var clientID = $(this).attr('value');
		var clientUri = $(this).attr('uri');
		var clientName = $(this).attr('name');
		var clientProduct = $(this).attr('product');
		apiToken = $('#apiTokenData').attr('data');
		$.get('api.php?apiToken=' + apiToken, {device:'plexClient',id:clientID,uri:clientUri,name:clientName,product:clientProduct});
		$('.ddLabel').html($(this).attr('name'));
		$('#clientURI').attr('data',decodeURIComponent($(this).attr('uri')));
		if ($(this).attr('id') !== "rescan") {
            $(this).siblings().removeClass('dd-selected');
            $(this).addClass('dd-selected');
        }
	});
	
	$("#serverList").change(function(){
		var serverID = $(this).val();
		var element = $(this).find('option:selected'); 
		var serverUri = element.attr('uri');
        var serverPublicUri = element.attr('publicaddress');
		var serverName = element.attr('name');
		var serverToken = element.attr('token');
		var serverProduct = element.attr('product');
		apiToken = $('#apiTokenData').attr('data');
		$.get('api.php?apiToken=' + apiToken, {device:'plexServer',id:serverID,uri:serverUri,publicUri:serverPublicUri,name:serverName,token:serverToken,product:serverProduct});
	});
	
	$(".profileList").change(function(){
		var service = $(this).attr('id');
		var index = $(this).find('option:selected').attr('index');
		apiToken = $('#apiTokenData').attr('data');
		$.get('api.php?apiToken=' + apiToken, {id:service,value:index});
	});
	
	$("#dvrList").change(function(){
		var serverID = $(this).val();
		var element = $(this).find('option:selected'); 
		var serverUri = element.attr('uri');
        var serverPublicUri = element.attr('publicaddress');
		var serverName = element.attr('name');
		var serverToken = element.attr('token');
		var serverProduct = element.attr('product');
		apiToken = $('#apiTokenData').attr('data');
		$.get('api.php?apiToken=' + apiToken, {device:'plexServer',id:serverID,uri:serverUri,publicUri:serverPublicUri,name:serverName,token:serverToken,product:serverProduct});
	});


	$(".cmdBtn").click(function() {
		if ($(this).attr("id") !== action) {
			action = $(this).attr("id");
			switch (action) {
				case "play":
					playString = "\"Ask Flex TV to play\"";
					break;
				case "control":
					playString = "\"Tell Flex TV to\"";
					break;
				case "fetch":
					playString = "\"Ask Flex TV to download\"";
					break;
				default: 
					return;
			}
			$('#commandIcon').text($(this).text());
			$('#control').removeClass('clicked');
			$('#play').removeClass('clicked');
			$('#fetch').removeClass('clicked');
			$(this).addClass('clicked');
			$('#settings').removeClass('clicked');
			var playString;
			
			$('#commandTest').val('');
			$('#actionLabel').html(playString);
		}
	});
	
	
	// This handles sending and parsing our result for the web UI.
	$("#executeButton").click(function() {
		var command = $('#commandTest').val();
		command = command.replace(/ /g,"+");
		if (command !== '') {
			apiToken = $('#apiTokenData').attr('data');
			var url = 'api.php?' + action + '&command=' + command+'&apiToken=' + apiToken;
			$.get(url, function(data) {
				var dataArray =[data];
				updateCommands(dataArray,true);
			},dataType="json");
			
		}
		
	});
	
	$(".expandWrap").click(function() {
		$(this).children('.expand').slideToggle();
	});
	
	$("#sendLog").on('click',function() {
		apiToken = $('#apiTokenData').attr('data');
		$.get('api.php?sendlog&apiToken=' + apiToken);
	});
	
	
	$('#commandTest').keypress(function(event){
		  if(event.keyCode === 13){
			$('#executeButton').click();
		  }
	});
	
	var ombiEnabled = $('#ombiEnabled');
	var couchEnabled = $('#couchEnabled');
	var sonarrEnabled = $('#sonarrEnabled');
	var sickEnabled = $('#sickEnabled');
	var radarrEnabled = $('#radarrEnabled');
	
	ombiEnabled.prop("checked",ombi);
	couchEnabled.prop("checked",couch);
	sonarrEnabled.prop("checked",sonarr);
	sickEnabled.prop("checked",sick);
	radarrEnabled.prop("checked",radarr);
	
	
	if (ombiEnabled.is(':checked')) {
		$('#ombiGroup').show();
	} else {
		$('#ombiGroup').hide();
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

	if (dvr) {
		$('.dvrGroup').show();
	} else {
		$('.dvrGroup').hide();
	}

	$('#plexServerEnabled').change(function() {
		$('#plexGroup').toggle();
	});
	
	ombiEnabled.change(function() {
		$('#ombiGroup').toggle();
	});
	 
	couchEnabled.change(function() {
		$('#couchGroup').toggle();
	});
	
	$('#apiEnabled').change(function() {
		$('.apiGroup').toggle();
	});
	
	sonarrEnabled.change(function() {
		$('#sonarrGroup').toggle();
	});
		
	sickEnabled.change(function() {
		$('#sickGroup').toggle();
	});
	
	radarrEnabled.change(function() {
		$('#radarrGroup').toggle();
	});

    $('#resolution').change(function() {
		apiToken = $('#apiTokenData').attr('data');
		var res = $(this).find('option:selected').attr('value');
		$.get('api.php?apiToken=' + apiToken, {id:'resolution', value:res});
	});
	
	
	
	// Update our status every 10 seconds?  Should this be longer?  Shorter?  IDK...
	window.setInterval(function(){updateStatus();}, 5000);
	var sliderWidth = $('.statusText').width();
	$("#progressSlider").css('width',sliderWidth);
	var IPString = $('#publicAddress').val() + "/api.php?";
	if (IPString.substring(0,4) !== 'http') {
		IPString = document.location.protocol + '//' + IPString;
	}
	var playString = IPString + "play&apiToken="+apiToken+"&command={{TextField}}";
	var controlString = IPString + "control&apiToken="+apiToken+"&command={{TextField}}";
	var fetchString = IPString + "fetch&apiToken="+apiToken+"&command={{TextField}}";
	var apiString = publicIP + "/api.php";
	$('#playURL').val(playString);
	$('#controlURL').val(controlString);
	$('#fetchURL').val(fetchString);
	$('#apiURL').val(apiString);

	var clientList = $.get('api.php?clientList&apiToken=' + apiToken,function(clientData) {
		$('#clientWrapper').html(clientData);
	}) ;
	
	updateStatus();
    setWeather();
    setInterval(function() {
    	setBackground();
    }, 1000 * 60);

    setInterval(function() {
    	setWeather();
    }, 30000);

});

function setBackground() {
    console.log("Setting background image.");
    var bgString = '<div class="bg"></div>';
    bgs = $('.bg');
    bgs.after(bgString);
    bgs = $('.bg');
    var urls = "https://unsplash.it/1920/1080?image=" + (Math.floor(Math.random()*(1084)));
    bgs.last().css('background-image','url('+urls+')');
    bgs.last().fadeIn(1000);

    setTimeout(
        function()
        {
            bgs.first().fadeOut(1000);

            bgs.first().remove();

        }, 5000);

}

function resetApiUrl(newUrl) {
	if (newUrl.substring(0,4) !== 'http') {
		newUrl = document.location.protocol + '://' + newUrl;
	}
	playString = newUrl + "play&apiToken="+apiToken+"&command={{TextField}}";
	controlString = newUrl + "control&apiToken="+apiToken+"&command={{TextField}}";
	fetchString = newUrl + "fetch&apiToken="+apiToken+"&command={{TextField}}";
	
}

function updateStatus() {
	apiToken = $('#apiTokenData').attr('data');
	var footer = $('.nowPlayingFooter');
	$.get('api.php?pollPlayer&apiToken=' + apiToken, function(data) {
		var dataCommands = data.commands.replace(/\+/g, '%20');
		if (dataCommands) {
			try {
				a = JSON.parse(decodeURIComponent(dataCommands));
				updateCommands(a,false);
            } catch(e) {
                //alert(e); // error in the above string (in this case, yes)!
            }
	}
		try {
			$('#clientWrapper').html(data.players);	
			$('#serverList').html(data.servers);
			$('#dvrList').html(data.dvrs);			
			ddText = $('.dd-selected').text();
			$('.ddLabel').html(ddText);
			data.playerStatus = JSON.parse(data.playerStatus);
			var TitleString;
			if ((data.playerStatus.status === 'playing') || (data.playerStatus.status === 'paused')) {
                var mr = data.playerStatus.mediaResult;
                if (hasContent(mr)) {
					var resultTitle = mr.title;
					var resultType = mr.type;
					var resultYear = mr.year;
					var thumbPath = mr.thumb;
					var artPath = mr.art;
					var resultSummary = mr.summary;
					var resultOffset = data.playerStatus.time;
					resultDuration = mr.duration;
					var progressSlider = document.getElementById('progressSlider');
                    TitleString = resultTitle + ((resultYear !== '')? "(" + resultYear + ")" : '');
					if (resultType === "episode") TitleString = "S" + mr.parentIndex + "E"+mr.index + " - " + resultTitle;
					if (resultType === "track") {
						console.log("The title should be right, fucker.");
						TitleString = mr.grandparentTitle + " - " + resultTitle;
                    }
					
					progressSlider.noUiSlider.set((resultOffset/resultDuration)*100);
					if (thumbPath != false) {
						$('#statusImage').attr('src',thumbPath).show();
					} else {
						$('#statusImage').hide();
					}
					$('#playerName').html($('.ddLabel').html());
					$('#mediaTitle').html(TitleString);
					$('#mediaSummary').html(resultSummary);
					$('.wrapperArt').css('background-image','url('+artPath+')');
					if ((!(footer.is(":visible")))&& (!(footer.hasClass('reHide')))) {
						footer.slideDown(1000);
						footer.addClass("playing");
						setTimeout( function(){
							var sliderWidth = $('.statusWrapper').width() - $('#statusImage').width()-60;
							$("#progressSlider").css('width',sliderWidth);
						}  , 300 );
					} 
				}
			} else {
				if (footer.is(":visible")) {
					footer.slideUp(1000);
					footer.removeClass("playing");
					$('.wrapperArt').css('background-image','');
				}
			}
		} catch(e) {
			console.error(e, e.stack);
		}
		
	},dataType="json");

}

function msToTime(duration) {
    var milliseconds = parseInt((duration%1000)/100)
        , seconds = parseInt((duration/1000)%60)
        , minutes = parseInt((duration/(1000*60))%60)
        , hours = parseInt((duration/(1000*60*60))%24);

    hours = (hours < 10) ? "0" + hours : hours;
    minutes = (minutes < 10) ? "0" + minutes : minutes;
    seconds = (seconds < 10) ? "0" + seconds : seconds;

    return hours + ":" + minutes + ":" + seconds + "." + milliseconds;
}

function updateCommands(data,prepend) {
	
	if (JSON.stringify(lastUpdate) !== JSON.stringify(data)) {
		console.log("Update data: ", data);
		lastUpdate = data;

		if (!(prepend)) $('#resultsInner').html("");
					
		for (var i in data) {
			try {

				var initialCommand = data[i].initialCommand;
				var parsedCommand = data[i].parsedCommand;
				var timeStamp = data[i].timestamp;
				speech = (data[i].speech ? data[i].speech : "");
				var status = (data[i].mediaStatus ? data[i].mediaStatus : "");
				itemJSON = data[i];
				var resultLine = '<br><b>Initial command:</b> "' + initialCommand + '"<br><b>Parsed command:</b> "' + parsedCommand + '"';
				var mediaDiv = "";
				var JSONdiv = '<br><a href="javascript:void(0)" id="JSON'+i+'" class="JSONPop" data="'+encodeURIComponent(JSON.stringify(data[i],null,2))+'" title="Result JSON">{JSON}</a>';

				if ((data[i].commandType === 'play') || (data[i].commandType === 'control')) {
					var plexServerURI = data[i].serverURI;
                    var plexClientName = data[i].clientName;

				}
			
				if (typeof data[i].mediaResult !== 'undefined') {
					if (status.indexOf("ERROR") === -1) {
						//Get our general variables about this media object
						var mr = data[i].mediaResult;
						var resultTitle = mr.title;
						var resultYear = mr.year;
						var resultType = mr.type;
						var resultArt = (resultType !== 'track' ? mr.art : mr.thumb);
						console.log("ResultArt for " + resultTitle + " should be " + resultArt);
						var TitleString = resultTitle;
						if (resultType == "episode") {
							var TitleString = "S" + mr.parentIndex + "E"+mr.index + " - " + resultTitle;
						}
						// Determine if we should be using a Plex path, or a full path
						if (data[i].commandType != 'fetch') {
							var itemPath = plexServerURI+mr.key+"?X-Plex-Token="+token;
						}
						if (typeof resultArt !== 'undefined') {
							var mediaDiv = '<a href="' + itemPath + '" target="_blank"><div class="card-image"><img src="' + resultArt + '" alt="Loading image..." class="resultimg"><h4 class="card-image-headline">' + TitleString + '</h4></div></a>';

						} else {
							var mediaDiv = "";
						}
					}
				}
			
				statusLine = "";
				statusLine2 = "";
				if (status.indexOf('Not a media') === -1) {
					if(status.indexOf(":") != -1){
						var statusSplit = status.split(':');
						statusLine = "<b>Search status: </b>" + statusSplit[1] + "<br>";
					} else {	
						statusLine = "<b>Search status: </b>" + status + "<br>";
					}
				}
				if ((typeof data[i].playResult !== 'undefined') && (data[i].commandType == 'play') && (status.indexOf('SUCCESS') != -1)) {	
					var playUrl = data[i].playResult.url;
					var playStatus = data[i].playResult.status;
					if (typeof playUrl !== 'undefined') {
						var trimUrl = playUrl.replace(token,"XXXXXXXXXXXXXXXXXXXX");
						var trimUrl = trimUrl.substring(0,60) + "...";
					}
					statusLine2 = "<b>Playback URL:  </b><i><a href=\"" + playUrl + "\">"+trimUrl+"</a></i><br><b>Playback status:  </b>" + playStatus + "";
				}
				if (status.indexOf('Not a media') != -1) {
					statusLine2 = "<b>Command status:  </b>Success";
				}
				if (speech != null) {
					speech = "<br><b>Speech Response:</b>  " +speech;
				} else {
					speech = "";
				}
				var num = i;
				var closeBtn = "<button class='cardClose' id="+num+"><b>x</b></button>";
				var outLine = "<div class='resultDiv card hiddenCard'><div class='card-body'>" + timeStamp + closeBtn + mediaDiv + resultLine + "<br>" + statusLine + statusLine2 + speech + JSONdiv + "</div></div><br>";
				
				if (prepend) {
					$('#resultsInner').prepend(outLine);
				} else {
					$('#resultsInner').append(outLine);		
				}
                $('.hiddenCard').show("slide", { direction: "up" }, 1750)
                $('.hiddenCard').removeClass('hiddenCard');
			} catch(e) {
				console.error(e, e.stack);
			}
			$('#JSON'+i).click(function() {
				var JSON = decodeURIComponent($(this).attr('data'));
				BootstrapDialog.alert({
					title: 'Result JSON',
					message: JSON,
					closable: true,
					buttons: [{
						label: 'Copy JSON',
						title: 'Copy JSON to clipboard',
						cssClass: 'btnAdd',
						action: function(dialogItself){
							clipboard.copy(JSON);
						}
					
					}]
					});
			});

            $('.cardClose').click(function() {
                var id = $(this).attr("id");
                console.log("We need to remove card with id of " + id);
                $(this).parent().parent().slideUp();
                $.get('api.php?apiToken=' + apiToken + '&card='+id, function(data) {
                    if (data !== "") {
                        dataArray = [data];
                        updateCommands(dataArray,true);
                    }
                    if (data === "[]") $('#resultsInner').html("");

                });
            })
		}
		
	}
}


// Scale the dang diddly-ang slider to the correct width, as it doesn't like to be responsive by itself
$(window).on('resize', function(){
	var sliderWidth = $('.nowPlayingFooter').width()-30;
	$("#progressSlider").css('width',sliderWidth);
});


// Show/hide the now playing footer when scrolling
var userScrolled = false;

$(window).scroll(function() {
  userScrolled = true;
});



setInterval(function() {
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
	
function hasContent(obj) {
    for(var key in obj) {
        if(obj.hasOwnProperty(key))
            return true;
    }
    return false;
}

function ucFirst(string) {
    return string.charAt(0).toUpperCase() + string.slice(1);
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
    minutes = minutes < 10 ? '0'+minutes : minutes;
    var strTime = hours + ':' + minutes + ' ' + ampm;
    return strTime;
}

function fetchWeather() {
    $.get("https://freegeoip.net/json/", function (data) {
        console.log("Caching data");
        city = data.city;
        state = data.region_name;
        console.log("Data: ", data + "City: "+ city + " State: " + state);
        $.simpleWeather({
            location: city + ',' + state,
            woeid: '',
            unit: 'f',
            success: function (weather) {
            	console.log("Success");
                weatherHtml = weather.temp + String.fromCharCode(176) + weather.units.temp;
                condition = weather.code;
                console.log("Setting weather for " + city + ", "+state+" of " + condition + " and description " + weatherHtml);
            },
            error: function (error) {
            	console.log("Error: ",error);
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
    $('.weatherIcon').html('<span class="weather '+weatherClass+'"> </span>');
    $(".timeDiv").text(formatAMPM());
    $(".tempDiv").text(weatherHtml);
}