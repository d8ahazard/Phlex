<?php
require_once dirname(__FILE__) . '/vendor/autoload.php';
require_once dirname(__FILE__) . '/webApp.php';
require_once dirname(__FILE__) . '/util.php';
require_once dirname(__FILE__) . '/api.php';

function makeBody($newToken = false) {
	$hide = isWebApp();
	$hidden = $hide ? " remove" : "";
	$hidden2 = $hide ? " hidden" : "";
	if (!defined('LOGGED_IN')) {
		write_log("Dying because not logged in?", "ERROR");
		die();
	}
	$lang = checkSetLanguage();
	$webAddress = webAddress();
	$bodyText = ($_SESSION['darkTheme'] ? '<link href="./css/dark.css" rel="stylesheet">' : '') . PHP_EOL . '			<div id="body" class="row justify-content-center">
				<div class="wrapper col-xs-12 col-lg-8 col-xl-5" id="mainwrap">
			        <div class="queryWrap col-xs-12" id="queryCard">
			        <div class="query">
			            <div class="queryBg">
			                <div class="btn-toolbar row">
			                    <div class="queryGroup form-group label-floating col-xs-10 col-md-7 col-lg-6">
			                        <div class="inputWrap">
				                        <label id="actionLabel" for="commandTest" class="control-label">' . $lang['uiGreetingDefault'] . '</label>
				                        <input type="text" class="form-control" id="commandTest"/>
				                        <div class="load-bar">
											<div class="bar"></div>
											<div class="bar"></div>
											<div class="bar"></div>
										</div>
				                        <a class="material-icons sendBtn" id="executeButton">message</a>
			                        </div>
			                    </div>
			                    <div class="queryBtnWrap col">
			                        <div class="queryBtnGrp">
			                            <div class="btn btn-sm dropdown-toggle barBtn" href="javascript:void(0)" id="client" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
			                                <div class="ddLabel"></div><br>
			                                <i class="material-icons barIcon clientBtn">cast</i>
			                            </div>
			                            <div class="dropdown-menu" id="plexClient" aria-labelledby="dropdownMenuLink">
			                                <div id="clientWrapper">
			                                	<a class="dropdown-item client-item" data-id="rescan"><b>rescan devices</b></a>
			                                </div>
			                            </div>
			                            <a href="" id="settings" class="btn btn-sm barBtn" data-toggle="modal" data-target="#settingsModal"><i class="material-icons barIcon">settings</i></a>
			                            <a href="?logout" id="logout" class="btn btn-sm barBtn"><i class="material-icons barIcon">exit_to_app</i></a>
			
			                        </div>
			                    </div>
			                </div>
			            </div>
			        </div>
			    </div>
			        <div id="results" class="queryWrap">
			        <div id="resultsInner"  class=""></div>
			    </div>
			        <div class="modal fade" id="settingsModal">
			        <div class="modal-dialog" role="document">
			            <ul class="nav nav-tabs" id="tabContent" role="tablist">
						    <li class="nav-item active">
						        <a href="#generalSettingsTab" class="nav-link" data-toggle="tab" role="tab">' . $lang['uiSettingHeaderPhlex'] . '</a>
					        </li>
					        <li class="nav-item">
						        <a href="#plexSettingsTab" class="nav-link" data-toggle="tab" role="tab">' . $lang['uiSettingHeaderPlex'] . '</a>
					        </li>
					        <li class="nav-item'.$hidden.'" id="deviceSettingsHeader">
						        <a href="#deviceSettingsTab" class="nav-link" data-toggle="tab" role="tab">' . $lang['uiSettingHeaderDevices'] . '</a>
					        </li>
					        <li class="nav-item">
						        <a href="#fetcherSettingsTab" class="nav-link" data-toggle="tab" role="tab">' . $lang['uiSettingHeaderFetchers'] . '</a>
					        </li>
					        <li class="nav-item'.$hidden.'">
						        <a href="#logTab" class="nav-link" data-toggle="tab" role="tab">' . $lang['uiSettingHeaderLogs'] . '</a>
					        </li>
					            <button type="button" id="settingsClose" data-dismiss="modal" aria-label="Close">
				                    <span class="material-icons" aria-hidden="true">close</span>
				                </button>
							
						</ul>
						
					                
			            <div class="tab-content">
							<div class="tab-pane active" id="generalSettingsTab" role="tabpanel">
								<div class="modal-content">
			                        <div class="modal-body">
					                    <div class="appContainer card">
					                        <div class="card-body">
					                            <h4 class="cardHeader">' . $lang['uiSettingGeneral'] . '</h4>
					                            <div class="form-group">
				                                    <label class="appLabel" for="appLanguage">' . $lang['uiSettingLanguage'] . '</label>
				                                    <select class="form-control custom-select" id="appLanguage">
				                                    	' . listLocales() . '
				                                    </select>
				                                    <br><br>
			                                    </div>
					                            <div class="form-group'.$hidden.'">
					                                <div class="form-group">
					                                    <label for="apiToken" class="appLabel">' . $lang['uiSettingApiKey'] . '
					                                        <input id="apiToken" class="appInput form-control" type="text" value="' . $_SESSION["apiToken"] . '" readonly="readonly"/>
					                                    </label>
					                                </div>
					                            </div>
					                            <div class="form-group'.$hidden2.'">
					                                <div class="form-group">
					                                    <label for="publicAddress" class="appLabel">' . $lang['uiSettingPublicAddress'] . '
					                                        <input id="publicAddress" class="appInput form-control formpop" type="text" value="' . $webAddress . '" />
					                                    </label>
					                                </div>
					                            </div>
					                            <div class="form-group">
					                                <div class="form-group">
					                                    <label for="searchAccuracy" class="appLabel">' . $lang['uiSettingSearchAccuracy'] . '
					                                        <input id="searchAccuracy" class="appInput form-control" type="number" min="5" max="100" value="' . $_SESSION["searchAccuracy"] . '" />
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
					                            <div class="togglebutton'.$hidden.'">
					                                <label for="cleanLogs" class="appLabel checkLabel">' . $lang['uiSettingObscureLogs'] . '
					                                    <input id="cleanLogs" type="checkbox" class="appInput appToggle" ' . ($_SESSION["cleanLogs"] ? "checked" : "") . '/>
					                                </label>
					                            </div>
					                            <div class="togglebutton">
					                                <label for="darkTheme" class="appLabel checkLabel">' . $lang['uiSettingThemeColor'] . '
					                                    <input id="darkTheme" class="appInput" type="checkbox" ' . ($_SESSION["darkTheme"] ? "checked" : "") . '/>
					                                </label>
					                            </div>
					                            <div class="togglebutton'.$hidden.'">
					                                <label for="Debug" class="appLabel checkLabel">' . $lang['uiSettingDebugging'] . '
					                                    <input id="Debug" class="appInput" type="checkbox" ' . ($_SESSION["Debug"] ? "checked" : "") . '/>
					                                </label>
					                            </div>
					                            <div class="togglebutton'.$hidden.'">
					                                <label for="forceSSL" class="appLabel checkLabel">' . $lang['uiSettingForceSSL'] . '
					                                    <input id="forceSSL" class="appInput" type="checkbox" ' . ($_SESSION["forceSSL"] ? "checked" : "") . '/>
					                                </label>
					                                <span class="bmd-help">' . $lang['uiSettingForceSSLHint'] . '</span>
					                            </div>
					                            
					                            <div class="form-group text-center">
					                                <div class="form-group">
					                                    <label for="linkAccount">' . $lang['uiSettingAccountLinking'] . '</label><br>
					                                    <button class="foo'.$hidden.'" id="testServer" data-action="test" class="btn btn-raised linkBtn btn-warn">' . $lang['uiSettingTestServer'] . '</button><br>
					                                    <button id="linkAccount" data-action="google" class="btn btn-raised linkBtn btn-danger">' . $lang['uiSettingLinkGoogle'] . '</button>
					                                    <button id="linkAmazonAccount" data-action="amazon" class="btn btn-raised linkBtn btn-info">' . $lang['uiSettingLinkAmazon'] . '</button>
					                                </div>
					                            </div>
					                            <div class="text-center">
					                                <label for="sel1">' . $lang['uiSettingCopyIFTTT'] . '</label><br>
					                                <button id="sayURL" class="copyInput btn btn-raised btn-primary btn-70" type="button"><i class="material-icons">message</i></button>
					                            </div>
					                        </div>
					                    </div>' . ($hide ? (checkGit() ? '<div class="appContainer card updateDiv'.$hidden.'">
					                        <div class="card-body">
					                            <h4 class="cardHeader">' . $lang['uiSettingUpdates'] . '</h4>
					                            <div class="form-group">
					                                <div class="togglebutton">
					                                    <label for="autoUpdate" class="appLabel checkLabel">' . $lang['uiSettingAutoUpdate'] . '
					                                        <input id="autoUpdate" type="checkbox" class="appInput" ' . ($_SESSION["autoUpdate"] ? "checked" : "") . '/>
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
					                    </div>' : ''):'') . '<div class="appContainer card">
					                        <div class="card-body">
					                            <h4 class="cardHeader">' . $lang['uiSettingHookLabel'] . '</h4>
					                            <div class="togglebutton">
					                                <label for="hookEnabled" class="appLabel checkLabel">' . $lang['uiSettingEnable'] . '
					                                    <input id="hookEnabled" type="checkbox" class="appInput appToggle" ' . ($_SESSION["hookEnabled"] ? 'checked' : '') . '/>
					                                </label>
					                            </div>
					                            <div class="form-group" id="hookGroup">
						                            <div class="togglebutton">
						                                <label for="hookSplit" class="appLabel checkLabel">' . $lang['uiSettingSeparateHookUrl'] . '
						                                    <input id="hookSplit" type="checkbox" class="appInput appToggle" ' . ($_SESSION["hookSplit"] ? 'checked' : '') . '/>
						                                </label>
						                            </div>
						                            <div class="form-group" id="hookUrlGroup">
					                                    <label for="hookUrl" class="appLabel">' . $lang['uiSettingHookUrlGeneral'] . '
					                                        <input id="hookUrl" class="appInput form-control Webhooks" type="text" value="' . $_SESSION["hookUrl"] . '"/>
					                                        <span class="bmd-help">' . $lang['uiSettingHookPlayHint'] . '</span>
					                                    </label>
					                                </div>
					                                <div class="togglebutton">
						                                <label for="hookPlay" class="appLabel checkLabel">' . $lang['uiSettingHookPlayback'] . '
						                                    <input id="hookPlay" type="checkbox" class="appInput hookToggle" ' . ($_SESSION["hookPlay"] ? 'checked' : '') . '/>
						                                </label>
						                            </div>
						                            <div class="hookLabel">
						                                <div class="form-group urlGroup" id="hookPlayGroup">
						                                    <label for="hookPlayUrl" class="appLabel">' . $lang['uiSettingHookGeneric'] . '
						                                        <input id="hookPlayUrl" class="appInput form-control Webhooks" type="text" value="' . $_SESSION["hookPlayUrl"] . '"/>
						                                        <span class="bmd-help">' . $lang['uiSettingHookPlayHint'] . '</span>
						                                    </label>
						                                </div>
					                                </div>
					                                <div class="togglebutton">
						                                <label for="hookPaused" class="appLabel checkLabel">' . $lang['uiSettingHookPause'] . '
						                                    <input id="hookPaused" type="checkbox" class="appInput hookToggle" ' . ($_SESSION["hookPaused"] ? 'checked' : '') . '/>
						                                </label>
						                            </div>
						                            <div class="hookLabel">
						                                <div class="form-group urlGroup" id="hookPausedGroup">
						                                    <label for="hookPausedUrl" class="appLabel">' . $lang['uiSettingHookGeneric'] . '
						                                        <input id="hookPausedUrl" class="appInput form-control Webhooks" type="text" value="' . $_SESSION["hookPausedUrl"] . '"/>
						                                    </label>
						                                </div>
					                                </div>
					                                <div class="togglebutton">
						                                <label for="hookStop" class="appLabel checkLabel">' . $lang['uiSettingHookStop'] . '
						                                    <input id="hookStop" type="checkbox" class="appInput hookToggle"/ ' . ($_SESSION["hookStop"] ? 'checked' : '') . '>
						                                </label>
						                            </div>
						                            <div class="hookLabel">
						                                <div class="form-group urlGroup" id="hookStopGroup">
						                                    <label for="hookStopUrl" class="appLabel">' . $lang['uiSettingHookGeneric'] . '
						                                        <input id="hookStopUrl" class="appInput form-control Webhooks" type="text" value="' . $_SESSION["hookStopUrl"] . '"/>
						                                    </label>
						                                </div>
					                                </div>
					                                <div class="togglebutton">
						                                <label for="hookFetch" class="appLabel checkLabel">' . $lang['uiSettingHookFetch'] . '
						                                    <input id="hookFetch" type="checkbox" class="appInput hookToggle" ' . ($_SESSION["hookFetch"] ? 'checked' : '') . '/>
						                                </label>
						                            </div>
						                            <div class="hookLabel">
						                                <div class="form-group urlGroup" id="hookFetchGroup">
						                                    <label for="hookFetchUrl" class="appLabel">' . $lang['uiSettingHookGeneric'] . '
						                                        <input id="hookFetchUrl" class="appInput form-control Webhooks" type="text" value="' . $_SESSION["hookFetchUrl"] . '"/>
						                                    </label>
						                                </div>
					                                </div>
					                                <div class="togglebutton">
						                                <label for="hookCustom" class="appLabel checkLabel">' . $lang['uiSettingHookCustom'] . '
						                                    <input id="hookCustom" type="checkbox" class="appInput hookToggle" ' . ($_SESSION["hookCustom"] ? 'checked' : '') . '/>
						                                </label>
						                            </div>
					                                <div class="form-group" id="hookCustomPhraseGroup">
						                                <div class="hookLabel">
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
			                </div>
			                <div class="tab-pane fade" id="plexSettingsTab" role="tabpanel">
			                    <div class="modal-body">
			                    <div class="userGroup">
				                        <div class="userWrap row justify-content-center">
				                        	<img class="avatar col-xs-3" src="' . $_SESSION["plexAvatar"] . '"/>
						                    <div class="col-xs-9">
							                    <h4 class="userHeader">' . ucfirst($_SESSION["plexUserName"]) . '</h4>
							                    <hab>' . $_SESSION["plexEmail"] . '</hab>
						                    </div>
					                    </div>
					                </div>
			                        <div class="card">
			                            <div class="appContainer card">
	                                        <div class="card-body">
					                            <h4 class="cardHeader">' . $lang['uiSettingGeneral'] . '</h4>
				                                <div class="form-group">
				                                    <label class="appLabel" for="serverList">' . $lang['uiSettingPlaybackServer'] . '</label>
				                                    <select class="form-control custom-select serverList" id="serverList">
				                                    
				                                    </select>
				                                    <label class="appLabel" for="parentList">' . $lang['uiSettingMasterServer'] . '</label>
				                                    <select class="form-control custom-select serverList" id="parentList">
				                                    
				                                    </select>
				                                    <br><br>
			                                    </div>
			                                    <div class="form-group">
					                                <div class="form-group">
					                                    <label for="returnItems" class="appLabel">' . $lang['uiSettingOndeckRecent'] . '
					                                        <input id="returnItems" class="appInput form-control" type="number" min="1" max="20" value="' . $_SESSION["returnItems"] . '" />
					                                    </label>
					                                </div>
					                                <div class="text-center">
					                                    <div class="form-group btn-group">
					                                        <button value="Plex" class="testInput btn btn-raised btn-info btn-100" type="button">' . $lang['uiSettingBtnTest'] . '</button>
					                                    </div>
				                                	</div>
					                            </div>
					                        </div>
					                    </div>
					                    <div class="appContainer card dvrGroup">
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
				                                    </div>
					                                <div class="form-group">
					                                    <label for="plexDvrStartOffset" class="appLabel">' . $lang['uiSettingDvrStartOffset'] . '
					                                        <input id="plexDvrStartOffset" class="appInput form-control" type="number" min="1" max="30" value="' . $_SESSION["plexDvrStartOffset"] . '" />
					                                    </label>
					                                </div>
					                                <div class="form-group">
					                                    <label for="dvr_endoffset" class="appLabel">' . $lang['uiSettingDvrEndOffset'] . '
					                                        <input id="dvr_endoffset" class="appInput form-control" type="number" min="1" max="30" value="' . $_SESSION["plexDvrEndOffset"] . '" />
					                                    </label>
					                                </div>
					
					                            </div>
					                        </div>
							                
										</div>
									</div>
			                    </div>
			                </div> 
			                <div class="tab-pane fade'.$hidden.'" id="deviceSettingsTab" role="tabpanel">
			                    <div class="modal-body" id="deviceBody">
                                    <h4 class="cardHeader">' . $lang['uiSettingDevices'] . '</h4>
                                    <div id="deviceContainer"></div>
					                <button type="button" class="btn btn-primary fab" id="deviceFab" data-toggle="tooltip" data-placement="left" title="' . $lang['uiSettingDevicesAddNew'] . '">
										<i class="material-icons">add</i>
									</button>
			                    </div>
		                    </div>
			                <div class="tab-pane fade" id="fetcherSettingsTab" role="tabpanel">
			                    <div class="modal-body" id="fetcherBody">
			                    	<div class="appContainer card">
			                    		<div class="card-body">
			                    			<h4 class="cardHeader">Notifications</h4>
				                            <div class="fetchNotify">
				                                <button id="copyCouch" value="urlCouchPotato" class="hookLnk btn btn-raised btn-warn btn-100" title="Copy WebHook Notification URL">
		                                            <i class="material-icons">assignment</i>
	                                            </button>
	                                        </div>
										</div>
									</div>
			                        <div class="appContainer card">
					                        <div class="card-body">
					                            <h4 class="cardHeader">CouchPotato</h4>
					                            <div class="togglebutton">
					                                <label for="couchEnabled" class="appLabel checkLabel">' . $lang['uiSettingEnable'] . '
					                                    <input id="couchEnabled" type="checkbox" class="appInput appToggle"/>
					                                </label>
					                            </div>
					                            <div class="form-group" id="couchGroup">
					                                <div class="form-group">
					                                    <label for="couchUri" class="appLabel">Couchpotato URI:
					                                        <input id="couchUri" class="appInput form-control CouchPotato appParam" type="text" value="' . $_SESSION["couchUri"] . '"/>
					                                    </label>
					                                </div>
					                                 <div class="form-group">
					                                    <label for="couchToken" class="appLabel">Couchpotato ' . $lang['uiSettingFetcherToken'] . ':
					                                        <input id="couchToken" class="appInput form-control CouchPotato appParam" type="text" value="' . $_SESSION["couchToken"] . '"/>
					                                    </label>
					                                </div>
					                                <div class="form-group">
					                                    <label class="appLabel" for="couchProfile">' . $lang['uiSettingFetcherQualityProfile'] . ':</label>
					                                    <select class="form-control profileList" id="couchProfile">
															' . fetchList("couch") . '
					                                    </select>
					                                </div>
					                                <div class="text-center">
					                                    <div class="form-group btn-group">
					                                        <button value="CouchPotato" class="testInput btn btn-raised btn-info">' . $lang['uiSettingBtnTest'] . '</button>
					                                        <button id="resetCouch" value="CouchPotato" class="resetInput btn btn-raised btn-danger btn-100">' . $lang['uiSettingBtnReset'] . '</button>
					                                    </div>
					                                </div>
					                            </div>
					                        </div>
					                    </div>
					                    <div class="appContainer card ombiGroup">
					                        <div class="card-body">
					                            <h4 class="cardHeader">Ombi</h4>
					                            <div class="togglebutton">
					                                <label for="ombiEnabled" class="appLabel checkLabel">' . $lang['uiSettingEnable'] . '
					                                    <input id="ombiEnabled" type="checkbox" class="appInput appToggle"/>
					                                </label>
					                            </div>
					                            <div class="form-group" id="ombiGroup">
					                                <div class="form-group">
					                                    <label for="ombiUrl" class="appLabel">Ombi URI:
					                                        <input id="ombiUrl" class="appInput form-control ombiUrl appParam" type="text"  value="' . $_SESSION["ombiIP"] . '" />
					                                    </label>
					                                </div>
					                                 <div class="form-group">
					                                    <label for="ombiAuth" class="appLabel">Ombi ' . $lang['uiSettingFetcherToken'] . ':
					                                        <input id="ombiAuth" class="appInput form-control Ombi appParam" type="text" value="' . $_SESSION["ombiAuth"] . '"/>
					                                    </label>
					                                </div>
					                                <div class="text-center">
					                                    <div class="form-group btn-group">
					                                        <button value="Ombi" class="testInput btn btn-raised btn-info btn-100" type="button">' . $lang['uiSettingBtnTest'] . '</button>
					                                        <button id="resetOmbi" value="Ombi" class="resetInput btn btn-raised btn-danger btn-100" type="button">' . $lang['uiSettingBtnReset'] . '</button>
					                                    </div>
					                                </div>
					                            </div>
					                        </div>
					                    </div>
					                    <div class="appContainer card">
					                        <div class="card-body">
					                            <h4 class="cardHeader">Radarr</h4>
					                            <div class="togglebutton">
					                                <label for="radarrEnabled" class="appLabel checkLabel">' . $lang['uiSettingEnable'] . '
					                                    <input id="radarrEnabled" type="checkbox" class="appInput appToggle"/>
					                                </label>
					                            </div>
					                            <div class="form-group" id="radarrGroup">
					                                <div class="form-group">
					                                    <label for="radarrUri" class="appLabel">Radarr URI:
					                                        <input id="radarrUri" class="appInput form-control Radarr appParam" type="text" value="' . $_SESSION["radarrUri"] . '"/>
					                                    </label>
					                                </div>
                                                  	<div class="form-group">
					                                    <label for="radarrToken" class="appLabel">Radarr ' . $lang['uiSettingFetcherToken'] . ':
					                                        <input id="radarrToken" class="appInput form-control Radarr appParam" type="text" value="' . $_SESSION["radarrToken"] . '"/>
					                                    </label>
					                                </div>
					                                <div class="form-group">
					                                    <label class="appLabel" for="radarrProfile">' . $lang['uiSettingFetcherQualityProfile'] . ':</label>
					                                    <select class="form-control profileList" id="radarrProfile">
					                                        ' . fetchList("radarr") . '
					                                    </select>
					                                </div>
					                                <div class="text-center">
					                                    <div class="form-group btn-group">
					                                        <button value="Radarr" class="testInput btn btn-raised btn-info btn-100" type="button">' . $lang['uiSettingBtnTest'] . '</button>
					                                        <button id="resetRadarr" value="Radarr" class="resetInput btn btn-raised btn-danger btn-100" type="button">' . $lang['uiSettingBtnReset'] . '</button>
					                                    </div>
					                                </div>
					                            </div>
					                        </div>
					                    </div>
					                    <div class="appContainer card">
					                        <div class="card-body">
					                            <h4 class="cardHeader">Sickbeard/SickRage</h4>
					                            <div class="togglebutton">
					                                <label for="sickEnabled" class="appLabel checkLabel">' . $lang['uiSettingEnable'] . '
					                                    <input id="sickEnabled" type="checkbox" class="appInput appToggle"/>
					                                </label>
					                            </div>
					                            <div class="form-group" id="sickGroup">
					                                <div class="form-group">
					                                    <label for="sickUri" class="appLabel">Sick URI:
					                                        <input id="sickUri" class="appInput form-control Sick appParam" type="text" value="' . $_SESSION["sickUri"] . '"/>
					                                    </label>
					                                </div>
					                                <div class="form-group">
					                                    <label for="sickToken" class="appLabel">Sick ' . $lang['uiSettingFetcherToken'] . ':
					                                        <input id="sickToken" class="appInput form-control Sick appParam" type="text" value="' . $_SESSION["sickToken"] . '"/>
					                                    </label>
					                                </div>
					                                <div class="form-group">
					                                    <label class="appLabel" for="sickProfile">' . $lang['uiSettingFetcherQualityProfile'] . ':</label>
					                                    <select class="form-control appInput profileList" id="sickProfile">
					                                        ' . fetchList("sick") . '
					                                    </select>
					                                </div>
					                                <div class="text-center">
					                                    <div class="form-group btn-group">
					                                        <button value="Sick" class="testInput btn btn-raised btn-info btn-100" type="button">' . $lang['uiSettingBtnTest'] . '</button>
					                                        <button id="resetSick" value="Sick" class="resetInput btn btn-raised btn-danger btn-100" type="button">' . $lang['uiSettingBtnReset'] . '</button>
					                                    </div>
					                                </div>
					                            </div>
					                        </div>
					                    </div>
					                    <div class="appContainer card">
					                        <div class="card-body">
					                            <h4 class="cardHeader">Sonarr</h4>
					                            <div class="togglebutton">
					                                <label for="sonarrEnabled" class="appLabel checkLabel">' . $lang['uiSettingEnable'] . '
					                                    <input id="sonarrEnabled" type="checkbox" class="appInput appToggle"/>
					                                </label>
					                            </div>
					                            <div class="form-group" id="sonarrGroup">
					                                <div class="form-group">
					                                    <label for="sonarrUri" class="appLabel">Sonarr URI:
					                                        <input id="sonarrUri" class="appInput form-control Sonarr appParam" type="text" value="' . $_SESSION["sonarrUri"] . '"/>
					                                    </label>
					                                </div>
					                                <div class="form-group">
					                                    <label for="sonarrToken" class="appLabel">Sonarr ' . $lang['uiSettingFetcherToken'] . ':
					                                        <input id="sonarrToken" class="appInput form-control Sonarr appParam" type="text" value="' . $_SESSION["sonarrToken"] . '"/>
					                                    </label>
					                                </div>
					                                <div class="form-group">
					                                    <label class="appLabel" for="sonarrProfile">' . $lang['uiSettingFetcherQualityProfile'] . ':</label>
					                                    <select class="form-control profileList" id="sonarrProfile">
					                                        ' . fetchList("sonarr") . '
					                                    </select>
					                                </div>
					                                <div class="text-center">
					                                    <div class="form-group btn-group">
					                                        <button value="Sonarr" class="testInput btn btn-raised btn-info btn-100" type="button">' . $lang['uiSettingBtnTest'] . '</button>
					                                        <button id="resetSonarr" value="Sonarr" class="resetInput btn btn-raised btn-danger btn-100" type="button">' . $lang['uiSettingBtnReset'] . '</button>
					                                    </div>
					                                </div>
					                            </div>
					                        </div>
					                    </div>
			                    </div>
			                </div>
			                <div class="tab-pane fade'.$hidden.'" id="logTab" role="tabpanel">
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
				                        <label for="logLimit" class="logControl">' . $lang['uiSettingLogLevel'] . '
					                        <select id="logLevel" class="form-control logControl">
						                        <option value="DEBUG" selected>ALL</option>
						                        <option value="INFO">Info</option>
						                        <option value="WARN">Warn</option>
						                        <option value="ERROR">Error</option>
					                        </select>
				                        </label>
				                        <a class="logbutton" href="log.php?apiToken='.$_SESSION['apiToken'].'" target="_blank"><span class="material-icons">open_in_browser</span></a>
									</div>
								</div>
			                    <div class="modal-body" id="logBody">
			                        <div class="card">
			                            <div class="card-body">
			                                <h4 class="cardHeader" id="updateHeader">' . $lang['uiSettingLogupdate'] . '</h4>
			                                <div class="form-group" id="logBody"/>
										</div>
									</div>
			                    </div>
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

		            <div class="coverImage">
	                    <img class="statusImage card-1" src=""/>
	                    <div id="textBar">
			                <h6>Now Playing on <span id="playerName"></span>: </h6>
			                <h6><span id="mediaTitle"></span></h6>
			                <span id="mediaTagline"></span>
		                </div>
		            </div>
					
					<div class="statusWrapper row justify-content-around">
			            <div class="col-sm-5">
			            	
			            </div>
			            
						<div class="col-sm-4 volumeBar">
							<div class="scrollContainer">
								<div class="scrollContent" id="mediaSummary"></div>
							</div>
						</div>
			        </div>
					
					<div id="progressSlider" class="slider shor slider-material-orange"></div>
					
					<div class="controlWrap">
		                <div id="controlBar">
	                        <button class="controlBtn btn btn-default" id="previousBtn"><span class="material-icons mat-md">skip_previous</span></button>
		                    <button class="controlBtn btn btn-default" id="playBtn"><span class="material-icons mat-lg">play_circle_filled</span></button>
		                    <button class="controlBtn btn btn-default" id="pauseBtn"><span class="material-icons mat-lg">pause_circle_filled</span></button>
		                    <button class="controlBtn btn btn-default" id="nextBtn"><span class="material-icons mat-md">skip_next</span></button>
						</div>
					</div>
					
			        
			        
			        <div id="stopBtnDiv">
		                <button class="controlBtn btn btn-default" id="stopBtn"><span class="material-icons">close</span></button>
		                <div id="volumeSlider" class="slider shor slider-material-orange"></div>
		            </div>
		            
			    </div>
			    <div class="wrapperArt"></div>
			    <div class="castArt">
			        <div class="background-container">
			            <div class="ccWrapper">
			                <div class="fade1 ccBackground">
			                    <div class="ccTextDiv">
			                        <span class="spacer"></span>
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
			        <meta id="apiTokenData" data-token="' . $_SESSION["apiToken"] . '"/>
			        <meta id="strings" data-array="' . urlencode(json_encode($lang['javaStrings'])) . '"/>' .
					metaTags() . '
			    </div>
			    <script type="text/javascript" src="./js/main.js"></script>
			</div>';
	return $bodyText;
}

?>
