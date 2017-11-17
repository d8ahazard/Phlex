<?php

// Chris Ridings
// www.chrisridings.com

require_once("CCprotoBuf.php");
require_once("CCDefaultMediaPlayer.php");
require_once("CCPlexPlayer.php");
require_once dirname(__FILE__) . '/../util.php';
require_once("mdns.php");

class Chromecast {

	// Sends a picture or a video to a Chromecast using reverse
	// engineered castV2 protocol

	public $socket; // Socket to the Chromecast
	public $requestId = 1; // Incrementing request ID parameter
	public $transportid = ""; // The transportid of our connection
	public $sessionid = ""; // Session id for any media sessions
	public $DMP; // Represents an instance of the Default Media Player.
	public $Plex; // Represents an instance of the Plex player
	public $lastip = ""; // Store the last connected IP
	public $lastport; // Store the last connected port
	public $lastactivetime; // store the time we last did something
	public $breakout; // A limiter for while loops

	public function __construct($ip, $port, $breakout = 5) {
		write_log("Function fired.");
		// Establish Chromecast connection

		// Don't pay much attention to the Chromecast's certificate. 
		// It'll be for the wrong host address anyway if we 
		// use port forwarding!
		$contextOptions = ['ssl' => ['verify_peer' => false, 'verify_peer_name' => false,]];
		$context = stream_context_create($contextOptions);

		if ($this->socket = stream_socket_client('ssl://' . $ip . ":" . $port, $errno, $errstr, $breakout, STREAM_CLIENT_CONNECT, $context)) {
		} else {
			write_log("Failed to connect to remote Chromecast: " . $errstr, "ERROR");
			die();
		}

		$this->lastip = $ip;
		$this->lastport = $port;
		$this->breakout = $breakout;

		$this->lastactivetime = time();

		// Create an instance of the DMP for this CCDefaultMediaPlayer
		$this->DMP = new CCDefaultMediaPlayer($this);
		$this->Plex = new CCPlexPlayer($this);
	}

	public static function scan($wait = 15) {
		write_log("Function fired.");
		// Wrapper for scan
		$result = Chromecast::scansub($wait);
		return $result;
	}

	public static function scansub($wait = 15) {
		write_log("Function fired.");
		// Performs an mdns scan of the network to find chromecasts and returns an array
		// Let's test by finding Google Chromecasts
		$mdns = new mDNS();
		// Search for chromecast devices
		// For a bit more surety, send multiple search requests
		$firstresponsetime = -1;
		$lastpackettime = -1;
		$starttime = round(microtime(true) * 1000);
		$mdns->query("_googlecast._tcp.local", 1, 12, "");
		$mdns->query("_googlecast._tcp.local", 1, 12, "");
		$mdns->query("_googlecast._tcp.local", 1, 12, "");
		$cc = $wait;
		$filetoget = 1;
		$dontrequery = 0;
		set_time_limit($wait * 2);
		$chromecasts = [];
		while ($cc > 0) {
			$inpacket = "";
			while ($inpacket == "") {
				$inpacket = $mdns->readIncoming();
				if ($inpacket <> "") {
					if ($inpacket->packetheader->getQuestions() > 0) {
						$inpacket = "";
					}
				}
				if ($lastpackettime <> -1) {
					// If we get to here then we have a valid last packet time
					$timesincelastpacket = round(microtime(true) * 1000) - $lastpackettime;
					if ($timesincelastpacket > ($firstresponsetime * 5) && $firstresponsetime != -1) {
						return $chromecasts;
					}
				}
				if ($inpacket <> "") {
					$lastpackettime = round(microtime(true) * 1000);
				}
				$timetohere = round(microtime(true) * 1000) - $starttime;
				// Maximum five second rule
				if ($timetohere > 5000) {
					return $chromecasts;
				}
			}
			// If our packet has answers, then read them
			//$mdns->printPacket($inpacket);
			if ($inpacket->packetheader->getAnswerRRs() > 0) {
				$dontrequery = 0;
				//$mdns->printPacket($inpacket);
				for ($x = 0; $x < sizeof($inpacket->answerrrs); $x++) {
					if ($inpacket->answerrrs[$x]->qtype == 12) {
						//print_r($inpacket->answerrrs[$x]);
						if ($inpacket->answerrrs[$x]->name == "_googlecast._tcp.local") {
							if ($firstresponsetime == -1) {
								$firstresponsetime = round(microtime(true) * 1000) - $starttime;
							}
							$name = "";
							for ($y = 0; $y < sizeof($inpacket->answerrrs[$x]->data); $y++) {
								$name .= chr($inpacket->answerrrs[$x]->data[$y]);
							}
							// The chromecast itself fills in additional rrs. So if that's there then we have a quicker method of
							// processing the results.
							// First build any missing entries with any 33 packets we find.
							for ($p = 0; $p < sizeof($inpacket->additionalrrs); $p++) {
								if ($inpacket->additionalrrs[$p]->qtype == 33) {
									$d = $inpacket->additionalrrs[$p]->data;
									$port = ($d[4] * 256) + $d[5];
									// We need the target from the data
									$offset = 6;
									$size = $d[$offset];
									$offset++;
									$target = "";
									for ($z = 0; $z < $size; $z++) {
										$target .= chr($d[$offset + $z]);
									}
									$target .= ".local";
									if (!isset($chromecasts[$inpacket->additionalrrs[$p]->name])) {
										$chromecasts[$inpacket->additionalrrs[$x]->name] = ["port" => $port, "ip" => "", "target" => "", "friendlyname" => ""];
									}
									$chromecasts[$inpacket->additionalrrs[$x]->name]['target'] = $target;
								}
							}
							// Next repeat the process for 16
							for ($p = 0; $p < sizeof($inpacket->additionalrrs); $p++) {
								if ($inpacket->additionalrrs[$p]->qtype == 16) {
									$fn = "";
									for ($q = 0; $q < sizeof($inpacket->additionalrrs[$p]->data); $q++) {
										$fn .= chr($inpacket->additionalrrs[$p]->data[$q]);
									}
									$stp = strpos($fn, "fn=") + 3;
									$etp = strpos($fn, "ca=");
									$fn = substr($fn, $stp, $etp - $stp - 1);
									if (!isset($chromecasts[$inpacket->additionalrrs[$p]->name])) {
										$chromecasts[$inpacket->additionalrrs[$x]->name] = ["port" => 8009, "ip" => "", "target" => "", "friendlyname" => ""];
									}
									$chromecasts[$inpacket->additionalrrs[$x]->name]['friendlyname'] = $fn;
								}
							}
							// And finally repeat again for 1
							for ($p = 0; $p < sizeof($inpacket->additionalrrs); $p++) {
								if ($inpacket->additionalrrs[$p]->qtype == 1) {
									$d = $inpacket->additionalrrs[$p]->data;
									$ip = $d[0] . "." . $d[1] . "." . $d[2] . "." . $d[3];
									foreach ($chromecasts as $key => $value) {
										if ($value['target'] == $inpacket->additionalrrs[$p]->name) {
											$value['ip'] = $ip;
											$chromecasts[$key] = $value;
										}
									}
								}
							}
							$dontrequery = 1;
							// Check our item. If it doesn't exist then it wasn't in the additionals, so send requests.
							// If it does exist then check it has all the items. If not, send the requests.
							if (isset($chromecasts[$name])) {
								$xx = $chromecasts[$name];
								if ($xx['target'] == "") {
									// Send a 33 request
									$mdns->query($name, 1, 33, "");
									$dontrequery = 0;
								}
								if ($xx['friendlyname'] == "") {
									// Send a 16 request
									$mdns->query($name, 1, 16, "");
									$dontrequery = 0;
								}
								if ($xx['target'] != "" && $xx['friendlyname'] != "" && $xx['ip'] == "") {
									// Only missing the ip address for the target.
									$mdns->query($xx['target'], 1, 1, "");
									$dontrequery = 0;
								}
							} else {
								// Send queries. These'll trigger a 1 query when we have a target name.
								$mdns->query($name, 1, 33, "");
								$mdns->query($name, 1, 16, "");
								$dontrequery = 0;
							}

							if ($dontrequery == 0) {
								$cc = $wait;
							}
							set_time_limit($wait * 2);
						}
					}
					if ($inpacket->answerrrs[$x]->qtype == 33) {
						$d = $inpacket->answerrrs[$x]->data;
						$port = ($d[4] * 256) + $d[5];
						// We need the target from the data
						$offset = 6;
						$size = $d[$offset];
						$offset++;
						$target = "";
						for ($z = 0; $z < $size; $z++) {
							$target .= chr($d[$offset + $z]);
						}
						$target .= ".local";
						if (!isset($chromecasts[$inpacket->answerrrs[$x]->name])) {
							$chromecasts[$inpacket->answerrrs[$x]->name] = ["port" => $port, "ip" => "", "target" => $target, "friendlyname" => ""];
						} else {
							$chromecasts[$inpacket->answerrrs[$x]->name]['target'] = $target;
						}
						// We know the name and port. Send an A query for the IP address
						$mdns->query($target, 1, 1, "");
						$cc = $wait;
						set_time_limit($wait * 2);
					}
					if ($inpacket->answerrrs[$x]->qtype == 16) {
						$fn = "";
						for ($q = 0; $q < sizeof($inpacket->answerrrs[$x]->data); $q++) {
							$fn .= chr($inpacket->answerrrs[$x]->data[$q]);
						}
						$stp = strpos($fn, "fn=") + 3;
						$etp = strpos($fn, "ca=");
						$fn = substr($fn, $stp, $etp - $stp - 1);
						if (!isset($chromecasts[$inpacket->answerrrs[$x]->name])) {
							$chromecasts[$inpacket->answerrrs[$x]->name] = ["port" => 8009, "ip" => "", "target" => "", "friendlyname" => $fn];
						} else {
							$chromecasts[$inpacket->answerrrs[$x]->name]['friendlyname'] = $fn;
						}

						$mdns->query($chromecasts[$inpacket->answerrrs[$x]->name]['target'], 1, 1, "");
						$cc = $wait;
						set_time_limit($wait * 2);
					}
					if ($inpacket->answerrrs[$x]->qtype == 1) {
						$d = $inpacket->answerrrs[$x]->data;
						$ip = $d[0] . "." . $d[1] . "." . $d[2] . "." . $d[3];
						// Loop through the chromecasts and fill in the ip
						foreach ($chromecasts as $key => $value) {
							if ($value['target'] == $inpacket->answerrrs[$x]->name) {
								$value['ip'] = $ip;
								$chromecasts[$key] = $value;
								// If we have an IP address but no friendly name, try and get the friendly name again!
								if (strlen($value['friendlyname']) < 1) {
									$mdns->query($key, 1, 16, "");
									$cc = $wait;
									set_time_limit($wait * 2);
								}
							}
						}
					}
				}
			}
			$cc--;
		}
		return $chromecasts;
	}

	function testLive() {
		// If there is a difference of 10 seconds or more between $this->lastactivetime and the current time, then we've been kicked off and need to reconnect
		if ($this->lastip == "") {
			write_log("No last IP, returning.");
			return;
		}
		$diff = time() - $this->lastactivetime;
		if ($diff > 9) {
			write_log("Time is greater than last active, reconnecting.");
			// Reconnect
			$contextOptions = ['ssl' => ['verify_peer' => false, 'verify_peer_name' => false,]];
			$context = stream_context_create($contextOptions);
			if ($this->socket = stream_socket_client('ssl://' . $this->lastip . ":" . $this->lastport, $errno, $errstr, 30, STREAM_CLIENT_CONNECT, $context)) {
			} else {
				write_log("Failed to connect to remote Chromecast", "ERROR");
				die();
			}
			$this->cc_connect(1);
			$this->connect(1);
		}
	}

	function cc_connect($tl = 0) {
		write_log("Function fired.");
		// CONNECT TO CHROMECAST
		// This connects to the chromecast in general.
		// Generally this is called by launch($appid) automatically upon launching an app
		// but if you want to connect to an existing running application then call this first,
		// then call getStatus() to make sure you get a transportid.
		if ($tl == 0) {
			$this->testLive();
		};
		$c = new CastMessage();
		$c->source_id = "sender-0";
		$c->receiver_id = "receiver-0";
		$c->urnnamespace = "urn:x-cast:com.google.cast.tp.connection";
		$c->payloadtype = 0;
		$c->payloadutf8 = '{"type":"CONNECT"}';
		fwrite($this->socket, $c->encode());
		fflush($this->socket);
		$this->lastactivetime = time();
	}

	public function launch($appid) {
		write_log("Function fired.");
		// Launches the chromecast app on the connected chromecast

		// CONNECT
		$this->cc_connect();

		$this->getStatus();

		// LAUNCH
		$c = new CastMessage();
		$c->source_id = "sender-0";
		$c->receiver_id = "receiver-0";
		$c->urnnamespace = "urn:x-cast:com.google.cast.receiver";
		$c->payloadtype = 0;
		$c->payloadutf8 = '{"type":"LAUNCH","appId":"' . $appid . '","requestId":' . $this->requestId . '}';
		fwrite($this->socket, $c->encode());
		fflush($this->socket);
		$this->lastactivetime = time();
		$this->requestId++;

		$oldtransportid = $this->transportid;
		$count = 0;
		while (($this->transportid == "" || $this->transportid == $oldtransportid) && ($count < $this->breakout)) {
			$r = $this->getCastMessage();
			write_log("Looking for a cast message: " . $r);
			$count++;
		}
	}


	function getStatus() {
		write_log("Function fired.");
		// Get the status of the chromecast in general and return it
		// also fills in the transportId of any currently running app
		$this->testLive();
		$c = new CastMessage();
		$c->source_id = "sender-0";
		$c->receiver_id = "receiver-0";
		$c->urnnamespace = "urn:x-cast:com.google.cast.receiver";
		$c->payloadtype = 0;
		$c->payloadutf8 = '{"type":"GET_STATUS","requestId":' . $this->requestId . '}';
		$c = fwrite($this->socket, $c->encode());
		fflush($this->socket);
		$this->lastactivetime = time();
		$this->requestId++;
		$r = "";
		$count = 0;
		while (($this->transportid == "") && ($count < $this->breakout * 4)) {
			$r = $this->getCastMessage();
			if (preg_match("/controlType\"\:\"master\"/", $r) && !preg_match("/transportId/", $r)) {
				// Assume this is nvidia shield.
				$this->transportid = "generic-cast";
				$this->sessionid = 0;
			}
			$count++;
		}
		return $r;
	}

	function connect($tl = 0) {
		write_log("Function fired.");
		// This connects to the transport of the currently running app
		// (you need to have launched it yourself or connected and got the status)
		if ($tl == 0) {
			$this->testLive();
		};
		$c = new CastMessage();
		$c->source_id = "sender-0";
		$c->receiver_id = $this->transportid;
		$c->urnnamespace = "urn:x-cast:com.google.cast.tp.connection";
		$c->payloadtype = 0;
		$c->payloadutf8 = '{"type":"CONNECT"}';
		fwrite($this->socket, $c->encode());
		fflush($this->socket);
		$this->lastactivetime = time();
		$this->requestId++;
	}

	public function getCastMessage() {
		// Get the Chromecast Message/Response
		// Later on we could update CCprotoBuf to decode this
		// but for now all we need is the transport id  and session id if it is
		// in the packet and we can read that directly.
		$this->testLive();
		//stream_set_timeout($this->socket,1);
		$response = fread($this->socket, 10000);
		$response = preg_replace('/[[:^print:]]/', '', $response);
		$pongcount = 0;
		while (preg_match("/urn:x-cast:com.google.cast.tp.heartbeat/", $response) && preg_match("/\"PING\"/", $response)) {
			if ($response != "") {
				$this->pong();
			}
			$response = fread($this->socket, 10000);
			write_log("Response: " . $response);
			if ($response == "" || preg_match("/\"PING\"/", $response)) {
				$pongcount++;
				write_log("Pongcount: " . $pongcount);
			}
			if ($pongcount == 2) {
				break;
			}
			set_time_limit(30);
		}
		if (preg_match("/transportId/s", $response)) {
			preg_match("/transportId\"\:\"([^\"]*)/", $response, $matches);
			$matches = $matches[1];
			$this->transportid = $matches;
		}
		if (preg_match("/sessionId/s", $response)) {
			preg_match("/\"sessionId\"\:\"([^\"]*)/", $response, $r);
			$this->sessionid = $r[1];
		}
		$this->lastactivetime = time();
		return $response;
	}

	public function sendMessage($urn, $message) {
		// Send the given message to the given urn
		$this->testLive();
		$c = new CastMessage();
		$c->source_id = "sender-0";
		$c->receiver_id = $this->transportid;
		// Override - if the $urn is urn:x-cast:com.google.cast.receiver then
		// send to receiver-0 and not the running app
		if ($urn == "urn:x-cast:com.google.cast.receiver") {
			$c->receiver_id = "receiver-0";
		}
		if ($urn == "urn:x-cast:com.google.cast.tp.connection") {
			$c->receiver_id = "receiver-0";
		}
		$c->urnnamespace = $urn;
		$c->payloadtype = 0;
		$c->payloadutf8 = $message;
		fwrite($this->socket, $c->encode());
		fflush($this->socket);
		$this->lastactivetime = time();
		$this->requestId++;
		$response = $this->getCastMessage();
		return $response;
	}

	public function pingpong() {
		write_log("Function fired.");
		// Officially you should run this every 5 seconds or so to keep
		// the device alive. Doesn't seem to be necessary if an app is running
		// that doesn't have a short timeout.
		$c = new CastMessage();
		$c->source_id = "sender-0";
		$c->receiver_id = "receiver-0";
		$c->urnnamespace = "urn:x-cast:com.google.cast.tp.heartbeat";
		$c->payloadtype = 0;
		$c->payloadutf8 = '{"type":"PING"}';
		fwrite($this->socket, $c->encode());
		fflush($this->socket);
		$this->lastactivetime = time();
		$this->requestId++;
		$response = $this->getCastMessage();
	}

	public function pong() {
		write_log("Function fired.");
		// To answer a pingpong
		$c = new CastMessage();
		$c->source_id = "sender-0";
		$c->receiver_id = "receiver-0";
		$c->urnnamespace = "urn:x-cast:com.google.cast.tp.heartbeat";
		$c->payloadtype = 0;
		$c->payloadutf8 = '{"type":"PONG"}';
		fwrite($this->socket, $c->encode());
		fflush($this->socket);
		$this->lastactivetime = time();
		$this->requestId++;
	}
}

?>
