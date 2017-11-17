<?php

// Simple MDNS query object
// Chris Ridings
// www.chrisridings.com
require_once dirname(__FILE__) . '/../util.php';

class mDNS {

	private $mdnssocket; // Socket to listen to port 5353
	// A = 1;
	// PTR = 12;
	// SRV = 33;
	// TXT = 16;

	// query cache for the last query packet sent
	private $querycache = "";

	public function __construct() {
		error_reporting(E_ERROR | E_PARSE);
		// Create $mdnssocket, bind to 5353 and join multicast group 224.0.0.251
		$this->mdnssocket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
		if ($this->mdnssocket === false) {
			$errorcode = socket_last_error();
			$errormsg = socket_strerror($errorcode);
			write_log("Couldn't create socket: [$errorcode] $errormsg", "ERROR");
		}
		write_log("PHP is detecting the OS as " . PHP_OS);
		$os = strtolower(PHP_OS);
		if (preg_match("/darwin/", $os) || preg_match("/bsd/", $os)) {
			socket_set_option($this->mdnssocket, SOL_SOCKET, SO_REUSEPORT, 1);
		} else {
			socket_set_option($this->mdnssocket, SOL_SOCKET, SO_REUSEADDR, 1);
		}
		//socket_set_option($this->mdnssocket, SOL_SOCKET, SO_BROADCAST, 1);
		socket_set_option($this->mdnssocket, IPPROTO_IP, MCAST_JOIN_GROUP, ['group' => '224.0.0.251', 'interface' => 0]);
		socket_set_option($this->mdnssocket, SOL_SOCKET, SO_RCVTIMEO, ["sec" => 1, "usec" => 0]);
		$bind = socket_bind($this->mdnssocket, "0.0.0.0", 5353);
		if (!$bind) {
			$errorcode = socket_last_error();
			$errormsg = socket_strerror($errorcode);
			write_log("Couldn't bind socket: [$errorcode] $errormsg", "ERROR");
		}
	}

	public function query($name, $qclass, $qtype, $data = "") {
		// Sends a query
		$p = new DNSPacket;
		$p->clear();
		$p->packetheader->setTransactionID(rand(1, 32767));
		$p->packetheader->setQuestions(1);
		$q = new DNSQuestion();
		$q->name = $name;
		$q->qclass = $qclass;
		$q->qtype = $qtype;
		array_push($p->questions, $q);
		$b = $p->makePacket();
		// Send the packet
		$data = "";
		for ($x = 0; $x < sizeof($b); $x++) {
			$data .= chr($b[$x]);
		}
		$this->querycache = $data;
		$r = socket_sendto($this->mdnssocket, $data, strlen($data), 0, '224.0.0.251', 5353);
		if (!$r) write_log("Error sending to socket.", "ERROR");
		if ($data == "") write_log("No data retrieved from query.", "ERROR");
	}

	public function requery() {
		// resend the last query
		$r = socket_sendto($this->mdnssocket, $this->querycache, strlen($this->querycache), 0, '224.0.0.251', 5353);
		if (!$r) write_log("Error sending to socket.", "ERROR");
	}

	public function readIncoming() {
		// Read some incoming data. Timeout after 1 second
		$from = '0.0.0.0';
		$port = 0;
		$buf = '';
		$response = "";
		try {
			$response = socket_read($this->mdnssocket, 1024, PHP_BINARY_READ);
		} catch (Exception $e) {
			write_log("Exception: " . $e, "ERROR");
		}
		if (strlen($response) < 1) {
			return "";
		}
		// Create an array to represent the bytes
		$bytes = [];
		for ($x = 0; $x < strlen($response); $x++) {
			array_push($bytes, ord(substr($response, $x, 1)));
		}
		$p = new DNSPacket();
		$p->load($bytes);
		return $p;
	}

	public function load($data) {
		$p = new DNSPacket();
		$p->load($data);
		return $p;
	}

	public function printPacket($p) {
		// Echo a summary of packet contents to the screen
		echo "Questions: " . $p->packetheader->getQuestions() . "\n";
		if ($p->packetheader->getQuestions() > 0) {
			// List the AnswerRRs
			for ($x = 0; $x < $p->packetheader->getQuestions(); $x++) {
				echo "  Question Number: " . $x . "\n";
				$a = $p->questions[$x];
				$this->printRR($a);
			}
		}
		echo "AnswerRRs: " . $p->packetheader->getAnswerRRs() . "\n";
		if ($p->packetheader->getAnswerRRs() > 0) {
			// List the AnswerRRs
			for ($x = 0; $x < $p->packetheader->getAnswerRRs(); $x++) {
				echo "  Answer Number: " . $x . "\n";
				$a = $p->answerrrs[$x];
				$this->printRR($a);
			}
		}
		echo "AuthorityRRs: " . $p->packetheader->getAuthorityRRs() . "\n";
		if ($p->packetheader->getAuthorityRRs() > 0) {
			// List the AnswerRRs
			for ($x = 0; $x < $p->packetheader->getAuthorityRRs(); $x++) {
				echo "  AuthorityRR Number: " . $x . "\n";
				$a = $p->authorityrrs[$x];
				$this->printRR($a);
			}
		}
		echo "AdditionalRRs: " . $p->packetheader->getAdditionalRRs() . "\n";
		if ($p->packetheader->getAdditionalRRs() > 0) {
			// List the AnswerRRs
			for ($x = 0; $x < $p->packetheader->getAdditionalRRs(); $x++) {
				echo "  Answer Number: " . $x . "\n";
				$a = $p->additionalrrs[$x];
				$this->printRR($a);
			}
		}
	}

	private function printRR($a) {
		echo "    Name: " . $a->name . "\n";
		echo "    QType: " . $a->qtype . "\n";
		echo "    QClass: " . $a->qclass . "\n";
		echo "    TTL: " . $a->ttl . "\n";
		$s = "";
		for ($x = 0; $x < sizeof($a->data); $x++) {
			$s .= chr($a->data[$x]);
		}
		echo "    Data: " . $s . "\n";
	}

}

class DNSPacket {
	// Represents and processes a DNS packet
	public $packetheader; // DNSPacketHeader
	public $questions; // array
	public $answerrrs; // array
	public $authorityrrs; // array
	public $additionalrrs; // array
	public $offset = 0;

	public function __construct() {
		$this->clear();
	}

	public function clear() {
		$this->packetheader = new DNSPacketHeader();
		$this->packetheader->clear();
		$this->questions = [];
		$this->answerrrs = [];
		$this->authorityrrs = [];
		$this->additionalrrs = [];
	}

	public function load($data) {
		// $data is an array of integers representing the bytes.
		// Load the data into the DNSPacket object.
		$this->clear();

		// Read the first 12 bytes and load into the packet header
		$headerbytes = [];
		for ($x = 0; $x < 12; $x++) {
			$headerbytes[$x] = $data[$x];
		}
		$this->packetheader->load($headerbytes);
		$this->offset = 12;

		if ($this->packetheader->getQuestions() > 0) {
			// There are some questions in this DNS Packet. Read them!
			for ($xq = 1; $xq <= $this->packetheader->getQuestions(); $xq++) {
				$name = "";
				$size = 0;
				$resetoffsetto = 0;
				$firstreset = 0;
				while ($data[$this->offset] <> 0) {
					if ($size == 0) {
						$size = $data[$this->offset];
						if (($size & 192) == 192) {
							if ($firstreset == 0 && $resetoffsetto <> 0) {
								$firstrest = $resetoffsetto;
							}
							$resetoffsetto = $this->offset;
							$this->offset = $data[$this->offset + 1];
							$size = $data[$this->offset];
						}
					} else {
						$name = $name . chr($data[$this->offset]);
						$size--;
						if ($size == 0) {
							$name = $name . ".";
						}
					}
					$this->offset++;
				}
				if ($firstreset <> 0) {
					$resetoffsetto = $firstreset;
				}
				if ($resetoffsetto <> 0) {
					$this->offset = $resetoffsetto + 1;
				}
				if (strlen($name) > 0) {
					$name = substr($name, 0, strlen($name) - 1);
				}
				$this->offset = $this->offset + 1;
				$qtype = ($data[$this->offset] * 256) + $data[$this->offset + 1];
				$qclass = ($data[$this->offset + 2] * 256) + $data[$this->offset + 3];
				$this->offset = $this->offset + 4;
				$r = new DNSQuestion();
				$r->name = $name;
				$r->qclass = $qclass;
				$r->qtype = $qtype;
				array_push($this->questions, $r);
			}
		}
		if ($this->packetheader->getAnswerRRs() > 0) {
			// There are some answerrrs in this DNS Packet. Read them!
			for ($xq = 1; $xq <= $this->packetheader->getAnswerRRs(); $xq++) {
				$qr = $this->readRR($data);
				array_push($this->answerrrs, $qr);
			}
		}
		if ($this->packetheader->getAuthorityRRs() > 0) {
			// Read the authorityrrs
			for ($xq = 1; $xq <= $this->packetheader->getAuthorityRRs(); $xq++) {
				$qr = $this->readRR($data);
				array_push($this->authorityrrs, $qr);
			}
		}
		if ($this->packetheader->getAdditionalRRs() > 0) {
			// Finally read any additional rrs
			for ($xq = 1; $xq <= $this->packetheader->getAdditionalRRs(); $xq++) {
				$qr = $this->readRR($data);
				array_push($this->additionalrrs, $qr);
			}
		}
	}

	public function readRR($data) {
		// Returns a DNSResourceRecord object representing the $data (array of integers)
		$name = "";
		$size = 0;
		$resetoffsetto = 0;
		$firstreset = 0;
		$sectionstart = $this->offset;
		$sectionsize = 0;
		while ($data[$this->offset] <> 0) {
			if ($size == 0) {
				$size = $data[$this->offset];
				if ($sectionsize == 0) {
					$sectionsize = $size;
				}
				if (($size & 192) == 192) {
					if ($firstreset == 0 && $resetoffsetto <> 0) {
						$firstreset = $resetoffsetto;
					}
					$resetoffsetto = $this->offset;
					$this->offset = $data[$this->offset + 1] + (($data[$this->offset] - 192) * 256);
					$size = $data[$this->offset];
				}
			} else {
				$name = $name . chr($data[$this->offset]);
				$size--;
				if ($size == 0) {
					$name = $name . ".";
				}
			}
			$this->offset++;
		}
		if ($firstreset <> 0) {
			$resetoffsetto = $firstreset;
		}
		if ($resetoffsetto <> 0) {
			$this->offset = $resetoffsetto + 1;
		}
		if (strlen($name) > 0) {
			$name = substr($name, 0, strlen($name) - 1);
		}
		$this->offset = $this->offset + 1;
		$qtype = ($data[$this->offset] * 256) + $data[$this->offset + 1];
		$qclass = ($data[$this->offset + 2] * 256) + $data[$this->offset + 3];
		$this->offset = $this->offset + 4;
		$ttl = 1000;
		$this->offset = $this->offset + 4;
		// The next two bytes are the length of the data section
		$dl = ($data[$this->offset] * 256) + $data[$this->offset + 1];
		$this->offset = $this->offset + 2;
		$oldoffset = $this->offset;
		$ddata = [];
		for ($x = 0; $x < $dl; $x++) {
			array_push($ddata, $data[$this->offset]);
			$this->offset = $this->offset + 1;
		}
		$storeoffset = $this->offset;
		// For PTR, SRV, and TXT records we need to uncompress the data
		$datadecode = "";
		$size = 0;
		$resetoffsetto = 0;
		if ($qtype == 12) {
			$this->offset = $oldoffset;
			$firstreset = 0;
			while ($data[$this->offset] <> 0) {
				if ($size == 0) {
					$size = $data[$this->offset];
					if (($size & 192) == 192) {
						if ($firstreset == 0 && $resetoffsetto <> 0) {
							$firstreset = $resetoffsetto;
						}
						$resetoffsetto = $this->offset;
						$this->offset = $data[$this->offset + 1];
						$size = $data[$this->offset];
					}
				} else {
					$datadecode = $datadecode . chr($data[$this->offset]);
					$size = $size - 1;
					if ($size == 0) {
						$datadecode = $datadecode . ".";
					}
				}
				$this->offset++;
			}
			if ($firstreset <> 0) {
				$resetoffsetto = $firstreset;
			}
			if ($resetoffsetto <> 0) {
				$offset = $resetoffsetto + 1;
			}
			$datadecode = substr($datadecode, 0, strlen($datadecode) - 1);
			$ddata = [];
			for ($x = 0; $x < strlen($datadecode); $x++) {
				array_push($ddata, ord(substr($datadecode, $x, 1)));
				$this->offset++;
			}
		}
		$this->offset = $storeoffset;
		$r = New DNSResourceRecord;
		$r->name = $name;
		$r->qclass = $qclass;
		$r->qtype = $qtype;
		$r->ttl = $ttl;
		$r->data = $ddata;
		return $r;
	}

	public function makePacket() {
		// For the current DNS packet produce an array of bytes to send.
		// Should make this support unicode, but currently it doesn't :(
		$bytes = [];
		// First copy the header in
		$header = $this->packetheader->getBytes();
		for ($x = 0; $x < sizeof($header); $x++) {
			array_push($bytes, $header[$x]);
		}
		$this->offset = 12;
		if (sizeof($this->questions) > 0) {
			// We have some questions to encode
			for ($pp = 0; $pp < sizeof($this->questions); $pp++) {
				$thisq = $this->questions[$pp];
				$thisname = $thisq->name;
				$undotted = "";
				while (strpos($thisname, ".") > 0) {
					$undotted .= chr(strpos($thisname, ".")) . substr($thisname, 0, strpos($thisname, "."));
					$thisname = substr($thisname, strpos($thisname, ".") + 1);
				}
				$undotted .= chr(strlen($thisname)) . $thisname . chr(0);
				for ($pq = 0; $pq < strlen($undotted); $pq++) {
					array_push($bytes, ord(substr($undotted, $pq, 1)));
				}
				$this->offset = $this->offset + strlen($undotted);
				array_push($bytes, (int)($thisq->qtype / 256));
				array_push($bytes, $thisq->qtype % 256);
				$this->offset = $this->offset + 2;
				array_push($bytes, (int)($thisq->qclass / 256));
				array_push($bytes, $thisq->qclass % 256);
				$this->offset = $this->offset + 2;
			}
		}
		// Questions are done. Others go here.
		// Maybe do this later, but for now we're only asking questions!

		return $bytes;
	}
}

class DNSPacketHeader {
	// Represents the 12 byte packet header of a DNS request or response
	private $contents; // Byte() - in reality use an array of integers here

	public function clear() {
		$this->contents = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
	}

	public function getBytes() {
		return $this->contents;
	}

	public function load($data) {
		// Assume we're passed an array of bytes
		$this->clear();
		$this->contents = $data;
	}

	public function getTransactionID() {
		return ($this->contents[0] * 256) + $this->contents[1];
	}

	public function setTransactionID($value) {
		$this->contents[0] = (int)($value / 256);
		$this->contents[1] = $value % 256;
	}

	public function getMessageType() {
		return ($this->contents[2] & 128) / 128;
	}

	public function setMessageType($value) {
		$value = $value * 128;
		$this->contents[2] = $this->contents[2] & 127;
		$this->contents[2] = $this->contents[2] | $value;
	}

	// As far as I know the opcode is always zero. But code it anyway (just in case)
	public function getOpCode() {
		return ($this->contents[2] & 120) / 8;
	}

	public function setOpCode($value) {
		$value = $value * 8;
		$this->contents[2] = $this->contents[2] & 135;
		$this->contents[2] = $this->contents[2] | $value;
	}

	public function getAuthorative() {
		return ($this->contents[2] & 4) / 4;
	}

	public function setAuthorative($value) {
		$value = $value * 4;
		$this->contents[2] = $this->contents[2] & 251;
		$this->contents[2] = $this->contents[2] | $value;
	}

	// We always want truncated to be 0 as this class doesn't support multi packet.
	// But handle the value anyway
	public function getTruncated() {
		return ($this->contents[2] & 2) / 2;
	}

	public function setTruncated($value) {
		$value = $value * 2;
		$this->contents[2] = $this->contents[2] & 253;
		$this->contents[2] = $this->contents[2] | $value;
	}

	// We return this but we don't handle it!
	public function getRecursionDesired() {
		return ($this->contents[2] & 1);
	}

	public function setRecursionDesired($value) {
		$this->contents[2] = $this->contents[2] & 254;
		$this->contents[2] = $this->contents[2] | $value;
	}

	// We also return this but we don't handle it
	public function getRecursionAvailable() {
		return ($this->contents[3] & 128) / 128;
	}

	public function setRecursionAvailable($value) {
		$value = $value * 128;
		$this->contents[3] = $this->contents[3] & 127;
		$this->contents[3] = $this->contents[3] | $value;
	}

	public function getReserved() {
		return ($this->contents[3] & 64) / 64;
	}

	public function setReserved($value) {
		$value = $value * 64;
		$this->contents[3] = $this->contents[3] & 191;
		$this->contents[3] = $this->contents[3] | $value;
	}

	// This always seems to be 0, but handle anyway
	public function getAnswerAuthenticated() {
		return ($this->contents[3] & 32) / 32;
	}

	public function setAnswerAuthenticated($value) {
		$value = $value * 32;
		$this->contents[3] = $this->contents[3] & 223;
		$this->contents[3] = $this->contents[3] | $value;
	}

	// This always seems to be 0, but handle anyway
	public function getNonAuthenticatedData() {
		return ($this->contents[3] & 16) / 16;
	}

	public function setNonAuthenticatedData($value) {
		$value = $value * 16;
		$this->contents[3] = $this->contents[3] & 239;
		$this->contents[3] = $this->contents[3] | $value;
	}

	// We want this to be zero
	// 0 : No error condition
	// 1 : Format error - The name server was unable to interpret the query.
	// 2 : Server failure - The name server was unable to process this query due to a problem with the name server.
	// 3 : Name Error - Meaningful only for responses from an authoritative name server, this code signifies that the domain name referenced in the query does not exist.
	// 4 : Not Implemented - The name server does not support the requested kind of query.
	// 5 : Refused - The name server refuses to perform the specified operation for policy reasons. You should set this field to 0, and should assert an error if you receive a response indicating an error condition. You should treat 3 differently, as this represents the case where a requested name doesn’t exist.
	public function getReplyCode() {
		return ($this->contents[3] & 15);
	}

	public function setReplyCode($value) {
		$this->contents[3] = $this->contents[3] & 240;
		$this->contents[3] = $this->contents[3] | $value;
	}

	// The number of Questions in the packet
	public function getQuestions() {
		return ($this->contents[4] * 256) + $this->contents[5];
	}

	public function setQuestions($value) {
		$this->contents[4] = (int)($value / 256);
		$this->contents[5] = $value % 256;
	}

	// The number of AnswerRRs in the packet
	public function getAnswerRRs() {
		return ($this->contents[6] * 256) + $this->contents[7];
	}

	public function setAnswerRRs($value) {
		$this->contents[6] = (int)($value / 256);
		$this->contents[7] = $value % 256;
	}

	// The number of AuthorityRRs in the packet
	public function getAuthorityRRs() {
		return ($this->contents[8] * 256) + $this->contents[9];
	}

	public function setAuthorityRRs($value) {
		$this->contents[8] = (int)($value / 256);
		$this->contents[9] = $value % 256;
	}

	// The number of AdditionalRRs in the packet
	public function getAdditionalRRs() {
		return ($this->contents[10] * 256) + $this->contents[11];
	}

	public function setAdditionalRRs($value) {
		$this->contents[10] = (int)($value / 256);
		$this->contents[11] = $value % 256;
	}
}

class DNSQuestion {
	public $name; // String
	public $qtype; // UInt16
	public $qclass; // UInt16
}

class DNSResourceRecord {
	public $name; // String
	public $qtype; // UInt16
	public $qclass; // UInt16
	public $ttl; // UInt32
	public $data; // Byte ()
}

?>