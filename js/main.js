var action = "play";
var appName, autoUpdate, token, newToken, deviceID, resultDuration, lastUpdate, logLevel, lastLog, itemJSON, apiToken, ombi, couch, sonarr, radarr, sick, publicIP, dvr, weatherClass, city, state, updateAvailable, weatherHtml;
var condition = null;

jQuery(document).ready(function($) {

    var loginBox = $('.login-box');
    if (loginBox.length > 0) {
        console.log("Hiding login box.");
        loginBox.css({"top":"-1000px"});
        $('.snackbar').snackbar("hide");
        $.snackbar({content: "Login successful!"});

    }
	logLevel = "ALL";
    var bgDiv = $('.bg');
    if (bgDiv.css('display') === 'none') {
        bgDiv.fadeIn(1000);
    }
    $('#mainwrap').css({"top": 0});
    setTimeout(
        function()
        {
            $('#results').css({"top":0,"max-height":"100%","overflow":"inherit"})
        }, 500);

    $('.castArt').fadeIn(1000);

    dvr = $("#plexDvr").attr('enable') === 'true';
	apiToken = $('#apiTokenData').attr('data');
	token = $('#tokenData').attr('data');
	deviceID = $('#deviceID').attr('data');
	publicIP = $('#publicIP').attr('data');
    newToken = $('#newToken').attr('enable') === 'true';
	sonarr = $('#sonarr').attr('enable') === 'true';
    sick = $('#sick').attr('enable') === 'true';
    couch = $('#couchpotato').attr('enable') === 'true';
    radarr = $('#radarr').attr('enable') === 'true'
    ombi = $('#ombi').attr('enable') === 'true';
    autoUpdate = $('#autoUpdate').attr('enable') === 'true';
    updateAvailable = $('#updateAvailable').attr('data');
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

	if (newToken) {
        var serverAddress = $('#publicAddress').val();
        var regUrl = 'https://phlexserver.cookiehigh.us/api.php?apiToken='+apiToken+"&serverAddress="+serverAddress;
        showMessage("New API Token Detected","A new API Token was created. Click here to re-register your server.",regUrl);
	}

	if (updateAvailable >= 1) {
		showMessage("Updates available!","You have " + updateAvailable + " update(s) available.");
	}
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
			$.get('api.php?apiToken=' + apiToken, {id:id, value:value}, function() {
                if (id==='darkTheme') setTimeout( function() { location.reload(); }, 200);
			});
			if ($(this).hasClass("appParam")) {
				id = $(this).parent().parent().parent().attr('id').replace("Group","");
				$.get('api.php?apiToken=' + apiToken + '&fetchList=' + id, function(data){
					$('#'+id + 'Profile').html(data);
				})
			}
		}
	});

	$('#logLevel').change(function(){
        logLevel = $(this).val();
        console.log("Log level changed to " + logLevel);
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

	$('#logout').click(function(){
        var bgDiv = $('.bg');
		$('#results').css({"top":"-2000px","max-height":0,"overflow":"hidden"})

        setTimeout(
            function()
            {
                $('#mainwrap').css({"top": "-200px"});
                bgDiv.fadeOut(1000);
                $('.castArt').fadeOut(1000);
            }, 500);


	});

    $(".btn").on('click', function() {
		var value,regUrl;
        var serverAddress = $('#publicAddress').val();
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
			});
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
			});
			$.snackbar({content: "Setting up API.ai Bot."});
		}
		
		if ($(this).hasClass("linkBtn")) {
			regUrl = 'https://phlexserver.cookiehigh.us/api.php?apiToken='+apiToken+"&serverAddress="+serverAddress;
			newwindow=window.open(regUrl,'');
			if (window.focus) {
				newwindow.focus();
			}
		}
        if ($(this).hasClass("alexaBtn")) {
            regUrl = 'https://phlexchat.com/alexaAuth.php?apiToken='+apiToken+"&serverAddress="+serverAddress;
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
        var serverPublicUri = element.attr('publicuri');
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


	// This handles sending and parsing our result for the web UI.
	$("#executeButton").click(function() {
		console.log("Execute clicked!");
		$('.load-bar').show();
        var command = $('#commandTest').val();
		if (command !== '') {
            command = command.replace(/ /g,"+");
			apiToken = $('#apiTokenData').attr('data');
			var url = 'api.php?say&command=' + command+'&apiToken=' + apiToken;
			$.get(url, function() {

			})
			.done(function(data) {
				var dataArray = [data];
				updateCommands(dataArray, true);
			})
			.always(function() {
				setTimeout(function(){
					$('.load-bar').hide();
				},1000);

			},dataType="json");
		}
		
	});

	$('#client').click(function() {
		console.log("CLICKED CLIENT!");
        var pos = $('#client').position();
        var width = $('#client').outerWidth();

        //show the menu directly over the placeholder
        $("#plexClient").css({
            position: "absolute",
            top: pos.bottom + "px",
            left: (pos.left - width) + "px"
        });
	})


	
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
        urlGroup.css("height","");
    } else {
        $('#hookUrlGroup').show();
        $('.hookLabel').hide();
        urlGroup.css("height","0");
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

	$('#plexServerEnabled').change(function() {
		$('#plexGroup').toggle();
	});
	
	ombiEnabled.change(function() {
		$('#ombiGroup').toggle();
	});

    autoUpdateEnabled.change(function() {
        $('#installUpdates').toggle();
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

    hookEnabled.change(function() {
        $('#hookGroup').toggle();
    });

    hookEnabled.change(function() {
        $('#hookGroup').toggle();
    });

    hookSplit.change(function() {
        $('#hookUrlGroup').toggle();
        $('.hookLabel').toggle();
    });

    hookPlay.change(function() {$('#hookPlayGroup').toggle();});
    hookPaused.change(function() {$('#hookPausedGroup').toggle();});
    hookStop.change(function() {$('#hookStopGroup').toggle();});
    hookFetch.change(function() {$('#hookFetchGroup').toggle();});
    hookCustom.change(function() {$('#hookCustomPhraseGroup').toggle();});



    $('#resolution').change(function() {
		apiToken = $('#apiTokenData').attr('data');
		var res = $(this).find('option:selected').attr('value');
		$.get('api.php?apiToken=' + apiToken, {id:'plexDvrResolution', value:res});
	});

    $('#checkUpdates').click(function() {
        apiToken = $('#apiTokenData').attr('data');
        $.get('api.php?apiToken=' + apiToken, {checkUpdates:true},function (data) {
			$('#updateContainer').html(data);
        });
    });

    $('#installUpdates').click(function() {
        apiToken = $('#apiTokenData').attr('data');
        $.get('api.php?apiToken=' + apiToken, {installUpdates:true},function (data) {
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
	var sliderWidth = $('.statusText').width();
	$("#progressSlider").css('width',sliderWidth);
	var IPString = $('#publicAddress').val() + "/api.php?";
	if (IPString.substring(0,4) !== 'http') {
		IPString = document.location.protocol + '//' + IPString;
	}
	var sayString = IPString + "say&apiToken="+apiToken+"&command={{TextField}}";
	var apiString = publicIP + "/api.php";
	$('#sayURL').val(sayString);

	var clientList = $.get('api.php?clientList&apiToken=' + apiToken,function(clientData) {
		$('#clientWrapper').html(clientData);
	}) ;

    setInterval(function(){
    	updateStatus();
    }, 5000);

    setInterval(function() {
        setBackground();
    }, 1000 * 60);

    setInterval(function() {
        setWeather();
    }, 10 * 1000 * 60);

});



function setBackground() {
    console.log("Caching background image.");
    var bgString = '<div class="bg"></div>';
    bgs = $('.bg');
    bgs.after(bgString);
    bgs = $('.bg');
    var urls = "https://unsplash.it/1920/1080?random&v=" + (Math.floor(Math.random()*(1084)));
    $('<img />').attr('src',urls,).attr("onload","setBg(this.src)").addClass("imgHolder").appendTo('body').css('display','none');

}

function setBg(imgUrl) {
	console.log("Image cached, loading.");
    bgs.last().css('background-image','url('+imgUrl+')');
    bgs.last().fadeIn(1000);

    setTimeout(
        function()
        {
            console.log("Removing first background");
            bgs.first().fadeOut(1000).remove();
            $('.imgHolder').remove();

        }, 1500);
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
	var logLimit = $('#logLimit').find(":selected").val();
	$.get('api.php?pollPlayer&apiToken=' + apiToken + '&logLimit='+ logLimit, function(data) {
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
			$('#updateContainer').html(data.updates);
			$('#logBody').html(formatLog(JSON.parse(data.logs)));
			if (data.hasOwnProperty(updateAvailable)) {
				showMessage("An update is available.","An update is available for Phlex.  Click here to install it now.",'api.php?apiToken=' + apiToken + '&installUpdates=true');
			}
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
					if (resultSummary === "") resultSummary = mr.tagline;
					var resultOffset = data.playerStatus.time;
					resultDuration = mr.duration;
					console.log("Time: "+ resultOffset + " Duration: " + resultDuration);
					var progressSlider = document.getElementById('progressSlider');
                    TitleString = resultTitle;
					if (resultType === "episode") TitleString = "S" + mr.parentIndex + "E"+mr.index + " - " + resultTitle;
					if (resultType === "track") {
						console.log("The title should be right, fucker.");
						TitleString = mr.grandparentTitle + " - " + resultTitle;
                    }
					
					progressSlider.noUiSlider.set((resultOffset/resultDuration)*100);
					if (thumbPath !== false) {
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
		lastUpdate = data;

		if (!(prepend)) $('#resultsInner').html("");

        $.each(data, function(i, value) {
            try {
				var initialCommand = ucFirst(value.initialCommand);
				var timeStamp = ($.inArray('timeStamp',value) ? $.trim(value.timestamp) : '');
				speech = (value.speech ? value.speech : "");
                if ($(window).width() < 700) speech = speech.substring(0,100);
                console.log("SPEECH: "+ speech);
				var status = (value.mediaStatus ? value.mediaStatus : "");
				itemJSON = value;
				var JSONdiv = '<a href="javascript:void(0)" id="JSON'+i+'" class="JSONPop" data="'+encodeURIComponent(JSON.stringify(value,null,2))+'" title="Result JSON">{JSON}</a>';
				var mediaDiv = false;
				// Build our card
				if (value.hasOwnProperty('card')) {
					if (($.inArray('card',value)) && (value.card !== 'false')) {
						mediaDiv = buildCards(value,i);
					}
				}
				if (! mediaDiv) {
					timeLine = '<li class="list-group-item">' + timeStamp + '</li><br>';
					mediaDiv = '<ul class="list-group list-group-flush">' +
                    '<li class="list-group-item">' + timeLine + '</li>' +
                    '<li class="list-group-item"><b>You said: </b>"' + initialCommand + '."</li>' +
					'<li class="list-group-item"><b>My reply:</b> "' +speech + '</li><br>' +
					'<li class="list-group-item">' + JSONdiv + '</li>' +
                    '</ul><br>';
                }


				var outLine =
						"<div class='resultDiv card hiddenCard'>" +
                        	mediaDiv +
						"</div><br>";
				
				if (prepend) {
					$('#resultsInner').prepend(outLine);
                    $('.hiddenCard').show("slide", { direction: "up" }, 500);
                    $('.hiddenCard').removeClass('hiddenCard');
				} else {
					$('#resultsInner').append(outLine);
                    $('.hiddenCard').show();
                    $('.hiddenCard').removeClass('hiddenCard');
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

            $('.cardClose').click(function() {
            	$(this).open();
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
            Swiped.init({
                query: '.resultDiv',
                left: 1000,
                onOpen: function() {
                    this.destroy(true)
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
                    '<span class="badge badge-default"><b>' + line.time +
                    '</span></b> - ' +
                    '<span class="badge badge-primary">' + line.caller +
                    '</span><br>' + line.message + logHTML +
                    '</div>';
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

function recurseJSON(json) {

        return '<pre class="prettyprint">' + JSON.stringify(json, undefined, 2) + '</pre>';


}
function buildCards(value,i) {
    var initialCommand = ucFirst(value.initialCommand);
    var timeStamp = ($.inArray('timeStamp',value) ? $.trim(value.timestamp) : '');
    speech = (value.speech ? value.speech : "");
    if ($(window).width() < 700) speech = speech.substring(0,100);
    var status = (value.mediaStatus ? value.mediaStatus : "");
    itemJSON = value;
    var JSONdiv = '<a href="javascript:void(0)" id="JSON'+i+'" class="JSONPop" data="'+encodeURIComponent(JSON.stringify(value,null,2))+'" title="Result JSON">{JSON}</a>';
    console.log("We got a card here...");
    var cardDiv = '';
    var cardArray = value.card;
    //Get our general variables about this media object
    if (cardArray.length === 1) {
    	var card = cardArray[0];
        // Determine if we should be using a Plex path, or a full path
        subtitle = ((card.hasOwnProperty('subtitle') && (card.subtitle != null)) ? '<h6 class="card-subtitle text-muted cardtagline">' + card.subtitle + '</h6>' : '');
        formatted_text = ((card.hasOwnProperty('formatted_text')) ? '<br><p class="card-text cardsummary">' + card.formatted_text + '</p>' : '');
        cardDiv = '' +
            '<img src="' + card.image.url + '" onerror="imgError(this);" class="card-img-top"/>' +
            '<div class="card-img-overlay card-inverse">' +
            '<p class="card-subtitle">' + $.trim(timeStamp) + '</p>' +
            '<h4 class="card-title">' + card.title + '</h4>' +
            subtitle +
            formatted_text+
            '<p class="card-text card-base">' +
            '<b>You said: </b>"' + initialCommand + '"' +
            '<br><b>My reply: </b>"' + speech + '"<br><br>' +
            JSONdiv +
            '</p><br>' +
            '</div>';
    }
    if (cardArray.length >= 2) {
        cardDiv = '<div class="card-img-top">';
        var count = cardArray.length;
        $.each(cardArray, function(l, subCard) {
            var newLine = '';
            var width = '';
            if (count >= 2) width = '50%';
            if (count >= 6) width = '33.3%';

            if (((count === 4 || count === 5) && (l===1)) || ((count >= 6) && ((l===2) || (l===5)))) newLine='<br>';

            cardDiv += '<img class="card-imgs-top" onerror="imgError(this);"  style="width:' + width + '" src="' + subCard.image.url + '" alt="' + subCard.title + '"></img>' +
                newLine;

            if ((count === 2 || count === 3) && (l ===1)) {
                return false;
            }
            if ((count === 4 || count === 5) && (l ===3)) {
                return false;
            }
            if ((count >= 6 && count <= 8) && (l ===5)) {
                return false;
            }
            if ((count >= 9) && (l ===8)) {
                return false;
            }
        })

        cardDiv += '</div><div class="card-img-overlay card-inverse">' +
            '<p class="card-subtitle">' + $.trim(timeStamp) + '</p><br>' +
            '<p class="card-text card-base">' +
            '<p class="reques-text card-text"><b>You said: </b>"' + initialCommand + '"</p>' +
            '<p class="reply-text card-text"><b>My reply: </b>"' + speech + '"</p>' +
            JSONdiv +
            '</p><br>' +
            '</div>';
    }
    return cardDiv;
}

function hasContent(obj) {
    for(var key in obj) {
        if(obj.hasOwnProperty(key))
            return true;
    }
    return false;
}

function ucFirst(string) {
	if (string != '') {
        return string.charAt(0).toUpperCase() + string.slice(1);
    } else {
		return '';
	}
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
        city = data.city;
        state = data.region_name;
        console.log("Data: ", data + "City: "+ city + " State: " + state);
        $.simpleWeather({
            location: city + ',' + state,
            woeid: '',
            unit: 'f',
            success: function (weather) {
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

function imgError(image) {
    image.onerror = "";
    image.src = "https://unsplash.it/1920/1080?random&v=" + (Math.floor(Math.random()*(1084)));
    return true;
}