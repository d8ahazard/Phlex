<?PHP
use Cz\Git\GitRepository;


require_once dirname(__FILE__) . '/vendor/autoload.php';

	// Checks whether an API Token exists for the current user, generates and saves one if none exists.
    // Returns generated or existing API Token.
	function checkSetApiToken($userName) {
		// Check that we have generated an API token for our user, create and save one if none exists
		$config = new Config_Lite('config.ini.php');
		$apiToken = false;
		foreach ($config as $section => $user) {
		    if ($section != "general") {
		    	if (($userName == urlencode($user['plexUserName'])) || ($userName == urlencode($user['email']))) {
		    		write_log("Found matching token for user ".$user['plexUserName']);
                    $apiToken = $user['apiToken'] ?? false;
                    break;
                }
            }
		}
		
		if (! $apiToken) {
			write_log("NO API TOKEN FOUND, generating one for ".$userName,"INFO");
			$apiToken = randomToken(21);
			$cleaned = str_repeat("X", strlen($apiToken)); 
			write_log("API token created ".$cleaned);
			$userString = 'user-_-'.$userName;
			$config->set('user-_-'.$userString,'apiToken',$apiToken);
			saveConfig($config);
			write_log("Setting some other values.");
			$_SESSION['apiToken'] = $apiToken;
			$_SESSION['newToken'] = true;
		}
		return $apiToken;
	}

    function cleanCommandString($string) {
        $string = trim(strtolower($string));
        $string = preg_replace("#[[:punct:]]#", "", $string);
        $string = preg_replace("/ask Flex TV/","",$string);
	    $string = preg_replace("/tell Flex TV/","",$string);
	    $string = preg_replace("/Flex TV/","",$string);
        $stringArray = explode(" "	,$string);
        $stripIn = array("of","the","an","a","at","th","nd","in","from","and");
        $stringArray = array_diff($stringArray,array_intersect($stringArray,$stripIn));
        $result = implode(" ",$stringArray);
        return $result;
    }

function flattenXML($xml){
	libxml_use_internal_errors(true);
	$return = [];
	if (is_string($xml)) {
		$xml = new SimpleXMLElement($xml);
	}
	if(!($xml instanceof SimpleXMLElement)){return false;}
	$_value = trim((string)$xml);
	if(strlen($_value)==0){$_value = null;};
	if($_value!==null){
		$return = $_value;
	}
	$children = array();
	$first = true;
	foreach($xml->children() as $elementName => $child){
		$value = flattenXML($child);
		if(isset($children[$elementName])){
			if($first){
				$temp = $children[$elementName];
				unset($children[$elementName]);
				$children[$elementName][] = $temp;
				$first=false;
			}
			$children[$elementName][] = $value;
		}
		else{
			$children[$elementName] = $value;
		}
	}
	if(count($children)>0){
		$return = array_merge($return,$children);
	}
	$attributes = array();
	foreach($xml->attributes() as $name=>$value){
		$attributes[$name] = trim($value);
	}
	if(count($attributes)>0){
		$return = array_merge($return, $attributes);
	}
	return $return;
}


// Generate a random token using the first available PHP function
    function randomToken($length = 32){
		write_log("Function fired.");
	    if(!isset($length) || intval($length) <= 8 ){
	      $length = 32;
	    }
	    if (function_exists('mcrypt_create_iv')) {
			write_log("Generating using mcrypt_create.");
	        return bin2hex(mcrypt_create_iv($length, MCRYPT_DEV_URANDOM));
	    } 
		if (function_exists('openssl_random_pseudo_bytes')) {
			write_log("Generating using pseudo_random.");
	        return bin2hex(openssl_random_pseudo_bytes($length));
	    }
		// Keep this last, as there appear to be issues with random_bytes and Docker.
		if (function_exists('random_bytes')) {
			write_log("Generating using random_bytes.");
	        return bin2hex(random_bytes($length));
	    }
	    return false;
	}
	
	// Generate a timestamp and return it
	function timeStamp() {
		return date(DATE_RFC2822,time());
	}
	
	// Recursively filter empty keys from an array
	// Returns filtered array.
	function array_filter_recursive( array $array, callable $callback = null ) {
		$array = is_callable( $callback ) ? array_filter( $array, $callback ) : array_filter( $array );
		foreach ( $array as &$value ) {
			if ( is_array( $value ) ) {
				$value = call_user_func( __FUNCTION__, $value, $callback );
			}
		}
 
		return $array;
	}
	
	//Get the current protocol of the server
	function serverProtocol() {
	   return (((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')	|| $_SERVER['SERVER_PORT'] == 443) ? 'https://' : 'http://');
	}
	
	//Get the relative path to $to in relation to where $from is
	function getRelativePath($from, $to)
	{
		// some compatibility fixes for Windows paths
		$from = is_dir($from) ? rtrim($from, '\/') . '/' : $from;
		$to   = is_dir($to)   ? rtrim($to, '\/') . '/'   : $to;
		$from = str_replace('\\', '/', $from);
		$to   = str_replace('\\', '/', $to);

		$from     = explode('/', $from);
		$to       = explode('/', $to);
		$relPath  = $to;

		foreach($from as $depth => $dir) {
			// find first non-matching dir
			if($dir === $to[$depth]) {
				// ignore this directory
				array_shift($relPath);
			} else {
				// get number of remaining dirs to $from
				$remaining = count($from) - $depth;
				if($remaining > 1) {
					// add traversals up to first matching dir
					$padLength = (count($relPath) + $remaining - 1) * -1;
					$relPath = array_pad($relPath, $padLength, '..');
					break;
				} else {
					$relPath[0] = '/' . $relPath[0];
				}
			}
		}
		return implode('/', $relPath);
	}
	
	// Grab an image from a server and save it locally
	function cacheImage($url,$image=false) {
    	write_log("Function fired, caching ".$url);
        $path = $url;
        $cached_filename = false;
		try {
			$URL_REF = $_SESSION['publicAddress'] ?? 'https://'.$_SERVER['HTTP_HOST'];
			$cacheDir = file_build_path(dirname(__FILE__),"img","cache");
			if (!file_exists($cacheDir)) {
				write_log("No cache directory found, creating.","INFO");
				mkdir($cacheDir, 0777, true);
			}
			if ($url) {
			    $cached_filename = md5($url);
				$files = glob($cacheDir . '/*.{jpg,jpeg,png,gif}', GLOB_BRACE);
				$now = time();
				foreach($files as $file) {
				    $fileName = explode('.',basename($file));
	                if ($fileName[0] == $cached_filename) {
	                    write_log("File is already cached.");
	                    $path = $URL_REF .getRelativePath(dirname(__FILE__),$file);
	                } else {
	                    if (is_file($file)) {
	                        if ($now - filemtime($file) >= 60 * 60 * 24 * 5) { // 5 days
	                            unlink($file);
	                        }
	                    }
	                }
				}
			}
			if ($image) {
				$cached_filename = md5($image);
			}
			if ((($path == $url) || ($image)) && ($cached_filename)) {
				write_log("Caching file.");
			    if (! $image) $image = file_get_contents($url);
                if ($image) {
                	write_log("Image retrieved successfully!");
                    $tempName = file_build_path($cacheDir, $cached_filename);
                    file_put_contents($tempName, $image);
                    $imageData = getimagesize($tempName);
                    $extension = image_type_to_extension($imageData[2]);
                    if ($extension) {
                    	write_log("Extension detected successfully!");
                        $filenameOut = file_build_path($cacheDir, $cached_filename.$extension);
                        $result = file_put_contents($filenameOut, $image);
                        if ($result) {
                            rename($tempName, $filenameOut);
                            $path = $URL_REF . getRelativePath(dirname(__FILE__), $filenameOut);
                            write_log("Success, returning cached URL: ".$path);
                        }
                    } else {
                        unset($tempName);
                    }
                }
            }
		} catch (\Exception $e) {
			write_log('Exception: ' . $e->getMessage());
		}
		return $path;
	}

	function setStartUrl() {
		$fileOut = dirname(__FILE__)."/manifest.json";
		$file = (file_exists($fileOut)) ? $fileOut : dirname(__FILE__)."/manifest_template.json";
		$json = json_decode(file_get_contents($file),true);
		$url = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
		write_log("JSON: ".json_encode($json));
		if ($json['start_url'] !== $url) {
			$json['start_url'] = $url;
			file_put_contents($fileOut, json_encode($json,JSON_PRETTY_PRINT));
		}
	}

	function transcodeImage($path,$uri="",$token="") {
    	if (preg_match("/library/",$path)) {
    		if ($uri) $server = $uri;
		    $server = $server ?? $_SESSION['plexServerPublicUri'] ?? $_SESSION['plexServerUri'] ?? false;
		    if ($token) $serverToken = $token;
		    $token = $serverToken ?? $_SESSION['plexServerToken'];
		    if ($server && $token) {
			    return $server . "/photo/:/transcode?width=1920&height=1920&minSize=1&url=" . urlencode($path) . "%3FX-Plex-Token%3D" . $token . "&X-Plex-Token=" . $token;
		    }
	    }
	    write_log("Invalid image path, returning generic image.","WARN");
		$path = 'https://phlexchat.com/img/avatar.png';
	    return $path;
	}

	// Check if string is present in an array
	function arrayContains($str, array $arr)	{
		//write_log("Function Fired.");
		$result = array_intersect($arr,explode(" ",$str));
		if (count($result)==1) $result = true;
		if (count($result)==0) $result = false;
		return $result;
	}

	function initMCurl() {
        return JMathai\PhpMultiCurl\MultiCurl::getInstance();
    }

	// Fetch data from a URL using CURL
	function curlGet($url, $headers=null,$timeout=4) {
    	$cert = getContent(file_build_path(dirname(__FILE__),"cacert.pem"),'https://curl.haxx.se/ca/cacert.pem');
    	if (!$cert) $cert = file_build_path(dirname(__FILE__),"cert","cacert.pem");
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL,$url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
		curl_setopt ($ch, CURLOPT_CAINFO, $cert);
		if ($headers !== null) curl_setopt($ch, CURLOPT_HTTPHEADER,$headers);
		$result = curl_exec($ch);
		if (!curl_errno($ch)) {
			switch ($http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE)) {
				case 200:
					break;
				default:
					write_log('Unexpected HTTP code: '. $http_code.', URL: '.$url, "ERROR");
					$result = false;
			}
		}
		curl_close($ch);
		return $result;
	}
	
	
	function curlPost($url,$content=false,$JSON=false, Array $headers=null) {
		$cert = getContent(file_build_path(dirname(__FILE__),"cacert.pem"),'https://curl.haxx.se/ca/cacert.pem');
		if (!$cert) $cert = file_build_path(dirname(__FILE__),"cert","cacert.pem");
        $mc = JMathai\PhpMultiCurl\MultiCurl::getInstance();
        $curl = curl_init($url);
		curl_setopt($curl, CURLOPT_HEADER, false);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt ($curl, CURLOPT_CAINFO, $cert);
		curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 4);
        curl_setopt($curl, CURLOPT_TIMEOUT, 3);
        if ($headers) {
            if ($JSON){
                $headers = array_merge($headers,array("Content-type: application/json"));
            }

            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        } else {
            if ($JSON){
                $headers = array("Content-type: application/json");
                curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
            }
        }
        if ($content) curl_setopt($curl, CURLOPT_POSTFIELDS, $content);
		//$response = curl_exec($curl);
		//curl_close($curl);
        $call = $mc->addCurl($curl);
        // Access response(s) from your cURL calls.
        $result = $call->response;
		return $result;
	}
	
	// Write log information to $filename
	// Auto rotates files larger than 2MB
	function write_log($text,$level=null,$caller=false) {
		if ($level === null) {
			$level = 'DEBUG';
		}
		if (isset($_GET['pollPlayer'])) return;
		$caller = $caller ? $caller : getCaller();
		$filename = file_build_path(dirname(__FILE__),'logs',"Phlex.log");
		//$filename = 'Phlex.log';
		$text = '['.date(DATE_RFC2822) . '] ['.$level.'] ['.$caller . "] - " . trim($text) . PHP_EOL;
		if (!file_exists($filename)) { touch($filename); chmod($filename, 0666); }
		if (filesize($filename) > 2*1024*1024) {
			$filename2 = "$filename.old";
			if (file_exists($filename2)) unlink($filename2);
			rename($filename, $filename2);
			touch($filename); chmod($filename,0666);
		}
		if (!is_writable($filename)) die;
		if (!$handle = fopen($filename, 'a+')) die;
		if (fwrite($handle, $text) === FALSE) die;
		fclose($handle);
	}

	function isDomainAvailible($domain) {
		//check, if a valid url is provided
		if(!filter_var($domain, FILTER_VALIDATE_URL))
		{
			return false;
		}

		//initialize curl
		$curlInit = curl_init($domain);
		curl_setopt($curlInit,CURLOPT_CONNECTTIMEOUT,10);
		curl_setopt($curlInit,CURLOPT_HEADER,true);
		curl_setopt($curlInit,CURLOPT_NOBODY,true);
		curl_setopt($curlInit,CURLOPT_RETURNTRANSFER,true);

		//get answer
		$response = curl_exec($curlInit);

		curl_close($curlInit);

		if ($response) return true;

		return false;
	}

	function logUpdate(array $log) {
		$config = new Config_Lite('config.ini.php');
		$filename = file_build_path(dirname(__FILE__),'logs',"Phlex_update.log");
		$data['installed'] = date(DATE_RFC2822);
		$data['commits'] = $log;
		$config->set("general","lastUpdate",$data['installed']);
		saveConfig($config);
		unset($_SESSION['updateAvailable']);
		if (!file_exists($filename)) { touch($filename); chmod($filename, 0666); }
		if (filesize($filename) > 2*1024*1024) {
			$filename2 = "$filename.old";
			if (file_exists($filename2)) unlink($filename2);
			rename($filename, $filename2);
			touch($filename); chmod($filename,0666);
		}
		if (!is_writable($filename)) die;
		$json=json_decode(file_get_contents($filename),true) ?? [];
		array_unshift($json,$data);
		file_put_contents($filename,json_encode($json));
	}

	function readUpdate() {
		$log = false;
		$filename = file_build_path(dirname(__FILE__),'logs',"Phlex_update.log");
		if (file_exists($filename)) {
			$log = json_decode(file_get_contents($filename),true) ?? [];
		}
		return $log;
	}

	function clientHeaders() {
        return array(
            'X-Plex-Client-Identifier:' . checkSetDeviceID(),
            'X-Plex-Target-Client-Identifier:' . $_SESSION['plexClientId'],
            'X-Plex-Device:PhlexWeb',
            'X-Plex-Device-Name:Phlex',
            'X-Plex-Device-Screen-Resolution:1520x707,1680x1050,1920x1080',
            'X-Plex-Platform:Web',
            'X-Plex-Platform-Version:1.0.0',
            'X-Plex-Product:Phlex',
            'X-Plex-Version:3.9.1'
        );
    }

	function clientHeaderArray() {
		return [
			'X-Plex-Client-Identifier'=> checkSetDeviceID(),
			'X-Plex-Target-Client-Identifier'=> $_SESSION['plexClientId'],
			'X-Plex-Device'=>'PhlexWeb',
			'X-Plex-Device-Name'=>'Phlex',
			'X-Plex-Device-Screen-Resolution'=>'1520x707,1680x1050,1920x1080',
			'X-Plex-Platform'=>'Web',
			'X-Plex-Platform-Version'=>'1.0.0',
			'X-Plex-Product'=>'Phlex',
			'X-Plex-Version'=>'3.9.1'
		];
	}

function clientString() {
	$string = '&X-Plex-Product=Phlex'.
		'&X-Plex-Version=3.9.1'.
		'&X-Plex-Client-Identifier=' . checkSetDeviceID().
		'&X-Plex-Platform=Web'.
		'&X-Plex-Platform-Version=1.0.0'.
		'&X-Plex-Device=PhlexWeb'.
		'&X-Plex-Device-Name=Phlex'.
		'&X-Plex-Device-Screen-Resolution=1520x707,1680x1050,1920x1080'.
		'&X-Plex-Token=' .$_SESSION['plexServerToken'].
		'&X-Plex-Target-Client-Identifier=' . $_SESSION['plexClientId'];
	return $string;
}


	// Get the name of the function calling write_log
	function getCaller() {
		$trace = debug_backtrace();
		$useNext = false;
		$caller = false;
		//write_log("TRACE: ".print_r($trace,true),null,true);
		foreach($trace as $event) {
			if ($useNext) {
				if (($event['function'] != 'require') && ($event['function'] != 'include')) {
					$caller .= "::".$event['function'];
					break;
				}
			}
			if (($event['function'] == 'write_log') || ($event['function'] == 'doRequest')) {
				$useNext = true;
				// Set our caller as the calling file until we get a function
				$file = pathinfo($event['file']);
				$caller = $file['filename'].".".$file['extension'];
			}
		}
		return $caller;   
	}

    // Save the specified configuration file using CONFIG_LITE
	function saveConfig(Config_Lite $inConfig) {
		try {
            $inConfig->save();
		} catch (Config_Lite_Exception $e) {
			echo "\n" . 'Exception Message: ' . $e->getMessage();
			write_log('Error saving configuration.','ERROR');
		}
		$configFile = file_build_path(dirname(__FILE__),"config.ini.php");
		$cache_new = "'; <?php die('Access denied'); ?>"; // Adds this to the top of the config so that PHP kills the execution if someone tries to request the config-file remotely.
		$cache_new .= file_get_contents($configFile);
		file_put_contents($configFile,$cache_new);
		
	}
	
	function protectURL($string) {
    	if ($_SESSION['cleanLogs']) {
            $keys = parse_url($string);
		    $parts = explode(".",$keys['host']);
		    if (count($parts) >= 2) {
			    $i = 0;
			    foreach($parts as $part) {
				    if ($i != 0) {
					    $parts[$i] = str_repeat("X", strlen($part));
				    }
				    $i++;
			    }
			    $cleaned = implode(".",$parts);
		    } else {
			    $cleaned = str_repeat("X", strlen($keys['host']));
		    }
		    $string = str_replace($keys['host'], $cleaned, $string);

		    $cleaned = str_repeat("X", strlen($keys['host']));
            $string = str_replace($keys['host'], $cleaned, $string);
            $pairs = array();
            if ($keys['query']) {
            	parse_str($keys['query'],$pairs);
                foreach ($pairs as $key => $value) {
                    if ((preg_match("/token/", $key)) || (preg_match("/Token/", $key))) {
                        $cleaned = str_repeat("X", strlen($value));
                        $string = str_replace($value, $cleaned, $string);
                    }
                    if (preg_match("/address/", $key)) {
                    	$parts = explode(".",$value);
                    	write_log("Parts: ".json_encode($parts));
                    	if (count($parts) >= 2) {
                    		$i = 0;
                    		foreach($parts as &$part) {
                    			if ($i <= count($parts) - 1) {
                    				$part = str_repeat("X", strlen($part));
			                    }
			                    $i++;
		                    }
		                    $cleaned = implode(".",$parts);
	                    } else {
		                    $cleaned = str_repeat("X", strlen($value));
	                    }
	                    $string = str_replace($value, $cleaned, $string);
                    }
                }
            }
        }
		return $string;
	}
	
	// A more precise way of calculating the similarity between two strings
	function similarity($str1, $str2) {
		$len1 = strlen($str1);
		$len2 = strlen($str2);
		
		$max = max($len1, $len2);
		$similarity = $i = $j = 0;
		
		while (($i < $len1) && isset($str2[$j])) {
			if ($str1[$i] == $str2[$j]) {
				$similarity++;
				$i++;
				$j++;
			} elseif ($len1 < $len2) {
				$len1++;
				$j++;
			} elseif ($len1 > $len2) {
				$i++;
				$len1--;
			} else {
				$i++;
				$j++;
			}
		}

		return round($similarity / $max, 2);
	}

    // Check if we have a running session before trying to start one
    function session_started() {
        if ( php_sapi_name() !== 'cli' ) {
            if ( version_compare(phpversion(), '5.4.0', '>=') ) {
                return session_status() === PHP_SESSION_ACTIVE ? TRUE : FALSE;
            } else {
                return session_id() === '' ? FALSE : TRUE;
            }
        }
        return FALSE;
    }

    // Check the validity of a URL response
    function check_url($url, $post=false) {
        write_log("Checking URL: ".$url);
        $certPath = file_build_path(dirname(__FILE__),"cert","cacert.pem");
        $ch = curl_init($url);
        curl_setopt($ch,  CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_TIMEOUT,2);
        curl_setopt ($ch, CURLOPT_CAINFO, $certPath);
        if ($post) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $_SESSION['plex_headers']);
        }
        /* Get the HTML or whatever is linked in $url. */
        curl_exec($ch);
		/* Check for 404 (file not found). */
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        /* If the document has loaded successfully without any redirection or error */
        if ($httpCode >= 200 && $httpCode < 300) {
            write_log("Connection is valid: ".$url);
            return true;
        } else {
            write_log("Connection failed with error code ".$httpCode.": ".$url,"ERROR");
            return false;
        }
    }


    // Build a path with OS-agnostic separator
    function file_build_path(...$segments) {
        return join(DIRECTORY_SEPARATOR, $segments);
    }

    function fetchCastDevices() {
        $returns = false;
    	if (!(isset($_GET['pollPlayer']))) write_log("Function fired.");
        $result = Chromecast::scan();
        if ($result) $returns = array();
		if (!(isset($_GET['pollPlayer']))) write_log("Returns: ".json_encode($result));
		if ($result[0] == "Error") return false;
        foreach ($result as $key=>$value) {
            $deviceOut = array();
            $nameString = preg_replace("/\._googlecast.*/","",$key);
            $nameArray = explode('-',$nameString);
            $id = array_pop($nameArray);
            $deviceOut['name'] = $value['friendlyname'];
            $deviceOut['product'] = 'cast';
            $deviceOut['id'] = $id;
            $deviceOut['token'] = 'none';
            $deviceOut['uri'] = "https://" . $value['ip'] . ":" . $value['port'];
            array_push($returns, $deviceOut);
        }

        return $returns;
    }

// Sign in, get a token if we need it

function signIn($credString) {
    $token = $_SESSION['plex_token'] ?? false;
    if ($token) {
        $url = 'https://plex.tv/pms/servers.xml?X-Plex-Token=' . $token;
        $result = curlGet($url);
        if (strpos($result, 'Please sign in.')) {
            write_log("Token invalid, signing in.");
            $token = false;
        } else {
            unset($token);
            $token['authToken'] = $_SESSION['plex_token'];
        }
    }
    if (! $token) {
        write_log("No token or not signed in, signing into Plex.");
        $url='https://plex.tv/users/sign_in.xml';
        $headers = [
            'X-Plex-Client-Identifier: '.checkSetDeviceID(),
            'X-Plex-Device:PhlexWeb',
            'X-Plex-Device-Screen-Resolution:1520x707,1680x1050,1920x1080',
            'X-Plex-Device-Name:Phlex',
            'X-Plex-Platform:Web',
            'X-Plex-Platform-Version:1.0.0',
            'X-Plex-Product:Phlex',
            'X-Plex-Version:1.0.0',
            'X-Plex-Provides:player,controller,sync-target,pubsub-player',
            'Authorization:Basic '.$credString
        ];
        $result=curlPost($url,false,false,$headers);
        if ($result) {
            $container = new SimpleXMLElement($result);
            $container = json_decode(json_encode($container),true)['@attributes'];
            write_log("Container: ".json_encode($container));
            $token = $container;
        }
    }
    return $token;
}

function checkSetDeviceID() {
    $config = new Config_Lite('config.ini.php');
    $deviceID = $config->get('general', 'deviceID', false);
    if (! $deviceID) {
        $deviceID = randomToken(12);
        $config->set("general","deviceID",$deviceID);
        saveConfig($config);
    }
    return $deviceID;
}

function fetchDirectory($id=1) {
    if ($id==1) return base64_decode("Y2QyMjlmNTU5NWZjYWEyNzI3MGI0NDU4OTIyOGE0OTI=");
	if ($id==2) return base64_decode("Njk0Nzg2RjBBMkVCNEUwOQ==");
	if ($id==3) return base64_decode("Y2NiODRhNTU3MDljNGYxOWFmMzlmN2RiZjZiNGQ1Mjg=");
	return false;
}

function setDefaults() {
    ini_set("log_errors", 1);
    ini_set('max_execution_time', 300);
    error_reporting(E_ERROR);
	$errorLogPath = file_build_path(dirname(__FILE__),'logs',"Phlex_error.log");
    ini_set("error_log", $errorLogPath);
    date_default_timezone_set((date_default_timezone_get() ? date_default_timezone_get() : "America/Chicago"));
    checkFiles();
}

function checkFiles() {
	$messages = [];
    $logDir = file_build_path(dirname(__FILE__),"logs");
    if(!is_dir($logDir) && !mkdir($logDir, 0777, true)) {
        die("Cannot create logs folder");
    }
    $logPath = file_build_path($logDir,"Phlex.log");
    $errorLogPath = file_build_path($logDir,"Phlex_error.log");
	$oldLogPath = file_build_path($logDir,"PhlexUpdate.log");
	$updateLogPath = file_build_path($logDir,"Phlex_update.log");
	if (file_exists($oldLogPath)) unlink($oldLogPath);
    $files = [$logPath,$errorLogPath,$updateLogPath,'config.ini.php','commands.php'];
    foreach ($files as $file) {
        if (!file_exists($file)) {
            touch($file);
            chmod($file, 0777);
        }
        if ((file_exists($file) && (!is_writable(dirname($file)) || !is_writable($file))) || !is_writable(dirname($file))) { // If file exists, check both file and directory writeable, else check that the directory is writeable.
            $message = 'Either the file '. $file .' and/or it\'s parent directory is not writable by the PHP process. Check the permissions & ownership and try again.';
            $url = '';
            if (PHP_SHLIB_SUFFIX === "so") { //Check for POSIX systems.
                $message .= "  Current permission mode of ". $file. " is " .decoct(fileperms($file) & 0777);
                $message .= "  Current owner of " . $file . " is ". posix_getpwuid(fileowner($file))['name'];
                $message .= "  Refer to the README on instructions how to change permissions on the aforementioned files.";
                $url = 'http://www.computernetworkingnotes.com/ubuntu-12-04-tips-and-tricks/how-to-fix-permission-of-htdocs-in-ubuntu.html';
            } else if (PHP_SHLIB_SUFFIX === "dll") {
                $message .= "  Detected Windows system, refer to guides on how to set appropriate permissions."; //Can't get fileowner in a trivial manner.
	            $url = 'https://stackoverflow.com/questions/32017161/xampp-on-windows-8-1-cant-edit-files-in-htdocs';
            }
            write_log($message,"ERROR");
            $error = ['title'=>'File error.','message'=>$message,'url'=>$url];
            array_push($messages,$error);
        }
    }
    $extensions = ['sockets','curl','xml'];
    foreach ($extensions as $extension) {
        if (! extension_loaded($extension)) {
            $message = "The ". $extension . " PHP extension, which is required for Phlex to work correctly, is not loaded." .
                " Please enable it in php.ini, restart your webserver, and then reload this page to continue.";
	        write_log($message,"ERROR");
	        $url = "http://php.net/manual/en/book.$extension.php";
	        $error = ['title'=>'PHP Extension not loaded.','message'=>$message,'url'=>$url];
	        array_push($messages,$error);
        }
    }
    try {new Config_Lite('config.ini.php');} catch (Config_Lite_Exception_Runtime $e) {
        $message = "An exception occurred trying to load config.ini.php.  Please check that the directory and file are writeable by your webserver application and try again.";
        $error = ['title'=>'Config error.','message'=>$message];
	    array_push($messages,$error);
    };
    return $messages;
}

function clearSession() {
	write_log("Function fired");
	if (! session_started()) session_start();
	if (isset($_SERVER['HTTP_COOKIE'])) {
		write_log("Cookies found, unsetting.");
		$cookies = explode(';', $_SERVER['HTTP_COOKIE']);
		foreach($cookies as $cookie) {
			$parts = explode('=', $cookie);
			$name = trim($parts[0]);
			write_log("Cookie: ".$name);
			setcookie($name, '', time()-1000);
			setcookie($name, '', time()-1000, '/');
		}
	}
	session_start();
	session_unset();
	$has_session = session_status() == PHP_SESSION_ACTIVE;
	if ($has_session) session_destroy();
	session_write_close();
	setcookie(session_name(),'',0,'/');
	session_regenerate_id(true);
}

function addScheme($url, $scheme = 'http://')
{
	return parse_url($url, PHP_URL_SCHEME) === null ?
		$scheme . $url : $url;
}

// Shamelessly stolen from https://davidwalsh.name/php-cache-function
// But slightly updated to do what I needed it to do.

function getContent($file,$url,$hours = 56,$fn = '',$fn_args = '') {
	$current_time = time(); $expire_time = $hours * 60 * 60; $file_time = filemtime($file);
	if(file_exists($file) && ($current_time - $expire_time < $file_time)) {
		return $file;
	}
	else {
		$content = getUrl($url);
		if ($content) {
			if ($fn) {
				$content = $fn($content, $fn_args);
			}
			$content .= '<!-- cached:  ' . time() . '-->';
			file_put_contents($file, $content);
			write_log('Retrieved fresh from ' . $url,"INFO");
			if(file_exists($file)) return $file;
		}
		return false;
	}
}


function toBool($var) {
	if (!is_string($var)) return $var;
	switch (strtolower($var)) {
		case 'true':
			return true;
		case 'false':
			return false;
		default:
			return $var;
	}
}

function checkSetLanguage($source="") {
    $locale = false;
    $locales = ["en","fr"];
	$defaultLocale = locale_get_default();
	write_log("Source: $source");
	// If a session language is set
	if (isset($_SESSION['appLanguage'])) {
		$locale = $_SESSION['appLanguage'];
		write_log("Detected session language: $locale","INFO");
	} else {
		if (file_exists(dirname(__FILE__) . "/config.ini.php") && isset($_SESSION['plexUserName'])) {
			$config = new Config_Lite('config.ini.php');
			$locale = $config->get('user-_-' . $_SESSION['plexUserName'],"appLanguage",false);
			if ($locale) $_SESSION['appLanguage'] = $locale;
			write_log("Locale not set for session, but saved in config: $locale");
		} else {
			write_log("No session username, can't look in settings.","ERROR");
		}
	}
	if (! $locale) {
		write_log("No saved locale set, detecting from system.","INFO");
		if (trim($defaultLocale) != "") {
			$locale = explode("-",$locale)[0];
			write_log("Locale set from default: $locale","INFO");
			if (trim($locale) == "") $locale = false;
		}
	}
	if (!$locale) {
		write_log("Couldn't detect a default or saved locale, defaulting to English.","WARN");
		$locale = "en";
	}
	if (file_exists(dirname(__FILE__)."/lang/".$locale.".php")) {
		include(dirname(__FILE__) . "/lang/" . $locale . ".php");
	} else {
		write_log("Couldn't find the selected locale, defaulting to 'Murica.");
		include(dirname(__FILE__) . "/lang/en.php");
	}
	// This gets added automagically, ignore IDE warnings about it...
	return $lang;
}

function checkUpdates($install=false) {
	write_log("Function fired.".($install ? " Install requested." : ""));
    $installed = $pp = $result = false;
    $html = '';
	$autoUpdate = $_SESSION['autoUpdate'];
	write_log("Auto Update is ".($autoUpdate ? " on " : "off"));
	if (checkGit()) {
		write_log("This is a repo and GIT is available, checking for updates.");
		try {
			$repo = new GitRepository(dirname(__FILE__));
			if ($repo) {

				$repo->fetch('origin');
				$result = $repo->readLog('origin','HEAD');
				$revision = $repo->getRev();
				$logHistory = readUpdate();
				if (count($logHistory)) $installed = $logHistory[0]['installed'];
				$header = '<div class="cardHeader">
							Current revision: '.substr($revision,0,7).'<br>
							'.($installed ? "Last Update: ".$installed : '').'
						</div>';
				// This never works right when you're developing the app...
				if (1==2) {
					$html = $header. '<div class="cardHeader">
								Status: ERROR: Local file conflicts exist.<br><br>
							</div><br>';
					write_log("LOCAL CHANGES DETECTED.","ERROR");
					return $html;
				}

				if (count($result)) {
					$log = $result;
					$_SESSION['updateAvailable'] = count($log);
					$html = parseLog($log);
					$html = $header.'<div class="cardHeader">
								Status: '.count($log).' commit(s) behind.<br><br>
								Missing Update(s):'.$html.
							'</div>';
					if (($install) || ($autoUpdate)) {
						if (isset($_SESSION['pollPlayer'])) {
							$pp = true;
							unset($_SESSION['pollPlayer']);
						}
						write_log("Updating from repository - ".($install ? 'Manually triggered.' : 'Automatically triggered.'));
						$repo->pull('origin');
						//write_log("Pull result: ".$result);
						if ($pp) $_SESSION['pollPlayer'] = true;
						logUpdate($log);

					}
				} else {
					write_log("No changes detected.");
					if (count($logHistory)) {
						$html = parseLog($logHistory[0]['commits']);
						$installed = $logHistory[0]['installed'];
					} else {
						$html = parseLog($repo->readLog("origin/master", 0));
					}
					$html = $header.'<div class="cardHeader">
								Status: Up-to-date<br><br>
								'.($installed ? "Installed: ".$installed : '').'
							Last Update:'.$html.'</div><br>';
				}
			} else {
				write_log("Couldn't initialize git.", "ERROR");
			}
		} catch (\Cz\Git\GitException $e) {
			write_log("An exception has occurred: ".$e,"ERROR");
		}
	} else {
		write_log("Doesn't appear to be a cloned repository or git not available.","INFO");
	}
	return $html;

}

function parseLog($log) {
    $html = '';
	foreach($log as $commit) {
		$html .= '
								<div class="panel panel-primary">
						  			<div class="panel-heading cardHeader">
						    			<div class="panel-title">'.$commit['shortHead'].' - '.$commit['date'].'</div>
						  			</div>
							        <div class="panel-body cardHeader">
							            <b>'.$commit['subject'].'</b><br>'.$commit['body'].'
							        </div>
								</div>';
	}
	return $html;
}

function checkGit() {
	exec("git",$lines);
	return ((preg_match("/git help/",implode(" ",$lines))) && (file_exists(dirname(__FILE__).'/.git')));
}

/* gets content from a URL via curl */
function getUrl($url) {
	$ch = curl_init();
	curl_setopt($ch,CURLOPT_URL,$url);
	curl_setopt($ch, CURLOPT_TIMEOUT, 2);
	curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
	curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,5);
	$content = curl_exec($ch);
	if (!curl_errno($ch)) {
		switch ($http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE)) {
			case 200:  # OK
				break;
			default:
				write_log('Unexpected HTTP code: '. $http_code.', URL: '.$url, "ERROR");
				$content = false;
		}
	}
	curl_close($ch);
	return $content;
}

function tail($filename, $lines = 50, $buffer = 4096){
	if(!is_file($filename)){
		return false;
	}
	$f = fopen($filename, "rb");
	if(!$f){
		return false;
	}
	fseek($f, -1, SEEK_END);
	if(fread($f, 1) != "\n") $lines -= 1;
	$output = '';
	while(ftell($f) > 0 && $lines >= 0)
	{
		$seek = min(ftell($f), $buffer);
		fseek($f, -$seek, SEEK_CUR);
		$output = ($chunk = fread($f, $seek)).$output;
		fseek($f, -mb_strlen($chunk, '8bit'), SEEK_CUR);
		$lines -= substr_count($chunk, "\n");
	}

	while($lines++ < 0)
	{
		// Find first newline and remove all text before that
		$output = substr($output, strpos($output, "\n") + 1);
	}
	fclose($f);
	return $output;
}

function formatLog($logData) {
	$lines = array_reverse(explode("\n", $logData));
	$JSON = false;
	$records = [];
	unset($_GET['pollPlayer']);
	foreach ($lines as $line) {
		$sections = explode(" - ",$line);
		preg_match_all("/\[([^\]]*)\]/", $sections[0], $matches);
		$params = $matches[0];
		$message = trim($sections[1]);
		$message = preg_replace('~\{(?:[^{}]|(?R))*\}~', '', $message);
		if ($message !== trim($sections[1])) $JSON = true;
		if ($JSON) $JSON = str_replace($message,"",trim($sections[1]));
		if (count($params)>= 3) {
			$record = ['time' => substr($params[0],1,-1), 'level' => substr($params[1],1,-1), 'caller' => substr($params[2],1,-1), 'message' => $message];
			if ($JSON) $record['JSON'] = trim($JSON);
			array_push($records, $record);
		}
	}
	return json_encode($records);
}


function doRequest($parts,$timeout=3) {
	$type = isset($parts['type']) ? $parts['type'] : 'get';
	$response = false;
	$options = [];
	$cert = getContent(file_build_path(dirname(__FILE__),"cacert.pem"),'https://curl.haxx.se/ca/cacert.pem');
	if (!$cert) $cert = file_build_path(dirname(__FILE__),"cert","cacert.pem");
	if (is_array($parts)) {
		if (!isset($parts['uri'])) $parts['uri'] = $_SESSION['plexServerUri'];
		if (isset($parts['query'])) {
			if (!is_string($parts['query'])) {
				$string = '?';
				$i = 0;
				foreach ($parts['query'] as $key => $value) {
					if (!is_array($value)) {
						if ($i > 0) $string .= '&';
						$string .= $key . '=' . $value;
						$i++;
					} else {
						foreach ($value as $subkey => $subval) {
							if ($i > 0) $string .= '&';
							$string .= $subkey . '=' . urlencode($subval);
							$i++;
						}
					}
				}
				$parts['query'] = $string;
			}
		}

		$parts = array_merge(parse_url($parts['uri']), $parts);

		$url = (isset($parts['scheme']) ? "{$parts['scheme']}:" : '') .
			((isset($parts['user']) || isset($parts['host'])) ? '//' : '') .
			(isset($parts['user']) ? "{$parts['user']}" : '') .
			(isset($parts['pass']) ? ":{$parts['pass']}" : '') .
			(isset($parts['user']) ? '@' : '') .
			(isset($parts['host']) ? "{$parts['host']}" : '') .
			(isset($parts['port']) ? ":{$parts['port']}" : '') .
			(isset($parts['path']) ? "{$parts['path']}" : '') .
			(isset($parts['query']) ? "{$parts['query']}" : '') .
			(isset($parts['fragment']) ? "#{$parts['fragment']}" : '');
	} else {
		$url = $parts;
	}
	write_log("URL is ".protectURL($url),"INFO",getCaller());

	$client = new GuzzleHttp\Client(['timeout'=>$timeout,'verify'=>$cert]);

	try {
		if ($type == 'get') {
			$response = $client->get($url,$options);
		}

		if ($type == 'post') {
			if (isset($parts['headers'])) $options['headers'] = $parts['headers'];
			$response = $client->post($url,$options);
		}
	} catch (Throwable $e) {
		write_log("An exception occurred: ".$e->getMessage(),"ERROR");
		if ($e->getCode() == 401) {
			write_log("Unauthorized error, rescanning devices...","WARN");
			//scanDevices(true);
			return false;
		}
	}
	if ($response) {
		$code = $response->getStatusCode();
		if ($code == 200) {
			return $response->getBody()->getContents();
		} else {
			write_log("An error has occurred: ".$response->getReasonPhrase(),"ERROR");
			return false;
		}
	}
	return false;
}

