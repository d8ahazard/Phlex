<?php

// Make it really easy to play videos by providing functions for the Chromecast Default Media Player

require_once("CCBaseSender.php");

class CCDefaultMediaPlayer extends CCBaseSender {
	public $appid = "CC1AD845";

	public function play($url, $streamType, $contentType, $autoPlay, $currentTime) {
		// Start a playing
		// First ensure there's an instance of the DMP running
		$this->launch();
		$json = '{"type":"LOAD","media":{"contentId":"' . $url . '","streamType":"' . $streamType . '","contentType":"' . $contentType . '"},"autoplay":' . $autoPlay . ',"currentTime":' . $currentTime . ',"requestId":921489134}';
		$this->chromecast->sendMessage("urn:x-cast:com.google.cast.media", $json);
		$r = "";
		while (!preg_match("/\"playerState\":\"PLAYING\"/", $r)) {
			$r = $this->chromecast->getCastMessage();
			sleep(1);
		}
		// Grab the mediaSessionId
		preg_match("/\"mediaSessionId\":([^\,]*)/", $r, $m);
		$this->mediaid = $m[1];
	}

	public function pause() {
		// Pause
		$this->launch(); // Auto-reconnects
		$this->chromecast->sendMessage("urn:x-cast:com.google.cast.media", '{"type":"PAUSE", "mediaSessionId":' . $this->mediaid . ', "requestId":1}');
		$this->chromecast->getCastMessage();
	}

	public function restart() {
		// Restart (after pause)
		$this->launch(); // Auto-reconnects
		$this->chromecast->sendMessage("urn:x-cast:com.google.cast.media", '{"type":"PLAY", "mediaSessionId":' . $this->mediaid . ', "requestId":1}');
		$this->chromecast->getCastMessage();
	}

	public function seek($secs) {
		// Seek
		$this->launch(); // Auto-reconnects
		$this->chromecast->sendMessage("urn:x-cast:com.google.cast.media", '{"type":"SEEK", "mediaSessionId":' . $this->mediaid . ', "currentTime":' . $secs . ',"requestId":1}');
		$this->chromecast->getCastMessage();
	}

	public function stop() {
		// Stop
		$this->launch(); // Auto-reconnects
		$this->chromecast->sendMessage("urn:x-cast:com.google.cast.media", '{"type":"STOP", "mediaSessionId":' . $this->mediaid . ', "requestId":1}');
		$this->chromecast->getCastMessage();
	}

	public function getStatus() {
		// Stop
		$this->launch(); // Auto-reconnects
		$this->chromecast->sendMessage("urn:x-cast:com.google.cast.media", '{"type":"GET_STATUS", "mediaSessionId":' . $this->mediaid . ', "requestId":1}');
		$r = $this->chromecast->getCastMessage();
		preg_match("/{\"type.*/", $r, $m);
		return json_decode($m[0]);
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
		$this->chromecast->getCastMessage();
	}
}

?>