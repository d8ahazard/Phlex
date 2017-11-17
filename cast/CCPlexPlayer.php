<?php

// Make it really easy to play videos by providing functions for the Plex Default Media Player
// Based off the CCDefaultMediaPlayer (as they seem to share functions

require_once("CCBaseSender.php");

class CCPlexPlayer extends CCBaseSender {
	public $appid = "9AC194DC";

	public function play($json) {
		write_log("Function fired.");
		// Start a playing
		// First ensure there's an instance of the DMP running
		write_log("Got a play command!");
		$this->launch();

		$this->chromecast->sendMessage("urn:x-cast:com.google.cast.media", $json);
		$r = "";
		$count = 0;
		while ((!preg_match("/\"playerState\":\"PAUSED\"/", $r)) && ($count < $this->chromecast->breakout * 2)) {
			$r = $this->chromecast->getCastMessage();
			write_log("R is " . $r);
			sleep(1);
			$count++;
		}
		// Grab the mediaSessionId
		preg_match("/\"mediaSessionId\":([^\,]*)/", $r, $m);
		$this->mediaid = $m[1];
		$this->restart();
	}

	public function launch() {
		write_log("Function fired.");
		// Launch the player or connect to an existing instance if one is already running
		// First connect to the chromecast
		write_log("Launching.");
		$this->chromecast->transportid = "";
		$this->chromecast->cc_connect(0);
		write_log("TransportID: " . $this->chromecast->transportid);
		// This never returns
		write_log("Gettingstatus 1");
		$s = $this->chromecast->getStatus();
		// Grab the appid
		preg_match("/\"appId\":\"([^\"]*)/", $s, $m);
		$appid = $m[1];
		if ($appid == $this->appid) {
			// Default Media Receiver is live
			$this->chromecast->getStatus();
			write_log("Gettingstatus 2");
			write_log($this->chromecast->transportid);
			$this->chromecast->connect(0);
		} else {
			// Default Media Receiver is not currently live, start it.
			$this->chromecast->launch($this->appid);
			$this->chromecast->transportid = "";
			$r = "";
			$count = 0;
			while ((!preg_match("/Plex/", $r) && !preg_match("/Default Media Receiver/", $r)) && ($count <= $this->chromecast->breakout)) {
				$r = $this->chromecast->getStatus();
				write_log("Gettingstatus 3");
				$count++;
			}
			$this->chromecast->connect(0);
		}
	}

	public function pause() {
		write_log("Function fired.");
		// Pause
		$this->launch(); // Auto-reconnects
		$this->chromecast->sendMessage("urn:x-cast:plex", '{"type":"PAUSE"}');
	}

	public function restart() {
		write_log("Function fired.");
		// Restart (after pause)
		$this->launch(); // Auto-reconnects
		$this->chromecast->sendMessage("urn:x-cast:plex", '{"type":"PLAY"}');
	}

	public function stepForward() {
		write_log("Function fired.");
		$this->launch();
		$this->chromecast->sendMessage("urn:x-cast:plex", '{"type":"STEPFORWARD"}');
	}

	public function stop() {
		write_log("Function fired.");
		// Stop
		$this->launch(); // Auto-reconnects
		$this->chromecast->sendMessage("urn:x-cast:plex", '{"type":"STOP"}');
		//$this->chromecast->getCastMessage();
	}

	public function skipBack() {
		write_log("Function fired.");
		$this->launch();
		$this->chromecast->sendMessage("urn:x-cast:plex", '{"type":"PREVIOUS"}');
	}

	public function skipForward() {
		write_log("Function fired.");
		$this->launch();
		$this->chromecast->sendMessage("urn:x-cast:plex", '{"type":"NEXT"}');
	}

	public function plexStatus() {
		write_log("Function fired.");
		$this->launch();
		$this->chromecast->sendMessage("urn:x-cast:com.google.cast.media", '{"type":"GET_STATUS", "requestId":1}');
		$r = $this->chromecast->getCastMessage();
		$r = substr($r, strpos($r, '{"type":'), 500000);
		return $r;
	}

	public function Mute() {
		write_log("Function fired.");
		// Mute a video
		$this->launch(); // Auto-reconnects
		$this->chromecast->sendMessage("urn:x-cast:com.google.cast.receiver", '{"type":"SET_VOLUME", "volume": { "muted": true }, "requestId":1 }');
		$this->chromecast->getCastMessage();
	}

	public function UnMute() {
		write_log("Function fired.");
		// Mute a video
		$this->launch(); // Auto-reconnects
		$this->chromecast->sendMessage("urn:x-cast:com.google.cast.receiver", '{"type":"SET_VOLUME", "volume": { "muted": false }, "requestId":1 }');
		$this->chromecast->getCastMessage();
	}

	public function SetVolume($volume) {
		write_log("Function fired.");
		// Mute a video
		$this->launch(); // Auto-reconnects
		$this->chromecast->sendMessage("urn:x-cast:com.google.cast.receiver", '{"type":"SET_VOLUME", "volume": { "level": ' . $volume . ' }, "requestId":1 }');
		//$this->chromecast->getCastMessage();
	}

	public function getStatus() {
		write_log("Function fired.");
		// Stop
		$this->chromecast->sendMessage("urn:x-cast:com.google.cast.media", '{"type":"GET_STATUS", "mediaSessionId":' . $this->mediaid . ', "requestId":1}');
		$r = $this->chromecast->getCastMessage();
		preg_match("/{\"type.*/", $r, $m);
		return json_decode($m[0]);
	}

}

?>