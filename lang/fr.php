<?php
// French strings file
//
// This needs translating!!
//
// This file should consist of array/key pairs for each language string used
// in the application.  The corresponding file will be included in each page
// by default, and all language words will be substituted when the pages are
// rendered.

$lang["uiPageTitle"] = "Phlex Web";
$lang["uiGreetingDefault"] = "Salut, I'm Flex TV. Que puis-je faire pour vous?";
$lang["uiRescanDevices"] = "Rescan devices";
$lang["uiPlayerSelectPrompt"] = "Sélectionnez un joueur pour contrôler.";
$lang["uiSettingHeaderPhlex"] = "Phlex";
$lang["uiSettingHeaderPlex"] = "Plex";
$lang["uiSettingHeaderFetchers"] = "Fetcher";
$lang["uiSettingHeaderLogs"] = "Logs";
$lang["uiSettingBtnTest"] = "TESTER";
$lang["uiSettingBtnReset"] = "RÉINITIALISER";
$lang["uiSettingLanguage"] = "La Langue:";
$lang["uiSettingLangEnglish"] = "Anglais";
$lang["uiSettingLangFrench"] = "français";
$lang["uiSettingApiKey"] = "Clé API:";
$lang["uiSettingPublicAddress"] = "Adresse Publique:";
$lang["uiSettingRescanInterval"] = "Intervalle de rééchantillonnage de l'appareil (minutes):";
$lang["uiSettingRescanHint"] = "À quelle fréquence re-mettre en cache les périphériques.";
$lang["uiSettingObscureLogs"] = "Données sensibles obscures dans les grumes";
$lang["uiSettingThemeColor"] = "Utiliser un Thème Sombre";
$lang["uiSettingAccountLinking"] = "Lien de Compte:";
$lang["uiSettingLinkGoogle"] = "LINK GOOGLE";
$lang["uiSettingLinkAmazon"] = "LINK AMAZON";
$lang["uiSettingCopyIFTTT"] = "Cliquez pour copier l'URL IFTTT:";
$lang["uiSettingUpdates"] = "Mises à jour";
$lang["uiSettingAutoUpdate"] = "Installer automatiquement les mises à jour";
$lang["uiSettingRefreshUpdates"] = "RAFRAÎCHIR";
$lang["uiSettingInstallUpdates"] = "INSTALLER";
$lang["uiSettingEnable"] = "Activer";
$lang["uiSettingSeparateHookUrl"] = "URLs séparées";
$lang["uiSettingHookLabel"] = "Webhooks";
$lang["uiSettingHookUrlGeneral"] = "Webhook URL:";
$lang["uiSettingHookGeneric"] = "URL:";
$lang["uiSettingHookPlayback"] = "Lecture";
$lang["uiSettingHookPause"] = "Pause";
$lang["uiSettingHookStop"] = "Arrêtez";
$lang["uiSettingHookFetch"] = "Fetch";
$lang["uiSettingHookCustom"] = "Douane";
$lang["uiSettingHookCustomPhrase"] = "Phrase personnalisée";
$lang["uiSettingHookCustomPhrase"] = "Réponse personnalisée";
$lang["uiSettingHookPlayHint"] = "?Param = {{media name}} sera ajouté";
$lang["uiSettingGeneral"] = "Général";
$lang["uiSettingPlaybackServer"] = "Serveur de lecture:";
$lang["uiSettingOndeckRecent"] = "Nombre de pièces sur le pont / récentes à renvoyer:";
$lang["uiSettingUseCast"] = "Utiliser des appareils dérivés";
$lang["uiSettingNoPlexDirect"] = "Pas de Plex.Direct";
$lang["uiSettingNoPlexDirectHint"] = "{EXPERIMENTAL) Activez ceci si vous ne pouvez pas vous connecter à votre serveur.";
$lang["uiSettingTestButton"] = "TESTER";
$lang["uiSettingPlexDVR"] = "Plex DVR";
$lang["uiSettingDvrServer"] = "Serveur DVR";
$lang["uiSettingDvrResolution"] = "Résolution";
$lang["uiSettingDvrResolutionAny"] = "Tout";
$lang["uiSettingDvrResolutionHD"] = "Haute définition";
$lang["uiSettingDvrNewAirings"] = "Enregistrer les nouvelles aéronefs seulement";
$lang["uiSettingDvrReplaceLower"] = "Remplacer les enregistrements de qualité inférieure";
$lang["uiSettingDvrRecordPartials"] = "Enregistrement d'épisodes partiels";
$lang["uiSettingDvrStartOffset"] = "Début Offset (Minutes):";
$lang["uiSettingDvrEndOffset"] = "Arrêt définitif (minutes):";
$lang["uiSettingLogCount"] = "Compter:";
$lang["uiSettingLogLevel"] = "Niveau:";
$lang["uiSettingLogUpdate"] = "Compter:";

// Fetcher labels
$lang['uiSettingfetcherPath'] = "Chemin d'accès (facultatif)";
$lang['uiSettingfetcherPort'] = "Port";
$lang['uiSettingfetcherToken'] = "Jeton";
$lang['uiSettingfetcherQualityProfile'] = "Profil de qualité";

//TODO
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
$lang['parsedDvrSuccessStart'] = "Add the ";
$lang['parsedDvrSuccessNamed'] = " named ";
$lang['parsedDvrFailStart'] = 'Add the media named ';
$lang['speechDvrFailStart'] = "I wasn't able to find any results in the episode guide that match '";
$lang['speechDvrNoDevice'] = "I'm sorry, but I didn't find any instances of Plex DVR to use.";
$lang['speechChangeDeviceSuccessStart'] = "Okay, I've switched the ";
$lang['speechWordTo'] = " to ";
$lang['speechChangDeviceFailureStart'] = "I'm sorry, but I couldn't find ";
$lang['speechChangeDeviceFailEnd'] = " in the device list.";
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