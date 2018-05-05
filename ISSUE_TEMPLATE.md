## ISSUE TEMPLATE

### IMPORANT, PLEASE READ FIRST:

Due to an overwhelming amount of issues with cast devices and network issues in general, I've begun
a complete overhaul of the codebase behind Flex TV. This includes delgating cast functionality to a custom
Plex Media Server plugin (https://github.com/d8ahazard/Cast.bundle), as well as a major reworking of device
scraping and storage, fetcher interactions, UI overhauls, and basically - rewriting a massive chunk of
the application.

Pending the release of Flex TV v2, I ask that you refrain from submitting issues regarding...well...most of the things.

Casting, device fetching, data storage, signin issues, media fetcher interactions, multiple languages...all
of these are receiving major love...and so until then, just sit tight.

I am still looking for OS-related issues (FreeBSD causes problems), and issues related to one-off players, smart TV's,
things that aren't related to casting or server connectivity. Also, feature requests.

That said - we now return you to your regularly scheduled program.


Please read over the [Notes/FAQ](Notes-FAQ) and [networking](Talking-To-The-Outside-World) sections before reporting an issue.

If your issue is not resolved, try to fill out the following as completely as possible. I'm not going to delete issues just because forms aren't filled out completely, but I will if you provide me no information whatsoever.

If submitting a feature request, these are not required.

#### 1. On what OS are you running Flex TV?

#### 2. Are you using a new instance of XAMPP, or an existing webserver?

#### 2b. If not XAMPP, what WebServer stack are you using?

#### 3. Have you enabled the sockets module and ensured PHP has write-access to the directory containing Flex TV?

#### 4. Have you followed the (networking)[Talking-To-The-Outside-World] section?

#### 5. If you're having issues with a specific command, please note the timestamp in the web UI, and take a look at Flex TV and Flex TV_error.log files.  Paste anything that looks related as well.

*BE SURE TO CHANGE ANY LINES CONTAINING API TOKENS OR OTHER PERSONAL IDENTIFYING INFORMATION*

#### 6. What are the last six digits of your server's API Token? (Settings -> Flex TV)