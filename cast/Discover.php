<?php

/**
 * Class Scanner
 *
 * @package CyberLine\phUPnP
 * @author Alexander Over <cyberline@php.net>
 * @mod by Digitalhigh
 */
class Scanner implements \JsonSerializable {
	/** @var string */
	static protected $host = '239.255.255.250';

	/** @var int */
	static protected $port = 1900;

	/**
	 * Maximum wait time in seconds. Should be between 1 and 120 inclusive.
	 *
	 * @var int
	 */
	protected $delayResponse = 1;

	/** @var int */
	protected $timeout = 5;

	/** @var string */
	protected $userAgent = 'iOS/5.0 UDAP/2.0 iPhone/4';

	/** @var array */
	protected $searchTypes = ['ssdp:all', 'upnp:rootdevice',];

	/** @var string */
	protected $searchType = 'ssdp:all';

	/** @var array */
	private $devices = [];

	/**
	 * @param integer $delayResponse
	 * @return $this
	 */
	public function setDelayResponse($delayResponse) {
		if ((int)$delayResponse >= 1 && (int)$delayResponse <= 120) {
			$this->delayResponse = (int)$delayResponse;

			return $this;
		}

		throw new \OutOfRangeException(sprintf('%d is not a valid delay. Valid delay is between 1 and 120 (seconds)', $delayResponse));
	}

	/**
	 * @param int $timeout
	 * @return Scanner
	 */
	public function setTimeout($timeout) {
		if ((int)$timeout <= (int)$this->delayResponse) {
			$this->timeout = (int)$timeout;

			return $this;
		}

		throw new \OutOfBoundsException(sprintf('Timeout of %d is smaller then delay of %d', $timeout, $this->delayResponse));
	}

	/**
	 * @param string $userAgent
	 * @return Scanner
	 */
	public function setUserAgent($userAgent) {
		$this->userAgent = $userAgent;

		return $this;
	}

	/**
	 * @param string $searchType
	 * @return $this
	 */
	public function setSearchType($searchType) {
		if (in_array($searchType, $this->searchTypes)) {
			$this->searchType = $searchType;

			return $this;
		}

		throw new \InvalidArgumentException(sprintf('%s is not a valid searchtype. Valid searchtypes are: %s', $searchType, implode(', ', $this->searchTypes)));
	}

	/**
	 * Main scan function
	 *
	 * @return array
	 */
	public function discover() {
		$devices = $this->doMSearchRequest();

		if (empty($devices)) {
			return [];
		}

		$targets = [];
		foreach ($devices as $key => $device) {
			$devices[$key] = $this->parseMSearchResponse($device);
			array_push($targets, $devices[$key]['location']);
		}

		foreach ($this->fetchUpnpXmlDeviceInfo($targets) as $location => $xml) {
			if (!empty($xml)) {
				$this->parseXmlDeviceResponse($location, $xml);
			}
		}
		$list = $this->devices;
		// Convert to a standard array
		$list = json_decode(json_encode($list), true);
		return $list;
	}

	/**
	 * Fetch all available UPnP devices via unicast
	 *
	 * @return array
	 */
	protected function doMSearchRequest() {
		$request = $this->getMSearchRequest();

		$socket = socket_create(AF_INET, SOCK_DGRAM, 0);
		socket_sendto($socket, $request, strlen($request), 0, '239.255.255.250', 1900);
		socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, ['sec' => $this->timeout, 'usec' => '0']);
		$response = [];
		do {
			$buf = null;
			socket_recvfrom($socket, $buf, 1024, MSG_WAITALL, $from, $port);
			if (!is_null($buf)) {
				array_push($response, $buf);
			}
		} while (!is_null($buf));
		socket_close($socket);
		return $response;
	}

	/**
	 * Prepare Msearch request string
	 *
	 * @return string
	 */

	protected function getMSearchRequest() {
		$st = 'ssdp:all';
		$mx = 2;
		$man = 'ssdp:discover';
		$from = null;
		$port = null;
		$sockTimout = '2';
		$request = 'M-SEARCH * HTTP/1.1' . "\r\n";
		$request .= 'HOST: 239.255.255.250:1900' . "\r\n";
		$request .= 'MAN: "ssdp:discover"' . "\r\n";
		$request .= 'MX: ' . $mx . '' . "\r\n";
		$request .= 'ST: ' . $st . '' . "\r\n";
		$request .= 'USER-AGENT: ' . $this->userAgent . "\r\n";
		$request .= "\r\n";
		return $request;
	}


	/**
	 * Parse response from device to a more readable format
	 *
	 * @param $response
	 * @return array
	 */
	protected function parseMSearchResponse($response) {
		$mapping = ['http' => 'http', 'cach' => 'cache-control', 'date' => 'date', 'ext' => 'ext', 'loca' => 'location', 'serv' => 'server', 'st:' => 'st', 'usn:' => 'usn', 'cont' => 'content-length',];

		$parsedResponse = [];
		foreach (explode("\r\n", $response) as $resultLine) {
			foreach ($mapping as $key => $replace) {
				if (stripos($resultLine, $key) === 0) {
					$parsedResponse[$replace] = str_ireplace($replace . ': ', '', $resultLine);
				}
			}
		}

		return $parsedResponse;
	}

	/**
	 * @param $location
	 * @param $xml
	 */
	protected function parseXmlDeviceResponse($location, $xml) {
		try {
			$simpleXML = new \SimpleXMLElement($xml);
			if (!property_exists($simpleXML, 'URLBase')) {
				$location = parse_url($location);
				$simpleXML->device->URI = sprintf('%s://%s:%d/', $location['scheme'], $location['host'], $location['port']);
			}
			// Filter for cast devices
			if ((string)$simpleXML->device->deviceType == 'urn:dial-multiscreen-org:device:dial:1') {
				array_push($this->devices, $simpleXML);
			}
		} catch (\Exception $e) { /* SimpleXML parsing failed */
		}
	}

	/**
	 * Fetch XML's from all devices async
	 *
	 * @param array $targets
	 * @return array
	 */
	protected function fetchUpnpXmlDeviceInfo(array $targets) {
		$targets = array_values(array_unique($targets));
		$multi = curl_multi_init();

		$curl = $xmls = [];
		foreach ($targets as $key => $target) {
			$curl[$key] = curl_init();
			curl_setopt($curl[$key], CURLOPT_URL, $target);
			curl_setopt($curl[$key], CURLOPT_TIMEOUT, $this->timeout);
			curl_setopt($curl[$key], CURLOPT_RETURNTRANSFER, true);
			curl_multi_add_handle($multi, $curl[$key]);
		}

		$active = null;
		do {
			$mrc = curl_multi_exec($multi, $active);
		} while ($mrc == CURLM_CALL_MULTI_PERFORM);

		while ($active && $mrc == CURLM_OK) {
			if (curl_multi_select($multi) != -1) {
				do {
					$mrc = curl_multi_exec($multi, $active);
				} while ($mrc == CURLM_CALL_MULTI_PERFORM);
			}
		}

		foreach ($curl as $key => $handle) {
			$xmls[$targets[$key]] = curl_multi_getcontent($handle);
			curl_multi_remove_handle($multi, $handle);
		}

		curl_multi_close($multi);

		return $xmls;
	}

	/**
	 * @return array
	 */
	public function jsonSerialize() {
		if (empty($this->devices)) {
			$this->discover();
		}

		return ['total' => count($this->devices), 'devices' => $this->devices,];
	}
}
