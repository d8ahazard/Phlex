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

	$bodyText = '<div id="body" class="row justify-content-center">

				<div class="wrapper col-xs-12 col-lg-8 col-xl-5" id="mainWrap">
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
			    	'.makeSettingsBody($defaults).'
	            </div>
	            
				<div id="metaTags">
			        <meta id="apiTokenData" data-token="' . $_SESSION["apiToken"] . '"/>
			        <meta id="strings" data-array="' . urlencode(json_encode($lang['javaStrings'])) . '"/>' .
					makeMetaTags() . '
			    </div>
			</div>';
    return [$bodyText,$_SESSION['darkTheme']];
}




function makeMetaTags($defaults) {
	$server = findDevice(false,false,"Server");
	$client = findDevice(false,false,"Client");
    $tags = '';
    $uiData = json_encode(getUiData(true));
    $uiData = str_replace("'","`",$uiData);
    $dvr = ($_SESSION['plexDvrUri'] ? "true" : "");
    $tags .= '<meta id="usernameData" data="' . $_SESSION['plexUserName'] . '"/>' . PHP_EOL .
        '<meta id="updateAvailable" data="' . $_SESSION['updateAvailable'] . '"/>' . PHP_EOL .
        '<meta id="deviceID" data="' . $_SESSION['deviceID'] . '"/>' . PHP_EOL .
        '<meta id="serverURI" data="' . $server['Uri'] . '"/>' . PHP_EOL .
        '<meta id="publicAddress" value="' . serverAddress() . '"/>' . PHP_EOL .
        '<meta id="clientURI" data="' . $client['Uri'] . '"/>' . PHP_EOL .
        '<meta id="clientName" data="' . $client['Name'] . '"/>' . PHP_EOL .
        '<meta id="plexDvr" data-enable="' . $dvr . '"/>' . PHP_EOL .
        '<div id="uiData" data-default=\''.$uiData.'\' class="hidden"></div>' . PHP_EOL .
        '<meta id="rez" value="' . $_SESSION['plexDvrResolution'] . '"/>' . PHP_EOL;

    return $tags;
}

function makeSettingsBody($defaults) {
	$hide = $defaults['isWebApp'];
	$hidden = $hide ? " remove" : "";
	$hiddenHome = $hide ? "" : " remove";
	$useGit = $hide ? false : checkGit();
	$webAddress = serverAddress();
	$lang = $defaults['lang'];

	$gitDiv = "<div class='appContainer card updateDiv$hidden'>
			        <div class='card-body'>
			            <h4 class='cardHeader'>" . $lang["uiSettingUpdates"] . "</h4>
			            <div class='form-group'>
			                <div class='togglebutton'>
			                    <label for='autoUpdate' class='appLabel checkLabel'>" . $lang["uiSettingAutoUpdate"] . "
			                        <input id='autoUpdate' type='checkbox' class='appInput appToggle'/>
			                    </label>
			                </div>
			                <div class='togglebutton'>
			                    <label for='notifyUpdate' class='appLabel checkLabel'>" . $lang["uiSettingNotifyUpdate"] . "
			                        <input id='notifyUpdate' type='checkbox' class='appInput'" . ($_SESSION['notifyUpdate'] ? 'checked' : '') . "/>
			                    </label>
			                </div>
			                <div class='form-group'>
			                    <div id='updateContainer'>
			                    </div>
			                </div>
			                <div class='text-center autoUpdateGroup'>
			                    <div class='form-group btn-group'>
			                        <button id='checkUpdates' value='checkUpdates' class='btn btn-raised btn-info btn-100' type='button'>" . $lang["uiSettingRefreshUpdates"] . "</button>
			                        <button id='installUpdates' value='installUpdates' class='btn btn-raised btn-warning btn-100' type='button'>" . $lang["uiSettingInstallUpdates"] . "</button>
			                    </div>
			                </div>
			            </div>
			        </div>
			    </div>";

	$gitDiv = $useGit ? $gitDiv : "";

	$apiToken = $_SESSION['apiToken'];

	$string = "	<div class='modal fade' id='settingsModal'>
					<div class='modal-dialog' role='document'>
			            <div class='modal-header' id='settingsHeader' role='header'>
						    <button type='button' id='settingsClose' data-dismiss='modal' aria-label='Close'>
			                    <span class='material-icons' aria-hidden='true'>close</span>
			                </button>
						</div>
						<ul class='nav nav-tabs' id='tabContent' role='tablist'>
						    <li class='nav-item active'>
						        <a href='#generalSettingsTab' class='nav-link' data-toggle='tab' role='tab'>" . $lang["uiSettingHeaderPhlex"] . "</a>
					        </li>
					        <li class='nav-item'>
						        <a href='#plexSettingsTab' class='nav-link' data-toggle='tab' role='tab'>" . $lang["uiSettingHeaderPlex"] . "</a>
					        </li>
					        <li class='nav-item'>
						        <a href='#movieFetcherSettingsTab' class='nav-link' data-toggle='tab' role='tab'>" . $lang["uiSettingHeaderMovies"] . "</a>
					        </li>
					        <li class='nav-item'>
						        <a href='#showFetcherSettingsTab' class='nav-link' data-toggle='tab' role='tab'>" . $lang["uiSettingHeaderShows"] . "</a>
					        </li>
					        <li class='nav-item'>
						        <a href='#musicFetcherSettingsTab' class='nav-link' data-toggle='tab' role='tab'>" . $lang["uiSettingHeaderMusic"] . "</a>
					        </li>
					        <li class='nav-item logNav".$hidden."'>
						        <a href='#logTab' class='nav-link' data-toggle='tab' role='tab'>" . $lang["uiSettingHeaderLogs"] . "</a>
					        </li>
				            <button type='button' id='settingsClose' data-dismiss='modal' aria-label='Close'>
			                    <span class='material-icons' aria-hidden='true'>close</span>
			                </button>
						</ul>
			            <div class='tab-content'>
							<div class='tab-pane active' id='generalSettingsTab' role='tabpanel'>
								<div class='modal-content'>
			                        <div class='modal-body'>
					                    <div class='appContainer card'>
					                        <div class='card-body'>
					                            <h4 class='cardHeader'>" . $lang["uiSettingGeneral"] . "</h4>
					                            
					                            <div class='form-group'>
				                                    <label class='appLabel' for='appLanguage'>" . $lang["uiSettingLanguage"] . "</label>
				                                    <select class='form-control custom-select' id='appLanguage'>
				                                    	" . listLocales() . "
				                                    </select>
				                                    <div class='form-group".$hidden."'>
					                                    <label for='apiToken' class='appLabel'>" . $lang["uiSettingApiKey"] . "
					                                        <input id='apiToken' class='appInput form-control' type='text' value='" . $_SESSION['apiToken'] . "' readonly='readonly'/>
					                                    </label>
					                                </div>
					                                <div class='form-group".$hidden."'>
					                                    <label for='publicAddress' class='appLabel'>" . $lang["uiSettingPublicAddress"] . "
					                                        <input id='publicAddress' class='appInput form-control formpop' type='text' value='" . $webAddress . "' />
					                                    </label>
					                                </div>
					                            </div>
					                            <div class='form-group'>
					                                <div class='form-group'>
					                                    <label for='rescanTime' class='appLabel'>" . $lang["uiSettingRescanInterval"] . "
					                                        <input id='rescanTime' class='appInput form-control' type='number' min='10' max='30' value='" . $_SESSION['rescanTime'] . "' />
					                                        <span class='bmd-help'>" . $lang["uiSettingRescanHint"] . "</span>
					                                    </label>
					                                </div>
					                            </div>
					                            <div class='noNewUsersGroup togglebutton".$hidden."'>
					                                <label for='noNewUsers' class='appLabel checkLabel'>" . $lang["uiSettingNoNewUsers"] . "
					                                    <input id='noNewUsers' title='".$lang["uiSettingNoNewUsersHint"]."' class='appInput' type='checkbox' " . ($_SESSION['noNewUsers'] ? 'checked' : '') . "/>
					                                </label>
					                            </div>
					                            <div class='togglebutton".$hidden."'>
					                                <label for='cleanLogs' class='appLabel checkLabel'>" . $lang["uiSettingObscureLogs"] . "
					                                    <input id='cleanLogs' type='checkbox' class='appInput appToggle' " . ($_SESSION['cleanLogs'] ? 'checked' : '') . "/>
					                                </label>
					                            </div>
					                            <div class='togglebutton'>
					                                <label for='darkTheme' class='appLabel checkLabel'>" . $lang["uiSettingThemeColor"] . "
					                                    <input id='darkTheme' class='appInput' type='checkbox' " . ($_SESSION['darkTheme'] ? 'checked' : '') . "/>
					                                </label>
					                            </div>
					                            <div class='togglebutton".$hidden."'>
					                                <label for='forceSSL' class='appLabel checkLabel'>" . $lang["uiSettingForceSSL"] . "
					                                    <input id='forceSSL' class='appInput' type='checkbox' " . ($_SESSION['forceSSL'] ? 'checked' : '') . "/>
					                                </label>
					                                <span class='bmd-help'>" . $lang["uiSettingForceSSLHint"] . "</span>
					                            </div>
					                        </div>
					                    </div>
					                    <div class='appContainer card'>
					                    	<div class='card-body'>
					                    	<h4 class='cardHeader'>".$lang["uiSettingAccountLinking"]."</h4>
					                    	<div class='form-group text-center'>
					                                <div class='form-group'>
					                                    <button class='btn btn-raised linkBtn btn-primary testServer".$hidden."' id='testServer' data-action='test'>" . $lang["uiSettingTestServer"] . "</button><br>
					                                    <button id='linkAccountv2' data-action='googlev2' class='btn btn-raised linkBtn btn-danger'>" . $lang["uiSettingLinkGoogle"] . "</button>
					                                    <button id='linkAmazonAccount' data-action='amazon' class='btn btn-raised linkBtn btn-info'>" . $lang["uiSettingLinkAmazon"] . "</button>
					                                </div>
					                            </div>
					                            <div class='text-center'>
					                                <label for='sel1'>" . $lang["uiSettingCopyIFTTT"] . "</label><br>
					                                <button id='sayURL' class='copyInput btn btn-raised btn-primary btn-70' type='button'><i class='material-icons'>message</i></button>
					                            </div>
											</div>
										</div>
					                    
					                    <div class='appContainer card'>
				                            <div class='card-body'>
				                                <h4 class='cardHeader'>Notifications</h4>
					                            <div class='fetchNotify'>
					                                <button id='copyCouch' value='urlCouchPotato' class='hookLnk btn btn-raised btn-warn btn-100' title='Copy WebHook Notification URL'>
			                                            <i class='material-icons'>assignment</i>
		                                            </button>
		                                        </div>
											</div>
										</div>
					                    " .
										$gitDiv .
										"<div class='appContainer card'>
					                        <div class='card-body'>
					                            <h4 class='cardHeader'>" . $lang["uiSettingHookLabel"] . "</h4>
					                            <div class='togglebutton'>
					                                <label for='hook' class='appLabel checkLabel'>" . $lang["uiSettingEnable"] . "
					                                    <input id='hook' type='checkbox' data-app='hook' class='appInput appToggle'/>
					                                </label>
					                            </div>
					                            <div class='form-group' id='hookGroup'>
						                            <div class='togglebutton'>
						                                <label for='hookSplit' class='appLabel checkLabel'>" . $lang["uiSettingSeparateHookUrl"] . "
						                                    <input id='hookSplit' type='checkbox' class='appInput appToggle'/>
						                                </label>
						                            </div>
						                            <div class='form-group'>
					                                    <label for='hookUrl' class='appLabel'>" . $lang["uiSettingHookUrlGeneral"] . "
					                                        <input id='hookUrl' class='appInput form-control Webhooks' type='text' value='" . $_SESSION['hookUrl'] . "'/>
					                                        <span class='bmd-help'>" . $lang["uiSettingHookPlayHint"] . "</span>
					                                    </label>
					                                </div>
					                                <div class='togglebutton'>
						                                <label for='hookPlay' class='appLabel checkLabel'>" . $lang["uiSettingHookPlayback"] . "
						                                    <input id='hookPlay' type='checkbox' data-app='hookPlay' class='appInput appToggle'/>
						                                </label>
						                            </div>
						                            <div class='hookLabel' id='hookPlayGroup'>
						                                <div class='form-group urlGroup hookSplitGroup'>
						                                    <label for='hookPlayUrl' class='appLabel'>" . $lang["uiSettingHookGeneric"] . "
						                                        <input id='hookPlayUrl' class='appInput form-control Webhooks' type='text' value='" . $_SESSION['hookPlayUrl'] . "'/>
						                                        <span class='bmd-help'>" . $lang["uiSettingHookPlayHint"] . "</span>
						                                    </label>
						                                </div>
					                                </div>
					                                <div class='togglebutton'>
						                                <label for='hookPause' class='appLabel checkLabel'>" . $lang["uiSettingHookPause"] . "
						                                    <input id='hookPause' type='checkbox' data-app='hookPause' class='appInput appToggle'/>
						                                </label>
						                            </div>
						                            <div class='hookLabel' id='hookPauseGroup'>
						                                <div class='form-group urlGroup hookSplitGroup'>
						                                    <label for='hookPauseUrl' class='appLabel'>" . $lang["uiSettingHookGeneric"] . "
						                                        <input id='hookPauseUrl' class='appInput form-control Webhooks' type='text' value='" . $_SESSION['hookPauseUrl'] . "'/>
						                                    </label>
						                                </div>
					                                </div>
					                                <div class='togglebutton'>
						                                <label for='hookStop' class='appLabel checkLabel'>" . $lang["uiSettingHookStop"] . "
						                                    <input id='hookStop' type='checkbox' data-app='hookStop' class='appInput appToggle'>
						                                </label>
						                            </div>
						                            <div class='hookLabel' id='hookStopGroup'>
						                                <div class='form-group urlGroup hookSplitGroup'>
						                                    <label for='hookStopUrl' class='appLabel'>" . $lang["uiSettingHookGeneric"] . " 
						                                        <input id='hookStopUrl' class='appInput form-control Webhooks' type='text' value='" . $_SESSION['hookStopUrl'] . "'/>
						                                    </label>
						                                </div>
					                                </div>
					                                <div class='togglebutton'>
						                                <label for='hookFetch' class='appLabel checkLabel'>" . $lang["uiSettingHookFetch"] . "
						                                    <input id='hookFetch' type='checkbox' class='appInput appToggle hookToggle'/>
						                                </label>
						                            </div>
						                            <div class='hookLabel' id='hookFetchGroup'>
						                                <div class='form-group urlGroup hookSplitGroup'>
						                                    <label for='hookFetchUrl' class='appLabel'>" . $lang["uiSettingHookGeneric"] . "
						                                        <input id='hookFetchUrl' class='appInput form-control Webhooks' type='text' value='" . $_SESSION['hookFetchUrl'] . "'/>
						                                    </label>
						                                </div>
					                                </div>
					                                <div class='togglebutton'>
						                                <label for='hookCustom' class='appLabel checkLabel'>" . $lang["uiSettingHookCustom"] . "
						                                    <input id='hookCustom' type='checkbox' data-app='hookCustom' class='appInput appToggle'/>
						                                </label>
						                            </div>
					                                <div class='form-group hookSplitGroup'>
						                                <div class='hookLabel' id='hookCustomGroup'>
						                                    <label for='hookCustomUrl' class='appLabel'>" . $lang["uiSettingHookGeneric"] . "
						                                        <input id='hookCustomUrl' class='appInput form-control Webhooks' type='text' value='" . $_SESSION['hookCustomUrl'] . "'/>
						                                    </label>
					                                    </div>
					                                    <label for='hookCustomPhrase' class='appLabel'>" . $lang["uiSettingHookCustomPhrase"] . "
					                                        <input id='hookCustomPhrase' class='appInput form-control Webhooks' type='text' value='" . $_SESSION['hookCustomPhrase'] . "'/>
					                                    </label>
					                                    <label for='hookCustomReply' class='appLabel'>" . $lang["uiSettingHookCustomResponse"] . "
					                                        <input id='hookCustomReply' class='appInput form-control Webhooks' type='text' value='" . $_SESSION['hookCustomReply'] . "'/>
					                                    </label>
					                                </div>
					                                <div class='text-center'>
					                                    <div class='form-group btn-group'>
					                                        <button value='Webhooks' class='testInput btn btn-raised btn-info' type='button'>" . $lang["uiSettingBtnTest"] . "</button>
					                                        <button id='resetCouch' value='Webhooks' class='resetInput btn btn-raised btn-danger btn-100' type='button'>" . $lang["uiSettingBtnReset"] . "</button>
					                                    </div>
					                                </div>
					                            </div>
					                        </div>
					                    </div>
					                </div>
					            </div>
			                </div>
			                <div class='tab-pane fade' id='plexSettingsTab' role='tabpanel'>
			                    <div class='modal-body'>
			                    <div class='userGroup'>
				                        <div class='userWrap row justify-content-center'>
				                        	<img class='avatar col-xs-3' src='" . $_SESSION['plexAvatar'] . "'/>
						                    <div class='col-xs-9'>
							                    <h4 class='userHeader'>" . ucfirst($_SESSION['plexUserName']) . "</h4>
							                    <hab>" . $_SESSION['plexEmail'] . "</hab>
						                    </div>
					                    </div>
					                </div>
			                        <div>
                                        <div class='appContainer card'>
	                                        <div class='card-body'>
					                            <h4 class='cardHeader'>" . $lang["uiSettingGeneral"] . "</h4>
				                                <div class='form-group'>
				                                    <label class='appLabel' for='serverList'>" . $lang["uiSettingPlaybackServer"] . "</label>
				                                    <select class='form-control custom-select serverList' id='serverList' title='".$lang['uiSettingPlaybackServerHint']."'>
				                                    
				                                    </select>
				                                    
			                                    </div>
			                                    <div class='form-group'>
					                                <div class='form-group'>
					                                    <label for='returnItems' class='appLabel'>" . $lang["uiSettingOndeckRecent"] . "
					                                        <input id='returnItems' class='appInput form-control' type='number' min='1' max='20' value='" . $_SESSION['returnItems'] . "' />
					                                    </label>
					                                </div>
					                            </div>
					                        </div>
					                    </div>
					                    <div class='appContainer card' id='dvrGroup'>
					                        <div class='card-body'>
					                            <h4 class='cardHeader'>" . $lang["uiSettingPlexDVR"] . "</h4>
					                            <div class='form-group'>
					                                <div class='form-group'>
					                                    <label class='appLabel serverList' for='dvrList'>" . $lang["uiSettingDvrServer"] . "</label>
					                                    <select class='form-control custom-select' id='dvrList'>
					                                    </select>
					                                </div>
					                                <div class='form-group'>
					                                    <label class='appLabel' for='resolution'>" . $lang["uiSettingDvrResolution"] . "</label>
					                                    <select class='form-control appInput' id='plexDvrResolution'>
					                                        <option value='0' " . ($_SESSION['plexDvrResolution'] == 0 ? 'selected' : '') . " >" . $lang["uiSettingDvrResolutionAny"] . "</option>
					                                        <option value='720' " . ($_SESSION['plexDvrResolution'] == 720 ? 'selected' : '') . " >" . $lang["uiSettingDvrResolutionHD"] . "</option>
					                                    </select>
					                                </div>
					                                <div class='form-group'>
						                                <div class='togglebutton'>
						                                    <label for='plexDvrNewAirings' class='appLabel checkLabel'>" . $lang["uiSettingDvrNewAirings"] . "
						                                        <input id='plexDvrNewAirings' type='checkbox' class='appInput' " . ($_SESSION['plexDvrNewAirings'] ? 'checked' : '') . " />
						                                    </label>
						                                </div>
						                                <div class='togglebutton'>
						                                    <label for='plexDvrReplaceLower' class='appLabel checkLabel'>" . $lang["uiSettingDvrReplaceLower"] . "
						                                        <input id='plexDvrReplaceLower' type='checkbox' class='appInput' " . ($_SESSION['plexDvrReplaceLower'] ? ' checked ' : '') . " />
						                                    </label>
						                                </div>
						                                <div class='togglebutton'>
						                                    <label for='plexDvrRecordPartials' class='appLabel checkLabel'>" . $lang["uiSettingDvrRecordPartials"] . "
						                                        <input id='plexDvrRecordPartials' type='checkbox' class='appInput' " . ($_SESSION['plexDvrRecordPartials'] ? 'checked' : '') . " />
						                                    </label>
					                                    </div>
					                                    <div class='togglebutton'>
						                                    <label for='plexDvrComskipEnabled' class='appLabel checkLabel'>" . $lang["uiSettingDvrComskipEnabled"] . "
						                                        <input id='plexDvrComskipEnabled' type='checkbox' class='appInput' " . ($_SESSION['plexDvrComskipEnabled'] ? 'checked' : '') . " />
						                                    </label>
					                                    </div>
				                                    </div>
					                                <div class='form-group'>
					                                    <label for='plexDvrStartOffsetMinutes' class='appLabel'>" . $lang["uiSettingDvrStartOffset"] . "
					                                        <input id='plexDvrStartOffsetMinutes' class='appInput form-control' type='number' min='1' max='30' value='" . $_SESSION['plexDvrStartOffsetMinutes'] . "' />
					                                    </label>
					                                </div>
					                                <div class='form-group'>
					                                    <label for='plexDvrEndOffsetMinutes' class='appLabel'>" . $lang["uiSettingDvrEndOffset"] . "
					                                        <input id='plexDvrEndOffsetMinutes' class='appInput form-control' type='number' min='1' max='30' value='" . $_SESSION['plexDvrEndOffsetMinutes'] . "' />
					                                    </label>
					                                </div>
					
					                            </div>
					                        </div>
							                
										</div>
									</div>
			                    </div>
			                </div> 
			                <div class='tab-pane fade' id='movieFetcherSettingsTab' role='tabpanel'>
			                    <div class='modal-body' id='fetcherBody'>
			                    	<div class='appContainer card'>
				                        <div class='card-body'>
				                            <h4 class='cardHeader'>CouchPotato</h4>
				                            <div class='togglebutton'>
				                                <label for='couch' class='appLabel checkLabel'>" . $lang["uiSettingEnable"] . "
				                                    <input id='couch' type='checkbox' data-app='couch' class='appInput appToggle'/>
				                                </label>
				                            </div>
				                            <div class='form-group' id='couchGroup'>
				                                <div class='form-group'>
				                                    <label for='couchUri' class='appLabel'>Couchpotato URI:
				                                        <input id='couchUri' class='appInput form-control CouchPotato appParam' type='text' value='" . $_SESSION['couchUri'] . "'/>
				                                    </label>
				                                </div>
				                                 <div class='form-group'>
				                                    <label for='couchToken' class='appLabel'>Couchpotato " . $lang["uiSettingFetcherToken"] . ":
				                                        <input id='couchToken' class='appInput form-control CouchPotato appParam' type='text' value='" . $_SESSION['couchToken'] . "'/>
				                                    </label>
				                                </div>
				                                <div class='form-group'>
				                                    <label class='appLabel' for='couchList'>" . $lang["uiSettingFetcherQualityProfile"] . ":</label>
				                                    <select class='form-control profileList' id='couchList'>
														" . fetchList('couch') . "
				                                    </select>
				                                </div>
				                                <div class='text-center'>
				                                    <div class='form-group btn-group'>
				                                        <button value='Couch' class='testInput btn btn-raised btn-info'>" . $lang["uiSettingBtnTest"] . "</button>
				                                        <button id='resetCouch' value='Couch' class='resetInput btn btn-raised btn-danger btn-100'>" . $lang["uiSettingBtnReset"] . "</button>
				                                    </div>
				                                </div>
				                            </div>
				                        </div>
				                    </div>
				                    <div class='appContainer card'>
				                        <div class='card-body'>
				                            <h4 class='cardHeader'>Radarr</h4>
				                            <div class='togglebutton'>
				                                <label for='radarr' class='appLabel checkLabel'>" . $lang["uiSettingEnable"] . "
				                                    <input id='radarr' type='checkbox' data-app='radarr' class='appInput appToggle'/>
				                                </label>
				                            </div>
				                            <div class='form-group' id='radarrGroup'>
				                                <div class='form-group'>
				                                    <label for='radarrUri' class='appLabel'>Radarr URI:
				                                        <input id='radarrUri' class='appInput form-control Radarr appParam' type='text' value='" . $_SESSION['radarrUri'] . "'/>
				                                    </label>
				                                </div>
				                                <div class='form-group'>
				                                    <label for='radarrToken' class='appLabel'>Radarr " . $lang["uiSettingFetcherToken"] . ":
				                                        <input id='radarrToken' class='appInput form-control Radarr appParam' type='text' value='" . $_SESSION['radarrToken'] . "'/>
				                                    </label>
				                                </div>
				                                <div class='form-group'>
				                                    <label class='appLabel' for='radarrList'>" . $lang["uiSettingFetcherQualityProfile"] . ":</label>
				                                    <select class='form-control appInput profileList' id='radarrList'>
				                                        " . fetchList('radarr') . "
				                                    </select>
				                                </div>
				                                <div class='text-center'>
				                                    <div class='form-group btn-group'>
				                                        <button value='Radarr' class='testInput btn btn-raised btn-info btn-100' type='button'>" . $lang["uiSettingBtnTest"] . "</button>
				                                        <button id='resetRadarr' value='Radarr' class='resetInput btn btn-raised btn-danger btn-100' type='button'>" . $lang["uiSettingBtnReset"] . "</button>
				                                    </div>
				                                </div>
				                            </div>
				                        </div>
				                    </div>
				                    <div class='appContainer card'>
				                        <div class='card-body'>
				                            <h4 class='cardHeader'>Watcher</h4>
				                            <div class='togglebutton'>
				                                <label for='watcher' class='appLabel checkLabel'>" . $lang["uiSettingEnable"] . "
				                                    <input id='watcher' type='checkbox' data-app='watcher' class='appInput appToggle'/>
				                                </label>
				                            </div>
				                            <div class='form-group' id='watcherGroup'>
				                                <div class='form-group'>
				                                    <label for='watcherUri' class='appLabel'>Watcher URI:
				                                        <input id='watcherUri' class='appInput form-control Watcher appParam' type='text' value='" . $_SESSION['watcherUri'] . "'/>
				                                    </label>
				                                </div>
				                                <div class='form-group'>
				                                    <label for='watcherToken' class='appLabel'>Watcher " . $lang["uiSettingFetcherToken"] . ":
				                                        <input id='watcherToken' class='appInput form-control Watcher appParam' type='text' value='" . $_SESSION['watcherToken'] . "'/>
				                                    </label>
				                                </div>
				                                <div class='form-group'>
				                                    <label class='appLabel' for='watcherList'>" . $lang["uiSettingFetcherQualityProfile"] . ":</label>
				                                    <select class='form-control appInput profileList' id='watcherList'>
				                                        " . fetchList('watcher') . "
				                                    </select>
				                                </div>
				                                <div class='text-center'>
				                                    <div class='form-group btn-group'>
				                                        <button value='Watcher' class='testInput btn btn-raised btn-info btn-100' type='button'>" . $lang["uiSettingBtnTest"] . "</button>
				                                        <button id='resetWatcher' value='Watcher' class='resetInput btn btn-raised btn-danger btn-100' type='button'>" . $lang["uiSettingBtnReset"] . "</button>
				                                    </div>
				                                </div>
				                            </div>
				                        </div>
				                    </div>
				                    <div class='appContainer card ombiGroup'>
				                        <div class='card-body'>
				                            <h4 class='cardHeader'>Ombi</h4>
				                            <div class='togglebutton'>
				                                <label for='ombi' class='appLabel checkLabel'>" . $lang["uiSettingEnable"] . "
				                                    <input id='ombi' type='checkbox' data-app='ombi' class='appInput appToggle'/>
				                                </label>
				                            </div>
				                            <div class='form-group' id='ombiGroup'>
				                                <div class='form-group'>
				                                    <label for='ombiUrl' class='appLabel'>Ombi URI:
				                                        <input id='ombiUrl' class='appInput form-control ombiUrl appParam' type='text'  value='" . $_SESSION['ombiIP'] . "' />
				                                    </label>
				                                </div>
				                                 <div class='form-group'>
				                                    <label for='ombiAuth' class='appLabel'>Ombi " . $lang["uiSettingFetcherToken"] . ":
				                                        <input id='ombiAuth' class='appInput form-control Ombi appParam' type='text' value='" . $_SESSION['ombiAuth'] . "'/>
				                                    </label>
				                                </div>
				                                <div class='text-center'>
				                                    <div class='form-group btn-group'>
				                                        <button value='Ombi' class='testInput btn btn-raised btn-info btn-100' type='button'>" . $lang["uiSettingBtnTest"] . "</button>
				                                        <button id='resetOmbi' value='Ombi' class='resetInput btn btn-raised btn-danger btn-100' type='button'>" . $lang["uiSettingBtnReset"] . "</button>
				                                    </div>
				                                </div>
				                            </div>
				                        </div>
				                    </div>			                   
			                    </div>
			                </div>
			                
			                <div class='tab-pane fade' id='showFetcherSettingsTab' role='tabpanel'>
			                    <div class='modal-body' id='fetcherBody'>
		                            <div class='appContainer card'>
				                        <div class='card-body'>
				                            <h4 class='cardHeader'>Sickbeard/SickRage</h4>
				                            <div class='togglebutton'>
				                                <label for='sick' class='appLabel checkLabel'>" . $lang["uiSettingEnable"] . "
				                                    <input id='sick' type='checkbox' data-app='sick' class='appInput appToggle'/>
				                                </label>
				                            </div>
				                            <div class='form-group' id='sickGroup'>
				                                <div class='form-group'>
				                                    <label for='sickUri' class='appLabel'>Sick URI:
				                                        <input id='sickUri' class='appInput form-control Sick appParam' type='text' value='" . $_SESSION['sickUri'] . "'/>
				                                    </label>
				                                </div>
				                                <div class='form-group'>
				                                    <label for='sickToken' class='appLabel'>Sick " . $lang["uiSettingFetcherToken"] . ":
				                                        <input id='sickToken' class='appInput form-control Sick appParam' type='text' value='" . $_SESSION['sickToken'] . "'/>
				                                    </label>
				                                </div>
				                                <div class='form-group'>
				                                    <label class='appLabel' for='sickList'>" . $lang["uiSettingFetcherQualityProfile"] . ":</label>
				                                    <select class='form-control appInput profileList' id='sickList'>
				                                        " . fetchList('sick') . "
				                                    </select>
				                                </div>
				                                <div class='text-center'>
				                                    <div class='form-group btn-group'>
				                                        <button value='Sick' class='testInput btn btn-raised btn-info btn-100' type='button'>" . $lang["uiSettingBtnTest"] . "</button>
				                                        <button id='resetSick' value='Sick' class='resetInput btn btn-raised btn-danger btn-100' type='button'>" . $lang["uiSettingBtnReset"] . "</button>
				                                    </div>
				                                </div>
				                            </div>
				                        </div>
				                    </div>
				                    <div class='appContainer card'>
				                        <div class='card-body'>
				                            <h4 class='cardHeader'>Sonarr</h4>
				                            <div class='togglebutton'>
				                                <label for='sonarr' class='appLabel checkLabel'>" . $lang["uiSettingEnable"] . "
				                                    <input id='sonarr' type='checkbox' data-app='sonarr' class='appInput appToggle'/>
				                                </label>
				                            </div>
				                            <div class='form-group' id='sonarrGroup'>
				                                <div class='form-group'>
				                                    <label for='sonarrUri' class='appLabel'>Sonarr URI:
				                                        <input id='sonarrUri' class='appInput form-control Sonarr appParam' type='text' value='" . $_SESSION['sonarrUri'] . "'/>
				                                    </label>
				                                </div>
				                                <div class='form-group'>
				                                    <label for='sonarrToken' class='appLabel'>Sonarr " . $lang["uiSettingFetcherToken"] . ":
				                                        <input id='sonarrToken' class='appInput form-control Sonarr appParam' type='text' value='" . $_SESSION['sonarrToken'] . "'/>
				                                    </label>
				                                </div>
				                                <div class='form-group'>
				                                    <label class='appLabel' for='sonarrList'>" . $lang["uiSettingFetcherQualityProfile"] . ":</label>
				                                    <select class='form-control profileList' id='sonarrList'>
				                                        " . fetchList('sonarr') . "
				                                    </select>
				                                </div>
				                                <div class='text-center'>
				                                    <div class='form-group btn-group'>
				                                        <button value='Sonarr' class='testInput btn btn-raised btn-info btn-100' type='button'>" . $lang["uiSettingBtnTest"] . "</button>
				                                        <button id='resetSonarr' value='Sonarr' class='resetInput btn btn-raised btn-danger btn-100' type='button'>" . $lang["uiSettingBtnReset"] . "</button>
				                                    </div>
				                                </div>
				                            </div>
				                        </div>
				                    </div>
				                </div>
			                </div>
			                <div class='tab-pane fade' id='musicFetcherSettingsTab' role='tabpanel'>
			                    <div class='modal-body' id='fetcherBody'>
			                    	        <div class='appContainer card'>
				                        <div class='card-body'>
				                            <h4 class='cardHeader'>Headphones</h4>
				                            <div class='togglebutton'>
				                                <label for='headphones' class='appLabel checkLabel'>" . $lang["uiSettingEnable"] . "
				                                    <input id='headphones' data-app='headphones' type='checkbox' class='appInput appToggle'/>
				                                </label>
				                            </div>
				                            <div class='form-group' id='headphonesGroup'>
				                                <div class='form-group'>
				                                    <label for='headphonesUri' class='appLabel'>Headphones URI:
				                                        <input id='headphonesUri' class='appInput form-control Headphones appParam' type='text' value='" . $_SESSION['headphonesUri'] . "'/>
				                                    </label>
				                                </div>
				                                <div class='form-group'>
				                                    <label for='headphonesToken' class='appLabel'>Headphones " . $lang["uiSettingFetcherToken"] . ":
				                                        <input id='headphonesToken' class='appInput form-control Headphones appParam' type='text' value='" . $_SESSION['headphonesToken'] . "'/>
				                                    </label>
				                                </div>
				                                <div class='text-center'>
				                                    <div class='form-group btn-group'>
				                                        <button value='Headphones' class='testInput btn btn-raised btn-info btn-100' type='button'>" . $lang["uiSettingBtnTest"] . "</button>
				                                        <button id='resetHeadphones' value='Headphones' class='resetInput btn btn-raised btn-danger btn-100' type='button'>" . $lang["uiSettingBtnReset"] . "</button>
				                                    </div>
				                                </div>
				                            </div>
				                        </div>
				                    </div>
				                    <div class='appContainer card'>
				                        <div class='card-body'>
				                            <h4 class='cardHeader'>Lidarr</h4>
				                            <div class='togglebutton'>
				                                <label for='lidarr' class='appLabel checkLabel'>" . $lang["uiSettingEnable"] . "
				                                    <input id='lidarr' type='checkbox' data-app='lidarr' class='appInput appToggle'/>
				                                </label>
				                            </div>
				                            <div class='form-group' id='lidarrGroup'>
				                                <div class='form-group'>
				                                    <label for='lidarrUri' class='appLabel'>Lidarr URI:
				                                        <input id='lidarrUri' class='appInput form-control Lidarr appParam' type='text' value='" . $_SESSION['lidarrUri'] . "'/>
				                                    </label>
				                                </div>
                                                <div class='form-group'>
				                                    <label for='lidarrToken' class='appLabel'>Lidarr " . $lang["uiSettingFetcherToken"] . ":
				                                        <input id='lidarrToken' class='appInput form-control Lidarr appParam' type='text' value='" . $_SESSION['lidarrToken'] . "'/>
				                                    </label>
				                                </div>
				                                <div class='form-group'>
				                                    <label class='appLabel' for='lidarrList'>" . $lang["uiSettingFetcherQualityProfile"] . ":</label>
				                                    <select class='form-control profileList' id='lidarrList'>
				                                        " . fetchList('lidarr') . "
				                                    </select>
				                                </div>
				                                <div class='text-center'>
				                                    <div class='form-group btn-group'>
				                                        <button value='Lidarr' class='testInput btn btn-raised btn-info btn-100' type='button'>" . $lang["uiSettingBtnTest"] . "</button>
				                                        <button id='resetLidarr' value='Lidarr' class='resetInput btn btn-raised btn-danger btn-100' type='button'>" . $lang["uiSettingBtnReset"] . "</button>
				                                    </div>
				                                </div>
				                            </div>
				                        </div>
			                    	</div>                 
			                    </div>
			                </div>
			                
			                <div class='tab-pane fade$hidden' id='logTab' role='tabpanel'>
			                    <div class='modal-header'>
				                    <div class='form-group' id='logGroup'>
			                            <label for='logLimit' class='logControl'>" . $lang["uiSettingLogCount"] . "
				                            <select id='logLimit' class='form-control'>
						                        <option value='10'>10</option>
						                        <option value='50' selected>50</option>
						                        <option value='100'>100</option>
						                        <option value='500'>500</option>
						                        <option value='1000'>1000</option>
					                        </select>
				                        </label>				                       
				                        <div id='log'>
								             <div id='logInner'>
								                 <div>
								                     <iframe class='card card-body' id='logFrame' src=''></iframe>															
								                  </div>
								             </div> 
								        </div>
				                        <a class='logbutton' href='log.php?apiToken=$apiToken' target='_blank'>
				                        	<span class='material-icons'>open_in_browser</span>
				                        </a>
			                        </div>
								</div>
							</div>
						</div>
					</div>
				</div>
						
				
		        
	            <div class='nowPlayingFooter'>
		            <div class='coverImage'>
	                    <img class='statusImage card-1' src=''/>
	                    <div id='textBar'>
			                <h6>Now Playing on <span id='playerName'></span>: </h6>
			                <h6><span id='mediaTitle'></span></h6>
			                <span id='mediaTagline'></span>
		                </div>
		            </div>
					<div class='statusWrapper row justify-content-around'>
						<div id='progressWrap'>
							<input id='progressSlider' type='text' data-slider-min='0' data-slider-id='progress' data-slider-tooltip='hide'/>
						</div>
						<div id='controlWrap'>
							<div id='controlBar'>
								<button class='controlBtn btn btn-default' id='previousBtn'><span class='material-icons mat-md'>skip_previous</span></button>
								<button class='controlBtn btn btn-default' id='playBtn'><span class='material-icons mat-lg'>play_circle_filled</span></button>
								<button class='controlBtn btn btn-default' id='pauseBtn'><span class='material-icons mat-lg'>pause_circle_filled</span></button>
								<button class='controlBtn btn btn-default' id='nextBtn'><span class='material-icons mat-md'>skip_next</span></button>
							</div>
						</div>
						<div class='scrollContainer'>
							<div class='scrollContent' id='mediaSummary'></div>
						</div>
			            <div id='volumeWrap'/>
							<div class='volumeBar'>
							</div>
						</div>
						<div id='stopBtnDiv'>
							<button class='controlBtn btn btn-default' id='stopBtn'><span class='material-icons'>close</span></button>
							<div id='volumeWrap'>
								<input id='volumeSlider' type='text' data-slider-min='0' data-slider-max='100' data-slider-id='volume' data-slider-orientation='vertical' data-slider-tooltip='hide'></input>
							</div>
						</div>
			    	</div>
			    </div>
			    
		        
			    <div class='modal fade' id='jsonModal'>
					<div class='modal-dialog' role='document'>
						<div class='modal-content'>
							<div class='modal-header'>
								<h5 class='modal-title' id='jsonTitle'>Modal title</h5>
								<button type='button' class='close' data-dismiss='modal' aria-label='Close'>
									<span aria-hidden='true'>&times;</span>
								</button>
							</div>
							<div class='modal-body' id='jsonBody'>
								<p>Modal body text goes here.</p>
							</div><div class='modal-footer'>
							<button class='btnAdd' title='Copy JSON to clipboard'>Copy JSON</button></div>
						</div>
					</div>
				</div>
				<div class='wrapperArt'>
		        
				</div>
				
		        <div class='castArt'>
			        <div class='background-container'>
			            <div class='ccWrapper'>
			                <div class='fade1 ccBackground'>
			                    <div class='ccTextDiv'>
			                        <span class='spacer'></span>
			                        <span class='tempDiv meta'></span>
			                        <div class='weatherIcon'></div>
			                        <div class='timeDiv meta'></div>
			                        <div id='metadata-line-1' class='meta'></div>
			                        <div id='metadata-line-2' class='meta'></div>
			                        <div id='metadata-line-3' class='meta'></div>
			                    </div>
			                </div>
			            </div>
			        </div>
			    </div>
				";

	return $string;
}


?>
