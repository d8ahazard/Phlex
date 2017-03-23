<?PHP

	function checkSetApiToken() {
		// Check that we have generated an API token for our user, create and save one if none exists
		foreach ($_SESSION['config'] as $section => $user) {
				if (($_SESSION['username'] = $user['plexUserName']) && ($section != "general")) {
					$apiToken = ($user['apiToken'] ? $user['apiToken'] : false);
				}
		}
		
		if (! $apiToken) {
			write_log("NO API TOKEN FOUND, generating one for ".$_SESSION['username']);
			$apiToken = randomToken(21);
			write_log("API token created ".$apiToken);
			$_SESSION['config']->set('user-_-'.$_SESSION['username'],'apiToken',$apiToken);
			saveConfig($_SESSION['config']);
		} else {
			write_log("EXISTING API TOKEN FOUND, RETURNING IT.");
		}
		
		$_SESSION['apiToken'] = $apiToken;
		return $apiToken;
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
		if (function_exists('random_bytes')) {
			write_log("Generating using random_bytes.");
	        return bin2hex(random_bytes($length));
	    }
	}
	
	// Generate a timestamp and return it
	function timeStamp() {
		$php_timestamp = time();
		$stamp = date(" h:i:s A - m/d/Y", $php_timestamp);
		return $stamp;
	}
	
	function array_filter_recursive( array $array, callable $callback = null ) {
		$array = is_callable( $callback ) ? array_filter( $array, $callback ) : array_filter( $array );
		foreach ( $array as &$value ) {
			if ( is_array( $value ) ) {
				$value = call_user_func( __FUNCTION__, $value, $callback );
			}
		}
 
		return $array;
	}
	
	function serverProtocol() {
	   return (((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')	|| $_SERVER['SERVER_PORT'] == 443) ? 'https://' : 'http://');
	}
	
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
					$relPath[0] = './' . $relPath[0];
				}
			}
		}
		return implode('/', $relPath);
	}
	
	function cacheImage($url) {
		try {
			$cacheDir = dirname(__FILE__) . '/img/cache/';
			$cached_filename = md5($url);
			$files = glob($cacheDir . '*.{jpg,jpeg,png,gif}', GLOB_BRACE);
			foreach($files as $file) {
				$fileName = explode('.',basename($file));
				if ($fileName[0] == $cached_filename) {
					  return getRelativePath(dirname(__FILE__),$file);
				}
			}
			$image = file_get_contents($url);
			if ($image) {
				write_log("Caching Image from URL: ".$url);
				$tempName = $cacheDir . $cached_filename;
				file_put_contents($tempName,$image);
				$imageData = getimagesize($tempName);
				$mimeType = image_type_to_mime_type($imageData[2]);
				$extension = image_type_to_extension($imageData[2]);
				if($extension) {
					$filenameOut = $cacheDir . $cached_filename . $extension;
					$result = file_put_contents($filenameOut, $image);
					if ($result) {
						rename($tempName,$filenameOut);
						return getRelativePath(dirname(__FILE__),$filenameOut);
					}
				} else {
					write_log("Supplied file doesn't appear to be an image.");
					unset($tempName);
				}
			}
		} catch (\Exception $e) {
			write_log('Exception: ' . $e->getMessage());
		}
		return false;
	}
	
	// Check if string is present in an array
	function arrayContains($str, array $arr)	{
		//write_log("Function Fired.");
		$result = array_intersect($arr,explode(" ",$str));
		if (count($result)==1) $result = implode($result);
		if (count($result)==0) $result = false;
		return $result;
	}
 
	function curlGet($url) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL,$url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt ($ch, CURLOPT_CAINFO, rtrim(dirname(__FILE__), '/') . "/cert/cacert.pem");
		$result = curl_exec($ch);
		curl_close ($ch);
		//write_log("URL is ".$url.". Result is ".$result);
		return $result;
	}
	
	// It would be nice to have this auto-read the name of the calling function...
	function write_log($text,$level=null) {
		if ($level === null) {
			$level = 'I';	
		}
		$caller = (string) getCaller();
		$filename = 'Phlex.log';
		$text = $level .'/'. date(DATE_RFC2822) . ': '.$caller . ": " . $text . PHP_EOL;
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
	
	// Get the name of the function calling the function that called getCaller (for logging)
	function getCaller($what = NULL) {
		$trace = debug_backtrace();
		$count = count($trace);
		if ($count >=4) {
			$previousCall = $trace[3]['function']; // 0 is this call, 1 is the include, 2 is call in previous function, 3 is caller of that function
		} else {
			$previousCall = $trace[$count - 2]['function'];
			if ($previousCall == 'include') $previousCall = $trace[$count - 1]['function'];
		}
		if(isset($what)) {
			return $previousCall[$what];
		} else {
			return $previousCall;
		}   
	}
	
	function saveConfig($inConfig) {
		write_log("Function fired.");
		try {
			$inConfig->save();
		} catch (Config_Lite_Exception $e) {
			echo "\n" . 'Exception Message: ' . $e->getMessage();
			write_log('Error saving configuration.','E');
		}
		$cache_new = "'; <?php die('Access denied'); ?>"; // Adds this to the top of the config so that PHP kills the execution if someone tries to request the config-file remotely.
		$cache_new .= file_get_contents(CONFIG);
		file_put_contents(CONFIG,$cache_new);
		
	}
?>