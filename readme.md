# **README**

## Introduction

**What is Phlex?**  Phlex stands for **P**ersonal **H**ome **L**anguage **EX**tension. I mean, I guess. I literally just made that up. Also, it sounds cool.


If you're confused because you've heard the project called "Flex TV" - Flex TV is the action name for Alexa and Google - Phlex is the name of the app before I realized Google wouldn't let me just call it Phlex.  :P

Aside from being a made-up name that sounds cool, The purpose of Phlex is to provide a natural language interface for Home Theater applications - effectively bridging the current gap between commercial AI Solutions like Google Home/Alexa and personal web applications like Plex, Couchpotato, and Sonarr.

Or, in short - you can watch and download movies and shows just by telling your phone to do so.

<a href="http://www.youtube.com/watch?feature=player_embedded&v=FZBlNwBocAc
" target="_blank"><img src="http://img.youtube.com/vi/FZBlNwBocAc/0.jpg"
alt="Phlex Demo #2" width="480" height="360" border="10" /></a>

<a href="http://imgur.com/kzIOZ8s"><img src="http://i.imgur.com/kzIOZ8s.png" title="source: imgur.com" /></a>

<a href="http://imgur.com/Ehdead2"><img src="http://i.imgur.com/Ehdead2.png" title="source: imgur.com" /></a>

<a href="http://imgur.com/rqottwZ"><img src="http://i.imgur.com/rqottwZ.png" title="source: imgur.com" /></a>

<a href="http://imgur.com/zKUb4wy"><img src="http://i.imgur.com/zKUb4wy.png" title="source: imgur.com" /></a>

<a href="http://imgur.com/t0PtEoA"><img src="http://i.imgur.com/t0PtEoA.png" title="source: imgur.com" /></a>

<a href="http://imgur.com/let2swA"><img src="http://i.imgur.com/let2swA.png" title="source: imgur.com" /></a>

<a href="http://imgur.com/O9sDzUx"><img src="http://imgur.com/O9sDzUx.png"></a>


## Installation

**NOTE: YOUR config.ini.php file is IMPORTANT. DO NOT DELETE OR REMOVE THIS FILE ONCE YOU'VE GOT PHLEX RUNNING. IF UPDATING, KEEP A COPY OF THIS FILE AND REPLACE IT. REMOVING THIS FILE WILL ABSOLUTELY BREAK ACCOUNT LINKING FOR GOOGLE ASSISTANT.**

There are many ways you can install Phlex, here are a few options.

### Using Docker

There is a [docker image](https://hub.docker.com/r/digitalhigh/phlex) available. Refer to the image documentation to get started.

### Using XAMPP

Phlex requires a webserver and PHP 7.0+ with CURL and SSH enabled in order to work correctly.

**For Cast Device Control, you will also need to enable the Sockets module.**

For most use cases, XAMPP is going to be the easiest options.

XAMPP is a free, cross-platform web server package, and can be found here: <https://www.apachefriends.org/index.html>

When installing XAMPP, we only require the PHP and Apache features.  You can uncheck the rest of the options in installation if you have no need for them:

MySQL, FileZilla FTP Server, Mercury Mail Server, Tomcat, Perl, phpMyAdmin, Webalizer, Fake Sendmail...ain't nobody got time fo dat.

Once installed, clone or download the Phlex directory to the root web directory of XAMPP, which should be the /htdocs folder.  When done, the path should be something like C:\xampp\htdocs\phlex (windows) or usr/home/xampp/htdocs/phlex (linux).

That should be it.  You can now restart the apache service for your webserver, and browse to your Phlex installation at 'http://yourserveraddress:80/Phlex'.

To log in, enter your **Plex** username and password.

If you are running Phlex on an existing webserver, Phlex PHP version 7.0 and up.  Phlex will also require r/w access to the root of the /Phlex directory for configuration and logging purposes.
You will also need the CURL and openSSL extensions enabled, and sockets if you have any Cast devices. If you're not using Xampp, you may also need to enable the xml module.

### Using a Raspberry Pi

(Thanks to giac0m0 for the writeup)

#### Check version of PHP

1. On the Raspberry Pi, check version of PHP

        php -v

1a. **If** PHP is not v7.0, remove PHP and install 7.0

        sudo apt-get remove php*

#### Install PHP v7.0 and plugins needed

2. Install Apache and PHP7.0 using instructions from site: <https://www.stewright.me/2016/03/turn-raspberry-pi-3-php-7-powered-web-server/>

3. Install PHP XML and mbstrings (missing from previous guide)

        sudo apt-get install php-xml php7.0-mbstring
        
4.  Install GIT

        sudo apt-get install git

5.  Create a phlex directory

        sudo mkdir /var/www/html/Phlex/
        
6.  Clone Phlex

        sudo git clone https://github.com/d8ahazard/Phlex.git /var/www/html/Phlex
        

7. Change ownership of Phlex folder to www-data

        sudo chown -R www-data /var/www/html/Phlex/

8. Add writeable folders to `/etc/php/7.0/apache2/php.ini`

   Add this line to the file, near the guidance notes for open_basedir :

        open_basedir = /var/www/html/Phlex/

9. Edit php.ini for Dynamic Extensions.  Add lines:

        extension=curl.so
        extension=openssl.so

#### Restart web server

        sudo service apache2 restart

## Updating

Congratulations. If you installed Phlex via git or Docker, you're all set. Go into settings, click the "Auto update" toggle, and go to town!

If you installed from a .zip file, it is important to not delete config.ini.php, as this is where your server's API token is stored, which is used to associate your local Phlex client with your Google account.

## Post-Installation (Network Stuff)

### Port forwarding

First, you'll need to forward IP traffic to port 80 on the computer where Phlex is running.  You do not have to forward port 80 to port 80, you can choose any open port you like.

If you wish to change the listening port for Phlex to something other than 80, you can do so in the Xampp control panel by clicking the "config" button next to Apache, and then opening httpd.conf.

In httpd.conf, you will see a line that reads "Listen 80".  Change 80 to whichever port you want.  Restart apache, and forward to that port.  If you are on Windows, you will need to make a rule in Windows Firewall to allow that port's traffic as well.

### MDNS/Multicast/Cast Discovery

Chromecasts are fun devices.  They're fun for this project because they use Multicast DNS (Aka Bonjour, Aka Avahii, Aka Zeroconf) in order to talk to one another on a home network.

On a very basic home network with just one router and everything connected via wifi, MDNS usually just works. *Usually.*

However, there are thousands of different router manufactureres, and thousands of different network configurations that could play a part in how well your cast devices will talk with Phlex.

If you are experiencing issues with cast devices not showing up in Phlex, you should first verify that the php sockets module is installed and enabled.  If you used XAMPP, it should be included, you just need to edit your php.ini file to enable it.

If you used an alternative webserver option, google it.

If sockets are enabled and you STILL can't see all of your cast devices, I would suggest looking in the web UI for your router and looking for any setting related to "multicast filtering", "bonjour","or MDNS discovery".  Most of them should support it, it may just need to be enabled.

## Setting it up with Google Assistant

Log into Phlex with your Plex.tv username and password.
Click on the gear icon to open settings.
In settings, under the Phlex tab, make sure you fill in the "Public Address" box with the FULL address of your Phlex server.  Typically, you will just need to append /Phlex to the directory path, and a port if you changed it to something other than 80.

### For example

    273.482.234.33:66678/Phlex

### If you've set up a domain with reverse proxy, then just put the url you've setup

    my.domain.com/Phlex

### Or

    phlex.my.domain.com

**It is important to try the URL you are providing for "public address" before attempting to link your Google account.  If the address is not accessible via a device that is not on your local network (your cell phone with the wifi turned off), then I can't talk to your Phlex client, and the server will refuse to link your account.**

Once you've got the address entered, click the "register server" button.  Your server address will be sent to the Phlex Mothership and verified, and if I determine your client is set up correctly for communication, will register you with the db and forward you to google to link your account.

Once this is done, you just need to ask your Google Assistant to "Talk to Flex TV". This is the invocation name you will use to talk to Phlex.

You should now be prompted to link your Google account(again).

Open the Google Home app on your phone, and look for the card prompting you to link your account. Go through that, and you should be all linked up!

Boom. You can now talk to Phlex by saying things like "Ask Flex TV to play batman begins" or "Ask Flex TV to play the lastest episode of THe Big Bang Theory". I'll be adding a wiki page for voice commands as time allows.

### Multiple Google Home Users

If you have multiple people in your household, it's possible to allow them to link their account to your Phlex instance, without having them create their own Plex.tv account.
All you need to do is have them sign into the Phlex UI from a device linked to their Google account. Go into settings, click the "link server" button, and have them select their own Google account.

Now have them ask to talk to Flex TV through Assistant, and finish linking their account through the Ghome app.

This can be repeated for as many users as you have in your household.

### Google Assistant Examples

When talking with Google Assistant, your speech is parsed using API.ai's natural language processing, meaning Phlex is always getting better! Below are just a few examples of the type of things you can ask Flex TV to do.

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


## Amazon Alexa

If you're reading this because you suddenly noticed that there is mention of Alexa floating around, you're in luck!

I'm currently looking for beta testers from both the US and UK to help work out bugs with Flex TV as I await certification from Amazon.  

## Trigger phrases

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

>**"Fetch":**
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

>**"Help":**
        "What are your commands?"

## Setting up IFTTT With Phlex (UPDATED)

1. Head on over to <https://ifttt.com/> and create an account.
1. Go to the "applets" page.
1. Click "New applet".
1. Click the blue + button for IF THIS.
1. Search for "Assistant".
1. "Say a phrase with a Text Ingredient".
1. What do you want to say:
      * Language parsing has been updated to use API.ai for all commands, so no need for separate URL's for separate commands.
      * Depends on the command you want to use.  I use "Tell Plex to", "I want to watch", and I want to download" for playback, control, and fetch commands respectively.
      * You can mostly use whatever you want, but some triggers are reserved - these should work.
      * Put the $ sign where the thing you're sending to Phlex goes.
      * > "Tell plex to $"

1. Save it.

1. Click the "then that" button.

1. Search for "Webhooks".

1. Pick "Make a web request", the only option.

1. Open the Phlex web UI, click the button under "Click to copy IFTTT URL:".  The URL should be copied to your clipboard, and look something like this:

       http://{YOUR_SERVER_ADDRESS}/Phlex/api.php?**say**&apiToken=asdfaksdfjasae670a877d1a1e5931f5cbf326c&command={{TextField}}

1. The "say" paramater tells Phlex to parse the command with API.ai, giving your greater flexibility over your commands.  If you wish to use the "legacy" paramaters, you can replace 'say' with either 'play','control', or 'fetch' to create specific commands for those triggers.

1. Click "Create Action", test it out.

1. Repeat for a download and "control playback" command.

## SUPPORT

For general help with installation, setup, or questions, head on over to the Plex forums and drop me a line.

<https://forums.plex.tv/discussion/252910/phlex-google-home-plex-integration-with-support-for-sonarr-couchpotato-etc-now-live/>

## Reporting Issues

If you think you've found a bug or would like to make a feature request, feel free to use the [issue tracker](https://github.com/d8ahazard/Phlex/issues) to let me know.  When posting an issue, try to include the following information:

1. On what OS are you running Phlex?
1. Are you using a new instance of XAMPP, or an existing webserver?
    * If Using an existing Webserver, have you ensured the proper modules are enabled and installed, and that PHP can read and write to the /Phlex directory?
1. If you're having issues communicating with a specific instance of Plex/Couchpotato/Sonarr/etc. - tell me the OS you're running that app on.
1. Is Phlex using http or https?  
1. If you're having issues with a specific command, please note the timestamp in the web UI, and take a look at Phlex and Phlex_error.log files.  Paste anything that looks related as well.

### DONATIONS

Phlex/Flex TV is currently a one-person operation. There is no big team of people, there are no slick corporate sponsors. I cannot stand ad-sponsored projects or "freemium" apps, and will never try to use this garbage to gain revenue from users.

However, this is a massive undertaking, and has snowballed enormously from a simple IFTTT hook/script to the project you now see today.

SO, If you really really like this project and want to show a little love, you can send money via paypal to **donate.to.digitalhigh@gmail.com**.

This address is for donations only, if you need support, please look above for information on how to ask for it.

I love lamp...          
