<?php


$lang['uiPageTitle'] = 'Phlex Web';
$lang['uiGreetingDefault'] = "Salut, je suis Flex TV. Que puis-je pour vous ?";
$lang['uiRescanDevices'] = "Rescan périphériques";
$lang['uiPlayerSelectPrompt'] = "Sélectionnez un lecteur a contrôler.";
// Setting Headers
$lang['uiSettingHeaderPhlex'] = "Phlex";
$lang['uiSettingHeaderPlex'] = "Plex";
$lang['uiSettingHeaderDevices'] = "Appareils";
$lang['uiSettingHeaderFetchers'] = "Chercheurs";
$lang['uiSettingHeaderLogs'] = "Journaux";
$lang['uiSettingBtnTest'] = "TEST";
$lang['uiSettingBtnReset'] = "RAZ";
$lang['uiSettingEnable'] = "Activer";
// Phlex Setting Labels
$lang['uiSettingLanguage'] = "Langue :";
$lang['uiSettingLangEnglish'] = "English";
$lang['uiSettingLangFrench'] = "Français";
$lang['uiSettingApiKey'] = "Clé API :";
$lang['uiSettingPublicAddress'] = "Adresse Publique :";
$lang['uiSettingRescanInterval'] = "Intervalle de Scan (minutes) :";
$lang['uiSettingRescanHint'] = "fréquence de recherche des périphériques.";
$lang['uiSettingObscureLogs'] = "Données sensibles dans les journaux";
$lang['uiSettingThemeColor'] = "Utiliser un thème sombre";
$lang['uiSettingForceSSL'] = "Forcer SSL";
$lang['uiSettingForceSSLHint'] = "Force SSL si disponible. Nécessaire pour les applications Web progressives.";
$lang['uiSettingAccountLinking'] = "Lien pour les comptes :";
$lang['uiSettingLinkGoogle'] = "LIEN GOOGLE";
$lang['uiSettingLinkAmazon'] = "LIEN AMAZON";
$lang['uiSettingCopyIFTTT'] = "Cliquez pour copier l'URL IFTTT:";
$lang['uiSettingUpdates'] = "Mises à jour";
$lang['uiSettingAutoUpdate'] = "Installer automatiquement les mises à jour";
$lang['uiSettingRefreshUpdates'] = "RAFRAÎCHIR";
$lang['uiSettingInstallUpdates'] = "INSTALLER";
$lang['uiSettingSeparateHookUrl'] = "URLs séparées";
$lang['uiSettingHookLabel'] = "Webhooks";
$lang['uiSettingHookUrlGeneral'] = "Webhook URL:";
$lang['uiSettingHookGeneric'] = "URL:";
$lang['uiSettingHookPlayback'] = "Lecture";
$lang['uiSettingHookPause'] = "Pause";
$lang['uiSettingHookStop'] = "Stop";
$lang['uiSettingHookFetch'] = "Fetch";
$lang['uiSettingHookCustom'] = "Personnalisée";
$lang['uiSettingHookCustomPhrase'] = "Expression personnalisée";
$lang['uiSettingHookCustomPhrase'] = "Réponse personnalisée";
$lang['uiSettingHookPlayHint'] = "?param={{media name}} sera ajouté";

// Plex Setting Labels
$lang['uiSettingGeneral'] = "Général";
$lang['uiSettingPlaybackServer'] = "Serveur de lecture :";
$lang['uiSettingOndeckRecent'] = "Nombre d’éléments disponible/récents à retourner :";
$lang['uiSettingUseCast'] = "Utiliser des appareils Cast";
$lang['uiSettingNoPlexDirect'] = "Pas de Plex.Direct";
$lang['uiSettingNoPlexDirectHint'] = "(EXPÉRIMENTAL) Activez ceci si vous ne pouvez pas vous connecter à votre serveur.";
$lang['uiSettingTestButton'] = "TEST";
$lang['uiSettingPlexDVR'] = "Plex DVR";
$lang['uiSettingDvrServer'] = "DVR Serveur";
$lang['uiSettingDvrResolution'] = "Résolution";
$lang['uiSettingDvrResolutionAny'] = "Tout ";
$lang['uiSettingDvrResolutionHD'] = "Haute définition ";
$lang['uiSettingDvrNewAirings'] = "Enregistrez les nouveaux seulement";
$lang['uiSettingDvrReplaceLower'] = "Remplacer les enregistrements de qualité inférieure";
$lang['uiSettingDvrRecordPartials'] = "Enregistrement d'épisodes partiels";
$lang['uiSettingDvrStartOffset'] = "Décalage de début (Minutes) :";
$lang['uiSettingDvrEndOffset'] = "Décalage de fin (Minutes) :";

// Device tab
$lang['uiSettingDevices'] = "Périphérique statiques";
$lang['uiSettingDevicesAddNew'] = "Ajouter un nouveau périphérique";
$lang['uiSettingDevicesNameLabel'] = "Nom de l'appareil";

// Log labels
$lang['uiSettingLogCount'] = "Afficher :";
$lang['uiSettingLogLevel'] = "Niveau :";
$lang['uiSettingLogUpdate'] = "Mettre à jour";

// Fetcher labels
$lang['uiSettingFetcherPath'] = "Chemin d'accès (facultatif)";
$lang['uiSettingFetcherPort'] = "Port";
$lang['uiSettingFetcherToken'] = "Jeton";
$lang['uiSettingFetcherQualityProfile'] = "Profil de qualité";

// Speech Responses (God help me)
$lang['speechHookCustomDefault'] = "Félicitations, vous avez lancé la commande webhook personnalisée !";
$lang['speechGreetingArray'] = [
	"Salut, je suis Flex TV.  Que puis-je faire pour vous aujourd'hui ?",
	"Salutations ! Comment puis-je vous aider ?",
	"Bonjour. Essayez de me demander de lire quelque chose. »"
];
$lang['speechGreetingHelpPrompt'] = " Si vous souhaitez une liste de commandes, vous pouvez dire 'Aide' ou 'Quelles sont vos commandes ?'";
$lang['cardReadmeButton'] = "Voir readme";
$lang['cardGreetingText'] = "Bienvenue sur Flex TV!";
$lang['speechShuffleResponse'] = "Okay, mélanger tous les épisodes de ";
$lang['speechDvrSuccessStart'] = "Hé, regarde ça. J'ai ajouté le ";
$lang['speechDvrSuccessEnd'] = "pour la programmation des enregistrements.";
$lang['parsedDvrFailStart'] = 'Ajouter les médias appelés ';
$lang['speechDvrFailStart'] = "Je n’ai pas pu trouver aucun résultat dans le guide d’épisode qui correspondent '";
$lang['speechDvrNoDevice'] = "Je suis désolé, mais je n’ai pas trouvé toutes les instances de Plex DVR à utiliser.";
$lang['speechChangeDevicePrompt'] = ", sûr.  Quel appareil souhaitez-vous utiliser ? ";
$lang['speechChangeDeviceSuccessStart'] = "Ok, j’ai changé le ";
$lang['speechWordTo'] = " à  ";
$lang['speechChangDeviceFailureStart'] = "Je suis désolé, mais je n'ai pas pu trouver ";
$lang['speechChangeDeviceFailEnd'] = " dans la liste des périphériques.";
$lang['speechChangeDeviceWarnStart1'] = "foo";
$lang['speechChangeDeviceWarnEnd1'] = "foo";
$lang['speechChangeDeviceWarnStart2'] ="foo" ;
$lang['speechChangeDeviceWarnEnd2'] = "foo";
$lang['speechChangeDeviceWarnStart3'] = "foo";
$lang['speechChangeDeviceWarnStart3'] = "foo";
$lang['speechStatusNothingPlaying'] = "Il ne semble pas qu'il y ait quelque chose de jouer en ce moment.";
$lang['speechReturnRecent'] = "Voici une liste des récents ";
$lang['speechReturnOndeck'] = "Voici une liste des objets disponible : ";
$lang['speechOndeckRecentTail'] = " Si vous souhaitez regarder quelque chose, dites simplement le nom, sinon, vous pouvez dire 'annuler'.";
$lang['speechOndeckRecentError'] = "Malheureusement, je n'ai pas pu trouver de résultats pour cela. Veuillez réessayer plus tard.";
$lang['speechAiringsReturn'] = "Voici une liste des enregistrements programmés : ";
$lang['speechAiringsAfternoon'] = "Cet après midi, ";
$lang['speechAiringsTonight'] = "Ce soir, ";
$lang['speechAiringsTomorrow'] = "Demain, ";
$lang['speechAiringsWeekend'] = "Ce week-end, ";
$lang['speechAiringsMids'] = ['vous avez ','Je vois ','J’ai trouvé '];
$lang['speechAiringTails'] = [' sur le calendrier.',' la valeur à enregistrer.',' à venir.'];
$lang['speechAiringsErrors'] = ["Je n’ai rien sur la liste pour ","Vous n’avez rien prévu pour "];
$lang['speechPlaybackAffirmatives'] = ["Oui capitaine, ","Okay, ","Bien sûr, ","Pas de problème, ","Oui maître, ","Compris, ","Comme tu ordonneras, ","De même, "];
$lang['speechEggBatman'] = "Médias piraté !  ";
$lang['speechEggGhostbusters'] = "Qui allez-vous appeler?  ";
$lang['speechEggIronMan'] = "Oui M. Stark, ";
$lang['speechEggAvengers'] = "Famille assembler ! ";
$lang['speechEggFrozen'] = "Laisser aller ! ";
$lang['speechEggOdyssey'] = "Je crains que je ne puisse pas faire ça Dave. Bon, très bien, ";
$lang['speechEggBigHero'] = "Bonjour, je suis Baymax, je vais être ";
$lang['speechEggWallE'] = "Nous vous remercions d'avoir acheté Buy and Large, et de profiter à mesure que nous commençons ";
$lang['speechEggEvilDead'] = "Salut au roi, bébé ! ";
$lang['speechEggFifthElement'] = "Leeloo Dallas Mul-ti-Pass ! ";
$lang['speechEggGameThrones'] = "Préparez-vous...";
$lang['speechEggTheyLive'] = "Je suis ici à mâcher du chewing-gum et ";
$lang['speechEggHeathers'] = "Bien, me découpe doucement avec une tronçonneuse.  ";
$lang['speechEggStarWars'] = "Ce ne sont pas les droïdes que vous recherchez.  ";
$lang['speechEggResidentEvil'] = "T-minus 1 minute jusqu'à ce que l’infection.  ";
$lang['speechEggAttackTheBlock'] = "Vous êtes le médecin ? ";
$lang['speechPlaying'] = "Lecture en cours ";
$lang['speechBy'] = " par ";
$lang['speechMultiResultArray'] =[
	"J’ai trouvé plusieurs résultats possibles pour cela, étaient-ils l'un de ces éléments que vous vouliez ? ",
	"Lequel voulais-tu dire ? ",
	"Vous avez quelques éléments qui pourraient correspondre à cette recherche, est-il l'un de ? ",
	"Il y avait quelques choses que je pense que vous pourriez demander, est-ce l'un de cela ?"
];
$lang['speechPlayErrorStart1'] = "Je ne pouvais pas trouver ";
$lang['speechPlayErrorEnd1'] = " dans votre bibliothèque. Dois-je essayer de l'ajouter a votre liste ?";
$lang['speechPlayErrorStart2'] = "Je ne trouve rien nommé '";
$lang['speechPlayErrorEnd2'] = "'à jouer. Dois-je essayer de le chercher pour vous ?'";
$lang['speechPlayError3'] = "Je ne pouvais pas trouver cela dans la bibliothèque, voulez-vous que j'essaie de le télécharger ?";

// Parsed Responses (Not really necessary, as we display the speech in cards/UI

$lang['parsedDvrSuccessStart'] = "Ajouter le ";
$lang['parsedDvrSuccessNamed'] = " nommé ";

$lang['suggestionYesNo'] = ['Oui','Non'];
$lang['speechChange'] = 'Change ';
$lang['speechPlayer'] = "lecteur";
$lang['speechServer'] = "serveur";

























