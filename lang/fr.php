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
$lang['uiSettingForceSSL'] = "Force SSL";
$lang['uiSettingForceSSLHint'] = "Force SSL si disponible. Nécessaire pour les applications Web progressives.";
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
$lang['uiSettingFetcherPath'] = "Chemin d'accès (facultatif)";
$lang['uiSettingFetcherPort'] = "Port";
$lang['uiSettingFetcherToken'] = "Jeton";
$lang['uiSettingFetcherQualityProfile'] = "Profil de qualité";

//TODO
// Speech Responses (God help me)
$lang['speechHookCustomDefault'] = "Félicitations, vous avez lancé la commande custom webhook!";
$lang['speechGreetingArray'] = [
	"Salut, je suis Flex TV. Que puis-je faire pour vous aujourd'hui?",
	"Salutations! Comment puis-je t'aider?",
	"Bonjour. Essayez de me demander de jouer quelque chose."
];
$lang['speechGreetingHelpPrompt'] = "Si vous souhaitez une liste de commandes, vous pouvez dire «Aide» ou «Quelles sont vos commandes?";
$lang['cardReadmeButton'] = "Voir Readme";
$lang['cardGreetingText'] = "Bienvenue sur Flex TV!";
$lang['speechShuffleResponse'] = "D'accord, mélangeant tous les épisodes de ";
$lang['speechDvrSuccessStart'] = "Hé, regarde ça. J'ai ajouté le ";
$lang['speechDvrSuccessEnd'] = "au calendrier d'enregistrement.";
$lang['parsedDvrSuccessStart'] = "Ajouter le ";
$lang['parsedDvrSuccessNamed'] = " nommé";
$lang['parsedDvrFailStart'] = 'Ajouter les médias appelés ';
$lang['speechDvrFailStart'] = "Je n'ai pas pu trouver de résultats dans le guide de l'épisode qui correspond à '";
$lang['speechDvrNoDevice'] = "Je suis désolé, mais je n'ai trouvé aucun cas de Plex DVR à utiliser.";
$lang['speechChangeDeviceSuccessStart'] = "D'accord, j'ai changé ";
$lang['speechWordTo'] = " à ";
$lang['speechChangDeviceFailureStart'] = "Je suis désolé, mais je n'ai pas pu trouver ";
$lang['speechChangeDeviceFailEnd'] = " dans la liste des périphériques.";
$lang['speechStatusNothingPlaying'] = "Il ne semble pas qu'il y ait quoi de quoi jouer en ce moment.";
$lang['speechReturnRecent'] = "Voici une liste des récents ";
$lang['speechReturnOndeck'] = "Voici une liste des objets sur le pont: ";
$lang['speechOndeckRecentTail'] = " Si vous souhaitez regarder quelque chose, dites simplement le nom, sinon, vous pouvez dire «annuler».";
$lang['speechOndeckRecentError'] = "Malheureusement, je n'ai pas pu trouver de résultats pour cela. Veuillez réessayer plus tard.";
$lang['speechAiringsReturn'] = "Voici une liste d'enregistrements programmés: ";
$lang['speechAiringsAfternoon'] = "Cet après midi, ";
$lang['speechAiringsTonight'] = "Ce soir, ";
$lang['speechAiringsTomorrow'] = "Demain, ";
$lang['speechAiringsWeekend'] = "Cette fin de semaine, ";
$lang['speechAiringsMids'] = ['vous avez', 'je vois', "j'ai trouvé"];
$lang['speechAiringTails'] = [' Sur l\'horaire.',' prêt à enregistrer.',' à venir.'];
$lang['speechAiringsErrors'] = ["Je n'ai rien sur la liste", "Vous n'avez rien prévu pour"];
$lang['speechPlaybackAffirmatives'] = ["Oui capitaine", "D'accord", "Bien sûr", "Pas de problème", "Oui maître", "Vous l'avez compris", "Comme vous le commandez", "Allrighty alors"];
$lang['speechEggBatman'] = "Les médias piratés sainte!  ";
$lang['speechEggGhostbusters'] = "À qui vous allez appeler?  ";
$lang['speechEggIronMan'] = "Oui M. Stark, ";