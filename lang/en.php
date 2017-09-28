<?php


$lang['uiPageTitle'] = 'Phlex Web';
$lang['uiGreetingDefault'] = "Hi, I'm Flex TV. What can I do for you?";
$lang['uiRescanDevices'] = "Rescan devices";
$lang['uiPlayerSelectPrompt'] = "Select a player to control.";
// Setting Headers
$lang['uiSettingHeaderPhlex'] = "Phlex";
$lang['uiSettingHeaderPlex'] = "Plex";
$lang['uiSettingHeaderDevices'] = "Devices";
$lang['uiSettingHeaderFetchers'] = "Fetchers";
$lang['uiSettingHeaderLogs'] = "Logs";
$lang['uiSettingBtnTest'] = "TEST";
$lang['uiSettingBtnReset'] = "RESET";
$lang['uiSettingEnable'] = "Enable";
// Phlex Setting Labels
$lang['uiSettingLanguage'] = "Language:";
$lang['uiSettingLangEnglish'] = "English";
$lang['uiSettingLangFrench'] = "French";
$lang['uiSettingApiKey'] = "API Key:";
$lang['uiSettingPublicAddress'] = "Public Address:";
$lang['uiSettingRescanInterval'] = "Device Rescan Interval (Minutes):";
$lang['uiSettingRescanHint'] = "How frequently to re-cache devices.";
$lang['uiSettingObscureLogs'] = "Obscure Sensitive Data in Logs";
$lang['uiSettingThemeColor'] = "Use Dark Theme";
$lang['uiSettingForceSSL'] = "Force SSL";
$lang['uiSettingForceSSLHint'] = "Force SSL if available.  Required for Progressive Web Apps.";
$lang['uiSettingAccountLinking'] = "Account Linking:";
$lang['uiSettingLinkGoogle'] = "LINK GOOGLE";
$lang['uiSettingLinkAmazon'] = "LINK AMAZON";
$lang['uiSettingCopyIFTTT'] = "Click to copy IFTTT URL:";
$lang['uiSettingUpdates'] = "Updates";
$lang['uiSettingAutoUpdate'] = "Automatically Install Updates";
$lang['uiSettingRefreshUpdates'] = "REFRESH";
$lang['uiSettingInstallUpdates'] = "INSTALL";
$lang['uiSettingSeparateHookUrl'] = "Separate URLs";
$lang['uiSettingHookLabel'] = "Webhooks";
$lang['uiSettingHookUrlGeneral'] = "Webhook URL:";
$lang['uiSettingHookGeneric'] = "URL:";
$lang['uiSettingHookPlayback'] = "Playback";
$lang['uiSettingHookPause'] = "Pause";
$lang['uiSettingHookStop'] = "Stop";
$lang['uiSettingHookFetch'] = "Fetch";
$lang['uiSettingHookCustom'] = "Custom";
$lang['uiSettingHookCustomPhrase'] = "Custom Phrase";
$lang['uiSettingHookCustomPhrase'] = "Custom Reply";
$lang['uiSettingHookPlayHint'] = "?param={{media name}} will be appended";

// Plex Setting Labels
$lang['uiSettingGeneral'] = "General";
$lang['uiSettingPlaybackServer'] = "Playback Server:";
$lang['uiSettingOndeckRecent'] = "Number of On-Deck/Recent Items to Return:";
$lang['uiSettingUseCast'] = "Use Cast Devices";
$lang['uiSettingNoPlexDirect'] = "No Plex.Direct";
$lang['uiSettingNoPlexDirectHint'] = "{EXPERIMENTAL) Enable this if you can't connect to your server.";
$lang['uiSettingTestButton'] = "TEST";
$lang['uiSettingPlexDVR'] = "Plex DVR";
$lang['uiSettingDvrServer'] = "DVR Server";
$lang['uiSettingDvrResolution'] = "Resolution";
$lang['uiSettingDvrResolutionAny'] = "Any ";
$lang['uiSettingDvrResolutionHD'] = "High-Definition ";
$lang['uiSettingDvrNewAirings'] = "Record New Airings Only";
$lang['uiSettingDvrReplaceLower'] = "Replace Lower Quality Recordings";
$lang['uiSettingDvrRecordPartials'] = "Record Partial Episodes";
$lang['uiSettingDvrStartOffset'] = "Start Offset (Minutes):";
$lang['uiSettingDvrEndOffset'] = "End Offset (Minutes):";

// Device tab
$lang['uiSettingDevices'] = "Static Devices";
$lang['uiSettingDevicesAddNew'] = "Add New Device";
$lang['uiSettingDevicesNameLabel'] = "Device Name";

// Log labels
$lang['uiSettingLogCount'] = "Count:";
$lang['uiSettingLogLevel'] = "Level:";
$lang['uiSettingLogUpdate'] = "Update";

// Fetcher labels
$lang['uiSettingFetcherPath'] = "Path (Optional)";
$lang['uiSettingFetcherPort'] = "Port";
$lang['uiSettingFetcherToken'] = "Token";
$lang['uiSettingFetcherQualityProfile'] = "Quality Profile";

// Speech Responses (God help me)
$lang['speechHookCustomDefault'] = "Congratulations, you've fired the custom webhook command!";
$lang['speechGreetingArray'] = [
	"Hi, I'm Flex TV.  What can I do for you today?",
	"Greetings! How can I help you?",
	"Hello there. Try asking me to play something.'"
];
$lang['speechGreetingHelpPrompt'] = " If you'd like a list of commands, you can say 'Help' or 'What are your commands?'";
$lang['cardReadmeButton'] = "View Readme";
$lang['cardGreetingText'] = "Welcome to Flex TV!";
$lang['speechShuffleResponse'] = "Okay, shuffling all episodes of ";
$lang['speechDvrSuccessStart'] = "Hey, look at that.  I've added the ";
$lang['speechDvrSuccessEnd'] = "to the recording schedule.";
$lang['parsedDvrFailStart'] = 'Add the media named ';
$lang['speechDvrFailStart'] = "I wasn't able to find any results in the episode guide that match '";
$lang['speechDvrNoDevice'] = "I'm sorry, but I didn't find any instances of Plex DVR to use.";
$lang['speechChangeDevicePrompt'] = ", sure.  What device would you like to use? ";
$lang['speechChangeDeviceSuccessStart'] = "Okay, I've switched the ";
$lang['speechWordTo'] = " to ";
$lang['speechChangDeviceFailureStart'] = "I'm sorry, but I couldn't find ";
$lang['speechChangeDeviceFailEnd'] = " in the device list.";
$lang['speechChangeDeviceWarnStart1'] = "foo";
$lang['speechChangeDeviceWarnEnd1'] = "foo";
$lang['speechChangeDeviceWarnStart2'] ="foo" ;
$lang['speechChangeDeviceWarnEnd2'] = "foo";
$lang['speechChangeDeviceWarnStart3'] = "foo";
$lang['speechChangeDeviceWarnStart3'] = "foo";
$lang['speechStatusNothingPlaying'] = "It doesn't look like there's anything playing right now.";
$lang['speechReturnRecent'] = "Here's a list of recent ";
$lang['speechReturnOndeck'] = "Here's a list of on deck items: ";
$lang['speechOndeckRecentTail'] = " If you'd like to watch something, just say the name, otherwise, you can say 'cancel'.";
$lang['speechOndeckRecentError'] = "Unfortunately, I wasn't able to find any results for that.  Please try again later.";
$lang['speechAiringsReturn'] = "Here's a list of scheduled recordings: ";
$lang['speechAiringsAfternoon'] = "This afternoon, ";
$lang['speechAiringsTonight'] = "Tonight, ";
$lang['speechAiringsTomorrow'] = "Tomorrow, ";
$lang['speechAiringsWeekend'] = "This weekend, ";
$lang['speechAiringsMids'] = ['you have ','I see ','I found '];
$lang['speechAiringTails'] = [' on the schedule.',' set to record.',' coming up.'];
$lang['speechAiringsErrors'] = ["I don't have anything on the list for ","You don't have anything scheduled for "];
$lang['speechPlaybackAffirmatives'] = ["Yes captain, ","Okay, ","Sure, ","No problem, ","Yes master, ","You got it, ","As you command, ","Allrighty then, "];
$lang['speechEggBatman'] = "Holy pirated media!  ";
$lang['speechEggGhostbusters'] = "Who you gonna call?  ";
$lang['speechEggIronMan'] = "Yes Mr. Stark, ";
$lang['speechEggAvengers'] = "Family assemble! ";
$lang['speechEggFrozen'] = "Let it go! ";
$lang['speechEggOdyssey'] = "I am afraid I can't do that Dave.  Okay, fine, ";
$lang['speechEggBigHero'] = "Hello, I am Baymax, I am going to be ";
$lang['speechEggWallE'] = "Thank you for shopping Buy and Large, and enjoy as we begin ";
$lang['speechEggEvilDead'] = "Hail to the king, baby! ";
$lang['speechEggFifthElement'] = "Leeloo Dallas Mul-ti-Pass! ";
$lang['speechEggGameThrones'] = "Brace yourself...";
$lang['speechEggTheyLive'] = "I'm here to chew bubblegum and ";
$lang['speechEggHeathers'] = "Well, charge me gently with a chainsaw.  ";
$lang['speechEggStarWars'] = "These are not the droids you're looking for.  ";
$lang['speechEggResidentEvil'] = "T-minus 1 minute until infection.  ";
$lang['speechEggAttackTheBlock'] = "Are you the Doctor? ";
$lang['speechPlaying'] = "Playing ";
$lang['speechBy'] = " by ";
$lang['speechMultiResultArray'] =[
	"I found several possible results for that, were one of these what you wanted? ",
	"Which one did you mean? ",
	"You have a few things that could match that search, are one of these it? ",
	"There were a few things I think you could be asking for, is it any of these?"
];
$lang['speechPlayErrorStart1'] = "I couldn't find ";
$lang['speechPlayErrorEnd1'] = " in your library.  Should I try to add it to the watch lists?";
$lang['speechPlayErrorStart2'] = "I didn't find anything named '";
$lang['speechPlayErrorEnd2'] = "' to play.  Should I try to fetch it for you?'";
$lang['speechPlayError3'] = "I couldn't find that in the library, do you want me to try downloading it?";

// Parsed Responses (Not really necessary, as we display the speech in cards/UI

$lang['parsedDvrSuccessStart'] = "Add the ";
$lang['parsedDvrSuccessNamed'] = " named ";

$lang['suggestionYesNo'] = ['Yes','No'];
$lang['speechChange'] = 'Change ';
$lang['speechPlayer'] = "player";
$lang['speechServer'] = "server";

























