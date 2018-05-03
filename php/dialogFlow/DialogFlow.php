<?php


namespace digitalhigh\DialogFlow;

use GuzzleHttp\Client;

class DialogFlow {
	protected $authToken;
	protected $url;
	protected $version;

	public $apiVersion;
	public $lang;
	public $requestData;
	public $sessionId;
	public $lastUrl;

    /**
     * dialogFlow constructor.
     * @param $authToken
     * @param string $lang - Defaults to english
     * @param int $apiVersion - Currently only writing this for v1
     * @param bool $sessionId
     * @throws \Exception
     */

	public function __construct($authToken, string $lang="EN", int $apiVersion=1,$sessionId=false)
	{
		$this->authToken = $authToken;
		$this->lang = $lang;
		$this->apiVersion = $apiVersion;
		$this->sessionId = $sessionId ? substr($sessionId,1,6) : bin2hex(random_bytes(4));
		$this->version = 20150910;
		$this->url = "https://api.api.ai/v$apiVersion/";
		$this->lastUrl = false;
	}

	public function query(string $speech, callable $callback=null, $context=false) {

		$params = [
			'query'=>urlencode($speech),
			'lang'=>$this->lang,
			'sessionId'=>$this->sessionId
		];
        write_log("Query params: ".json_encode($params));
		if ($context) $params['contexts'] = [$context];

		try {
			$response = $this->_request(
				[
					'type' => 'get',
					'uri' => 'query',
					'data' => $params
				]
			);

		} catch ( \Exception $e ) {
			//throw new \Exception($e->getMessage());
			return [];
		}

		if (is_callable($callback)) {
			return call_user_func($callback,$response->getBody()->getContents());
		} else {
			return $response->getBody()->getContents();
		}
	}

	public function process(array $request, callable $callback=null) {
		$request = $this->array_filter_recursive($request);
		$result = $request['result'];
		return $result;
	}

	public function reply(dialogFlowSpeech $speech) {

	}

	public function lastUrl() {
		return $this->lastUrl;
	}


	protected function _request(array $params)
	{
		$client = new Client();

		$options = [
			'headers' => [
				'Authorization' => ' Bearer '.$this->authToken
			]
		];
		$params = array_merge(['v'=>$this->version],$params);

		if ( $params['type'] == 'get' ) {
			$url = $this->url . $params['uri'] . '?' . http_build_query($params['data']);
			$this->lastUrl=$url;
			return $client->get($url, $options);
		}

		if ( $params['type'] == 'put' ) {
			$url = $this->url . '/api/' . $params['uri'];
			$options['json'] = $params['data'];
			$this->lastUrl=$url;
			return $client->put($url, $options);
		}

		if ( $params['type'] == 'post' ) {
			$url = $this->url . '/api/' . $params['uri'];
			$options['json'] = $params['data'];
			$this->lastUrl=$url;
			return $client->post($url, $options);
		}

		if ( $params['type'] == 'delete' ) {
			$url = $this->url . '/api/' . $params['uri'] . '?' . http_build_query($params['data']);
			$this->lastUrl=$url;
			return $client->delete($url, $options);
		}
		return false;
	}

	protected function array_filter_recursive(array $array, callable $callback = null) {
		$array = is_callable($callback) ? array_filter($array, $callback) : array_filter($array);
		foreach ($array as &$value) {
			if (is_array($value)) {
				$value = call_user_func(__FUNCTION__, $value, $callback);
			}
		}
		return $array;
	}

}


class dialogFlowSpeech {

}

class dialogFlowResponse {

}