<?PHP
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
			write_log("Setting some other values.");
			saveConfig($config);
		}
		return $apiToken;
	}

    function cleanCommandString($string) {
        $string = trim(strtolower($string));
        $string = preg_replace("#[[:punct:]]#", "", $string);
        $stringArray = explode(" "	,$string);
        $stripIn = array("of","the","an","a","at","th","nd","in","from","and");
        $stringArray = array_diff($stringArray,array_intersect($stringArray,$stripIn));
        $result = implode(" ",$stringArray);
        return $result;
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
		$php_timestamp = time();
		$stamp = date(" h:i:s A - m/d/Y", $php_timestamp);
		return $stamp;
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
	function cacheImage($url) {
    	write_log("Function fired, caching ".$url);
        $path = '';
		try {
		    $URL_REF = $_SESSION['publicAddress'] ?? 'https://'.$_SERVER['HTTP_HOST'];
            $cacheDir = file_build_path(dirname(__FILE__),"img","cache");
            if (!file_exists($cacheDir)) {
                write_log("No cache directory found, creating.","INFO");
                mkdir($cacheDir, 0777, true);
            }
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
			if ($path == $url) {
				write_log("Caching file.");
			    $image = file_get_contents($url);
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

	function transcodeImage($path,$uri=false,$token=false) {
		write_log("Function fired");
    	write_log("Path: ".$path);
    	if ($uri) $server = $uri;
    	$server = $server ?? $_SESSION['plexServerUri'] ?? $_SESSION['plexServerPublicUri'] ?? false;
    	if ($token) $serverToken = $token;
    	$token = $serverToken ?? $_SESSION['plexServerToken'];
    	if ($server) {
		    $image = $server . "/photo/:/transcode?width=1920&height=1920&minSize=1&url=" . urlencode($path) . "%3FX-Plex-Token%3D".$token."&X-Plex-Token=" . $token;
		    write_log("Image path: " . $image);
		    if (checkRemoteFile($image)) {
		    	write_log("Image valid, returning");
		    	return $image;
		    }
	    } else {
		    $path = cacheImage($uri . $path . "?X-Plex-Token=" . $token);
		}
		return $path;
	}

	function checkRemoteFile($url) {
    	write_log("Function fired");
		$certPath = file_build_path(dirname(__FILE__),"cert","cacert.pem");
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL,$url);
		curl_setopt ($ch, CURLOPT_CAINFO, $certPath);
		curl_setopt($ch, CURLOPT_NOBODY, 1);
		curl_setopt($ch, CURLOPT_FAILONERROR, 1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		if(curl_exec($ch)!==FALSE)
		{
			write_log("Success.");
			return true;
		}
		else
		{
			write_log("Fail");
			return false;
		}
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
        $mc = JMathai\PhpMultiCurl\MultiCurl::getInstance();
        return $mc;
    }

	// Fetch data from a URL using CURL
	function curlGet($url, $headers=null,$timeout=4) {
        $certPath = file_build_path(dirname(__FILE__),"cert","cacert.pem");
        $mc = JMathai\PhpMultiCurl\MultiCurl::getInstance();
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL,$url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
		curl_setopt ($ch, CURLOPT_CAINFO, $certPath);
		if ($headers) curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		$call = $mc->addCurl($ch);
        // Access response(s) from your cURL calls.
        $result = $call->response;
		return $result;
	}
	
	
	function curlPost($url,$content=false,$JSON=false, Array $headers=null) {
        $certPath = file_build_path(dirname(__FILE__),"cert","cacert.pem");
        $mc = JMathai\PhpMultiCurl\MultiCurl::getInstance();
        $curl = curl_init($url);
		curl_setopt($curl, CURLOPT_HEADER, false);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt ($curl, CURLOPT_CAINFO, $certPath);
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
	function write_log($text,$level=null) {
		if (isset($_GET['pollPlayer'])) return;
		if ($level === null) {
			$level = 'DEBUG';
		}
		$caller = getCaller();
		$filename = 'Phlex.log';
		$text = date(DATE_RFC2822) . ' [ '.$level.' ] '.$caller . " - " . $text . PHP_EOL;
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
            'X-Plex-Version:1.0.0'
        );
    }

function clientString() {
	return '&X-Plex-Client-Identifier=' . checkSetDeviceID().
		'&X-Plex-Target-Client-Identifier=' . $_SESSION['plexClientId'].
		'&X-Plex-Device=PhlexWeb'.
		'&X-Plex-Device-Name=Phlex'.
		'&X-Plex-Device-Screen-Resolution=1520x707,1680x1050,1920x1080'.
		'&X-Plex-Platform=Web'.
		'&X-Plex-Platform-Version=1.0.0'.
		'&X-Plex-Product=Phlex'.
		'&X-Plex-Version=1.0.0';
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
			if ($event['function'] == 'write_log') {
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
    	write_log("CleanLogs: ".(($_SESSION['cleanLogs'] == "true") ? 'ON' : 'OFF'));
        if ($_SESSION['cleanLogs'] == "true") {
            $keys = parse_url($string);
            $cleaned = str_repeat("X", strlen($keys['host']));
            $string = str_replace($keys['host'], $cleaned, $string);
            $pairs = array();
            if ($keys['query']) {
                $params = explode('&', $keys['query']);
                foreach ($params as $key) {
                    $set = explode('=', $key);
                    if (count($set) == 2) {
                        $pairs[$set[0]] = $set[1];
                    }
                }
            }
            if (!empty($pairs)) {
                foreach ($pairs as $key => $value) {
                    if ((preg_match("/token/", $key)) || (preg_match("/Token/", $key)) || (preg_match("/address/", $key))) {
                        $cleaned = str_repeat("X", strlen($value));
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
        $response = curl_exec($ch);

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

function setDefaults() {
    ini_set("log_errors", 1);
    ini_set('max_execution_time', 300);
    error_reporting(E_ERROR);
    $errfilename = 'Phlex_error.log';
    ini_set("error_log", $errfilename);
    date_default_timezone_set((date_default_timezone_get() ? date_default_timezone_get() : "America/Chicago"));
    checkFiles();
}

function checkFiles() {
    $files = ['Phlex.log','Phlex_error.log','config.ini.php','commands.php'];
    foreach ($files as $file) {
        if (!file_exists($file)) {
            touch($file);
            chmod($file, 0777);
        }
        if ((file_exists($file) && (!is_writable(dirname($file)) || !is_writable($file))) || !is_writable(dirname($file))) { // If file exists, check both file and directory writeable, else check that the directory is writeable.
            $message = 'Either the file '. $file .' and/or it\'s parent directory is not writable by the PHP process. Check the permissions & ownership and try again.';
            if (PHP_SHLIB_SUFFIX === "so") { //Check for POSIX systems.
                $message .= "  Current permission mode of ". $file. " is " .decoct(fileperms($file) & 0777);
                $message .= "  Current owner of " . $file . " is ". posix_getpwuid(fileowner($file))['name'];
                $message .= "  Refer to the README on instructions how to change permissions on the aforementioned files.";
            } else if (PHP_SHLIB_SUFFIX === "dll") {
                $message .= "  Detected Windows system, refer to guides on how to set appropriate permissions."; //Can't get fileowner in a trivial manner.
            }
            //$scriptBlock = "<script language='javascript'>alert(\"" . $message . "\");</script>";
            //echo $scriptBlock;
	        write_log($message,"ERROR");
            return $message;
            die();
        }
    }
    $extensions = ['sockets','curl'];
    foreach ($extensions as $extension) {
        if (! extension_loaded($extension)) {
            $message = "The ". $extension . " PHP extension, which is required for Phlex to work correctly, is not loaded." .
                "Please enable it in php.ini, restart your webserver, and then reload this page to continue.";
	        write_log($message,"ERROR");
            return $message;
        }
    }
    try {$config = new Config_Lite('config.ini.php');} catch (Config_Lite_Exception_Runtime $e) {
        $message = "An exception occurred trying to load config.ini.php.  Please check that the directory and file are writeable by your webserver application and try again: ".$e;
        //$scriptBlock = "<script language='javascript'>alert(\"" . $message . "\");</script>";
        //echo $scriptBlock;
	    write_log($message,"ERROR");
        return $message;
        die();
    };
    return false;
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
	session_destroy();
	header('Location: '.$_SERVER['PHP_SELF']);
	die;
}

function addScheme($url, $scheme = 'http://')
{
	return parse_url($url, PHP_URL_SCHEME) === null ?
		$scheme . $url : $url;
}

