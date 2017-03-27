var json, item, imgUrl, desc1, desc2, desc3, desc4, fade1, fade2, showFade, hideFade, fadeImg1, fadeImg2, fadeImg;
jQuery(document).ready(function($) {
	json = ('[["https://lh5.googleusercontent.com/proxy/zJc9lcwMHkKSDgfdVr3VtcgjR3FJBMHQKdigk20i4B_h9b8mMoKuAHhLaqWNSxXnAbywDEGwalTdoytXUfiNtVwsaeVC4UfnsHuELq_R85DdAk930Q99CE0C=s1280-w1280-h720-p-k-no-nd-mv"," Welcome to Phlex!"],["https://lh3.googleusercontent.com/BCxYlyhmIlMwuAKNh-1yVmP0bjZH66ekwQloH58vTAeXKeAY-nZ7_pUumo7yVbVvCCUTIjPxdkw=s1280-w1280-h720-p-k-no-nd-mv","Photo by Colby Brown"],["https://lh3.googleusercontent.com/ZWDcPnc1VC6Oz3S_mf8efYe9GFsoGf3rBgURVTazTeHXT2iX8mX4Ksby7-DBk9gJ77_iTtcbI18=s1280-w1280-h720-p-k-no-nd-mv","Photo by Erika Thornes"],["https://lh5.googleusercontent.com/proxy/9ePO2dlBXjGfYIu6YnBm_9EOEAtx2sisL0NOSW92ckskYUIZzqUCatL7XO2Lqh4T-FZUdoSwRb508uTThFfxSjorhOsEYhgHCYTAJ148E8akAAmimOj_=s1280-w1280-h720-p-k-no-nd-mv","Carbon County, United States"],["https://lh3.googleusercontent.com/Fa4-rxDZ_kblXs_cJAOTL_b8hNutPfBXzrxUwgG3Ok6CD8yXZR87cWYKQ9eexlgqWRiFui8OuA=s1280-w1280-h720-p-k-no-nd-mv","Photo by Dominik Behr"],["https://lh3.googleusercontent.com/xDYGSiU8N-_yCHJwa-EGPifUBcs9gpqmSq43ES73Qg7BbDLOZS09Gjl3gOZDhmVWmh--=s1280-w1280-h720-n-k-no","Cabrialia Bay","Pinacoteca do Estado de Sao Paulo","Antonio Parreiras"],["https://lh3.googleusercontent.com/_JwK0RS17kDLOTnapLtu6SHsII3Rd7dFbqsJDUYdxRYE95uM9sAl=s1280-w1280-h720-p-k-no-nd-mv","Mt. Amagi, Japan","Photo by Fumihiro Niwa"],["https://lh3.googleusercontent.com/proxy/OuLmkqg7_1a8N1dI-SrjNSSEqOSfRJ8ywzFS9lHhuF-Oi5w0RnzEbyqlaI2ZkFKuPMsQH8MMvW_9A8q48JT6RQtC60Mkkf4sxm5aME6sM3szTHRuD4bW=s1280-w1280-h720-p-k-no-nd-mv","Tangshan Shi, Hebei Sheng, China","2015 DigitalGlobe"],["https://lh5.ggpht.com/vLLY8HLWx3hrj2AzuLM2e7TSMOREofvDv5Z2VqqVRvUctjxSOqxL6k3sqI3JbXDRsNep=s1280-w1280-h720-n-k-no","Un claro del bosque","Ruben Dario Velazquez"],["https://lh5.ggpht.com/prSIqDYMfEjKAjOyi7gtIxO9I_xbo8k5x6fslC5Ox-GaFXCJEL-cA15A3_w=s1280-w1280-h720-n-k-no","Sheaves of Wheat","Dallas Museum of Art","Vincent van Gogh"],["https://lh3.googleusercontent.com/btvtVq9CAvw6rILmVRSSJKihdoH5plYNYjerz5fzrp5N3_ptMlR6Yg=s1280-w1280-h720-p-k-no-nd-mv","Photo by Ced Bennett","Glacier National Park, MT"],["https://lh4.googleusercontent.com/proxy/QWzaYnCvPrtPlDESm2A1grpAlWtoWUEslhNV9C2OA4467We8aUYhN4MQhF7WSmSj7Bj_U4_vxAr8r_2Ere1S50HDjOsCAaaWj9Bi4ksLsylgSsCF3YK4=s1280-w1280-h720-p-k-no-nd-mv","South Khorasan, Iran","2015 CNES / Astrium, DigitalGlobe"],["https://lh3.googleusercontent.com/Ex9Df6py0nQ3QaU-Bj20an-N1nMvjBj3ADM_noip-2AalSNVwJepUl-hJIhA56eey8ebNf0=s1280-w1280-h720-fcrop64=1,00003333ffffff73-k-no-nd","The Upper Nepean","Art Gallery of New South Wales","WC Piguenit"],["https://lh3.googleusercontent.com/cLvmcDjBbPf9B4kW9SGWD_AH5TexhndXJuUGWH-HwPsWDI_5nzmS860voFnu0pLp0A8uhQ=s1280-w1280-h720-n-k-no","Arredores da Cidade","Pinacoteca do Estado de Sao Paulo","Joseph Leon Righini"],["Photo by Rene Smith","Milford Sound, New Zealand"],["https://lh6.ggpht.com/BdTKB1VsQSl3tCgwUpP7OqKpkq0vmNzzkvQTJfLV9FtXnu75dt2Rd5xUYh1XaEeoegTEtw=s1280-w1280-h720-n-k-no","Chess - Andrew Phillips","Abstractals","Google Open Gallery"],["https://lh3.googleusercontent.com/proxy/t1OUSUOVzkv51HW5rBPvXIkhD_DttSnLhRoU7JdgPH_j3AGXIn5et5JzieUMGGj_omeb6OP4NKnbyRWi3SgAj67f61w31buJ_prWZwSI_TBZ4UVEt3AG=s1280-w1280-h720-p-k-no-nd-mv","Honolulu County, United States","2014 DigitalGlobe"],["https://lh3.googleusercontent.com/60dtE7jVuXzjAFOVghY8osdb23gpJ1Di2FZleHxLX9g3dJc1x-jHfRCzdzW_CahlkyIU6iKGeTg=s1280-w1280-h720-p-k-no-nd-mv","Photo by Leo Deegan"],["https://lh3.googleusercontent.com/Os2qRQ07HkPLZiH4o8JnnUU4YHGWaqDnZFx_75mQiOMhEiOdKu0rbHc6_Injf2s6LWK5tA=s1280-w1280-h720-n-k-no-nd","Eagle Cliff, Franconia Notch, New Hampshire","Amon Carter Museum of American Art","David Johnson"],["https://lh3.googleusercontent.com/MyWmMdncUe3XfqnbDkHffdETHlCe8IkLjK7l_r2YztOc8sDxB6z3=s1280-w1280-h720-p-k-no-nd-mv","Photo by Sam Post","Google+"],["https://lh3.googleusercontent.com/h7m8k9yXtLvTUKfm1y_6B0sEcj8U2WKi9molthFtLsoDsCRuEfAdBYno3SGUcFvWqguXKYo=s1280-w1280-h720-n-k-no","Sluice in the Optevoz Valley","The Museum of Fine Arts, Houston","Charles-Francois Daubigny"],["https://lh5.googleusercontent.com/proxy/mJAWPoNBx-t3XRVNw2Thy2rRjZS4QFPMcGRrZVacsKaSmGCPTUWQTbtM7PDXHt5MSELnyTHSX6i5P0-hDRLyBlPdKvrDrtGgDzdx3ZrE1IAThdIYnFFS=s1280-w1280-h720-p-k-no-nd-mv","Guanacaste, Costa Rica","2014 CNES / Astrium, DigitalGlobe"],["https://lh5.ggpht.com/raqVhm_JrTAyScu5sqDCClmJBBUJYapXa9IibeFqSTgFDyu3TLUchPAvhKCT_pGEodI-=s1280-w1280-h720-n-k-no","Bir Hakeim","Light painting by Christopher Hibbert","Christopher Hibbert"],["https://lh3.googleusercontent.com/JP3e33zPRDn-TDqn737tjJ5W7cJdQVf8W-vUDa_m7Uv1QoPWuTnGOK_ZS1RZiZ65QedCNIxcRU0=s1280-w1280-h720-p-k-no-nd-mv","Photo by Trey Ratcliff"],["https://lh5.ggpht.com/qN7nT4dbvNqX_D2i0FSeOHSzJFWDXXl16iS5Ac2wUnrJUmaM6BUjM4UBKn9bOcvU8kUW4w=s1280-w1280-h720-n-k-no","Andamio","VERONIQUE ATTIA"],["https://lh3.googleusercontent.com/bKiu4UxJlUEQpScI1uCO-7Dy3XohVHLITiK2m_hFsfDu8g4jq74_Ew=s1280-w1280-h720-p-k-no-nd-mv","Photo by Tim Gupta","Convict Lake, CA"],["https://lh3.ggpht.com/6W1mdWmdabr-Sv9vujxElK_kY3Og9BbBYrgVKC-UUOyy0D7ZWZm_d0PEwlQk1ferH8syyg=s1280-w1280-h720-n-k-no","Autumn River","Art by Galina Kakovkina","Galina Kakovkina"],["https://lh5.googleusercontent.com/proxy/z_2HT8BKe1aW5zg5Axj9JOgnVnbTBXcWQ5E5ueVPmmmYKTWPH73xJzCSSzFwFNcNAKnCsyz-RK7-5viMh5dmA92zggqCDZvntO2CeQEw5zxPMOR9yyWf=s1280-w1280-h720-p-k-no-nd-mv","Kailua-Kona, Hawaii, United States","2015 DigitalGlobe"],["https://lh3.googleusercontent.com/lkrPyI9ETvIB-ghAZyAZMKLXE0MI2nRqnPy_U6YbWd8hajrLr06mFA=s1280-w1280-h720-p-k-no-nd-mv","Photo by Keith McInnes","Milsons Point, Sydney, Australia"],["https://lh3.googleusercontent.com/pHTln13vMj6B1FcCC93gLKhiUfXcKHEDLTCU8hOuAv2y5VQDeXguLz3F6jR2xmY50USYDA=s1280-w1280-h720-fcrop64=1,028f028ffd6fe62b-k-no-nd","Red and Brown - Hoxton","Freer and Sackler Galleries","Artist: James McNeill Whistler"],["https://lh3.googleusercontent.com/e_V9orDeqE9ZacTqxuo-8V_xE5PN8eEQ8x8uEB1lg6w-N06b-jA8Wx-FDBwL2ORvg9ungEAeVg=s1280-w1280-h720-p-k-no-nd-mv","Photo by Dave Morrow"],["http://paulpichugin.com/wp-content/uploads/2015/11/garnet_rock.jpg","Photo by Paul Pichugin","Google+"],["https://lh3.ggpht.com/4h9axHhAdElxBZ1ed28PB-v9Symbf1VTURjHSHDJTnmpgy6IkGu01hhLyziA5OPHBcQAMw=s1280-w1280-h720-n-k-no","Liquefaction - Bamba Sourang","Bamba | ARTEXTURES","Google Open Gallery"],["https://lh3.googleusercontent.com/8q04GCzGxHZ3MGglP6-DpP2-QDXSjUkvXzm2R01dVtwyji0xId0oxbKCfi315YyC23DPXg=s1280-w1280-h720-n-k-no-nd","Spring frost","Art Gallery of New South Wales","Elioth Gruner"],["https://lh3.googleusercontent.com/9NWHf8KrhgUTn2iGd9GytCDeY9TOExFH6AIhYyitY8uc0JmHRuOzgQDBSiQYPxsPM353hR0=s1280-w1280-h720-n-k-no-nd","A Perch of Birds","The Walters Art Museum","Hector Giacomelli (French, 1822-1904)"],["https://lh3.googleusercontent.com/0Lord4NNHZJQ2UgGCVWvBZAPNN6M4a8_MQeyl5RN0wOAjrZriWdxZFfrNE1B6S7GXj1uQMnb=s1280-w1280-h720-p-k-no-nd-mv","Photo by Dave Morrow"],["https://lh4.ggpht.com/Imv2mgsjynkjp9NF8qQgI9H6f_rPY3TdB5MFERYHzJhAYk7Qtq9yg9RnRDrZ9qcuBICjOg=s1280-w1280-h720-n-k-no","Sentinel on the Cuyahoga","Robert Backston Collection","Google Open Gallery"],["https://lh4.googleusercontent.com/proxy/dkWrvLIzXa3I_KjXhuibEdy1_FSlp1aZnCs6CJxx309zBxm_ugDoi-CrLps8iGnUz2RNwViyUqgLXeF72Jy-o1VJS8QCZgaiceEf6f1paXXpanuipKQ=s1280-w1280-h720-p-k-no-nd-mv","Nickel Mines, New Caledonia","2013 DigitalGlobe"],["https://lh3.googleusercontent.com/df6L8Rf4pudLuPAsREua3mFh00jaBZtDV_NvygW5UZjcPShHXVz2p6VUdvb8P7JpwnMbf99awOA=s1280-w1280-h720-fcrop64=1,00000000ffffee03-k-no-nd-mv","Photo by Paul Moody"],["https://lh3.googleusercontent.com/IBFtGlJu5pCHbzRdiYnv2FIfdNiE-AeuqPwOZhP2gT8r5zyXLjvIOh5mb2W-09bYD4K1Bzh08Q=s1280-w1280-h720-p-k-no-nd-mv","Photo by Martin Suess"],["https://lh5.googleusercontent.com/proxy/-eErGGmNwpPdMX2Xso5oVBdEamDo4WhszJQ3I8QIKUHSHSqEPzvwKhbYTe5OGjKzJFoZwyC9PYzXL8QmkAFgC6U-fKEchRtw5Vr1bCEm9VIU5nlzDWaU=s1280-w1280-h720-p-k-no-nd-mv","Kiribati","2014 DigitalGlobe"],["https://lh3.ggpht.com/cCFNdS-QRG7ieEcdAy2vx8eZH1wvn1GvzZ-teHPU_2-y-mARTr2w-FM3BoS99o1Vdgs5aDQ=s1280-w1280-h720-n-k-no","Florence Henderson For The Win","Abstractals","Andrew Phillips"],["https://lh4.googleusercontent.com/proxy/yv7qomfrwUNb6Xigx1I05aj3AYU16Py64V9nYPOd0BYvWP-Wtf6Hw5dw-p0fV8R4RhW6slVGfBwKQ9J_6I-R6JbPwQ9_TmfHtpDhIFRD9GYSL319EJAD=s1280-w1280-h720-p-k-no-nd-mv","Los Vidrios, Mexico","2014 Cnes/Spot Image, DigitalGlobe, Landsat"],["https://lh5.ggpht.com/-7Mt6OeL3Mc5dJPHxGTLYlsJq_8Od8tsQ_7h3W-ghRKlg6DiwB_R3xVVY0JZh2FGTmPdPA=s1280-w1280-h720-n-k-no","Twilight Visions with a Piano","Gaya Art Gallery","Gaya (Gayane Karapetyan)"],["https://lh3.googleusercontent.com/55WJzkR3Zt8Tx2C29ufcIwSkXl8LzDcAPATMQfsrp1Y17bAmeUTj6g=s1280-w1280-h720-n-k-no-nd-mv","Photo by Keith Cuddeback","Rocky Creek Bridge, CA"],["https://lh3.googleusercontent.com/TVN4NftuxB3C7YVWdMlvcGAeohk9JXOQ6ptf7kyIxgm1iCHP5d5tWw=s1280-w1280-h720-p-k-no-nd-mv","Photo by Andrew Caldwell"],["https://lh3.ggpht.com/nXc5E29UDsTBYG-PSA1W8vrrte_o2yuNXe2CgZiABECMn8D1-fBZhJ1Al2f1a1SDZeoj=s1280-w1280-h720-n-k-no","BAJO EL SOL DE ANDALUCIA, ARCOS DE LA FRONTERA, ESPANIA","JORGE FRASCA ART GALLERY","JORGE FRASCA"],["https://lh3.googleusercontent.com/10GE3QkU5daR6TU5zTHJu3v6zR-WlehzxW-PNfMtwLUoe8nlq5UBYRc9OwFmG28US_J_jw=s1280-w1280-h720-fcrop64=1,00002666ffff8c86-k-no-nd","Vanitas","The J. Paul Getty Museum","Willem Isaacsz van Swanenburg, After Abraham Bloemaert"],["https://lh3.googleusercontent.com/liwVCy3QizQg9DoYdpmQO9VBOmEQD2Mlku5wPHBxFETr1UtKeUOdAFvH45V12XLCGzCe8u5JNQ=s1280-w1280-h720-p-k-no-nd-mv","Photo by Patrick Smith"],["http://img02.deviantart.net/05c9/i/2012/056/f/8/random_art_can_you_see_it_by_craig_ferguson_by_806designs-d4qydxt.jpg","Photo by Craig Ferguson","Deviantart"],["http://orig05.deviantart.net/4222/f/2011/059/a/9/random_paisley_art_by_catladyx21x-d3an0xz.png","Photo by CatLadyx21x","Deviantart"],["https://lh6.googleusercontent.com/proxy/wmscX6R40zpOpu7wJazhjrOoA2lKW1ZjrWyVplhvj-5CrNNJJFynXdQGyydipy6ThpcqH2wxDhNYVa5UBWX7KilrfBNCpvnOMZZw7W0Ecj5FwWuGICye=s1280-w1280-h720-p-k-no-nd-mv","Al Ahsa, Saudi Arabia","2014 Cnes/Spot Image, DigitalGlobe"]]');
	json = JSON.parse(json);
	fade1 = $('.fade1');
	fade2 = $('.fade2');
	fade = true;
	showFade = fade1;
	$('.wrapper').cycle();
	$('.wrapper').cycle('pause');
	setBackground();
	setInterval(function() {
		setBackground();
	}, 30000);
	
});

function setBackground() {
	// Pick a random item
	item = "";
	imgUrl = "";
	desc1 = "";
	desc2 = "";
	desc3 = "";
	desc4 = "";
	item = json[Math.floor(Math.random()*json.length)];
	imgUrl = item[0];
	desc1 = item[1];
	if (item.length >= 3) desc2 = item[2];
	if (item.length >= 4) desc3 = item[3];
	if (item.length >= 5) desc4 = item[4];
	showFade = $('.cycle-slide-active');
	if (showFade.hasClass('fade1')) {
		hideFade = fade2;
		console.log("Hidefade is fade2.");
	} else {
		hideFade = fade1;
		console.log("Hidefade is fade1.");
	}
	
	hideFade.css("background-image","url("+imgUrl+")");
	hideFade.find(".timeDiv").text(formatAMPM());
	hideFade.find("#metadata-line-1").text(desc1);
	hideFade.find("#metadata-line-2").text(desc2);
	hideFade.find("#metadata-line-3").text(desc3);
	setWeather();
	$('.wrapper').cycle('next');
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

function setWeather() {
	var city, state, locJson;
	$.get("http://freegeoip.net/json/", function(data) {
		//locJson = JSON.parse(data);
		city = data['city'];
		state = data['region_code'];
		$.simpleWeather({
			location: city + ','+state,
			woeid: '',
			unit: 'f',
			success: function(weather) {
				var html = weather.temp+String.fromCharCode(176)+weather.units.temp;
				showFade.find(".tempDiv").text(html);
				var condition = weather.code;
				var weatherClass;
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
						console.log("I'm here");
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
				$('.weatherIcon').html('<span class="weather '+weatherClass+'"> </span>')
				
			},
			error: function(error) {
			  return "";
			}
		});
	});
	
}