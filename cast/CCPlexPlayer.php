<?php

// Make it really easy to play videos by providing functions for the Plex Default Media Player
// Based off the CCDefaultMediaPlayer (as they seem to share functions

require_once("CCBaseSender.php");

class CCPlexPlayer extends CCBaseSender
{
	public $appid="9AC194DC";
	
	public function play($json) {
		// Start a playing
		// First ensure there's an instance of the DMP running
		$this->launch();
                
		$this->chromecast->sendMessage("urn:x-cast:com.google.cast.media", $json);
		$r = "";
		while (!preg_match("/\"playerState\":\"PAUSED\"/",$r)) {
			$r = $this->chromecast->getCastMessage();
                        //echo $r . "\n";
			sleep(1);
		}
		// Grab the mediaSessionId
		preg_match("/\"mediaSessionId\":([^\,]*)/",$r,$m);
		$this->mediaid = $m[1];
                $this->restart();
	}
        
	public function launch() {
		// Launch the player or connect to an existing instance if one is already running
		// First connect to the chromecast
		$this->chromecast->transportid = "";
		$this->chromecast->cc_connect();
		$s = $this->chromecast->getStatus();
		// Grab the appid
		preg_match("/\"appId\":\"([^\"]*)/",$s,$m);
		$appid = $m[1];
		if ($appid == $this->appid) {
			// Default Media Receiver is live
			$this->chromecast->getStatus();
			$this->chromecast->connect();
		} else {
			// Default Media Receiver is not currently live, start it.
			$this->chromecast->launch($this->appid);
			$this->chromecast->transportid = "";
			$r = "";
			while (!preg_match("/Plex/",$r) && !preg_match("/Default Media Receiver/",$r)) {
				$r = $this->chromecast->getStatus();
                                echo $r . "\n";
				sleep(1);
			}
			$this->chromecast->connect();
		}
	}
	
	public function pause() {
		// Pause
		$this->launch(); // Auto-reconnects
                $this->chromecast->sendMessage("urn:x-cast:plex",'{"type":"PAUSE"}');
	}

	public function restart() {
		// Restart (after pause)
		$this->launch(); // Auto-reconnects
                $this->chromecast->sendMessage("urn:x-cast:plex",'{"type":"PLAY"}');
	}
        
        public function stepForward() {
                $this->launch();
                $this->chromecast->sendMessage("urn:x-cast:plex",'{"type":"STEPFORWARD"}');
        }
	
	public function stop() {
		// Stop
		$this->launch(); // Auto-reconnects
                $this->chromecast->sendMessage("urn:x-cast:plex",'{"type":"STOP"}');
		//$this->chromecast->getCastMessage();
	}
	
        public function skipBack() {
                $this->launch();
                $this->chromecast->sendMessage("urn:x-cast:plex",'{"type":"PREVIOUS"}');
        }
        
        public function skipForward() {
                $this->launch();
                $this->chromecast->sendMessage("urn:x-cast:plex",'{"type":"NEXT"}');
        }
        
	public function Mute() {
		// Mute a video
		$this->launch(); // Auto-reconnects
		$this->chromecast->sendMessage("urn:x-cast:com.google.cast.receiver", '{"type":"SET_VOLUME", "volume": { "muted": true }, "requestId":1 }');
		$this->chromecast->getCastMessage();
	}
	
	public function UnMute() {
		// Mute a video
		$this->launch(); // Auto-reconnects
		$this->chromecast->sendMessage("urn:x-cast:com.google.cast.receiver", '{"type":"SET_VOLUME", "volume": { "muted": false }, "requestId":1 }');
		$this->chromecast->getCastMessage();
	}
	
	public function SetVolume($volume) {
		// Mute a video
		$this->launch(); // Auto-reconnects
		$this->chromecast->sendMessage("urn:x-cast:com.google.cast.receiver", '{"type":"SET_VOLUME", "volume": { "level": ' . $volume . ' }, "requestId":1 }');
		//$this->chromecast->getCastMessage();
	}
        
	public function getStatus() {
		// Stop
		$this->chromecast->sendMessage("urn:x-cast:com.google.cast.media",'{"type":"GET_STATUS", "mediaSessionId":' . $this->mediaid . ', "requestId":1}');
		$r = $this->chromecast->getCastMessage();
		preg_match("/{\"type.*/",$r,$m);
		return json_decode($m[0]);
	}
	
}

?>