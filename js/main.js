var action = "play";
var appName, token, deviceID, resultDuration, lastUpdate, itemJSON, apiToken, ombi, couch, sonarr, radarr, sick, publicIP, dvr, resolution, bgImg, weatherClass, city, state, weatherHtml;
var condition = null;

jQuery(document).ready(function($) {
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

    $('input[type=checkbox]').each(function(){
        if ($(this).is(':checked')) {
            $(this).parent().css("color", "1f50a0 !important");
        } else {
            $(this).parent().css("color", "#A1A1A1 !important");
        }

    })

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

	$("input").click(function(){
        if ($(this).attr('type') === 'checkbox') {
            if ($(this).is(':checked')) {
                $(this).parent().css("color", "1f50a0 !important");
            } else {
                $(this).parent().css("color", "#A1A1A1 !important");
            }
        }
	})

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
		var serverName = element.attr('name');
		var serverToken = element.attr('token');
		var serverProduct = element.attr('product');
		apiToken = $('#apiTokenData').attr('data');
		$.get('api.php?apiToken=' + apiToken, {device:'plexServer',id:serverID,uri:serverUri,name:serverName,token:serverToken,product:serverProduct});
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
		var serverName = element.attr('name');
		var serverToken = element.attr('token');
		var serverProduct = element.attr('product');
		apiToken = $('#apiTokenData').attr('data');
		$.get('api.php?apiToken=' + apiToken, {device:'plexServer',id:serverID,uri:serverUri,name:serverName,token:serverToken,product:serverProduct});
	});


	$(".cmdBtn").click(function() {
		if ($(this).attr("id") !== action) {
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
	
	if (dvr) {
		$('.dvrGroup').show();
	} else {
		$('.dvrGroup').hide();
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

	var clientList = $.get('api.php?clientList&apiToken=' + apiToken,function(clientData) {
		$('#clientWrapper').html(clientData);
	}) ;
	
	updateStatus();

    json = ('[["https://lh5.googleusercontent.com/proxy/zJc9lcwMHkKSDgfdVr3VtcgjR3FJBMHQKdigk20i4B_h9b8mMoKuAHhLaqWNSxXnAbywDEGwalTdoytXUfiNtVwsaeVC4UfnsHuELq_R85DdAk930Q99CE0C=s1280-w1280-h720-p-k-no-nd-mv"," Welcome to Phlex!"],["https://i.ytimg.com/vi/EMHrDLQD7xQ/maxresdefault.jpg","Photo by Colby Brown"],["https://lh3.googleusercontent.com/ZWDcPnc1VC6Oz3S_mf8efYe9GFsoGf3rBgURVTazTeHXT2iX8mX4Ksby7-DBk9gJ77_iTtcbI18=s1280-w1280-h720-p-k-no-nd-mv","Photo by Erika Thornes"],["https://lh5.googleusercontent.com/proxy/9ePO2dlBXjGfYIu6YnBm_9EOEAtx2sisL0NOSW92ckskYUIZzqUCatL7XO2Lqh4T-FZUdoSwRb508uTThFfxSjorhOsEYhgHCYTAJ148E8akAAmimOj_=s1280-w1280-h720-p-k-no-nd-mv","Carbon County, United States"],["https://i1.wp.com/www.modernists.fr/wp-content/uploads/2015/10/burning-man-gerome-viavant-modernists-11.jpg","Love","Burning Man 2016","Sculpture by Alexander Milov"],["https://lh3.googleusercontent.com/xDYGSiU8N-_yCHJwa-EGPifUBcs9gpqmSq43ES73Qg7BbDLOZS09Gjl3gOZDhmVWmh--=s1280-w1280-h720-n-k-no","Cabrialia Bay","Pinacoteca do Estado de Sao Paulo","Antonio Parreiras"],["https://lh3.googleusercontent.com/_JwK0RS17kDLOTnapLtu6SHsII3Rd7dFbqsJDUYdxRYE95uM9sAl=s1280-w1280-h720-p-k-no-nd-mv","Mt. Amagi, Japan","Photo by Fumihiro Niwa"],["https://lh3.googleusercontent.com/proxy/OuLmkqg7_1a8N1dI-SrjNSSEqOSfRJ8ywzFS9lHhuF-Oi5w0RnzEbyqlaI2ZkFKuPMsQH8MMvW_9A8q48JT6RQtC60Mkkf4sxm5aME6sM3szTHRuD4bW=s1280-w1280-h720-p-k-no-nd-mv","Tangshan Shi, Hebei Sheng, China","2015 DigitalGlobe"],["https://lh5.ggpht.com/vLLY8HLWx3hrj2AzuLM2e7TSMOREofvDv5Z2VqqVRvUctjxSOqxL6k3sqI3JbXDRsNep=s1280-w1280-h720-n-k-no","Un claro del bosque","Ruben Dario Velazquez"],["https://lh5.ggpht.com/prSIqDYMfEjKAjOyi7gtIxO9I_xbo8k5x6fslC5Ox-GaFXCJEL-cA15A3_w=s1280-w1280-h720-n-k-no","Sheaves of Wheat","Dallas Museum of Art","Vincent van Gogh"],["https://lh3.googleusercontent.com/btvtVq9CAvw6rILmVRSSJKihdoH5plYNYjerz5fzrp5N3_ptMlR6Yg=s1280-w1280-h720-p-k-no-nd-mv","Photo by Ced Bennett","Glacier National Park, MT"],["https://lh4.googleusercontent.com/proxy/QWzaYnCvPrtPlDESm2A1grpAlWtoWUEslhNV9C2OA4467We8aUYhN4MQhF7WSmSj7Bj_U4_vxAr8r_2Ere1S50HDjOsCAaaWj9Bi4ksLsylgSsCF3YK4=s1280-w1280-h720-p-k-no-nd-mv","South Khorasan, Iran","2015 CNES / Astrium, DigitalGlobe"],["https://lh3.googleusercontent.com/Ex9Df6py0nQ3QaU-Bj20an-N1nMvjBj3ADM_noip-2AalSNVwJepUl-hJIhA56eey8ebNf0=s1280-w1280-h720-fcrop64=1,00003333ffffff73-k-no-nd","The Upper Nepean","Art Gallery of New South Wales","WC Piguenit"],["https://lh3.googleusercontent.com/cLvmcDjBbPf9B4kW9SGWD_AH5TexhndXJuUGWH-HwPsWDI_5nzmS860voFnu0pLp0A8uhQ=s1280-w1280-h720-n-k-no","Arredores da Cidade","Pinacoteca do Estado de Sao Paulo","Joseph Leon Righini"],["https://lh3.googleusercontent.com/-oMvwFP9slW4/VRzDo8uncKI/AAAAAAAAPhc/gEHQR7qFFE08w1o-YpGogudYMrjwa1dVgCJoC/w5500-h3640/milfordclouds.jpg","Photo by Rene Smith","Milford Sound, New Zealand"],["https://lh3.googleusercontent.com/-rPSwKl3x9tM/VKyQHUdhNnI/AAAAAAAAbNk/ppue2IniU8s/w4950-h3282/IMGP7345_6_7_8_9_tonemapped.jpg","Photo by Masao Minami","Mt. Fuji from Lake Tanuki"],["https://lh3.googleusercontent.com/-BJ9ZZFEsXtM/Vjbj_Cf27jI/AAAAAAAARx8/ONaiGo-dQmo/w7607-h5016/milfordsoundblue.jpg","Photo by Rene Smith","Milford Sound, New Zealand"],["https://lh6.ggpht.com/BdTKB1VsQSl3tCgwUpP7OqKpkq0vmNzzkvQTJfLV9FtXnu75dt2Rd5xUYh1XaEeoegTEtw=s1280-w1280-h720-n-k-no","Chess - Andrew Phillips","Abstractals","Google Open Gallery"],["https://lh3.googleusercontent.com/proxy/t1OUSUOVzkv51HW5rBPvXIkhD_DttSnLhRoU7JdgPH_j3AGXIn5et5JzieUMGGj_omeb6OP4NKnbyRWi3SgAj67f61w31buJ_prWZwSI_TBZ4UVEt3AG=s1280-w1280-h720-p-k-no-nd-mv","Honolulu County, United States","2014 DigitalGlobe"],["https://lh3.googleusercontent.com/60dtE7jVuXzjAFOVghY8osdb23gpJ1Di2FZleHxLX9g3dJc1x-jHfRCzdzW_CahlkyIU6iKGeTg=s1280-w1280-h720-p-k-no-nd-mv","Photo by Leo Deegan"],["https://lh3.googleusercontent.com/Os2qRQ07HkPLZiH4o8JnnUU4YHGWaqDnZFx_75mQiOMhEiOdKu0rbHc6_Injf2s6LWK5tA=s1280-w1280-h720-n-k-no-nd","Eagle Cliff, Franconia Notch, New Hampshire","Amon Carter Museum of American Art","David Johnson"],["https://lh3.googleusercontent.com/MyWmMdncUe3XfqnbDkHffdETHlCe8IkLjK7l_r2YztOc8sDxB6z3=s1280-w1280-h720-p-k-no-nd-mv","Photo by Sam Post","Google+"],["https://lh3.googleusercontent.com/h7m8k9yXtLvTUKfm1y_6B0sEcj8U2WKi9molthFtLsoDsCRuEfAdBYno3SGUcFvWqguXKYo=s1280-w1280-h720-n-k-no","Sluice in the Optevoz Valley","The Museum of Fine Arts, Houston","Charles-Francois Daubigny"],["https://lh5.googleusercontent.com/proxy/mJAWPoNBx-t3XRVNw2Thy2rRjZS4QFPMcGRrZVacsKaSmGCPTUWQTbtM7PDXHt5MSELnyTHSX6i5P0-hDRLyBlPdKvrDrtGgDzdx3ZrE1IAThdIYnFFS=s1280-w1280-h720-p-k-no-nd-mv","Guanacaste, Costa Rica","2014 CNES / Astrium, DigitalGlobe"],["https://lh5.ggpht.com/raqVhm_JrTAyScu5sqDCClmJBBUJYapXa9IibeFqSTgFDyu3TLUchPAvhKCT_pGEodI-=s1280-w1280-h720-n-k-no","Bir Hakeim","Light painting by Christopher Hibbert","Christopher Hibbert"],["https://lh3.googleusercontent.com/-mPzTQza8eaU/VA-l2pcFHcI/AAAAAAAJN9c/s8Wr1gdpKtg/w3686-h2062/Center%2BCamp%2BFrom%2BAbove.jpg","Photo by Trey Ratcliff"],["https://lh5.ggpht.com/qN7nT4dbvNqX_D2i0FSeOHSzJFWDXXl16iS5Ac2wUnrJUmaM6BUjM4UBKn9bOcvU8kUW4w=s1280-w1280-h720-n-k-no","Andamio","VERONIQUE ATTIA"],["https://lh3.googleusercontent.com/bKiu4UxJlUEQpScI1uCO-7Dy3XohVHLITiK2m_hFsfDu8g4jq74_Ew=s1280-w1280-h720-p-k-no-nd-mv","Photo by Tim Gupta","Convict Lake, CA"],["https://lh3.ggpht.com/6W1mdWmdabr-Sv9vujxElK_kY3Og9BbBYrgVKC-UUOyy0D7ZWZm_d0PEwlQk1ferH8syyg=s1280-w1280-h720-n-k-no","Autumn River","Art by Galina Kakovkina","Galina Kakovkina"],["https://lh5.googleusercontent.com/proxy/z_2HT8BKe1aW5zg5Axj9JOgnVnbTBXcWQ5E5ueVPmmmYKTWPH73xJzCSSzFwFNcNAKnCsyz-RK7-5viMh5dmA92zggqCDZvntO2CeQEw5zxPMOR9yyWf=s1280-w1280-h720-p-k-no-nd-mv","Kailua-Kona, Hawaii, United States","2015 DigitalGlobe"],["https://lh3.googleusercontent.com/lkrPyI9ETvIB-ghAZyAZMKLXE0MI2nRqnPy_U6YbWd8hajrLr06mFA=s1280-w1280-h720-p-k-no-nd-mv","Photo by Keith McInnes","Milsons Point, Sydney, Australia"],["https://ids.lib.harvard.edu/ids/view/17358225?width=1920&height=1080","The Storm -- Sunset","Harvard Art Museums","Artist: James McNeill Whistler"],["https://lh3.googleusercontent.com/e_V9orDeqE9ZacTqxuo-8V_xE5PN8eEQ8x8uEB1lg6w-N06b-jA8Wx-FDBwL2ORvg9ungEAeVg=s1280-w1280-h720-p-k-no-nd-mv","Photo by Dave Morrow"],["https://paulpichugin.com/wp-content/uploads/2015/11/garnet_rock.jpg","Photo by Paul Pichugin","Google+"],["https://lh3.ggpht.com/4h9axHhAdElxBZ1ed28PB-v9Symbf1VTURjHSHDJTnmpgy6IkGu01hhLyziA5OPHBcQAMw=s1280-w1280-h720-n-k-no","Liquefaction - Bamba Sourang","Bamba | ARTEXTURES","Google Open Gallery"],["https://lh3.googleusercontent.com/8q04GCzGxHZ3MGglP6-DpP2-QDXSjUkvXzm2R01dVtwyji0xId0oxbKCfi315YyC23DPXg=s1280-w1280-h720-n-k-no-nd","Spring frost","Art Gallery of New South Wales","Elioth Gruner"],["https://lh3.googleusercontent.com/9NWHf8KrhgUTn2iGd9GytCDeY9TOExFH6AIhYyitY8uc0JmHRuOzgQDBSiQYPxsPM353hR0=s1280-w1280-h720-n-k-no-nd","A Perch of Birds","The Walters Art Museum","Hector Giacomelli (French, 1822-1904)"],["https://lh3.googleusercontent.com/0Lord4NNHZJQ2UgGCVWvBZAPNN6M4a8_MQeyl5RN0wOAjrZriWdxZFfrNE1B6S7GXj1uQMnb=s1280-w1280-h720-p-k-no-nd-mv","Photo by Dave Morrow"],["https://lh4.ggpht.com/Imv2mgsjynkjp9NF8qQgI9H6f_rPY3TdB5MFERYHzJhAYk7Qtq9yg9RnRDrZ9qcuBICjOg=s1280-w1280-h720-n-k-no","Sentinel on the Cuyahoga","Robert Backston Collection","Google Open Gallery"],["https://lh4.googleusercontent.com/proxy/dkWrvLIzXa3I_KjXhuibEdy1_FSlp1aZnCs6CJxx309zBxm_ugDoi-CrLps8iGnUz2RNwViyUqgLXeF72Jy-o1VJS8QCZgaiceEf6f1paXXpanuipKQ=s1280-w1280-h720-p-k-no-nd-mv","Nickel Mines, New Caledonia","2013 DigitalGlobe"],["https://lh3.googleusercontent.com/df6L8Rf4pudLuPAsREua3mFh00jaBZtDV_NvygW5UZjcPShHXVz2p6VUdvb8P7JpwnMbf99awOA=s1280-w1280-h720-fcrop64=1,00000000ffffee03-k-no-nd-mv","Photo by Paul Moody"],["https://lh3.googleusercontent.com/IBFtGlJu5pCHbzRdiYnv2FIfdNiE-AeuqPwOZhP2gT8r5zyXLjvIOh5mb2W-09bYD4K1Bzh08Q=s1280-w1280-h720-p-k-no-nd-mv","Photo by Martin Suess"],["https://lh5.googleusercontent.com/proxy/-eErGGmNwpPdMX2Xso5oVBdEamDo4WhszJQ3I8QIKUHSHSqEPzvwKhbYTe5OGjKzJFoZwyC9PYzXL8QmkAFgC6U-fKEchRtw5Vr1bCEm9VIU5nlzDWaU=s1280-w1280-h720-p-k-no-nd-mv","Kiribati","2014 DigitalGlobe"],["https://lh3.ggpht.com/cCFNdS-QRG7ieEcdAy2vx8eZH1wvn1GvzZ-teHPU_2-y-mARTr2w-FM3BoS99o1Vdgs5aDQ=s1280-w1280-h720-n-k-no","Florence Henderson For The Win","Abstractals","Andrew Phillips"],["https://lh4.googleusercontent.com/proxy/yv7qomfrwUNb6Xigx1I05aj3AYU16Py64V9nYPOd0BYvWP-Wtf6Hw5dw-p0fV8R4RhW6slVGfBwKQ9J_6I-R6JbPwQ9_TmfHtpDhIFRD9GYSL319EJAD=s1280-w1280-h720-p-k-no-nd-mv","Los Vidrios, Mexico","2014 Cnes/Spot Image, DigitalGlobe, Landsat"],["https://lh5.ggpht.com/-7Mt6OeL3Mc5dJPHxGTLYlsJq_8Od8tsQ_7h3W-ghRKlg6DiwB_R3xVVY0JZh2FGTmPdPA=s1280-w1280-h720-n-k-no","Twilight Visions with a Piano","Gaya Art Gallery","Gaya (Gayane Karapetyan)"],["https://lh3.googleusercontent.com/55WJzkR3Zt8Tx2C29ufcIwSkXl8LzDcAPATMQfsrp1Y17bAmeUTj6g=s1280-w1280-h720-n-k-no-nd-mv","Photo by Keith Cuddeback","Rocky Creek Bridge, CA"],["https://lh3.googleusercontent.com/TVN4NftuxB3C7YVWdMlvcGAeohk9JXOQ6ptf7kyIxgm1iCHP5d5tWw=s1280-w1280-h720-p-k-no-nd-mv","Photo by Andrew Caldwell"],["https://lh3.ggpht.com/nXc5E29UDsTBYG-PSA1W8vrrte_o2yuNXe2CgZiABECMn8D1-fBZhJ1Al2f1a1SDZeoj=s1280-w1280-h720-n-k-no","BAJO EL SOL DE ANDALUCIA, ARCOS DE LA FRONTERA, ESPANIA","JORGE FRASCA ART GALLERY","JORGE FRASCA"],["https://lh3.googleusercontent.com/10GE3QkU5daR6TU5zTHJu3v6zR-WlehzxW-PNfMtwLUoe8nlq5UBYRc9OwFmG28US_J_jw=s1280-w1280-h720-fcrop64=1,00002666ffff8c86-k-no-nd","Vanitas","The J. Paul Getty Museum","Willem Isaacsz van Swanenburg, After Abraham Bloemaert"],["https://patricksmithphotography.com/blog/wp-content/uploads/2015/05/100731-4553-MangoSkies.jpg","Photo by Patrick Smith"],["https://img02.deviantart.net/05c9/i/2012/056/f/8/random_art_can_you_see_it_by_craig_ferguson_by_806designs-d4qydxt.jpg","Photo by Craig Ferguson","Deviantart"],["https://orig05.deviantart.net/4222/f/2011/059/a/9/random_paisley_art_by_catladyx21x-d3an0xz.png","Photo by CatLadyx21x","Deviantart"],["https://lh6.googleusercontent.com/proxy/wmscX6R40zpOpu7wJazhjrOoA2lKW1ZjrWyVplhvj-5CrNNJJFynXdQGyydipy6ThpcqH2wxDhNYVa5UBWX7KilrfBNCpvnOMZZw7W0Ecj5FwWuGICye=s1280-w1280-h720-p-k-no-nd-mv","Al Ahsa, Saudi Arabia","2014 Cnes/Spot Image, DigitalGlobe"]]');
    json = JSON.parse(json);
    fade1 = $('.fade1');
    fade2 = $('.fade2');
    fade = true;
    showFade = fade1;
    $('.ccWrapper').cycle();
    $('.ccWrapper').cycle('pause');
    fetchWeather();
    preloadall();
    preload();
    setBackground();
    setInterval(function() {
        if (! $('.nowPlayingFooter').is(":visible")) setBackground();
    }, 30000);
    setInterval(function() {
        fetchWeather();
    }, 1000 * 60 * 30);


});

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
			console.log("Raw Status: " + data.playerStatus);
			data.playerStatus = JSON.parse(data.playerStatus);
			var TitleString;
			if ((data.playerStatus.status === 'playing') || (data.playerStatus.status === 'paused')) {
                var mr = data.playerStatus.mediaResult;
                console.log("Mediaresult",mr);
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
                    console.log("This is a " + resultType);
                    TitleString = resultTitle + ((resultYear !== '')? "(" + resultYear + ")" : '');
					if (resultType === "episode") TitleString = "S" + mr.parentIndex + "E"+mr.index + " - " + resultTitle;
					if (resultType === "track") {
						console.log("The title should be right, fucker.");
						TitleString = mr.grandparentTitle + " - " + resultTitle;
                    }
					
					progressSlider.noUiSlider.set((resultOffset/resultDuration)*100);
					if (thumbPath != false) {
						$('#statusImage').attr('src',thumbPath);
						$('#statusImage').show();
					} else {
						$('#statusImage').hide();
					}
					$('#playerName').html($('.ddLabel').html());
					$('#mediaTitle').html(TitleString);
					$('#mediaSummary').html(resultSummary);
					$('.wrapperArt').css('background-image','url('+artPath+')');
					$('.castArt').hide();
                    if ((!(footer.is(":visible")))&& (!(footer.hasClass('reHide')))) {
						footer.slideDown();
						footer.addClass("playing");
						setTimeout( function(){
							var sliderWidth = $('.statusWrapper').width() - $('#statusImage').width()-60;
							$("#progressSlider").css('width',sliderWidth);
						}  , 300 );
					} 
				}
			} else {
				if (footer.is(":visible")) {
					$('.castArt').show();
					footer.slideUp();
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
		console.log("Update data: "+data);
		lastUpdate = data;

		var username = $('usernameData').attr('data');
		if (!(prepend)) $('#resultsInner').html("");
					
		for (var i in data) {
			try {
				var int = i;
				var initialCommand = data[int].initialCommand;
				var parsedCommand = data[int].parsedCommand;
				var timeStamp = data[int].timestamp;
				speech = (data[int].speech ? data[int].speech : "");
				var status = (data[int].mediaStatus ? data[int].mediaStatus : "");
				itemJSON = data[int];
				var resultLine = '<br><b>Initial command:</b> "' + initialCommand + '"<br><b>Parsed command:</b> "' + parsedCommand + '"';
				var mediaDiv = "";
				var JSONdiv = '<br><a href="javascript:void(0)" id="JSON'+i+'" class="JSONPop" data="'+encodeURIComponent(JSON.stringify(data[i],null,2))+'" title="Result JSON">{JSON}</a>';

				if ((data[i].commandType === 'play') || (data[i].commandType === 'control')) {
					var plexServerURI = data[i].serverURI;
                    var plexClientName = data[i].clientName;

				}
			
				if (data[i].mediaResult) {
					if (status.indexOf("ERROR") === -1) {
						//Get our general variables about this media object
						var mr = Object.values(data[i].mediaResult);
						var resultTitle = mr[0].title;
						var resultYear = mr[0].year;
						var resultType = mr[0].type;
						var resultArt = (resultType !== 'track' ? mr[0].art : mr[0].thumb);
						console.log("ResultArt for " + resultTitle + " should be " + resultArt);
						var TitleString = resultTitle;
						if (resultType == "episode") {
							var TitleString = "S" + mr[0].parentIndex + "E"+mr[0].index + " - " + resultTitle;
						}
						// Determine if we should be using a Plex path, or a full path
						if (data[i].commandType != 'fetch') {
							var itemPath = plexServerURI+mr[0].key+"?X-Plex-Token="+token;
						}
						if (typeof resultArt !== 'undefined') {
							var mediaDiv = '<a href="'+itemPath+'" target="_blank"><div class="card-image"><img src="' + resultArt + '" alt="Loading image..." class="resultimg"><h4 class="card-image-headline">' + TitleString + '</h4></div></a>';							
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
				var outLine = "<div class='resultDiv card'><div class='card-body'>" + timeStamp + closeBtn + mediaDiv + resultLine + "<br>" + statusLine + statusLine2 + speech + JSONdiv + "</div></div><br>";
				
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

function preload() {
	console.log("Preload fired");
    item = json[Math.floor(Math.random()*json.length)];
    bgImg = new Image();
    imgUrl = item[0];
    bgImg.src = imgUrl;
}

function preloadall() {
    var images = new Array();
    for (i = 0; i < json.length; i++) {
        images[i] = new Image();
        images[i].src = json[i][0];
    }
}

function notify() {
    console.log("Image loaded: ".imgUrl);
}


function setBackground() {
    // Pick a random item
    desc1 = "";
    desc2 = "";
    desc3 = "";
    desc4 = "";
    console.log("Setting image path to "+imgUrl);
    desc1 = item[1];
    if (item.length >= 3) desc2 = item[2];
    if (item.length >= 4) desc3 = item[3];
    if (item.length >= 5) desc4 = item[4];
    showFade = $('.cycle-slide-active');
    if (showFade.hasClass('fade1')) {
        hideFade = fade2;
    } else {
        hideFade = fade1;
    }
    hideFade.css("background-image",'url(' + imgUrl + ')');
    hideFade.find(".timeDiv").text(formatAMPM());
    hideFade.find("#metadata-line-1").text(desc1);
    hideFade.find("#metadata-line-2").text(desc2);
    hideFade.find("#metadata-line-3").text(desc3);
    setWeather();
    $('.ccWrapper').cycle('next');
    preload();
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
    if (condition === null) {
        fetchWeather();
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
    $('.weatherIcon').html('<span class="weather '+weatherClass+'"> </span>');
    showFade.find(".tempDiv").text(weatherHtml);
}
