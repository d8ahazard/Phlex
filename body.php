<?php
require_once dirname(__FILE__) . '/vendor/autoload.php';
require_once dirname(__FILE__) . '/cast/Chromecast.php';
require_once dirname(__FILE__) . '/util.php';
require_once dirname(__FILE__) . '/api.php';

function makeBody() {
    write_log("Function fired.");
    if (!defined('LOGGED_IN')) {
        write_log("Dying because not logged in?","ERROR");
        die();
    }
    $config = new Config_Lite('config.ini.php');
    // Check our config file exists
    $_SESSION['apiToken'] = $config->get('user-_-'.$_SESSION['plexUserName'], 'apiToken', checkSetApiToken($_SESSION['plexUserName']));
    $_SESSION['plexAvatar'] = $config->get('user-_-'.$_SESSION['plexUserName'], 'plexAvatar', false);
    $_SESSION['plexEmail'] = $config->get('user-_-'.$_SESSION['plexUserName'], 'plexEmail', false);

    $_SESSION['enable_couch'] = $config->get('user-_-'.$_SESSION['plexUserName'], 'couchEnabled', false);
    $_SESSION['enable_ombi'] = $config->get('user-_-'.$_SESSION['plexUserName'], 'ombiEnabled', false);
    $_SESSION['enable_sonarr'] = $config->get('user-_-'.$_SESSION['plexUserName'], 'sonarrEnabled', false);
    $_SESSION['enable_sick'] = $config->get('user-_-'.$_SESSION['plexUserName'], 'sickEnabled', false);
    $_SESSION['enable_radarr'] = $config->get('user-_-'.$_SESSION['plexUserName'], 'radarrEnabled', false);
    $_SESSION['enable_apiai'] = $config->get('user-_-'.$_SESSION['plexUserName'], 'apiEnabled', false);

    $_SESSION['returnItems'] = $config->get('user-_-'.$_SESSION['plexUserName'], 'returnItems', "6");
    $_SESSION['rescanTime'] = $config->get('user-_-'.$_SESSION['plexUserName'], 'rescanTime', "6");

    $_SESSION['ip_couch'] = $config->get('user-_-'.$_SESSION['plexUserName'], 'couchIP', 'localhost');
    $_SESSION['ip_ombi'] = $config->get('user-_-'.$_SESSION['plexUserName'], 'ombiUrl', 'localhost');
    $_SESSION['ip_sonarr'] = $config->get('user-_-'.$_SESSION['plexUserName'], 'sonarrIP', 'localhost');
    $_SESSION['ip_sick'] = $config->get('user-_-'.$_SESSION['plexUserName'], 'sickIP', 'localhost');
    $_SESSION['ip_radarr'] = $config->get('user-_-'.$_SESSION['plexUserName'], 'radarrIP', 'localhost');

    $_SESSION['port_couch'] = $config->get('user-_-'.$_SESSION['plexUserName'], 'couchPort', '5050');
    $_SESSION['port_ombi'] = $config->get('user-_-'.$_SESSION['plexUserName'], 'ombiPort', '3579');
    $_SESSION['port_sonarr'] = $config->get('user-_-'.$_SESSION['plexUserName'], 'sonarrPort', '8989');
    $_SESSION['port_sick'] = $config->get('user-_-'.$_SESSION['plexUserName'], 'sickPort', '8083');
    $_SESSION['port_radarr'] = $config->get('user-_-'.$_SESSION['plexUserName'], 'radarrPort', '7878');

    $_SESSION['auth_couch'] = $config->get('user-_-'.$_SESSION['plexUserName'], 'couchAuth', '');
    $_SESSION['auth_sonarr'] = $config->get('user-_-'.$_SESSION['plexUserName'], 'sonarrAuth', '');
    $_SESSION['auth_sick'] = $config->get('user-_-'.$_SESSION['plexUserName'], 'sickAuth', '');
    $_SESSION['auth_radarr'] = $config->get('user-_-'.$_SESSION['plexUserName'], 'radarrAuth', '');

    $_SESSION['apiai_client_token'] = $config->get('user-_-'.$_SESSION['plexUserName'], 'apiClientToken', '');
    $_SESSION['apiai_dev_token'] = $config->get('user-_-'.$_SESSION['plexUserName'], 'apiDevToken', '');

    $_SESSION['use_cast'] = $config->getBool('user-_-'.$_SESSION['plexUserName'], 'useCast', false);
    $_SESSION['phpPath'] = $config->get('user-_-'.$_SESSION['plexUserName'], 'phpPath', '');
    $_SESSION['clean_logs'] = $config->getBool('user-_-'.$_SESSION['plexUserName'], 'cleanLogs', true);
    $_SESSION['darkTheme'] = $config->getBool('user-_-'.$_SESSION['plexUserName'], 'darkTheme', false);

    $_SESSION['dvr_resolution'] = $config->getBool('user-_-'.$_SESSION['plexUserName'], 'dvr_resolution', "0");
    $_SESSION['dvr_newairings'] = $config->getBool('user-_-'.$_SESSION['plexUserName'], 'dvr_newairings', true);
    $_SESSION['dvr_replacelower'] = $config->getBool('user-_-'.$_SESSION['plexUserName'], 'dvr_replacelower', true);
    $_SESSION['dvr_recordpartials'] = $config->getBool('user-_-'.$_SESSION['plexUserName'], 'dvr_recordpartials', false);
    $_SESSION['dvr_startoffset'] = $config->get('user-_-'.$_SESSION['plexUserName'], 'dvr_startoffset', 2);
    $_SESSION['dvr_endoffset'] = $config->get('user-_-'.$_SESSION['plexUserName'], 'dvr_endoffset', 2);
    $_SESSION['resolution'] = $config->get('user-_-'.$_SESSION['plexUserName'], 'resolution', 0);

    $url = 'https://plex.tv/pms/:/ip';
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL,$url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt ($ch, CURLOPT_CAINFO, dirname(__FILE__) . "/cert/cacert.pem");
    $realIP = curl_exec($ch);
    curl_close ($ch);
    $ipString = (((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')	|| $_SERVER['SERVER_PORT'] == 443) ? 'https://' : 'http://') . $realIP . '/Phlex';
    $_SESSION['publicAddress'] = $config->get('user-_-'.$_SESSION['plexUserName'], 'publicAddress', $ipString);
    write_log("Reloaded, dark theme is ".($_SESSION['darkTheme'] ? 'enabled.' : 'disabled.'));
    $bodyText = ($_SESSION['darkTheme'] ? '<link href="./css/dark.css" rel="stylesheet">' : '') .
'<div id="body"><div class="wrapper" id="mainwrap">
    <div class="queryWrap col-xs-12">
        <div class="col-xs-12 col-md-8 col-lg-6 col-md-offset-1 query">
            <div class="card">
                <div class="btn-toolbar">
                    <div class="queryGroup form-group label-floating col-xs-10 col-md-7 col-lg-7">
                        <div class="material-icons sendBtn" id="executeButton">message</div>
                        <label id="actionLabel" for="commandTest" class="control-label">Hi, I\'m Flex TV.  What can I do for you?</label>
                        <input type="text" class="form-control" id="commandTest">
                    </div>
                    <div class="queryBtnWrap">
                        <div class="queryBtnGrp">
                            <div class="btn btn-sm dropdown-toggle barBtn" href="javascript:void(0)" id="client" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <div class="ddLabel"></div><br>
                                <i class="material-icons barIcon clientBtn">cast</i>
                            </div>
                            <div class="dropdown-menu" id="plexClient" aria-labelledby="dropdownMenuLink">
                                <h6 class="dropdown-header">Select a player to control.</h6>
                                <div id="clientWrapper">
                                </div>
                            </div>
                            <a href="" id="settings" class="btn btn-sm barBtn" data-toggle="modal" data-target="#settingsModal"><i class="material-icons barIcon">settings</i></a>
                            <a href="?logout" id="logout" class="btn btn-sm barBtn"><i class="material-icons barIcon">power_settings_new</i></a>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div id="results" class="queryWrap col-xs-12">
        <div id="resultsInner"  class="col-xs-8 col-lg-6 col-xs-offset-1 query"></div>
    </div>
    <div class="modal fade" id="settingsModal">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <hac class="modal-title">Settings</hac>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <br>
                    <div class="userGroup">
                        <img class="avatar" src="' . $_SESSION["plexAvatar"] . '"/>
                        <haa>' . ucfirst($_SESSION["username"]) . '</haa><br>
                        <hab>' . $_SESSION["plexEmail"] . '</hab>
                    </div>

                </div>
                <div class="modal-body">
                    <div class="appContainer card">
                        <div class="card-body">
                            <h4 class="cardHeader">General</h4>
                            <div class="form-group">
                                <div class="form-group">
                                    <label for="apiToken" class="appLabel">API Key:
                                        <input id="apiToken" class="appInput form-control" type="text" value="' . $_SESSION["apiToken"] . '" readonly="readonly"/>
                                    </label>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="form-group">
                                    <label for="publicAddress" class="appLabel">Public Address:
                                        <input id="publicAddress" class="appInput form-control formpop" type="text" value="' . $_SESSION["publicAddress"] . '" />
                                        <span class="bmd-help">Make sure this works from a cellphone with wifi turned off.</span>
                                    </label>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="form-group">
                                    <label for="returnItems" class="appLabel">Number of On-Deck/Recent Items to Return:
                                        <input id="returnItems" class="appInput form-control" type="number" min="1" max="20" value="' . $_SESSION["returnItems"] . '" />
                                    </label>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="form-group">
                                    <label for="rescanTime" class="appLabel">Device Rescan Interval (Minutes):
                                        <input id="rescanTime" class="appInput form-control" type="number" min="5" max="30" value="' . $_SESSION["rescanTime"] . '" />
                                    </label>
                                </div>
                            </div>
                            <div class="togglebutton">
                                <label for="cleanLogs" class="appLabel checkLabel">Obscure Sensitive Data in Logs
                                    <input id="cleanLogs" type="checkbox" class="appInput appToggle" ' . ($_SESSION["clean_logs"] ? "checked" : "") . '/>
                                </label>
                            </div>
                            <div class="togglebutton">
                                <label for="darkTheme" class="appLabel checkLabel">Use Dark Theme
                                    <input id="darkTheme" class="appInput" type="checkbox" ' . ($_SESSION["darkTheme"] ? "checked" : "") . '/>
                                </label>
                            </div>
                            <div class="form-group text-center">
                                <div class="form-group">
                                    <label for="linkAccount">Google Action Account Linking:</label><br>
                                    <button id="linkAccount" class="btn btn-raised linkBtn btn-info">Register Server</button>
                                </div>
                            </div>
                            <div class="text-center">
                                <label for="sel1">Click to copy IFTTT URL:</label><br>
                                <button id="sayURL" class="copyInput btn btn-raised btn-primary btn-70" type="button"><i class="material-icons">message</i></button>
                            </div>

                        </div>
                    </div>
                    <div class="appContainer card">
                        <div class="card-body">
                            <h4 class="cardHeader">Plex</h4>
                            <div class="form-group">
                                <div class="form-group">
                                    <label class="appLabel" for="serverList">Playback Server:</label>
                                    <select class="form-control custom-select" id="serverList">
                                    </select>
                                    <br><br>
                                    <div class="togglebutton">
                                        <label for="useCast" class="appLabel checkLabel">Use Cast Devices
                                            <input id="useCast" type="checkbox" class="appInput appToggle" ' . ($_SESSION["use_cast"] ? "checked" : "") . '/>
                                        </label>
                                    </div>
                                    <div class="form-group">
                                        <div class="form-group"'.((substr(php_uname(), 0, 7) != "Windows") ? 'style="display:none"' : '').'>
                                            <label for="phpPath" class="appLabel">PHP Executable path:
                                                <input id="phpPath" class="appInput form-control" type="text" value="' . $_SESSION["phpPath"] . '"/>
                                                <span class="bmd-help">Enter the full path to php.exe used by your webserver.</span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-center">
                                    <div class="form-group btn-group">
                                        <button value="Plex" class="testInput btn btn-raised btn-info btn-100" type="button">Test</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="appContainer card dvrGroup">
                        <div class="card-body">
                            <h4 class="cardHeader">Plex DVR</h4>
                            <div class="form-group">
                                <div class="form-group">
                                    <label class="appLabel" for="dvrList">DVR Server:</label>
                                    <select class="form-control custom-select" id="dvrList">

                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="appLabel" for="resolution">Resolution:</label>
                                    <select class="form-control appInput" id="resolution">
                                        <option value="0" '.($_SESSION["resolution"] == 0 ? "selected" : "") .' >Any </option>
                                        <option value="720" '. ($_SESSION["resolution"] == 720 ? "selected" : "") .' >High-Definition </option>
                                    </select>
                                </div>
                                <br>
                                <div class="togglebutton">
                                    <label for="dvr_newairings" class="appLabel checkLabel">Record new Airings Only
                                        <input id="dvr_newairings" type="checkbox" class="appInput" '.($_SESSION["dvr_newairings"] ? "checked" : "") . ' />
                                    </label>
                                </div>
                                <br>
                                <div class="togglebutton">
                                    <label for="dvr_replacelower" class="appLabel checkLabel">Replace Lower Quality Recordings
                                        <input id="dvr_replacelower" type="checkbox" class="appInput" '. ($_SESSION["dvr_replacelower"] ? " checked " : "") . ' />
                                    </label>
                                </div>
                                <br>
                                <div class="togglebutton">
                                    <label for="dvr_recordpartials" class="appLabel checkLabel">Record partial episodes
                                        <input id="dvr_recordpartials" type="checkbox" class="appInput" '. ($_SESSION["dvr_recordpartials"] ? "checked" : "") . ' />
                                    </label>
                                </div>
                                <div class="form-group">
                                    <label for="dvr_startoffset" class="appLabel">Start Offset (Minutes):
                                        <input id="dvr_startoffset" class="appInput form-control" type="number" min="1" max="30" value="' . $_SESSION["dvr_startoffset"] . '" />
                                    </label>
                                </div>
                                <div class="form-group">
                                    <label for="dvr_endoffset" class="appLabel">End Offset (Minutes):
                                        <input id="dvr_endoffset" class="appInput form-control" type="number" min="1" max="30" value="' . $_SESSION["dvr_endoffset"] . '" />
                                    </label>
                                </div>

                            </div>
                        </div>
                    </div>
                    <div class="appContainer card">
                        <div class="card-body">
                            <h4 class="cardHeader">CouchPotato</h4>
                            <div class="togglebutton">
                                <label for="couchEnabled" class="appLabel checkLabel">Enable
                                    <input id="couchEnabled" type="checkbox" class="appInput appToggle"/>
                                </label>
                            </div>
                            <div class="form-group" id="couchGroup">
                                <div class="form-group">
                                    <label for="couchIP" class="appLabel">Couchpotato IP/URL:
                                        <input id="couchIP" class="appInput form-control CouchPotato appParam" type="text" value="' . $_SESSION["ip_couch"] . '"/>
                                    </label>
                                </div>
                                <div class="form-group">
                                    <label for="couchPort" class="appLabel">Couchpotato Port:
                                        <input id="couchPort" class="appInput form-control CouchPotato appParam" type="text" value="' . $_SESSION["port_couch"] . '"/>
                                    </label>
                                </div>
                                <div class="form-group">
                                    <label for="couchAuth" class="appLabel">Couchpotato Token:
                                        <input id="couchAuth" class="appInput form-control CouchPotato appParam" type="text" value="' . $_SESSION["auth_couch"] . '"/>
                                    </label>
                                </div>
                                <div class="form-group">
                                    <label class="appLabel" for="couchProfile">Quality Profile:</label>
                                    <select class="form-control profileList" id="couchProfile">
                                        '. fetchList("couch") .'
                                    </select>
                                </div>
                                <div class="text-center">
                                    <div class="form-group btn-group">
                                        <button value="CouchPotato" class="testInput btn btn-raised btn-info" type="button">Test</button>
                                        <button id="resetCouch" value="CouchPotato" class="resetInput btn btn-raised btn-danger btn-100" type="button">Reset</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="appContainer card ombiGroup">
                        <div class="card-body">
                            <h4 class="cardHeader">Ombi</h4>
                            <div class="togglebutton">
                                <label for="ombiEnabled" class="appLabel checkLabel">Enable
                                    <input id="ombiEnabled" type="checkbox" class="appInput appToggle"/>
                                </label>
                            </div>
                            <div class="form-group" id="ombiGroup">
                                <div class="form-group">
                                    <label for="ombiUrl" class="appLabel">Ombi IP/URL:
                                        <input id="ombiUrl" class="appInput form-control ombiUrl appParam" type="text"  value="' . $_SESSION["ip_ombi"] . '" />
                                    </label>
                                </div>
                                <div class="form-group">
                                    <label for="ombiPort" class="appLabel">Ombi Port:
                                        <input id="ombiPort" class="appInput form-control Ombi appParam" type="text" value="' . $_SESSION["port_ombi"] . '"/>
                                    </label>
                                </div>
                                <div class="form-group">
                                    <label class="appLabel" for="ombi">Quality Profile:</label>
                                    <select class="form-control profileList" id="ombi">

                                    </select>
                                </div>
                                <div class="text-center">
                                    <div class="form-group btn-group">
                                        <button value="Ombi" class="testInput btn btn-raised btn-info btn-100" type="button">Test</button>
                                        <button id="resetOmbi" value="Ombi" class="resetInput btn btn-raised btn-danger btn-100" type="button">Reset</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="appContainer card">
                        <div class="card-body">
                            <h4 class="cardHeader">Radarr</h4>
                            <div class="togglebutton">
                                <label for="radarrEnabled" class="appLabel checkLabel">Enable
                                    <input id="radarrEnabled" type="checkbox" class="appInput appToggle"/>
                                </label>
                            </div>
                            <div class="form-group" id="radarrGroup">
                                <div class="form-group">
                                    <label for="radarrIP" class="appLabel">Radarr IP/URL:
                                        <input id="radarrIP" class="appInput form-control Radarr appParam" type="text" value="' . $_SESSION["ip_radarr"] . '"/>
                                    </label>
                                </div>
                                <div class="form-group">
                                    <label for="radarrPort" class="appLabel">Radarr Port:
                                        <input id="radarrPort" class="appInput form-control Radarr appParam" type="text" value="' . $_SESSION["port_radarr"] . '"/>
                                    </label>
                                </div>
                                <div class="form-group">
                                    <label for="radarrAuth" class="appLabel">Radarr Token:
                                        <input id="radarrAuth" class="appInput form-control Radarr appParam" type="text" value="' . $_SESSION["auth_radarr"] . '"/>
                                    </label>
                                </div>
                                <div class="form-group">
                                    <label class="appLabel" for="radarrProfile">Quality Profile:</label>
                                    <select class="form-control profileList" id="radarrProfile">
                                        '. fetchList("radarr") .'
                                    </select>
                                </div>
                                <div class="text-center">
                                    <div class="form-group btn-group">
                                        <button value="Radarr" class="testInput btn btn-raised btn-info btn-100" type="button">Test</button>
                                        <button id="resetRadarr" value="Radarr" class="resetInput btn btn-raised btn-danger btn-100" type="button">Reset</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="appContainer card">
                        <div class="card-body">
                            <h4 class="cardHeader">Sickbeard/SickRage</h4>
                            <div class="togglebutton">
                                <label for="sickEnabled" class="appLabel checkLabel">Enable
                                    <input id="sickEnabled" type="checkbox" class="appInput appToggle"/>
                                </label>
                            </div>
                            <div class="form-group" id="sickGroup">
                                <div class="form-group">
                                    <label for="sickIP" class="appLabel">Sick IP/URL:
                                        <input id="sickIP" class="appInput form-control Sick appParam" type="text" value="' . $_SESSION["ip_sick"] . '"/>
                                    </label>
                                </div>
                                <div class="form-group">
                                    <label for="sickPort" class="appLabel">Sick Port:
                                        <input id="sickPort" class="appInput form-control Sick appParam" type="text" value="' . $_SESSION["port_sick"] . '"/>
                                    </label>
                                </div>
                                <div class="form-group">
                                    <label for="sickAuth" class="appLabel">Sick Token:
                                        <input id="sickAuth" class="appInput form-control Sick appParam" type="text" value="' . $_SESSION["auth_sick"] . '"/>
                                    </label>
                                </div>
                                <div class="form-group">
                                    <label class="appLabel" for="sickProfile">Quality Profile:</label>
                                    <select class="form-control appInput profileList" id="sickProfile">
                                        '. fetchList("sick") .'
                                    </select>
                                </div>
                                <div class="text-center">
                                    <div class="form-group btn-group">
                                        <button value="Sick" class="testInput btn btn-raised btn-info btn-100" type="button">Test</button>
                                        <button id="resetSick" value="Sick" class="resetInput btn btn-raised btn-danger btn-100" type="button">Reset</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="appContainer card">
                        <div class="card-body">
                            <h4 class="cardHeader">Sonarr</h4>
                            <div class="togglebutton">
                                <label for="sonarrEnabled" class="appLabel checkLabel">Enable
                                    <input id="sonarrEnabled" type="checkbox" class="appInput appToggle"/>
                                </label>
                            </div>
                            <div class="form-group" id="sonarrGroup">
                                <div class="form-group">
                                    <label for="sonarrIP" class="appLabel">Sonarr IP/URL:
                                        <input id="sonarrIP" class="appInput form-control Sonarr appParam" type="text" value="' . $_SESSION["ip_sonarr"] . '"/>
                                    </label>
                                </div>
                                <div class="form-group">
                                    <label for="sonarrPort" class="appLabel">Sonarr Port:
                                        <input id="sonarrPort" class="appInput form-control Sonarr appParam" type="text" value="' . $_SESSION["port_sonarr"] . '"/>
                                    </label>
                                </div>
                                <div class="form-group">
                                    <label for="sonarrAuth" class="appLabel">Sonarr Token:
                                        <input id="sonarrAuth" class="appInput form-control Sonarr appParam" type="text" value="' . $_SESSION["auth_sonarr"] . '"/>
                                    </label>
                                </div>
                                <div class="form-group">
                                    <label class="appLabel" for="sonarrProfile">Quality Profile:</label>
                                    <select class="form-control profileList" id="sonarrProfile">
                                        '. fetchList("sonarr") .'
                                    </select>
                                </div>
                                <div class="text-center">
                                    <div class="form-group btn-group">
                                        <button value="Sonarr" class="testInput btn btn-raised btn-info btn-100" type="button">Test</button>
                                        <button id="resetSonarr" value="Sonarr" class="resetInput btn btn-raised btn-danger btn-100" type="button">Reset</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <div id="inputs" class="col-xs-12 col-sm-5 col-md-3">

    </div>

    <div id="log">
        <div id="logInner">
        </div>
    </div>
    
</div>
<div class="nowPlayingFooter">
        <div class="statusWrapper">
            <img id="statusImage" src=""/>
            <div class="statusText">
                <h6>Now Playing on <span id="playerName"></span>: </h6>
                <h4><span id="mediaTitle"></span></h4>
                <span id="mediaSummary"></span>
                <div id="progressSlider" class="slider shor slider-material-orange"></div>
            </div>
        </div>

    </div>

    <div class="wrapperArt"></div>

    <div class="castArt">
        <div class="background-container">
            <div class="ccWrapper">
                <div class="fade1 ccBackground">
                    <div class="ccTextDiv">
                        <span class="spacer" ng-if="showWeather"></span>
                        <span class="tempDiv meta"></span>
                        <div class="weatherIcon"></div>
                        <div class="timeDiv meta"></div>
                        <div id="metadata-line-1" class="meta"></div>
                        <div id="metadata-line-2" class="meta"></div>
                        <div id="metadata-line-3" class="meta"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div id="metaTags">
    <meta id="apiTokenData" data="' . $_SESSION["apiToken"] . '" property="" content=""/>' . metaTags() . '</div>
    <script type="text/javascript" src="./js/main.js"></script></div>';
return $bodyText;
}

?>