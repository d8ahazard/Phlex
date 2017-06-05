# **README**

## Introduction:
<BR>
<B> What is Phlex?</B>  Phlex stands for <B>P</B>ersonal <B>H</B>ome <B>L</B>anguage <B>EX</B>tension.  I mean, I guess.  I literally just made that up.  Also, it sounds cool.  

Aside from being a made-up name that sounds cool, The purpose of Phlex is to provide a natural language interface for Home Theater applications - effectively bridging the current gap between commercial AI Solutions like Google Home and personal web applications like Plex, Couchpotato, and Sonarr.

Or, in short - you can watch and download movies and shows just by telling your phone to do so.
<br><br>

<a href="http://www.youtube.com/watch?feature=player_embedded&v=FZBlNwBocAc
" target="_blank"><img src="http://img.youtube.com/vi/FZBlNwBocAc/0.jpg"
alt="Phlex Demo #2" width="480" height="360" border="10" /></a>


<a href="http://s803.photobucket.com/user/d8ahazard/media/Phlex/0.png.html" target="_blank"><img src="http://i803.photobucket.com/albums/yy318/d8ahazard/Phlex/0.png" border="0" alt=" photo 0.png"/></a>

<a href="http://s803.photobucket.com/user/d8ahazard/media/ccbg.png.html" target="_blank"><img src="http://i803.photobucket.com/albums/yy318/d8ahazard/ccbg.png" border="0" alt=" photo ccbg.png"/></a>

<a href="http://s803.photobucket.com/user/d8ahazard/media/ccbg2.png.html" target="_blank"><img src="http://i803.photobucket.com/albums/yy318/d8ahazard/ccbg2.png" border="0" alt=" photo ccbg2.png"/></a>

<a href="http://s803.photobucket.com/user/d8ahazard/media/ccbg3.png.html" target="_blank"><img src="http://i803.photobucket.com/albums/yy318/d8ahazard/ccbg3.png" border="0" alt=" photo ccbg3.png"/></a>
<a href="http://s803.photobucket.com/user/d8ahazard/media/ccbg4.png.html" target="_blank"><img src="http://i803.photobucket.com/albums/yy318/d8ahazard/ccbg4.png" border="0" alt=" photo ccbg4.png"/></a>
<a href="http://s803.photobucket.com/user/d8ahazard/media/Phlex/6.png.html" target="_blank"><img src="http://i803.photobucket.com/albums/yy318/d8ahazard/Phlex/6.png" border="0" alt=" photo 6.png"/></a>

## Installation:
<BR>

Phlex requires a webserver and PHP 7.0+ with CURL and SSH enabled in order to work correctly.

**For Cast Device Control, you will also need to enable the Sockets module.**

For most use cases, XAMPP is going to be the easiest options.

XAMPP is a free, cross-platform web server package, and can be found here:

[https://www.apachefriends.org/index.html](https://www.apachefriends.org/index.html)

<br>
When installing XAMPP, we only require the PHP and Apache features.  You can uncheck the rest of the options in installation if you have no need for them:


MySQL, FileZilla FTP Server, Mercury Mail Server, Tomcat, Perl, phpMyAdmin, Webalizer, Fake Sendmail...ain't nobody got time fo dat.


Once installed, clone or download the Phlex directory to the root web directory of XAMPP, which should be the /htdocs folder.  When done, the path should be something like C:\xampp\htdocs\phlex (windows) or usr/home/xampp/htdocs/phlex (linux).  


That should be it.  You can now restart the apache service for your webserver, and browse to your Phlex installation at 'http://yourserveraddress:80/Phlex'.

To log in, enter your **Plex** username and password.


If you are running Phlex on an existing webserver, Phlex PHP version 7.0 and up.  Phlex will also require r/w access to the root of the /Phlex directory for configuration and logging purposes.
You will also need the CURL and openSSL extensions enabled, and sockets if you have any Cast devices.
<br><br>


## Updating:

If you cloned Phlex using .git, simply do a git pull, your configuration files will remain untouched.  If you installed via .zip, you should be able to download the latest copy and extract it in-place.  

However you update, it is important to not delete config.ini.php, as this is where your server's API token is stored, which is used to associate your local Phlex client with your Google account.
<br><br>

## Post-Installation (Network Stuff):
#### Port forwarding
First, you'll need to forward IP traffic to port 80 on the computer where Phlex is running.  You do not have to forward port 80 to port 80, you can choose any open port you like.
<br><br>

If you wish to change the listening port for Phlex to something other than 80, you can do so in the Xampp control panel by clicking the "config" button next to Apache, and then opening httpd.conf.

In httpd.conf, you will see a line that reads "Listen 80".  Change 80 to whichever port you want.  Restart apache, and forward to that port.  If you are on Windows, you will need to make a rule in Windows Firewall to allow that port's traffic as well.
<br><br>
#### MDNS/Multicast/Cast Discovery
Chromecasts are fun devices.  They're fun for this project because they use Multicast DNS (Aka Bonjour, Aka Avahii, Aka Zeroconf) in order to talk to one another on a home network.  

On a very basic home network with just one router and everything connected via wifi, MDNS usually just works.  *Usually.*

However, there are thousands of different router manufactureres, and thousands of different network configurations that could play a part in how well your cast devices will talk with Phlex.

If you are experiencing issues with cast devices not showing up in Phlex, you should first verify that the php sockets module is installed and enabled.  If you used XAMPP, it should be included, you just need to edit your php.ini file to enable it.  

If you used an alternative webserver option, google it.  


If sockets are enabled and you STILL can't see all of your cast devices, I would suggest looking in the web UI for your router and looking for any setting related to "multicast filtering", "bonjour","or MDNS discovery".  Most of them should support it, it may just need to be enabled.

## Setting it up with Google Assistant: ##
<br>
Log into Phlex with your Plex.tv username and password.
Click on the gear icon to open settings.
In settings, under the general tab, make sure you fill in the "Public Address" box with the FULL address of your Phlex server.  Typically, you will just need to append /Phlex to the directory path, and a port if you changed it to something other than 80.  
<BR>
#### For example: ####

    273.482.234.33:66678/Phlex

#### If you've set up a domain with reverse proxy, then just put the url you've setup: ####

    my.domain.com/Phlex

#### Or: ####

    phlex.my.domain.com

<br>

**It is important to try the URL you are providing for "public address" before attempting to link your Google account.  If the address is not accessible via a device that is not on your local network (your cell phone with the wifi turned off), then I can't talk to your Phlex client, and the server will refuse to link your account.** 

Once you've got the address entered, click the "register server" button.  Your server address will be sent to the Phlex Mothership and verified, and if I determine your client is set up correctly for communication, will register you with the db and forward you to google to link your account.

Once this is done, you just need to ask your Google Assistant to "Talk to Flex TV".  This is the invocation name you will use to talk to Phlex.  

You should now be prompted to link your Google account(again).  

Open the Google Home app on your phone, and look for the card prompting you to link your account.  Go through that, and you should be all linked up!

Boom.  You can now talk to Phlex by saying things like "Ask Flex TV to play batman begins" or "Ask Flex TV to play the lastest episode of THe Big Bang Theory".  I'll be adding a wiki page for voice commands as time allows.
<br><br>

## Google Assistant Examples: ##
<br>

When talking with Google Assistant, your speech is parsed using API.ai's natural language processing, meaning Phlex is always getting better!  Below are just a few examples of the type of things you can ask Flex TV to do.
<BR>

    OK Google, Ask Flex TV to play Batman Begins.

    OK Google, Tell Flex TV to play the latest episode of Game of Thrones.
    
    Ok Google, Ask Flex TV to play Enter Sandman by Metallica.
    
    Ok Google, Ask Flex TV to play Eminem.
    
    Ok Google, Ask Flex TV to play The Slim Shady LP.

    OK Google, Ask Flex TV to pause playback.

    OK Google, Ask Flex TV to stop playback.

    OK Google, Ask Flex TV to set the volume to 80%.

    OK Google, Ask Flex TV turn the volume down.

    OK Google, Ask Flex TV to turn on a Bill Murray movie.

    OK Google, Ask Flex TV to fire up a Comedy.

    OK Google, Ask Flex TV to play a movie.

    OK Google, Ask Flex TV to play season 4 episode 3 of The Simpsons.

    OK Google, Ask Flex TV to download the show Alf.

    OK Google, Ask Flex TV to download Season 2 Episode 5 of the show The Americans.

    OK Google, Ask Flex TV to fetch The Avengers Age of Ultron.

    OK Google, Ask Flex TV what's playing.

    OK Google, Ask Flex TV what the name of this movie is.

    OK Google, Tell Flex TV I want to watch Frozen From 1 Hour and 45 Minutes.

    OK Google, Ask Flex TV if I have any new movies.
    
    OK Google, Ask Flex TV What's on deck.

<br><br>
## Trigger phrases: ##
<br>
Below are all of the recognized trigger phrases for Google Assistant commands.

*(Note, these values are always expanding, so you may want to try other "synonyms" for these phrases!)*


>**"Play":**
        "I want to watch",
        "I feel like watching",
        "do I have",
        "can you fire up",
        "can you play",
        "start",
        "turn on",
        "put on",
        "throw on",
        "find",
        "can you find",
        "cast"


>**"Resume":**
        "Play",
        "Resume",
        "Start",
        "Continue"

>**"Pause":**
	"Pause"

>**"Stop":**
        "Stop Playback",
        "Halt",
        "End Playback",
        "Cease",
        "Desist"

>**"Skip Forward":**
        "Skip Forward",
        "Skip Ahead",
        "Jump Ahead",
        "Next Chapter"

>**"Skip Backward":**
        "Skip Backward",
        "Skip Back",
        "Jump Back",
        "Previous Chapter"

>**"Seek Forward":**
        "Seek Forward",
        "Fast Forward",
        "Step Forward",
        "Seek Ahead"


>**"Seek Backward":**
        "Seek Backward",
        "Rewind",
        "Step Backward",
        "Step Back"

>**"Jump To":** // Not Implemented Yet
        "Jump To",
        "Seek To",
        "Skip To",
        "Fast Forward To",
        "Rewind To",
        "Go Back To"

>**"Stop":**
        "stop"


>**"Volume":**
        "set the volume",
        "adjust the volume",
        "turn the volume down",
        "turn the volume up",
        "turn it up",
        "turn it down"


>**"Fetch*:**
	"fetch",
        "I want to download",
        "can you download",
        "download",
        "grab",
        "snatch",
        "pirate",
        "add",
        "snag"

>**"Change Player"/"Change Server":**
	"switch player",
        "choose player",
        "change player",
        "select player",
        "pick player",
        "swap player"

>**"Currently playing":**
	"What's playing?",
        "What's on?",
        "What is playing?",
        "What is on?",
        "What am I watching?",
        "What is on TV?",
        "What movie is on?",
        "What show is on?",
        "Am I watching something?",
        "Can you tell me what this movie is called?",
        "Can you tell me what this show is called?",
        "What is this called?",
        "what the name of this"

>**"New Media":** 
	"What new TYPE",
        "have any new TYPE",
        "What's new?",
        "what is new",
        "recent",
        "recently added",
        "just added"

>**"On Deck Items":**
 	"ondeck",
        "What's on deck",
        "What is on deck",
        "up next"
        
>**"DVR Control":**
    "dvr",
        "Record Jeopardy",
        "DVR Family Guy"

>**"What's airing":**
    "What's on TV tonight?",
    "What's airing on Friday?",
    "What's dvred this weekend?",
    "What's recorded tomorrow?",
    "What's scheduled on Sunday?"

>(Note - What's airing commands work with Plex DVR, Sonarr, and Sickrage)

## Setting up IFTTT With Phlex (UPDATED)

1.  Head on over to https://ifttt.com/ and create an account.
2.  Go to the "applets" page.
3.  Click "New applet".
4.  Click the blue + button for IF THIS.
5.  search for "Assistant".
6.  "Say a phrase with a Text Ingredient".
7.  What do you want to say:
      * Language parsing has been updated to use API.ai for all commands, so no need for separate URL's for separate commands.
      * Depends on the command you want to use.  I use "Tell Plex to", "I want to watch", and I want to download" for playback, control, and fetch commands respectively.
    
      * You can mostly use whatever you want, but some triggers are reserved - these should work.
    
      * Put the $ sign where the thing you're sending to Phlex goes.
    
      * > "Tell plex to $"

8.  Save it.

9.  Click the "then that" button.

10.  Search for "Maker".

11.  Pick "Make a web request", the only option.

12.  Open the Phlex web UI, click the button under "Click to copy IFTTT URL:".  The URL should be copied to your clipboard, and look something like this:

       * http://{YOUR_SERVER_ADDRESS}/Phlex/api.php?**say**&apiToken=asdfaksdfjasae670a877d1a1e5931f5cbf326c&command={{TextField}}
        
13.  The "say" paramater tells Phlex to parse the command with API.ai, giving your greater flexibility over your commands.  If you wish to use the "legacy" paramaters, you can replace 'say' with either 'play','control', or 'fetch' to create specific commands for those triggers.

14.  Click "Create Action", test it out.

15.  Repeat for a download and "control playback" command.
<br><br>

## SUPPORT ##
For general help with installation, setup, or questions, head on over to the Plex forums and drop me a line.
<br><br>
 https://forums.plex.tv/discussion/252910/phlex-google-home-plex-integration-with-support-for-sonarr-couchpotato-etc-now-live/
<BR>

## Reporting Issues ##
If you think you've found a bug or would like to make a feature request, feel free to use the [issue tracker](https://github.com/d8ahazard/Phlex/issues) to let me know.  When posting an issue, try to include the following information:

1.  On what OS are you running Phlex?
2.  Are you using a new instance of XAMPP, or an existing webserver?
  * If Using an existing Webserver, have you ensured the proper modules are enabled and installed, and that PHP can read and write to the /Phlex directory?
3.  If you're having issues communicating with a specific instance of Plex/Couchpotato/Sonarr/etc. - tell me the OS you're running that app on.
4.  Is Phlex using http or https?  
5.  If you're having issues with a specific command, please note the timestamp in the web UI, and take a look at Phlex and Phlex_error.log files.  Paste anything that looks related as well.

<br><br>

### DONATIONS ###
If you really really like this project and want to thank me for sharing it with the world, you can send money via paypal to ** donate.to.digitalhigh@gmail.com **.  

This is a donate-only address, support requests will not be answered.
