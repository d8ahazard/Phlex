var action = "play";
var token, deviceID, resultDuration, lastUpdate, itemJSON, apiToken, ombi, couch, sonarr, radarr, publicIP;

jQuery(document).ready(function($) {
	apiToken = $('#apiTokenData').attr('data');
	token = $('#tokenData').attr('data');
	deviceID = $('#deviceID').attr('data');
	publicIP = $('#publicIP').attr('data');
	sonarr = ($('#sonarr').attr('enable') == 'true');
	sick = ($('#sick').attr('enable') == 'true');
	couch = ($('#couchpotato').attr('enable') == 'true');
	radarr = ($('#radarr').attr('enable') == 'true');
	ombi = ($('#ombi').attr('enable') == 'true');
	$.material.init();
	var Logdata = $('#logData').attr('data');
	if (Logdata != "") {
		Logdata = decodeURIComponent($('#logData').attr('data').replace(/\+/g, '%20'));
		updateCommands(JSON.parse(Logdata),false);
	}
	var plexServerURI = $('#serverURI').attr('data');
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
		console.log("Slider moved, value is " + value +". Duration is currently " + resultDuration);
		var newOffset = Math.round((resultDuration * (value * .01)));
		console.log("Calculated offset would be " + newOffset);
		apiToken = $('#apiTokenData').attr('data');
		var url = plexClientURI + '/player/playback/seekTo?offset='+newOffset + '&X-Plex-Token='+ token+'&X-Plex-Client-Identifier=' + deviceID;
		$.get(url);
	});
			
	// Handle our input changes and zap them to PHP for immediate saving
	$("input").change(function(){
		if ($(this).hasClass("appInput")) {
			var id = $(this).attr('id');
			var value;
			if (($(this).attr('type') == 'checkbox') || ($(this).attr('type') == 'radio')) {
				value = $(this).is(':checked');
				console.log($(this).attr('id') + ' value set to ' + $(this).is(':checked'));
			} else {
				value = $(this).val();
				console.log($(this).attr('id') + ' value set to ' + $(this).val());
			}
			if ($(this).id == 'publicAddress') {
				resetApiUrl($(this).val());
			}
			apiToken = $('#apiTokenData').attr('data');
			var posting = $.get('api.php?apiToken=' + apiToken, {id:id, value:value});
		}
		
		
	});
	
	$(".btn").on('click', function() {
		if ($(this).hasClass("copyInput")) {
			var value = $(this).val();
			clipboard.copy(value);
			var options =  {
				content: "Successfully copied URL", // text of the snackbar
				style: "centerSnackbar", // add a custom class to your snackbar
				timeout: 100, // time in milliseconds after the snackbar autohides, 0 is disabled
				htmlAllowed: true, // allows HTML as content value
				onClose: function(){ } // callback called when the snackbar gets closed.
			}
			$.snackbar({content: "Successfully copied URL."});
		}
		
		if ($(this).hasClass("testInput")) {
			var value = $(this).attr('value');
			apiToken = $('#apiTokenData').attr('data');
			$.get('api.php?test='+ value+'&apiToken=' + apiToken,function(data) {
				var dataArray =[data];
				console.log("Data and array are " + data + " and " + JSON.stringify(dataArray));
				$.snackbar({content: JSON.stringify(dataArray[0].status).replace(/\"/g, "")});
			},dataType="json");
		}
		if ($(this).hasClass("resetInput")) {
			var appName = $(this).attr('value');
			if (confirm('Are you sure you want to clear settings for ' + appName + '?')) {
				$('.APIai').val("");
				$('.APIai').change();
			}  
		}
		
		if ($(this).hasClass("setupInput")) {
			var appName = $(this).attr('value');
			apiToken = $('#apiTokenData').attr('data');
			$.get('api.php?setup&apiToken=' + apiToken,function(data) {
				console.log("Got a result.");
				$.snackbar({content: JSON.stringify(data).replace(/\"/g, "")});
			},dataType="json");
			$.snackbar({content: "Setting up API.ai Bot."});
		}
		
		if ($(this).hasClass("linkBtn")) {
			localStorage.setItem("apiToken", apiToken);
			clipboard.copy(apiToken);
			window.alert("API Token has been copied to clipboard.  Please paste it in the new window and click 'Submit'.");
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
		$(this).siblings().removeClass('dd-selected');
		$(this).addClass('dd-selected');
	});
	
	$("#serverList").change(function(){
		var serverID = $(this).val();
		var element = $(this).find('option:selected'); 
		var serverUri = element.attr('uri');
		var serverName = element.attr('name');
		var serverToken = element.attr('token');
		var serverProduct = element.attr('product');
		apiToken = $('#apiTokenData').attr('data');
		console.log("Server should be changing to " + serverName);
		$.get('api.php?apiToken=' + apiToken, {device:'plexServer',id:serverID,uri:serverUri,name:serverName,token:serverToken,product:serverProduct});
	});
		
	$(".cmdBtn").click(function() {
		if ($(this).attr("id") != action) {
			action = $(this).attr("id");
			switch (action) {
				case "play":
					playString = "\"I want to watch\"";
					break;
				case "control":
					playString = "\"Tell Plex to\"";
					break;
				case "fetch":
					playString = "\"I want to download\"";
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
		if (command != '') {
			var playUrl, trimUrl, resultLine, statusLine;
			apiToken = $('#apiTokenData').attr('data');
			var url = 'api.php?' + action + '&command=' + command+'&apiToken=' + apiToken;
			var items;
			var d = new Date();
			var n = d.toLocaleTimeString() + ": ";
				
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
		  if(event.keyCode == 13){
			$('#executeButton').click();
		  }
	});
	
	$('#ombiEnabled').prop("checked",ombi);
	$('#couchEnabled').prop("checked",couch);
	$('#sonarrEnabled').prop("checked",sonarr);
	$('#sickEnabled').prop("checked",sick);
	$('#radarrEnabled').prop("checked",radarr);
	
	
	
	
	if ($('#plexServerEnabled').is(':checked')) {
		$('#plexGroup').show();
	} else {
		$('#plexGroup').hide();
	}
	
	if ($('#ombiEnabled').is(':checked')) {
		$('#ombiGroup').show();
	} else {
		$('#ombiGroup').hide();
	}
	
	if ($('#couchEnabled').is(':checked')) {
		$('#couchGroup').show();
	} else {
		$('#couchGroup').hide();
	}
	
	if ($('#sonarrEnabled').is(':checked')) {
		$('#sonarrGroup').show();
	} else {
		$('#sonarrGroup').hide();
	}
	
	if ($('#sickEnabled').is(':checked')) {
		$('#sickGroup').show();
	} else {
		$('#sickGroup').hide();
	}
	
	if ($('#radarrEnabled').is(':checked')) {
		$('#radarrGroup').show();
	} else {
		$('#radarrGroup').hide();
	}
	
	if ($('#apiEnabled').is(':checked')) {
		$('.apiGroup').show();
	} else {
		$('.apiGroup').hide();
	}
	
	
	
	$('#plexServerEnabled').change(function() {
		$('#plexGroup').toggle();
	});
	
	$('#ombiEnabled').change(function() {
		$('#ombiGroup').toggle();
	});
	 
	$('#couchEnabled').change(function() {
		$('#couchGroup').toggle();
	});
	
	$('#apiEnabled').change(function() {
		$('.apiGroup').toggle();
	});
	
	$('#sonarrEnabled').change(function() {
		$('#sonarrGroup').toggle();
	});
		
	$('#sickEnabled').change(function() {
		$('#sickGroup').toggle();
	});
	
	$('#radarrEnabled').change(function() {
		$('#radarrGroup').toggle();
	});
	
	
	
	// Update our status every 10 seconds?  Should this be longer?  Shorter?  IDK...
	window.setInterval(function(){updateStatus();}, 5000);
	var sliderWidth = $('.statusText').width();
	console.log("Setting slider width to " + sliderWidth);
	$("#progressSlider").css('width',sliderWidth);
	var IPString = $('#publicAddress').val() + "/api.php?";
	if (IPString.substring(0,4) != 'http') {
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
	console.log("Location detected as "+ publicIP);
	
	var clientList = $.get('api.php?clientList&apiToken=' + apiToken,function(clientData) {
		console.log("Received HTML Data from fetch. " + clientData);
		$('#clientWrapper').html(clientData);	
	}) ;
	
	
	updateStatus();
	
	
});

function resetApiUrl(newUrl) {
	if (newUrl.substring(0,4) != 'http') {
		newUrl = document.location.protocol + '://' + newUrl;
	}
	playString = newUrl + "play&apiToken="+apiToken+"&command={{TextField}}";
	controlString = newUrl + "control&apiToken="+apiToken+"&command={{TextField}}";
	fetchString = newUrl + "fetch&apiToken="+apiToken+"&command={{TextField}}";
	
}

function updateStatus() {
	apiToken = $('#apiTokenData').attr('data');
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
			ddText = $('.dd-selected').text();
			$('.ddLabel').html(ddText);
			data.playerStatus = JSON.parse(data.playerStatus);
			if (data.playerStatus.status=='playing') {
				var plexServerURI = data.playerStatus.plexServer;
				var mr = Object.values(data.playerStatus.mediaResult);
				console.log("MediaResult: ", mr);
				if (hasContent(mr)) {
					console.log("We have a result of playing.");
					var resultTitle = mr[0].title;
					console.log("Title of next item is " + resultTitle);
					var resultType = mr[0].type;
					var resultYear = mr[0].year;
					var thumbPath = mr[0].thumb;
					var artPath = mr[0].art;
					var resultKey = mr[0].key;
					var resultSummary = mr[0].summary;
					var resultOffset = data.playerStatus.time;
					resultDuration = mr[0].duration;
					var progressSlider = document.getElementById('progressSlider');
					if (resultType == "episode") {
						for(var propName in mr[0]) {
							propValue = mr[0][propName];
						}
						var TitleString = "S" + mr[0].parentIndex + "E"+mr[0].index + " - " + resultTitle;
					} else {
						var TitleString = resultTitle;
					}
					
					progressSlider.noUiSlider.set((resultOffset/resultDuration)*100);
					if (thumbPath != false) {
						$('#statusImage').attr('src',thumbPath);
						$('#statusImage').show();
					} else {
						$('#statusImage').hide();
					}
					$('#playerName').html($('.ddLabel').html());
					$('#mediaTitle').html(resultTitle);
					$('#mediaSummary').html(resultSummary);
					$('#mediaYear').html(resultYear);
					$('.wrapperArt').css('background-image','url('+artPath+')');
					$('.backArt').hide();
					var itemPath = plexServerURI+resultKey+"?X-Plex-Token="+token;
					if ((!($('.nowPlayingFooter').is(":visible")))&& (!($('.nowPlayingFooter').hasClass('reHide')))) {
						console.log("Now Playing footer is hidden, showing.");
						$('.nowPlayingFooter').slideDown();
						$('.nowPlayingFooter').addClass("playing");
						setTimeout( function(){ 
							var sliderWidth = $('.statusText').width()-30;
							$("#progressSlider").css('width',sliderWidth);
						}  , 300 );
					} 
				}
			} else {
				if ($('.nowPlayingFooter').is(":visible")) {
					$('.backArt').show();
					$('.nowPlayingFooter').slideUp();
					$('.nowPlayingFooter').removeClass("playing");
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
	
	if (JSON.stringify(lastUpdate) != JSON.stringify(data)) {
		console.log("Update commands fired, result is "+ data);
		console.log("Update commands fired, last update is" + lastUpdate);
		lastUpdate = data;
		var username = $('usernameData').attr('data');
		if (!(prepend)) $('#resultsInner').html("");
					
		for (var i in data) {
			try {
				var initialCommand = data[i].initialCommand;
				var parsedCommand = data[i].parsedCommand;
				var timeStamp = data[i].timestamp;
				var speech = "";
				speech = data[i].speech;
				console.log("Speech for this guy is " + speech);
				var status = data[i].mediaStatus;
				itemJSON = data[i];
				var resultLine = '<br><b>Initial command:</b> "' + initialCommand + '"<br><b>Parsed command:</b> "' + parsedCommand + '"';
				var mediaDiv = "";
				var JSONdiv = '<br><a href="javascript:void(0)" id="JSON'+i+'" class="JSONPop" data="'+encodeURIComponent(JSON.stringify(data[i],null,2))+'" title="Result JSON">{JSON}</a>';
				
				//var JSONdiv = "<br><div class='expandWrap'>{json}<div class='expand'>" + JSON.stringify(data[i]) + "</div></div>";
				
				if ((data[i].commandType == 'play') || (data[i].commandType == 'control')) {
					var plexServerURI = data[i].serverURI;
					var plexClientURI = data[i].clientURI;
					var plexClientName = data[i].clientName;
					var token = data[i].serverToken;
				}
			
				if (data[i].mediaResult) {
					var errstr = "ERROR";
					if (status.indexOf(errstr) == -1) {
						//Get our general variables about this media object
						var mr = Object.values(data[i].mediaResult);
						var resultTitle = mr[0].title;
						var resultYear = mr[0].year;
						var resultType = mr[0].type;
						var resultArt = mr[0].art;
						var TitleString = resultTitle;
						if (resultType == "episode") {
							var TitleString = "S" + mr[0].parentIndex + "E"+mr[0].index + " - " + resultTitle;
						}
						
						// Determine if we should be using a Plex art path, or a full path
						if (data[i].commandType != 'fetch') {
							var itemPath = plexServerURI+mr[0].key+"?X-Plex-Token="+token;
						}
						var artPath = resultArt;
						if (artPath != false) {
							var mediaDiv = '<a href="'+itemPath+'" target="_blank"><div class="card-image"><img src="' + artPath + '" alt="Loading image..." class="resultimg"><h4 class="card-image-headline">' + TitleString + '</h4></div></a>';
							
						} else {
							var mediaDiv = "";
						}
						//This adds the image and title display to relevant media
						
					}
				}
			
				statusLine = "";
				statusLine2 = "";
				if (status.indexOf('Not a media') == -1) {
					if(status.indexOf(":") != -1){
						var statusSplit = status.split(':');
						statusLine = "<b>Search status: </b>" + statusSplit[1] + "<br>";
					} else {	
						statusLine = "<b>Search status: </b>" + status + "<br>";
					}
				}
				
				if ((data[i].playResult) && (data[i].commandType == 'play') && (status.indexOf('SUCCESS') != -1)) {	
					var playUrl = data[i].playResult.url;
					var playStatus = data[i].playResult.status;
					var trimUrl = playUrl.replace(token,"XXXXXXXXXXXXXXXXXXXX");
					var trimUrl = trimUrl.substring(0,60) + "...";
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
				var outLine = "<div class='resultDiv card'><div class='card-body'>" + timeStamp + mediaDiv + resultLine + "<br>" + statusLine + statusLine2 + speech + JSONdiv + "</div></div><br>";
				
				if (prepend) {
					$('#resultsInner').prepend(outLine);		
				} else {
					$('#resultsInner').append(outLine);		
				}
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
	if ($('.nowPlayingFooter').hasClass("playing")) {
		if (pos >= divHeight) {
			$('.nowPlayingFooter').slideUp().addClass('reHide');
		} else {
			$('.nowPlayingFooter').slideDown().removeClass('reHide');
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