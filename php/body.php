<?php
require_once dirname(__FILE__) . '/vendor/autoload.php';
require_once dirname(__FILE__) . '/../api.php';


function makeBody($defaults) {
	if (!defined('LOGGED_IN')) {
		write_log("Dying because not logged in?", "ERROR");
		die();
	}
	$lang = checkSetLanguage();
	$defaults['lang'] = $lang;
	$hide = $defaults['isWebApp'];
	$hidden = $hide ? " remove" : "";
	$hiddenHome = $hide ? "" : " remove";
	$useGit = $hide ? false : checkGit();
	$webAddress = serverAddress();
	$lang = $defaults['lang'];
	$apiToken = $_SESSION['apiToken'];

	$rev = checkRevision(true);
	$revString = $rev ? "Revision: $rev" : "";
	$ombiAddress = $_SESSION['ombiUri'] ?? "./ombi";
	$homeBase = file_get_contents(dirname(__FILE__) . "/homeBase/index.html");
	$homeBase = str_replace('<OMBI_URL>', $ombiAddress, $homeBase);
	$recentPage = file_get_contents(dirname(__FILE__) . "/homeBase/recentlyadded/recently_added.html");

	$gitDiv = $useGit ? '<div class="appContainer card updateDiv'.$hidden.'">
			        <div class="card-body">
			            <h4 class="cardHeader">' . $lang['uiSettingUpdates'] . '</h4>
			            <div class="form-group">
			                <div class="togglebutton">
			                    <label for="autoUpdate" class="appLabel checkLabel">' . $lang['uiSettingAutoUpdate'] . '
			                        <input id="autoUpdate" type="checkbox" class="appInput appToggle" data-app="autoUpdate"/>
			                    </label>
			                </div>
			                <div class="togglebutton">
			                    <label for="notifyUpdate" class="appLabel checkLabel">' . $lang['uiSettingNotifyUpdate'] . '
			                        <input id="notifyUpdate" type="checkbox" class="appInput"' . ($_SESSION["notifyUpdate"] ? "checked" : "") . '/>
			                    </label>
			                </div>
			                <div class="form-group">
			                    <div id="updateContainer">
			                    </div>
			                </div>
			                <div class="text-center">
			                    <div class="form-group btn-group">
			                        <button id="checkUpdates" value="checkUpdates" class="btn btn-raised btn-info btn-100" type="button">' . $lang['uiSettingRefreshUpdates'] . '</button>
			                        <button id="installUpdates" value="installUpdates" class="btn btn-raised btn-warning btn-100" type="button">' . $lang['uiSettingInstallUpdates'] . '</button>
			                    </div>
			                </div>
			            </div>
			        </div>
			    </div>' : "";



	$bodyText = '
			
			<div class="wrapperArt"></div>
			<div class="castArt">
				<div class="background-container">
					<div class="ccWrapper">
						<div class="fade1 ccBackground">
							<div class="ccTextDiv">
								<span class="spacer"></span>
								<span class="tempDiv meta"></span><br>
								<div class="weatherIcon"></div>
								<div class="timeDiv meta"></div>
								<div id="revision" class="meta">'.$revString.'</div>
							</div>
						</div>
					</div>
				</div>      
			</div>
		                    
        	
			<div class="modal fade" id="jsonModal">
				<div class="modal-dialog" role="document">
					<div class="modal-content">
						<div class="modal-header">
							<h5 class="modal-title" id="jsonTitle">Modal title</h5>
							<button type="button" class="close" data-dismiss="modal" aria-label="Close">
								<span aria-hidden="true">&times;</span>
							</button>
						</div>
						<div class="modal-body" id="jsonBody">
							<p>Modal body text goes here.</p>
						</div><div class="modal-footer">
						<button class="btnAdd" title="Copy JSON to clipboard">Copy JSON</button></div>
					</div>
				</div>
			</div>
			
			<div class="modal" id="cardModal">
				<div class="row justify-content-center" role="document" id="cardModalBody">
					<div id="cardWrap" class="col-xl-4 col-lg-6 col-md-6 col-sm-12">
					
					</div>
				</div>
			</div>
			<div id="plexClient">
			 	<div class="popover-arrow"></div>
				<div id="clientWrapper">
                    <a class="dropdown-item client-item" data-id="rescan"><b>rescan devices</b></a>
                </div>
  		    </div>
			<div id="sideMenu">
            	<div class="drawer-header container">
	                <div class="userWrap row justify-content-around">
	                	<div class="col-3">
	                    	<img class="avatar" src="' . $_SESSION['plexAvatar'] . '"/>
	                    </div>
	                    <div class="col-9">
		                    <p class="userHeader">' . ucfirst($_SESSION['plexUserName']) . '</p>
		                    <p class="userEmail">' . $_SESSION['plexEmail'] . '</p>
	                    </div>
	                </div>
            	</div>
            	<div class="drawer-item btn active" data-link="homeTab" data-label="Home">
                	<span class="barBtn"><i class="material-icons colorItem barIcon">home</i></span>Home
                </div>
                <div class="drawer-item btn" data-link="expandDrawer" data-target="Client" id="clientBtn">
                	<span class="barBtn"><i class="material-icons colorItem barIcon">cast</i></span>Clients
                </div>
                <div class="drawer-list collapsed" id="ClientDrawer">
	                <div class="drawer-item btn" data-link="rescan">
	                    <span class="barBtn"><i class="material-icons colorItem barIcon">refresh</i></span>Rescan Devices
	                </div>
                </div>
                <div class="drawer-item btn" data-link="voiceTab" data-label="Voice">
                	<span class="barBtn"><i class="material-icons colorItem barIcon">list</i></span>Commands
                </div>
                <div class="drawer-separator"></div>
                <div class="drawer-item btn" data-link="expandDrawer" data-target="Appz">
                	<span class="barBtn"><i class="material-icons colorItem barIcon">apps</i></span>Apps
                </div>
                <div class="drawer-list collapsed" id="AppzDrawer">
                </div>
                <div class="drawer-item btn" data-link="expandDrawer" data-target="Stats">
                	<span class="barBtn"><i class="material-icons colorItem barIcon">show_chart</i></span>Stats
                </div>
                <div class="drawer-list collapsed" id="StatsDrawer">
	                <div class="drawer-item btn" id="recent" data-link="recentStats" data-label="Recents">
	                    <span class="barBtn"><i class="material-icons colorItem barIcon">watch_later</i></span>Recent
	                </div>
	                <div class="drawer-item btn" data-link="popularStats" data-target="Stats">
	                    <span class="barBtn"><i class="material-icons colorItem barIcon">grade</i></span>Popular
	                </div>
	                <div class="drawer-item btn" data-link="userStats" data-target="Stats">
	                    <span class="barBtn"><i class="material-icons colorItem barIcon">account_circle</i></span>User
	                </div>
	                <div class="drawer-item btn" data-link="lbraryStats" data-target="Stats">
	                    <span class="barBtn"><i class="material-icons colorItem barIcon">local_library</i></span>Library
	                </div>
				</div>
                <div class="drawer-item btn" data-link="expandDrawer" data-target="Settings">
                	<span class="barBtn"><i class="material-icons colorItem barIcon">settings</i></span>Settings
                </div>
                
                <div class="drawer-list collapsed" id="SettingsDrawer">
                	
                    <div class="drawer-item btn" data-link="generalSettingsTab" data-label="General">
                        <span class="barBtn"><i class="material-icons colorItem barIcon">build</i></span>General
                    </div>
                    <div class="drawer-item btn" data-link="plexSettingsTab" data-label="Plex">
                        <span class="barBtn"><i class="material-icons colorItem barIcon">label_important</i></span>Plex
                    </div> 
				</div>
				<div class="drawer-separator"></div>
				<div class="drawer-item btn" data-link="logTab" data-label="Logs">
                	<span class="barBtn"><i class="material-icons colorItem barIcon">bug_report</i></span>Logs
                </div>
				<div class="drawer-item btn" id="logout">
                    <span class="barBtn"><i class="material-icons colorItem barIcon">exit_to_app</i></span>Log Out
                </div>   
			</div>
        	<div id="ghostDiv"></div>
			<div id="body" class="container">
				<div id="topBar" class="row testGrp justify-content-between">
					<div class="col-2 col-sm-1 col-md-1 col-lg-2" id="leftGrp">
						<div class="row testGrp">
							<div class="col-12 col-md-6 col-lg-4 col-xl-3 center-block">
								<div class="btn btn-sm navIcon center-block" id="hamburger">
									<span class="material-icons colorItem">menu</span>
								</div>
							</div>
							<div class="col-0 col-md-6 col-lg-4 col-xl-3 center-block">
								<div class="btn btn-sm navIcon center-block" id="refresh">
									<span class="material-icons colorItem spin">refresh</span>
								</div>
							</div>
							<div class="col-md-0 col-lg-4 col-xl-6"></div>
						</div>
					</div>
					<div class="col-8 col-sm-10 col-md-9 col-lg-8 wrapper">
						<div class="searchWrap" id="queryCard">
				            <div class="query">
					            <div class="queryBg">
					                <div class="btn-toolbar">
					                    <div class="queryGroup form-group label-floating">
					                        <div class="inputWrap">
						                        <label id="actionLabel" for="commandTest" class="control-label">'. $lang['uiGreetingDefault'] . '
						                        </label>
						                        <input type="text" class="form-control" id="commandTest"/>
						                        <div class="load-barz colorBg" id="loadbar">
													<div class="barz"></div>
													<div class="barz colorBg"></div>
													<div class="barz"></div>
													<div class="barz"></div>
												</div>
						                        <a class="material-icons colorItem sendBtn" id="sendBtn">message</a>
					                        </div>
					                    </div>					                   
					                </div>
					            </div>
				            </div>
				        </div>
					</div>
					<div class="col-2 col-sm-1 col-md-1 col-lg-2" id="rightGrp">
						<div class="row testGrp">
							<div class="col-md-0 col-lg-8 col-xl-8">
								<div id="sectionLabel" class="colorItem">
									Home
								</div>
							</div>
							<div class="col-sm-12 col-md-12 col-lg-4 col-xl-4 center-block">
								<div class="btn btn-sm navIcon main clientBtn center-block" data-position="right" id="client">
	            					<span class="material-icons colorItem">cast</span>
	            					<div class="ddLabel"></div>
            					</div>
            					<div class="btn btn-sm navIcon sendBtn" id="smallSendBtn">
            						<span class="material-icons colorItem">message</span>
								</div>	
							</div>
						</div>
					</div>
				</div>
		        <div id="results">	    
			        <div class="view-tab active" id="homeTab">
			            '. $homeBase. '
			        </div>
			        <div class="view-tab fade" id="recentStats">
			        	'. $recentPage. '
					</div>
			        <div class="view-tab fade col-md-9 col-lg-10 col-xl-8" id="voiceTab">
			            <div id="resultsInner"  class="queryWrap row justify-content-around">
			            </div>
			        </div>
        			<div class="view-tab fade show active settingPage col-md-9 col-lg-10 col-xl-8" id="generalSettingsTab">     
        			<div class="gridBox">      
			            <div class="appContainer card">
			                <div class="card-body">
			                    <h4 class="cardHeader">' . $lang['uiSettingGeneral'] . '</h4>
			                    <div class="form-group">
			                        <label class="appLabel" for="appLanguage">' . $lang['uiSettingLanguage'] . '</label>
			                        <select class="form-control custom-select" id="appLanguage">
			                            ' . listLocales() . '
			                        </select>
			                        <div class="form-group'.$hidden.'">
			                            <label for="apiToken" class="appLabel">' . $lang['uiSettingApiKey'] . '
			                                <input id="apiToken" class="appInput form-control" type="text" value="' . $_SESSION["apiToken"] . '" readonly="readonly"/>
			                            </label>
			                        </div>
			                        <div class="form-group'.($hide ? ' hidden' : '').'">
			                            <label for="publicAddress" class="appLabel">' . $lang['uiSettingPublicAddress'] . '
			                                <input id="publicAddress" class="appInput form-control formpop" type="text" value="' . $webAddress . '" />
			                            </label>
			                        </div>
			                    </div>
			                    <div class="form-group">
			                        <div class="form-group">
			                            <label for="rescanTime" class="appLabel">' . $lang['uiSettingRescanInterval'] . '
			                                <input id="rescanTime" class="appInput form-control" type="number" min="10" max="30" value="' . $_SESSION["rescanTime"] . '" />
			                                <span class="bmd-help">' . $lang['uiSettingRescanHint'] . '</span>
			                            </label>
			                        </div>
			                    </div>
			                    <div class="noNewUsersGroup togglebutton'.$hidden.'">
			                        <label for="noNewUsers" class="appLabel checkLabel">' . $lang['uiSettingNoNewUsers'] . '
			                            <input id="noNewUsers" title="'.$lang['uiSettingNoNewUsersHint'].'" class="appInput" type="checkbox" ' . ($_SESSION["noNewUsers"] ? "checked" : "") . '/>
			                        </label>
			                    </div>
			                    <div class="togglebutton">
			                        <label for="shortAnswers" class="appLabel checkLabel">' . $lang['uiSettingShortAnswers'] . '
			                            <input id="shortAnswers" class="appInput" type="checkbox" ' . ($_SESSION["shortAnswers"] ? "checked" : "") . '/>
			                        </label>
			                    </div>
			                    <div class="togglebutton'.$hidden.'">
			                        <label for="cleanLogs" class="appLabel checkLabel">' . $lang['uiSettingObscureLogs'] . '
			                            <input id="cleanLogs" type="checkbox" class="appInput" ' . ($_SESSION["cleanLogs"] ? "checked" : "") . '/>
			                        </label>
			                    </div>
			                    <div class="togglebutton">
			                        <label for="darkTheme" class="appLabel checkLabel">' . $lang['uiSettingThemeColor'] . '
			                            <input id="darkTheme" class="appInput" type="checkbox" ' . ($_SESSION["darkTheme"] ? "checked" : "") . '/>
			                        </label>
			                    </div>
			                    <div class="togglebutton'.$hidden.'">
			                        <label for="forceSSL" class="appLabel checkLabel">' . $lang['uiSettingForceSSL'] . '
			                            <input id="forceSSL" class="appInput" type="checkbox" ' . ($_SESSION["forceSSL"] ? "checked" : "") . '/>
			                        </label>
			                        <span class="bmd-help">' . $lang['uiSettingForceSSLHint'] . '</span>
			                    </div>
			                </div>
			            </div>
			            ' . $gitDiv . '
			            <div class="appContainer card">
			                <div class="card-body">
			                    <h4 class="cardHeader">'.$lang['uiSettingAccountLinking'].'</h4>
			                    <div class="form-group text-center">
			                        <div class="form-group">
			                            <button class="btn btn-raised linkBtn btn-primary testServer'.$hidden.'" id="testServer" data-action="test">' . $lang['uiSettingTestServer'] . '</button><br>
			                            <button id="linkAccountv2" data-action="googlev2" class="btn btn-raised linkBtn btn-danger">' . $lang['uiSettingLinkGoogle'] . '</button>
			                            <button id="linkAmazonAccount" data-action="amazon" class="btn btn-raised linkBtn btn-info">' . $lang['uiSettingLinkAmazon'] . '</button>
			                        </div>
			                    </div>
			                    <div class="text-center">
			                        <label for="sel1">' . $lang['uiSettingCopyIFTTT'] . '</label><br>
			                        <button id="sayURL" class="copyInput btn btn-raised btn-primary btn-70" type="button"><i class="material-icons colorItem">message</i></button>
			                    </div>
			                </div>
			            </div>
			            <div class="appContainer card">
			                <div class="card-body">
			                    <h4 class="cardHeader">Notifications</h4>
			                    <div class="form-group">
			                        <label class="appLabel" for="broadcastList">' . $lang['uiSettingBroadcastDevice'] . '</label>
			                        <select class="form-control custom-select deviceList" id="broadcastList" title="'.$lang["uiSettingBroadcastDeviceHint"].'">
			                        </select>
			                    </div>
			                    <div class="form-group center-group">
			                        <label for="appt-time">Start:</label>
			                        <input type="time" id="quietStart" class="form-control form-control-sm appInput" min="0:00" max="23:59"/>
			                        <label for="appt-time">Stop:</label>
			                        <input type="time" id="quietStop" class="form-control form-control-sm appInput" min="0:00" max="23:59"/>
			                    </div>
			                    <div class="fetchNotify">
			                        <button id="copyBroadcast" class="hookLnk btn btn-raised btn-warn btn-100" title="Copy WebHook Notification URL">
			                            <i class="material-icons colorItem">assignment</i>
			                        </button>
			                        <button id="testBroadcast" value="broadcast" class="testInput btn btn-info btn-raised btn-100" title="Test WebHook Notification">
			                            <i class="material-icons colorItem">send</i>
			                        </button>
			                    </div>
			                </div>
			            </div>
			            <div class="appContainer card">
			                <div class="card-body">
			                    <h4 class="cardHeader">' . $lang['uiSettingHookLabel'] . '</h4>
			                    <div class="togglebutton">
			                        <label for="hook" class="appLabel checkLabel">' . $lang['uiSettingEnable'] . '
			                            <input id="hook" type="checkbox" data-app="hook" class="appInput appToggle"/>
			                        </label>
			                    </div>
			                    <div class="form-group" id="hookGroup">
			                        <div class="togglebutton">
			                            <label for="hookSplit" class="appLabel checkLabel">' . $lang['uiSettingSeparateHookUrl'] . '
			                                <input id="hookSplit" type="checkbox" class="appInput appToggle"/>
			                            </label>
			                        </div>
			                        <div class="form-group">
			                            <label for="hookUrl" class="appLabel">' . $lang['uiSettingHookUrlGeneral'] . '
			                                <input id="hookUrl" class="appInput form-control Webhooks" type="text" value="' . $_SESSION["hookUrl"] . '"/>
			                                <span class="bmd-help">' . $lang['uiSettingHookPlayHint'] . '</span>
			                            </label>
			                        </div>
			                        <div class="togglebutton">
			                            <label for="hookPlay" class="appLabel checkLabel">' . $lang['uiSettingHookPlayback'] . '
			                                <input id="hookPlay" type="checkbox" data-app="hookPlay" class="appInput appToggle"/>
			                            </label>
			                        </div>
			                        <div class="hookLabel" id="hookPlayGroup">
			                            <div class="form-group urlGroup hookSplitGroup">
			                                <label for="hookPlayUrl" class="appLabel">' . $lang['uiSettingHookGeneric'] . '
			                                    <input id="hookPlayUrl" class="appInput form-control Webhooks" type="text" value="' . $_SESSION["hookPlayUrl"] . '"/>
			                                    <span class="bmd-help">' . $lang['uiSettingHookPlayHint'] . '</span>
			                                </label>
			                            </div>
			                        </div>
			                        <div class="togglebutton">
			                            <label for="hookPause" class="appLabel checkLabel">' . $lang['uiSettingHookPause'] . '
			                                <input id="hookPause" type="checkbox" data-app="hookPause" class="appInput appToggle"/>
			                            </label>
			                        </div>
			                        <div class="hookLabel" id="hookPauseGroup">
			                            <div class="form-group urlGroup hookSplitGroup">
			                                <label for="hookPauseUrl" class="appLabel">' . $lang['uiSettingHookGeneric'] . '
			                                    <input id="hookPauseUrl" class="appInput form-control Webhooks" type="text" value="' . $_SESSION["hookPauseUrl"] . '"/>
			                                </label>
			                            </div>
			                        </div>
			                        <div class="togglebutton">
			                            <label for="hookStop" class="appLabel checkLabel">' . $lang['uiSettingHookStop'] . '
			                                <input id="hookStop" type="checkbox" data-app="hookStop" class="appInput appToggle">
			                            </label>
			                        </div>
			                        <div class="hookLabel" id="hookStopGroup">
			                            <div class="form-group urlGroup hookSplitGroup">
			                                <label for="hookStopUrl" class="appLabel">' . $lang['uiSettingHookGeneric'] . '
			                                    <input id="hookStopUrl" class="appInput form-control Webhooks" type="text" value="' . $_SESSION["hookStopUrl"] . '"/>
			                                </label>
			                            </div>
			                        </div>
			                        <div class="togglebutton">
			                            <label for="hookFetch" class="appLabel checkLabel">' . $lang['uiSettingHookFetch'] . '
			                                <input id="hookFetch" type="checkbox" class="appInput appToggle hookToggle"/>
			                            </label>
			                        </div>
			                        <div class="hookLabel" id="hookFetchGroup">
			                            <div class="form-group urlGroup hookSplitGroup">
			                                <label for="hookFetchUrl" class="appLabel">' . $lang['uiSettingHookGeneric'] . '
			                                    <input id="hookFetchUrl" class="appInput form-control Webhooks" type="text" value="' . $_SESSION["hookFetchUrl"] . '"/>
			                                </label>
			                            </div>
			                        </div>
			                        <div class="togglebutton">
			                            <label for="hookCustom" class="appLabel checkLabel">' . $lang['uiSettingHookCustom'] . '
			                                <input id="hookCustom" type="checkbox" data-app="hookCustom" class="appInput appToggle"/>
			                            </label>
			                        </div>
			                        <div class="form-group hookSplitGroup">
			                            <div class="hookLabel" id="hookCustomGroup">
			                                <label for="hookCustomUrl" class="appLabel">' . $lang['uiSettingHookGeneric'] . '
			                                    <input id="hookCustomUrl" class="appInput form-control Webhooks" type="text" value="' . $_SESSION["hookCustomUrl"] . '"/>
			                                </label>
			                            </div>
			                            <label for="hookCustomPhrase" class="appLabel">' . $lang['uiSettingHookCustomPhrase'] . '
			                                <input id="hookCustomPhrase" class="appInput form-control Webhooks" type="text" value="' . $_SESSION["hookCustomPhrase"] . '"/>
			                            </label>
			                            <label for="hookCustomReply" class="appLabel">' . $lang['uiSettingHookCustomResponse'] . '
			                                <input id="hookCustomReply" class="appInput form-control Webhooks" type="text" value="' . $_SESSION["hookCustomReply"] . '"/>
			                            </label>
			                        </div>
			                        <div class="text-center">
			                            <div class="form-group btn-group">
			                                <button value="Webhooks" class="testInput btn btn-raised btn-info" type="button">' . $lang['uiSettingBtnTest'] . '</button>
			                                <button id="resetCouch" value="Webhooks" class="resetInput btn btn-raised btn-danger btn-100" type="button">' . $lang['uiSettingBtnReset'] . '</button>
			                            </div>
			                        </div>
			                    </div>
			                </div>
			            </div>
		            </div>	                    
        		</div>
		            <div class="view-tab fade settingPage col-md-9 col-lg-10 col-xl-8" id="plexSettingsTab">
			            <div class="gridBox">
		                    <div class="appContainer card">
		                        <div class="card-body">
		                            <h4 class="cardHeader">' . $lang['uiSettingGeneral'] . '</h4>
		                            <div class="form-group">
		                                <label class="appLabel" for="serverList">' . $lang['uiSettingPlaybackServer'] . '</label>
		                                <select class="form-control custom-select serverList" id="serverList" title="'.$lang["uiSettingPlaybackServerHint"].'">
		                                </select>
		                            </div>
		                            <div class="form-group">
		                                <div class="form-group">
		                                    <label for="returnItems" class="appLabel">' . $lang['uiSettingOndeckRecent'] . '
		                                        <input id="returnItems" class="appInput form-control" type="number" min="1" max="20" value="' . $_SESSION["returnItems"] . '" />
		                                    </label>
		                                </div>
		                                <div class="form-group text-center">
		                                    <button class="btn btn-raised logBtn btn-primary" id="castLogs" data-action="castLogs">' . $lang['uiSettingCastLogs'] . '</button><br>
		                                </div>
		                            </div>
		                        </div>
		                    </div>
		                    <div class="appContainer card" id="dvrGroup">
		                        <div class="card-body">
		                            <h4 class="cardHeader">' . $lang['uiSettingPlexDVR'] . '</h4>
		                            <div class="form-group">
		                                <div class="form-group">
		                                    <label class="appLabel serverList" for="dvrList">' . $lang['uiSettingDvrServer'] . '</label>
		                                    <select class="form-control custom-select" id="dvrList">
		                                    </select>
		                                </div>
		                                <div class="form-group">
		                                    <label class="appLabel" for="resolution">' . $lang['uiSettingDvrResolution'] . '</label>
		                                    <select class="form-control appInput" id="plexDvrResolution">
		                                        <option value="0" ' . ($_SESSION["plexDvrResolution"] == 0 ? "selected" : "") . ' >' . $lang['uiSettingDvrResolutionAny'] . '</option>
		                                        <option value="720" ' . ($_SESSION["plexDvrResolution"] == 720 ? "selected" : "") . ' >' . $lang['uiSettingDvrResolutionHD'] . '</option>
		                                    </select>
		                                </div>
		                                <div class="form-group">
		                                    <div class="togglebutton">
		                                        <label for="plexDvrNewAirings" class="appLabel checkLabel">' . $lang['uiSettingDvrNewAirings'] . '
		                                            <input id="plexDvrNewAirings" type="checkbox" class="appInput" ' . ($_SESSION["plexDvrNewAirings"] ? "checked" : "") . ' />
		                                        </label>
		                                    </div>
		                                    <div class="togglebutton">
		                                        <label for="plexDvrReplaceLower" class="appLabel checkLabel">' . $lang['uiSettingDvrReplaceLower'] . '
		                                            <input id="plexDvrReplaceLower" type="checkbox" class="appInput" ' . ($_SESSION["plexDvrReplaceLower"] ? " checked " : "") . ' />
		                                        </label>
		                                    </div>
		                                    <div class="togglebutton">
		                                        <label for="plexDvrRecordPartials" class="appLabel checkLabel">' . $lang['uiSettingDvrRecordPartials'] . '
		                                            <input id="plexDvrRecordPartials" type="checkbox" class="appInput" ' . ($_SESSION["plexDvrRecordPartials"] ? "checked" : "") . ' />
		                                        </label>
		                                    </div>
		                                    <div class="togglebutton">
		                                        <label for="plexDvrComskipEnabled" class="appLabel checkLabel">' . $lang['uiSettingDvrComskipEnabled'] . '
		                                            <input id="plexDvrComskipEnabled" type="checkbox" class="appInput" ' . ($_SESSION["plexDvrComskipEnabled"] ? "checked" : "") . ' />
		                                        </label>
		                                    </div>
		                                </div>
		                                <div class="form-group">
		                                    <label for="plexDvrStartOffsetMinutes" class="appLabel">' . $lang['uiSettingDvrStartOffset'] . '
		                                        <input id="plexDvrStartOffsetMinutes" class="appInput form-control" type="number" min="1" max="30" value="' . $_SESSION["plexDvrStartOffsetMinutes"] . '" />
		                                    </label>
		                                </div>
		                                <div class="form-group">
		                                    <label for="plexDvrEndOffsetMinutes" class="appLabel">' . $lang['uiSettingDvrEndOffset'] . '
		                                        <input id="plexDvrEndOffsetMinutes" class="appInput form-control" type="number" min="1" max="30" value="' . $_SESSION["plexDvrEndOffsetMinutes"] . '" />
		                                    </label>
		                                </div>	
		                            </div>
		                        </div>
		                    </div>		                
			            </div>
		            </div>
		       
					<div class="view-tab settingPage col-sm-9 col-lg-8 fade'. $hidden.'" id="logTab">
						<div class="modal-header">
							<div class="form-group" id="logGroup">
								<label for="logLimit" class="logControl">' . $lang['uiSettingLogCount'] . '
									<select id="logLimit" class="form-control">
										<option value="10">10</option>
										<option value="50" selected>50</option>
										<option value="100">100</option>
										<option value="500">500</option>
										<option value="1000">1000</option>
									</select>
								</label>
								<div id="log">
									<div id="logInner">
										<div>
											<iframe class="card card-body" id="logFrame" src=""></iframe>
										</div>
									</div>
								</div>
								<a class="logbutton" href="log.php?apiToken='.$apiToken.'" target="_blank">
									<span class="material-icons colorItem">open_in_browser</span>
								</a>
							</div>
						</div>
					</div>
			
					<div class="nowPlayingFooter">
						<div class="coverImage">
							<img class="statusImage card-1" src=""/>
							<div id="textBar">
								<h6>Now Playing on <span id="playerName"></span>: </h6>
								<h6><span id="mediaTitle"></span></h6>
								<span id="mediaTagline"></span>
							</div>
						</div>
						<div class="statusWrapper row justify-content-around">
							<div id="progressWrap">
								<input id="progressSlider" type="text" data-slider-min="0" data-slider-id="progress" data-slider-tooltip="hide"/>
							</div>
							<div id="controlWrap">
								<div id="controlBar">
									<button class="controlBtn btn btn-default" id="previousBtn"><span class="material-icons colorItem mat-md">skip_previous</span></button>
									<button class="controlBtn btn btn-default" id="playBtn"><span class="material-icons colorItem mat-lg">play_circle_filled</span></button>
									<button class="controlBtn btn btn-default" id="pauseBtn"><span class="material-icons colorItem mat-lg">pause_circle_filled</span></button>
									<button class="controlBtn btn btn-default" id="nextBtn"><span class="material-icons colorItem mat-md">skip_next</span></button>
								</div>
							</div>
							<div class="scrollContainer">
								<div class="scrollContent" id="mediaSummary"></div>
							</div>
							<div id="volumeWrap"/>
							<div class="volumeBar"></div>
						</div>
						<div id="stopBtnDiv">
							<button class="controlBtn btn btn-default" id="stopBtn"><span class="material-icons colorItem">close</span></button>
							<div id="volumeWrap">
								<input id="volumeSlider" type="text" data-slider-min="0" data-slider-max="100" data-slider-id="volume" data-slider-orientation="vertical" data-slider-tooltip="hide"/>
							</div>
						</div>
					</div>
				        <div id="metaTags">
					    <meta id="apiTokenData" data-token="' . $_SESSION["apiToken"] . '"/>
					</div>
					';


    return [$bodyText,$_SESSION['darkTheme']];
}
