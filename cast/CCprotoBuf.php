<?php

// Class to represent a protobuf object for a command.

class CastMessage {

	public $protocolversion = 0; // CASTV2_1_0 - It's always this
	public $source_id; // Source ID String
	public $receiver_id; // Receiver ID String
	public $urnnamespace; // Namespace
	public $payloadtype = 0; // PayloadType String=0 Binary = 1
	public $payloadutf8; // Payload

	public function encode() {

		// Deliberately represent this as a binary first (for readability and so it's obvious what's going on.
		// speed impact doesn't really matter!)
		$r = "";
	
		// First the protocol version
		$r = "00001"; // Field Number 1
		$r .= "000"; // Int
		// Value is always 0
		$r .= $this->varintToBin($this->protocolversion);

		// Now the Source id
		$r .= "00010"; // Field Number 2
		$r .= "010"; // String
		$r .= $this->stringToBin($this->source_id);

		// Now the Receiver id
		$r .= "00011"; // Field Number 3
		$r .= "010"; // String
		$r .= $this->stringToBin($this->receiver_id);

		// Now the namespace
		$r .= "00100"; // Field Number 4
		$r .= "010"; // String
		$r .= $this->stringToBin($this->urnnamespace);

		// Now the payload type
		$r .= "00101"; // Field Number 5
		$r .= "000"; // VarInt
		$r .= $this->varintToBin($this->payloadtype);

		// Now payloadutf8
		$r .= "00110"; // Field Number 6
		$r .= "010"; // String
		$r .= $this->stringToBin($this->payloadutf8);
		
		// Ignore payload_binary field 7 as never used

		// Now convert it to a binary packet
		$hexstring = "";
		for ($i=0; $i < strlen($r); $i=$i+8) {
			$thischunk = substr($r,$i,8);
			$hx = dechex(bindec($thischunk));
			if (strlen($hx) == 1) { $hx = "0" . $hx; }
			$hexstring .= $hx;
		}
		$l = strlen($hexstring) / 2;
		$l = dechex($l);
		while (strlen($l) < 8) { $l = "0" . $l; }
		$hexstring = $l . $hexstring;
		return hex2bin($hexstring);
	}

	private function varintToBin($inval) {
		// Convert an integer to a binary varint
		// A variant is returned least significant part first.
		// Number is represented in 7 bit portions. The 8th (MSB) of a byte represents if there
		// is a following byte.
		$r = array();
		while ($inval / 128 > 1) {
			$thisval = ($inval - ($inval % 128)) / 128;
			array_push($r, $thisval);
			$inval = $inval - ($thisval * 128);
		}
		array_push($r, $inval);
		$r = array_reverse($r);
		$binaryString = "";
		$c = 1;
		foreach ($r as $num) {
			if ($c != sizeof($r)) { $num = $num + 128; }
			$tv = decbin($num);
			while (strlen($tv) < 8) { $tv = "0" . $tv; }
			$c++;
			$binaryString .= $tv;
		}
		return $binaryString;
	}

	private function stringToBin($string) {
		// Convert a string to a Binary string
		// First the length (note this is a binary varint)
		$l = strlen($string);
		$ret = "";
		$ret = $this->varintToBin($l);
		for ($i = 0; $i < $l; $i++) {
			$n = decbin(ord(substr($string,$i,1)));
			while (strlen($n) < 8) { $n = "0" . $n; }
			$ret .= $n;
		}
		return $ret;
	}

}


?>
