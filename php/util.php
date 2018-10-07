<?PHP

require_once dirname(__FILE__) . '/vendor/autoload.php';
require_once dirname(__FILE__) . '/JsonXmlElement.php';
require_once dirname(__FILE__) . '/multiCurl.php';

function array_diff_assoc_recursive($array1, $array2)
{
    foreach($array1 as $key => $value)
    {
        if(is_array($value))
        {
            if(!isset($array2[$key]))
            {
                $difference[$key] = $value;
            }
            elseif(!is_array($array2[$key]))
            {
                $difference[$key] = $value;
            }
            else
            {
                $new_diff = array_diff_assoc_recursive($value, $array2[$key]);
                if($new_diff != FALSE)
                {
                    $difference[$key] = $new_diff;
                }
            }
        }
        elseif(!isset($array2[$key]) || $array2[$key] != $value)
        {
            $difference[$key] = $value;
        }
    }
    return !isset($difference) ? 0 : $difference;
}


function array_filter_recursive(array $array, callable $callback = null) {
    $array = is_callable($callback) ? array_filter($array, $callback) : array_filter($array);
    foreach ($array as &$value) {
        if (is_array($value)) {
            $value = call_user_func(__FUNCTION__, $value, $callback);
        }
    }
    return $array;
}


function arrayContains($str, array $arr) {
    //write_log("Function Fired.");
    $result = array_intersect($arr, explode(" ", $str));
    if (count($result) == 1) $result = true;
    if (count($result) == 0) $result = false;
    return $result;
}

function bye($msg = false, $title = false, $url = false, $log = false, $clear = false) {
    if ($msg) {
        $display = "<script type=text/javascript>
                    var array = [{title:'$title',message:'$msg',url:'$url'}];
                    loopMessages(array);
                </script>";
	    echo $display;
    }
    $actual_link = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
    $url = parse_url($actual_link);
    $url = $url['scheme']."://".$url['host'].$url['path'];
    $url = "$url?device=Client&id=rescan&passive=true&apiToken=".$_SESSION['apiToken'];
    $rescan = $_GET['pollPlayer'] ?? $_GET['passive'] ?? null;
    $executionTime = round(microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"],2)."s";

    if ($rescan === null) {
        curlQuick($url);
    }
    if ($log) write_log("Ending session now with message '$msg'.", "INFO");
    if ($clear) clearSession();
    // TODO: Make sure this is only done when webflag is set

    write_log("-------TOTAL RUN TIME: $executionTime-------","ALERT");
	if (function_exists('fastcgi_finish_request')) {
		fastcgi_finish_request();
	}
    die();
}

function build_sorter($key) {
    return function ($a, $b) use ($key) {
        return $a["$key"] <=> $b["$key"];
    };
}

function cacheImage($url) {
    $block = parse_url($url);
    $host = $block['host'] ?? $block['address'];
	$isIp = filter_var($host,FILTER_VALIDATE_IP);
	if ($isIp) {
		$filtered = filter_var($host, FILTER_VALIDATE_IP,
			FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE |  FILTER_FLAG_NO_RES_RANGE | FILTER_FLAG_IPV6
		);
	} else {
		$filtered = !preg_match("/localhost/",$host);
	}
	$good = ($filtered);
	if ($good) {
		write_log("No need to cache, this should be a valid public address.","INFO");
		return $url;
	}
    $path = $url;

    $homeAddress = rtrim($_SESSION['publicAddress'] ?? fetchUrl(false),"/");
    $cacheDir = file_build_path(dirname(__FILE__), "..", "img", "cache");
    checkCache($cacheDir);
    $cached_filename = md5($url);
    $files = glob($cacheDir . '/*.{jpg,jpeg,png,gif}', GLOB_BRACE);
    $now = time();
    foreach ($files as $file) {
        $fileName = explode('.', basename($file));
        if ($fileName[0] == $cached_filename) {
        	$path = "$homeAddress/img/cache/".basename($file);
        } else {
            if (is_file($file)) {
                if ($now - filemtime($file) >= 60 * 60 * 24 * 5) { // 5 days
                    unlink($file);
                }
            }
        }
    }

    if ($path == $url && $cached_filename) {
        $image = file_get_contents($url);
        if ($image) {
	        $tempName = file_build_path($cacheDir, $cached_filename);
	        file_put_contents($tempName, $image);
	        $imageData = getimagesize($tempName);
	        $extension = image_type_to_extension($imageData[2]);
	        if ($extension) {
		        $outFile = file_build_path($cacheDir, $cached_filename . $extension);
		        $result = file_put_contents($outFile, $image);
		        if ($result) {
			        rename($tempName, $outFile);
			        $path = "$homeAddress/img/cache/${cached_filename}${extension}";
		        }
	        } else {
		        unset($tempName);
	        }
        } else {
	        write_log("Unable to fetch image: $url","WARN");
        }
    }

    return $path;
}



function checkUrl($url, $returnError=false) {
	$cert = getCert();
	$url = filter_var($url, FILTER_SANITIZE_URL);
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        write_log("URL $url is not valid.","ERROR");
        return false;
    }

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_CAINFO, $cert);

    $result = curl_exec($ch);
    /* Get the error code. */
    $httpCode = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $errMsg = curl_error($ch);
    curl_close($ch);

    $codes = array(100 => "Continue", 101 => "Switching Protocols", 102 => "Processing", 200 => "OK", 201 => "Created", 202 => "Accepted", 203 => "Non-Authoritative Information", 204 => "No Content", 205 => "Reset Content", 206 => "Partial Content", 207 => "Multi-Status", 300 => "Multiple Choices", 301 => "Moved Permanently", 302 => "Found", 303 => "See Other", 304 => "Not Modified", 305 => "Use Proxy", 306 => "(Unused)", 307 => "Temporary Redirect", 308 => "Permanent Redirect", 400 => "Bad Request", 401 => "Unauthorized", 402 => "Payment Required", 403 => "Forbidden", 404 => "Not Found", 405 => "Method Not Allowed", 406 => "Not Acceptable", 407 => "Proxy Authentication Required", 408 => "Request Timeout", 409 => "Conflict", 410 => "Gone", 411 => "Length Required", 412 => "Precondition Failed", 413 => "Request Entity Too Large", 414 => "Request-URI Too Long", 415 => "Unsupported Media Type", 416 => "Requested Range Not Satisfiable", 417 => "Expectation Failed", 418 => "I'm a teapot", 419 => "Authentication Timeout", 420 => "Enhance Your Calm", 422 => "Unprocessable Entity", 423 => "Locked", 424 => "Failed Dependency", 424 => "Method Failure", 425 => "Unordered Collection", 426 => "Upgrade Required", 428 => "Precondition Required", 429 => "Too Many Requests", 431 => "Request Header Fields Too Large", 444 => "No Response", 449 => "Retry With", 450 => "Blocked by Windows Parental Controls", 451 => "Unavailable For Legal Reasons", 494 => "Request Header Too Large", 495 => "Cert Error", 496 => "No Cert", 497 => "HTTP to HTTPS", 499 => "Client Closed Request", 500 => "Internal Server Error", 501 => "Not Implemented", 502 => "Bad Gateway", 503 => "Service Unavailable", 504 => "Gateway Timeout", 505 => "HTTP Version Not Supported", 506 => "Variant Also Negotiates", 507 => "Insufficient Storage", 508 => "Loop Detected", 509 => "Bandwidth Limit Exceeded", 510 => "Not Extended", 511 => "Network Authentication Required", 598 => "Network read timeout error", 599 => "Network connect timeout error");
    /* If the document has loaded successfully without any redirection or error */
    if ($httpCode >= 200 && $httpCode < 300) {
        write_log("Connection is valid: " . $url);
        if ($returnError) return [true,$result];
        return $result;
    } else {
        write_log("Connection failed with error code " . $httpCode . ": " . $url, "ERROR");

        $errMsg = (trim($errMsg) ? $errMsg : ($codes[$httpCode] ?? "Unknown Error."));
        write_log("Error message? - $errMsg");
        if ($returnError) return [false,$errMsg];
        return false;
    }
}

function checkCache($cacheDir) {
    if (!file_exists($cacheDir)) {
        write_log("No cache directory found, creating.", "INFO");
        mkdir($cacheDir, 0777, true);
    }
}

function checkSetLanguage($locale = false) {
    $locale = $locale ? $locale : getLocale();

    if (file_exists(dirname(__FILE__) . "/lang/" . $locale . ".json")) {
        $langJSON = file_get_contents(dirname(__FILE__) . "/lang/" . $locale . ".json");
    } else {
        write_log("Couldn't find the selected locale, defaulting to 'Murica.");
        $langJSON = file_get_contents(dirname(__FILE__) . "/lang/en.json");
    }
    return json_decode($langJSON, true);
}

function cleanCommandString($string) {
    $string = trim(strtolower($string));
    $string = preg_replace("/ask Flex TV/", "", $string);
    $string = preg_replace("/tell Flex TV/", "", $string);
    $string = preg_replace("/Flex TV/", "", $string);
    $stringArray = explode(" ", $string);
    $stripIn = ["th", "nd", "rd", "by"];
    $stringArray = array_diff($stringArray, array_intersect($stringArray, $stripIn));
    foreach ($stringArray as &$word) {
        $word = preg_replace("/[^\w\']+|\'(?!\w)|(?<!\w)\'/", "", $word);
    }
    $result = implode(" ", $stringArray);
    return $result;
}

function clearSession() {
    write_log("Function fired");
    foreach($_SESSION as $key=>$val) {
        unset($_SESSION[$key]);
    }
    if (!session_started()) session_start();
    if (isset($_SERVER['HTTP_COOKIE'])) {
        write_log("Cookies found, unsetting.");
        $cookies = explode(';', $_SERVER['HTTP_COOKIE']);
        foreach ($cookies as $cookie) {
            $parts = explode('=', $cookie);
            $name = trim($parts[0]);
            write_log("Cookie: " . $name);
            setcookie($name, '', time() - 1000);
            setcookie($name, '', time() - 1000, '/');
        }
    }
    session_start();
    session_unset();
    $has_session = session_status() == PHP_SESSION_ACTIVE;
    if ($has_session) session_destroy();
    session_write_close();
    setcookie(session_name(), '', 0, '/');
    session_regenerate_id(true);
}

function cleanUri($url) {
    $parsed = parse_url($url);
    if ($parsed) {
        $parsed['scheme'] = $parsed['scheme'] ?? 'http';
        $created = http_build_url($parsed);
        $parsed = $created;
    }
    $parsed = rtrim($parsed,"/");
    write_log("Cleaned URI: $parsed");
    return $parsed;
}

function clientHeaders($server=false, $client=false) {
    $client = $client ? $client : findDevice(false, false, 'Client');
    return array_merge(plexHeaders($server),[
        'X-Plex-Target-Client-Identifier' => $client['Id']
    ]);
}

function cmp($a, $b) {
    if ($b['ratingCount'] == $a['ratingCount']) return 0;
    return $b['ratingCount'] > $a['ratingCount'] ? 1 : -1;
}

function compareTitles($search, $check, $sendWeight=false, $exact=false) {
    if (!is_string($search) || !is_string($check)) return false;
    $search = cleanCommandString($search);
    $check = cleanCommandString($check);
    // Check for a 100% match.
    if ($search === $check) return $sendWeight ? 100 : true;
    if ($exact) return false;
    // Now check for a roman numeral match. Don't question me, I'm a scientist.
    $searchRoman = explode(" ", $search);
    $new = [];
    foreach($searchRoman as $string) {
        $temp = strtolower(numberToRoman($string));
        if (trim($temp) && $temp !== $string) {
            $string = $temp;
        }
        array_push($new,$string);
    }
    $searchRoman = implode(" ", $new);

    $checkRoman = explode(" ", $check);
    $new = [];
    foreach($checkRoman as $string) {
        $temp = strtolower(numberToRoman($string));
        if (trim($temp) && $temp !== $string) {
            $string = $temp;
        }
        array_push($new,$string);
    }
    $checkRoman = implode(" ", $new);
    if ($searchRoman !== $search || $checkRoman !== $check) {
        if ($searchRoman === $check || $checkRoman === $search) {
            write_log("Returning because of a Roman numeral match!!", "ALERT");
            $str = ($searchRoman === $check) ? $searchRoman : $checkRoman;
            return $sendWeight ? 100 : $str;
        }
    }
    // Okay, now do some more nerdy comparisons...
    $goal = $exact ? 100 : ($_SESSION['searchAccuracy'] ?? 70);
    $strength = similar_text($search,$check);
    $lev = levenshtein($search,$check);
    $len = strlen($search) > strlen($check) ? strlen($search) : strlen($check);
    $similarity = 100-(($lev/$len) * 100);
    $heavy = ($strength >= $goal || $similarity >= $goal);
    $substring = (stripos($search,$check) !== false || stripos($check,$search) !== false);
    if ($heavy || $substring) {
        write_log("Returning because of ". ($heavy ? "heavy." : "substring."));
        $str = (strlen($search) == $len) ? $search : $check;
        $weight = ($strength > $similarity) ? $strength : $similarity;
        return $sendWeight ? $weight : $str;
    }
    return false;
}

function numberToRoman($number) {
    $map = [
        'M' => 1000,
        'CM' => 900,
        'D' => 500,
        'CD' => 400,
        'C' => 100,
        'XC' => 90,
        'L' => 50,
        'XL' => 40,
        'X' => 10,
        'IX' => 9,
        'V' => 5,
        'IV' => 4,
        'I' => 1
    ];

    $returnValue = '';
    while ($number > 0) {
        foreach ($map as $roman => $int) {
            if($number >= $int) {
                $number -= $int;
                $returnValue .= $roman;
                break;
            }
        }
    }
    return $returnValue;
}

function curlGet($url, $headers = null, $timeout = 4, $decode = true) {
	$cert = getCert();
	write_log("GET url $url","INFO","curlGet");
    $url = filter_var($url, FILTER_SANITIZE_URL);
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        write_log("URL $url is not valid.","ERROR");
        return false;
    }
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_CAINFO, $cert);
    if ($headers !== null) curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $result = curl_exec($ch);
    if (!curl_errno($ch)) {
        switch ($http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE)) {
            case 200:
                break;
            default:
                write_log('Unexpected HTTP code: ' . $http_code . ', URL: ' . $url, "ERROR");
                $result = false;
        }
    }
    curl_close($ch);
    if ($result) {
    	if ($decode) {
		    $decoded = false;
		    try {
			    $array = json_decode($result, true);
			    if ($array) {
				    $decoded = true;
				    write_log("Curl result(JSON): " . json_encode($array));
			    } else {
				    $array = (new JsonXmlElement($result))->asArray();
				    if (!empty($array)) {
					    $decoded = true;
					    write_log("Curl result(XML): " . json_encode($array));
				    }
			    }
			    if (!$decoded) write_log("Curl result(String): $result");
		    } catch (Exception $e) {

		    }
	    } else {
    		write_log("Curl result(RAW): ".json_encode($result));
	    }
    }
    return $result;
}

function curlPost($url, $content = false, $JSON = false, Array $headers = null, $timeOut=3) {
    write_log("POST url $url","INFO","curlPost");
    $url = filter_var($url, FILTER_SANITIZE_URL);
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        write_log("URL $url is not valid.");
        return false;
    }

	$cert = getCert();
    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_HEADER, false);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_CAINFO, $cert);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 4);
    curl_setopt($curl, CURLOPT_TIMEOUT, $timeOut);
    if ($headers) {
        if ($JSON) {
            $headers = array_merge($headers, ["Content-type: application/json"]);
        }

        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    } else {
        if ($JSON) {
            $headers = ["Content-type: application/json"];
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        }
    }
    if ($content) curl_setopt($curl, CURLOPT_POSTFIELDS, $content);
    $response = curl_exec($curl);
    if (!curl_errno($curl)) {
        switch ($http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE)) {
            case 200:
                break;
            default:
                write_log('Unexpected HTTP code: ' . $http_code . ', URL: ' . $url, "ERROR","curlPost");
                $response = false;
        }
    }
    curl_close($curl);
    return $response;
}

function curlQuick($url) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_exec($ch);
    curl_close($ch);
}

function doRequest($parts, $timeout = 6) {
    $type = isset($parts['type']) ? $parts['type'] : 'get';
    $response = false;
    $options = [];
    $server = findDevice(false, false, 'Server');
    //write_log("Function fired: ".json_encode($params));
    if (is_array($parts)) {
        if (!isset($parts['uri'])) $parts['uri'] = $server['Uri'];
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

        $url = (isset($parts['scheme']) ? "{$parts['scheme']}:" : '') . ((isset($parts['user']) || isset($parts['host'])) ? '//' : '') . (isset($parts['user']) ? "{$parts['user']}" : '') . (isset($parts['pass']) ? ":{$parts['pass']}" : '') . (isset($parts['user']) ? '@' : '') . (isset($parts['host']) ? "{$parts['host']}" : '') . (isset($parts['port']) ? ":{$parts['port']}" : '') . (isset($parts['path']) ? "{$parts['path']}" : '') . (isset($parts['query']) ? "{$parts['query']}" : '') . (isset($parts['fragment']) ? "#{$parts['fragment']}" : '');
    } else {
        $url = $parts;
    }
    $url = filter_var($url, FILTER_SANITIZE_URL);
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        write_log("URL $url is not valid.","ERROR");
        return false;
    }
    write_log("Request URL: $url", "INFO", getCaller());

    $client = new GuzzleHttp\Client([
        'timeout' => $timeout,
        'verify' => false
    ]);

    try {
        if ($type == 'get') {
            $response = $client->get($url, $options);
        }

        if ($type == 'post') {
            if (isset($parts['headers'])) $options['headers'] = $parts['headers'];
            $response = $client->post($url, $options);
        }
    } catch (Throwable $e) {
        write_log("An exception occurred: " . $e->getMessage(), "ERROR");
        if ($e->getCode() == 401) {
            return false;
        }
    }
    if ($response) {
        $code = $response->getStatusCode();
        if ($code == 200) {
            return $response->getBody()->getContents();
        } else {
            write_log("An error has occurred: " . $response->getReasonPhrase(), "ERROR");
            return false;
        }
    } else write_log("Error getting response from URL fetch.", "ERROR");
    return false;
}

function dumpRequest() {
    $data = [
        "Request Method" => $_SERVER['REQUEST_METHOD'],
        "Request URI" => $_SERVER['REQUEST_URI'],
        "Server Protocol" => $_SERVER['SERVER_PROTOCOL'],
        "Request Data" => $request = explode("/", substr(@$_SERVER['PATH_INFO'], 1))
    ];

    foreach ($_SERVER as $name => $value) {
        if (preg_match('/^HTTP_/', $name)) {
            // convert HTTP_HEADER_NAME to Header-Name
            $name = strtr(substr($name, 5), '_', ' ');
            $name = ucwords(strtolower($name));
            $name = strtr($name, ' ', '-');
            // add to list
            $data[$name] = $value;
        }
    }
    if ($_SERVER['request_METHOD'] !== 'PUT') {
        $data['Request body'] = file_get_contents('php://input');
    } else {
        parse_str(file_get_contents("php://input"), $post_vars);
        foreach ($post_vars as $key => $value) {
            $data[$key] = $value;
        }
    }
    write_log("Request dump!!: " . json_encode($data), "WARN");
}

function fetchDirectory($id = 0) {
    $dir = [
        null,
        "Y2QyMjlmNTU5NWZjYWEyNzI3MGI0NDU4OTIyOGE0OTI=",
        "Njk0Nzg2RjBBMkVCNEUwOQ==",
        "NjU2NTRmODIwZDQ2NDdhYjljZjdlZGRkZGJiYTZlMDI=",
        "MTk1MDAz",
        "TmpKbE9XSXdPV010TWpBMllpMDBPRGxoTFRoaE1EUXROR05pTXpReE5tUTBNRE5r",
        "N2EwMDg5NjFhYWZhNGUyNmFlOTNjYzA4MTZkMWYwNzI=",
        "QUl6YVN5QnNob2xic0phUkI5MUVNdy1hX3hsRHdUQ3VpWjBZWHVv"
    ];

    $d = $dir[$id] ?? false;
    return ($d ? base64_decode($d) : $d);
}

function fetchUrl($https = false) {
    if (isset($_SESSION['webApp'])) return $_SESSION['appAddress'];
    if ($https) $protocol = 'https://'; else {
        $protocol = (((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443) ? 'https://' : 'http://');
    }
    $actual_link = "$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
    $url = explode("/", $actual_link);
    $len = count($url);
    if (preg_match("/.php/", $url[$len - 1])) array_pop($url);
    $actual_link = $protocol;
    foreach ($url as $part) $actual_link .= $part . "/";
    return $actual_link;
}

function file_build_path(...$segments) {
    return join(DIRECTORY_SEPARATOR, $segments);
}

/**
 * findDevice
 *
 * Find a device from the list
 *
 * @param string | bool $key - Optional. The key to search device by.
 * @param string | bool $value - Optional. Defaults to currently selected device if none specified.
 * @param string $type - They device type to search for, with one of "Server", "Client", or "Dvr".
 *
 * @return array | bool - Device array, or false if none found.
 */
function findDevice($key=false, $value=false, $type) {
    if(!$key && !$value) {
        $key = "Id";
        $value = $_SESSION["plex". $type ."Id"] ?? false;
    }
    $string = "$type with a $key of $value";
    $devices = $_SESSION['deviceList'] ?? [];
    $section = $devices["$type"] ?? false;
    if ($section) {
        if (!$key || !$value) {
            return $devices["$type"][0] ?? false;
        }
        foreach ($section as $device) {
            if (trim(strtolower($device["$key"])) === trim(strtolower($value))) {
                return $device;
            }
        }
    }
    if(session_started()) write_log("Unable to find $string.","ERROR");
    return false;
}

/**
 * @deprecated
 * @param $xml
 * @return array|bool|null|string
 */
function flattenXML($xml) {
    libxml_use_internal_errors(true);
    $return = [];
    if (is_string($xml)) {
        try {
            $xml = new SimpleXMLElement($xml);
        } catch (Exception $e) {
            write_log("PARSE ERROR: $e","ERROR");
            return false;
        }
    }
    if (!($xml instanceof SimpleXMLElement)) {
        return false;
    }
    $_value = trim((string)$xml);
    if (strlen($_value) == 0) {
        $_value = null;
    };
    if ($_value !== null) {
        $return = $_value;
    }
    $children = [];
    $first = true;
    foreach ($xml->children() as $elementName => $child) {
        $value = flattenXML($child);
        if (isset($children[$elementName])) {
            if ($first) {
                $temp = $children[$elementName];
                unset($children[$elementName]);
                $children[$elementName][] = $temp;
                $first = false;
            }
            $children[$elementName][] = $value;
        } else {
            $children[$elementName] = $value;
        }
    }
    if (count($children) > 0) {
        $return = array_merge($return, $children);
    }
    $attributes = [];
    foreach ($xml->attributes() as $name => $value) {
        $attributes[$name] = trim($value);
    }
    if (count($attributes) > 0) {
        $return = array_merge($return, $attributes);
    }
    if (empty($return)) $return = false;
    return $return;
}

function getCaller($custom = "foo") {
    $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
    $useNext = false;
    $caller = false;
    $callers = [];
    foreach ($trace as $event) {
        if ($event['function'] !== "write_log" &&
            $event['function'] !== "getCaller" &&
            $event['function'] !== "initialize" &&
            $event['function'] !== "analyzeRequest") array_push($callers,$event['function']);

//        if ($useNext) {
//            if (($event['function'] != 'require') && ($event['function'] != 'include')) {
//                $caller .= "::" . $event['function'];
//                break;
//            }
//        }
//        if (($event['function'] == 'write_log') || ($event['function'] == 'doRequest') || ($event['function'] == $custom)) {
//            $useNext = true;
//            $file = pathinfo($event['file']);
//            $caller = $file['filename'] . "." . $file['extension'];
//        }
    }
    $file = pathinfo($trace[count($trace) - 1]['file'])['filename'];
    $info = $file . "::" . join(":",array_reverse($callers));
    return $info;
}

function getCert() {
//	if (function_exists('openssl_get_cert_locations')) {
//		$paths = openssl_get_cert_locations();
//		foreach($paths as $key=>$path) if ($path == "") unset($paths[$key]);
//		$sysCert = $paths['ini_cafile'] ?? $paths['default_cert_file'] ?? false;
//		if ($sysCert) {
//			write_log("Using system cert.");
//			return $sysCert;
//		}
//	}
	$file = file_build_path(dirname(__FILE__), "..", "rw", "cacert.pem");
	$url = 'https://curl.haxx.se/ca/cacert.pem';
	$current_time = time();
	$expire_time = 56 * 60 * 60;
	if (file_exists($file)) {
		$file_time = filemtime($file);
		if ($current_time - $expire_time < $file_time) {
			return $file;
		}
	} else {
		write_log("Fetching updated cert.");
		$content = doRequest($url,5);
		if ($content) {
			$content .= '<!-- cached:  ' . time() . '-->';
			file_put_contents($file, $content);
			write_log('Retrieved fresh from ' . $url, "INFO");
			if (file_exists($file)) return $file;
		}
	}
	// If unable to fetch or write cert, use the "default" one in the project root
	$cert = file_build_path(dirname(__FILE__), "..", "cacert.pem");
	return $cert;
}


function getLocale() {
    $locale = trim($_SESSION['appLanguage'] ?? "");
    $store = false;
    if (!$locale) {
        $store = true;
        $langs = listLocales(false);
        $preferred = explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']);
        foreach($preferred as $idx => $lang) {
            $lang = substr($lang, 0, 2);
            if (in_array($lang, $langs)) {
                write_log("Found a language from the server, neato.","INFO");
                $locale = $lang;
                break;
            }
        }
    }
    if (!$locale) {
        write_log("Failed to select a langauge, setting to English here.","ERROR");
        $locale = "en";
    }

    if ($store) {
        if (isset($_SESSION['plexUserName'])) {
            updateUserPreference('appLanguage',$locale);
        } else {
            writeSession('appLanguage', $locale);
        }
    }

    return $locale;

}

function getRelativePath($from, $to) {
    // some compatibility fixes for Windows paths
    $from = is_dir($from) ? rtrim($from, '\/') . '/' : $from;
    $to = is_dir($to) ? rtrim($to, '\/') . '/' : $to;
    $from = str_replace('\\', '/', $from);
    $to = str_replace('\\', '/', $to);

    $from = explode('/', $from);
    $to = explode('/', $to);
    $relPath = $to;

    foreach ($from as $depth => $dir) {
        // find first non-matching dir
        if ($dir === $to[$depth]) {
            // ignore this directory
            array_shift($relPath);
        } else {
            // get number of remaining dirs to $from
            $remaining = count($from) - $depth;
            if ($remaining > 1) {
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


function getSessionData($filter=false,$stripFilter=false)
{
    $data = [];
    $boolKeys = [
        'couchEnabled',
        'sonarrEnabled',
        'radarrEnabled',
        'ombiEnabled',
        'sickEnabled',
        'headphonesEnabled',
        'lidarrEnabled',
        'darkTheme',
        'hasPlugin',
        'alertPlugin',
        'plexPassUser',
        'plexDvrReplaceLower',
        'plexDvrNewAirings',
        'hook',
        'hookPaused',
        'hookPlay',
        'hookFetch',
        'hookCustom',
        'hookSplit',
        'hookStop'
    ];
    foreach ($_SESSION as $key => $value) {
    	if ($key !== "lang") {
            if (in_array($key, $boolKeys)) {
                $value = boolval($value);
            }
		    if (!$filter) {
			    $data[$key] = $value;
		    } else {
            	if (preg_match("/$filter/",$key)) {
            		if ($stripFilter) $key = preg_replace("/$filter/","",$key);
            		$key = lcfirst($key);
		            $data[$key] = $value;
	            }
		    }
        }
    }
    $dvr = $_SESSION['plexDvrId'] ?? false;
    $data['dvrEnabled'] = boolval($dvr) ? true : false;
    write_log("Session data: " . json_encode($data), "INFO");
    return $data;
}

function hasGzip() {
    return (function_exists('ob_gzhandler') && ini_get('zlib.output_compression'));
}

function headerHtml() {
    $string = "<div id='X-Plex-Data' class='hidden'";
    foreach(plexHeaders() as $key => $value) {
        $string .= " data-$key='$value'";
    }
    $string .="></div>";
    return $string;
}

function headerQuery($headers) {
    $string = "";
    foreach($headers as $key => $val) {
        $string.="&".urlencode($key)."=".urlencode($val);
    }
    return $string;
}

function headerRequestArray($headers) {
    $headerArray = [];
    foreach ($headers as $key => $val) {
        $headerArray[] = "$key:$val";
    }
    return $headerArray;
}

if (!function_exists('http_build_url')) {
    define('HTTP_URL_REPLACE', 1);              // Replace every part of the first URL when there's one of the second URL
    define('HTTP_URL_JOIN_PATH', 2);            // Join relative paths
    define('HTTP_URL_JOIN_QUERY', 4);           // Join query strings
    define('HTTP_URL_STRIP_USER', 8);           // Strip any user authentication information
    define('HTTP_URL_STRIP_PASS', 16);          // Strip any password authentication information
    define('HTTP_URL_STRIP_AUTH', 32);          // Strip any authentication information
    define('HTTP_URL_STRIP_PORT', 64);          // Strip explicit port numbers
    define('HTTP_URL_STRIP_PATH', 128);         // Strip complete path
    define('HTTP_URL_STRIP_QUERY', 256);        // Strip query string
    define('HTTP_URL_STRIP_FRAGMENT', 512);     // Strip any fragments (#identifier)
    define('HTTP_URL_STRIP_ALL', 1024);         // Strip anything but scheme and host
    // Build an URL
    // The parts of the second URL will be merged into the first according to the flags argument.
    //
    // @param   mixed           (Part(s) of) an URL in form of a string or associative array like parse_url() returns
    // @param   mixed           Same as the first argument
    // @param   int             A bitmask of binary or'ed HTTP_URL constants (Optional)HTTP_URL_REPLACE is the default
    // @param   array           If set, it will be filled with the parts of the composed url like parse_url() would return
    function http_build_url($url, $parts = [], $flags = HTTP_URL_REPLACE, &$new_url = false) {
        $keys = [
            'user',
            'pass',
            'port',
            'path',
            'query',
            'fragment'
        ];
        // HTTP_URL_STRIP_ALL becomes all the HTTP_URL_STRIP_Xs
        if ($flags & HTTP_URL_STRIP_ALL) {
            $flags |= HTTP_URL_STRIP_USER;
            $flags |= HTTP_URL_STRIP_PASS;
            $flags |= HTTP_URL_STRIP_PORT;
            $flags |= HTTP_URL_STRIP_PATH;
            $flags |= HTTP_URL_STRIP_QUERY;
            $flags |= HTTP_URL_STRIP_FRAGMENT;
        } // HTTP_URL_STRIP_AUTH becomes HTTP_URL_STRIP_USER and HTTP_URL_STRIP_PASS
        else if ($flags & HTTP_URL_STRIP_AUTH) {
            $flags |= HTTP_URL_STRIP_USER;
            $flags |= HTTP_URL_STRIP_PASS;
        }
        // Parse the original URL
        // - Suggestion by Sayed Ahad Abbas
        //   In case you send a parse_url array as input
        $parse_url = !is_array($url) ? parse_url($url) : $url;
        // Scheme and Host are always replaced
        if (isset($parts['scheme']))
            $parse_url['scheme'] = $parts['scheme'];
        if (isset($parts['host']))
            $parse_url['host'] = $parts['host'];
        // (If applicable) Replace the original URL with it's new parts
        if ($flags & HTTP_URL_REPLACE) {
            foreach ($keys as $key) {
                if (isset($parts[$key]))
                    $parse_url[$key] = $parts[$key];
            }
        } else {
            // Join the original URL path with the new path
            if (isset($parts['path']) && ($flags & HTTP_URL_JOIN_PATH)) {
                if (isset($parse_url['path']))
                    $parse_url['path'] = rtrim(str_replace(basename($parse_url['path']), '', $parse_url['path']), '/') . '/' . ltrim($parts['path'], '/');
                else
                    $parse_url['path'] = $parts['path'];
            }
            // Join the original query string with the new query string
            if (isset($parts['query']) && ($flags & HTTP_URL_JOIN_QUERY)) {
                if (isset($parse_url['query']))
                    $parse_url['query'] .= '&' . $parts['query'];
                else
                    $parse_url['query'] = $parts['query'];
            }
        }
        // Strips all the applicable sections of the URL
        // Note: Scheme and Host are never stripped
        foreach ($keys as $key) {
            if ($flags & (int)constant('HTTP_URL_STRIP_' . strtoupper($key)))
                unset($parse_url[$key]);
        }
        $new_url = $parse_url;
        return
            ((isset($parse_url['scheme'])) ? $parse_url['scheme'] . '://' : '')
            . ((isset($parse_url['user'])) ? $parse_url['user'] . ((isset($parse_url['pass'])) ? ':' . $parse_url['pass'] : '') . '@' : '')
            . ((isset($parse_url['host'])) ? $parse_url['host'] : '')
            . ((isset($parse_url['port'])) ? ':' . $parse_url['port'] : '')
            . ((isset($parse_url['path'])) ? $parse_url['path'] : '')
            . ((isset($parse_url['query'])) ? '?' . $parse_url['query'] : '')
            . ((isset($parse_url['fragment'])) ? '#' . $parse_url['fragment'] : '');
    }
}

function isDomainAvailable($domain) {
    //check, if a valid url is provided
    if (!filter_var($domain, FILTER_VALIDATE_URL)) {
        return false;
    }

    //initialize curl
    $curlInit = curl_init($domain);
    curl_setopt($curlInit, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($curlInit, CURLOPT_HEADER, true);
    curl_setopt($curlInit, CURLOPT_NOBODY, true);
    curl_setopt($curlInit, CURLOPT_RETURNTRANSFER, true);

    //get answer
    $response = curl_exec($curlInit);

    curl_close($curlInit);

    if ($response) return true;

    return false;
}

function joinSpeech(...$segments) {
    return join(" ", $segments);
}

function joinStrings($items, $tail = "and") {
    foreach($items as &$item) {
        if ($item == "couch") $item = "couchpotato";
        $item = ucfirst($item);
    }
    $count = count($items);
    $string = "";
    if ($count == 1) $string = $items[0] . ".";
    if ($count == 2) {
        $title1 = $items[0];
        $title2 = $items[1];
        $string = "$title1 $tail $title2.";
    }
    if ($count >= 3) {
        $last = array_pop($items);
        $string = join(", ", $items) . ", $tail $last.";
    }
    return $string;
}

function joinItems($items, $tail = "and", $noType=false) {
    $titles = [];
    $counts = [];
    $names = [];
    foreach ($items as $item) {
        write_log("Item: " . json_encode($item));
        $type = explode(".",$item['type'])[1] ?? $item['type'];
        if (!isset($counts[$type])) $counts[$type] = 0;
        $title = $item['Title'];
        foreach($names as $check) if ($check['Title'] == $title) {
            $counts[$type]++;
        }
        array_push($names,$item);
    }

    write_log("Counts: ". json_encode($counts));
    $singleType = (count($counts) == 1);
    foreach ($names as $item) {
        $year = $item['year'] ?? false;
	    if (is_array($year)) $year = $year[0];
        $type = explode(".",$item['type'])[1] ?? $item['type'];
        $typeCount = $counts[$type];
        switch ($type) {
            case 'movie':
            case 'show':
                $string = $item['title'];
                if ($year) {
	                $string .= " ($year)";
                }
                break;
            case 'episode':
                $string = $item['grandparentTitle'] . " - " . $item['title'];
                break;
            case 'track':
                $string = $item['artist'] . " - " . $item['title'];
                if ($typeCount >= 2 && isset($item['album'])) {
                	$album = (is_array($item['album'])) ? $item['album'][0] : $item['album'];
	                $string .= " ($album)";
                }
                break;
            case 'album':
	        case 'artist':
                $string = $item['title'] . "(The $type)";
                if ($typeCount >=2 && $year) {
                	$string .= " ($year)";
                }
                break;
            default:
                $string = $item['title'];
                if ($typeCount >=2 && $year) $string .= " ($year)";
        }
        if (!$singleType && !$noType) $string = $string . " (the $type)";
        $string = trim($string);
        write_log("String is $string");
        if (!in_array($string, $titles)) array_push($titles, $string);
    }
    $string = "";
    $count = count($titles);
    if ($count == 1) $string = $titles[0] . ".";
    if ($count == 2) {
        $title1 = $titles[0];
        $title2 = $titles[1];
        $string = "$title1 $tail $title2.";
    }
    if ($count >= 3) {
        $last = array_pop($titles);
        $string = join(", ", $titles) . ", $tail $last.";
    }
    return $string;
}

function joinTitles($items,$tail="and") {
	$titles = [];
	foreach($items as $item) {
		$title = is_array($item) ? $item['title'] : $item;
		$type = $item['type'] ?? false;
		if ($type) {
			switch ($item['type']) {
				case 'episode':
					$showName = $item['seriesTitle'] ?? $item['grandparentTitle'] ?? false;
					if ($showName) $title = "$showName - $title";
					break;
				case 'track':
					$title .= " (" . $item['album'] . ")";
					break;
				case 'album':
					$title = ($item['parentTitle'] ?? $item['artist']) . " - $title";
					break;
				case 'movie':
					$year = $item['year'] ?? false;
					if ($year) $title .= " ($year)";
					break;
			}
		}
		array_push($titles,$title);
	}
}

/**
 * @param $string
 * @return string | array
 */
function lang($string) {
    $strings = checkSetLanguage();
    return $strings["$string"] ?? "";
}

function listLocales($html=true) {
    $dir = file_build_path(dirname(__FILE__), "lang");
    $list = "";
    $langs = [];
    $lang = $_SESSION["appLanguage"];
    if (trim($lang) == "") $lang = "en";
    write_log("Local language should be $lang","INFO");
    if (is_dir($dir)) {
        if ($dh = opendir($dir)) {
            while (($file = readdir($dh)) !== false) {
                $name = trim(str_replace(".", "", trim($file)));
                if ($name) {
                    $locale = str_replace("json", "", $name);
                    if ($html) {
                        $localeName = localeName($locale);
                        $json = file_get_contents(file_build_path($dir, $file));
                        $json = json_decode($json, true);
                        if ($json) {
                            $selected = ($lang == $locale ? 'selected' : '');
                            $list .= "<option data-value='$locale' id='$locale' $selected>$localeName</option>" . PHP_EOL;
                        }
                    } else {
                        array_push($langs,$locale);
                    }
                }
            }
            closedir($dh);
        }
    }
    return $html ? $list : $langs;
}

function localeName($locale = "en") {
    if (function_exists("locale_get_display_region")) {
        return ucfirst(locale_get_display_name($locale, $locale));
    } else switch ($locale) {
        case "af_NA":
            return "Afrikaans (Namibia)";
        case "af_ZA":
            return "Afrikaans (South Africa)";
        case "af":
            return "Afrikaans";
        case "ak_GH":
            return "Akan (Ghana)";
        case "ak":
            return "Akan";
        case "sq_AL":
            return "Albanian (Albania)";
        case "sq":
            return "Albanian";
        case "am_ET":
            return "Amharic (Ethiopia)";
        case "am":
            return "Amharic";
        case "ar_DZ":
            return "Arabic (Algeria)";
        case "ar_BH":
            return "Arabic (Bahrain)";
        case "ar_EG":
            return "Arabic (Egypt)";
        case "ar_IQ":
            return "Arabic (Iraq)";
        case "ar_JO":
            return "Arabic (Jordan)";
        case "ar_KW":
            return "Arabic (Kuwait)";
        case "ar_LB":
            return "Arabic (Lebanon)";
        case "ar_LY":
            return "Arabic (Libya)";
        case "ar_MA":
            return "Arabic (Morocco)";
        case "ar_OM":
            return "Arabic (Oman)";
        case "ar_QA":
            return "Arabic (Qatar)";
        case "ar_SA":
            return "Arabic (Saudi Arabia)";
        case "ar_SD":
            return "Arabic (Sudan)";
        case "ar_SY":
            return "Arabic (Syria)";
        case "ar_TN":
            return "Arabic (Tunisia)";
        case "ar_AE":
            return "Arabic (United Arab Emirates)";
        case "ar_YE":
            return "Arabic (Yemen)";
        case "ar":
            return "Arabic";
        case "hy_AM":
            return "Armenian (Armenia)";
        case "hy":
            return "Armenian";
        case "as_IN":
            return "Assamese (India)";
        case "as":
            return "Assamese";
        case "asa_TZ":
            return "Asu (Tanzania)";
        case "asa":
            return "Asu";
        case "az_Cyrl":
            return "Azerbaijani (Cyrillic)";
        case "az_Cyrl_AZ":
            return "Azerbaijani (Cyrillic, Azerbaijan)";
        case "az_Latn":
            return "Azerbaijani (Latin)";
        case "az_Latn_AZ":
            return "Azerbaijani (Latin, Azerbaijan)";
        case "az":
            return "Azerbaijani";
        case "bm_ML":
            return "Bambara (Mali)";
        case "bm":
            return "Bambara";
        case "eu_ES":
            return "Basque (Spain)";
        case "eu":
            return "Basque";
        case "be_BY":
            return "Belarusian (Belarus)";
        case "be":
            return "Belarusian";
        case "bem_ZM":
            return "Bemba (Zambia)";
        case "bem":
            return "Bemba";
        case "bez_TZ":
            return "Bena (Tanzania)";
        case "bez":
            return "Bena";
        case "bn_BD":
            return "Bengali (Bangladesh)";
        case "bn_IN":
            return "Bengali (India)";
        case "bn":
            return "Bengali";
        case "bs_BA":
            return "Bosnian (Bosnia and Herzegovina)";
        case "bs":
            return "Bosnian";
        case "bg_BG":
            return "Bulgarian (Bulgaria)";
        case "bg":
            return "Bulgarian";
        case "my_MM":
            return "Burmese (Myanmar [Burma])";
        case "my":
            return "Burmese";
        case "ca_ES":
            return "Catalan (Spain)";
        case "ca":
            return "Catalan";
        case "tzm_Latn":
            return "Central Morocco Tamazight (Latin)";
        case "tzm_Latn_MA":
            return "Central Morocco Tamazight (Latin, Morocco)";
        case "tzm":
            return "Central Morocco Tamazight";
        case "chr_US":
            return "Cherokee (United States)";
        case "chr":
            return "Cherokee";
        case "cgg_UG":
            return "Chiga (Uganda)";
        case "cgg":
            return "Chiga";
        case "zh_Hans":
            return "Chinese (Simplified Han)";
        case "zh_Hans_CN":
            return "Chinese (Simplified Han, China)";
        case "zh_Hans_HK":
            return "Chinese (Simplified Han, Hong Kong SAR China)";
        case "zh_Hans_MO":
            return "Chinese (Simplified Han, Macau SAR China)";
        case "zh_Hans_SG":
            return "Chinese (Simplified Han, Singapore)";
        case "zh_Hant":
            return "Chinese (Traditional Han)";
        case "zh_Hant_HK":
            return "Chinese (Traditional Han, Hong Kong SAR China)";
        case "zh_Hant_MO":
            return "Chinese (Traditional Han, Macau SAR China)";
        case "zh_Hant_TW":
            return "Chinese (Traditional Han, Taiwan)";
        case "zh":
            return "Chinese";
        case "kw_GB":
            return "Cornish (United Kingdom)";
        case "kw":
            return "Cornish";
        case "hr_HR":
            return "Croatian (Croatia)";
        case "hr":
            return "Croatian";
        case "cs_CZ":
            return "Czech (Czech Republic)";
        case "cs":
            return "Czech";
        case "da_DK":
            return "Danish (Denmark)";
        case "da":
            return "Danish";
        case "nl_BE":
            return "Dutch (Belgium)";
        case "nl_NL":
            return "Dutch (Netherlands)";
        case "nl":
            return "Dutch";
        case "ebu_KE":
            return "Embu (Kenya)";
        case "ebu":
            return "Embu";
        case "en_AS":
            return "English (American Samoa)";
        case "en_AU":
            return "English (Australia)";
        case "en_BE":
            return "English (Belgium)";
        case "en_BZ":
            return "English (Belize)";
        case "en_BW":
            return "English (Botswana)";
        case "en_CA":
            return "English (Canada)";
        case "en_GU":
            return "English (Guam)";
        case "en_HK":
            return "English (Hong Kong SAR China)";
        case "en_IN":
            return "English (India)";
        case "en_IE":
            return "English (Ireland)";
        case "en_JM":
            return "English (Jamaica)";
        case "en_MT":
            return "English (Malta)";
        case "en_MH":
            return "English (Marshall Islands)";
        case "en_MU":
            return "English (Mauritius)";
        case "en_NA":
            return "English (Namibia)";
        case "en_NZ":
            return "English (New Zealand)";
        case "en_MP":
            return "English (Northern Mariana Islands)";
        case "en_PK":
            return "English (Pakistan)";
        case "en_PH":
            return "English (Philippines)";
        case "en_SG":
            return "English (Singapore)";
        case "en_ZA":
            return "English (South Africa)";
        case "en_TT":
            return "English (Trinidad and Tobago)";
        case "en_UM":
            return "English (U.S. Minor Outlying Islands)";
        case "en_VI":
            return "English (U.S. Virgin Islands)";
        case "en_GB":
            return "English (United Kingdom)";
        case "en_US":
            return "English (United States)";
        case "en_ZW":
            return "English (Zimbabwe)";
        case "en":
            return "English";
        case "eo":
            return "Esperanto";
        case "et_EE":
            return "Estonian (Estonia)";
        case "et":
            return "Estonian";
        case "ee_GH":
            return "Ewe (Ghana)";
        case "ee_TG":
            return "Ewe (Togo)";
        case "ee":
            return "Ewe";
        case "fo_FO":
            return "Faroese (Faroe Islands)";
        case "fo":
            return "Faroese";
        case "fil_PH":
            return "Filipino (Philippines)";
        case "fil":
            return "Filipino";
        case "fi_FI":
            return "Finnish (Finland)";
        case "fi":
            return "Finnish";
        case "fr_BE":
            return "French (Belgium)";
        case "fr_BJ":
            return "French (Benin)";
        case "fr_BF":
            return "French (Burkina Faso)";
        case "fr_BI":
            return "French (Burundi)";
        case "fr_CM":
            return "French (Cameroon)";
        case "fr_CA":
            return "French (Canada)";
        case "fr_CF":
            return "French (Central African Republic)";
        case "fr_TD":
            return "French (Chad)";
        case "fr_KM":
            return "French (Comoros)";
        case "fr_CG":
            return "French (Congo - Brazzaville)";
        case "fr_CD":
            return "French (Congo - Kinshasa)";
        case "fr_CI":
            return "French (Côte d’Ivoire)";
        case "fr_DJ":
            return "French (Djibouti)";
        case "fr_GQ":
            return "French (Equatorial Guinea)";
        case "fr_FR":
            return "French (France)";
        case "fr_GA":
            return "French (Gabon)";
        case "fr_GP":
            return "French (Guadeloupe)";
        case "fr_GN":
            return "French (Guinea)";
        case "fr_LU":
            return "French (Luxembourg)";
        case "fr_MG":
            return "French (Madagascar)";
        case "fr_ML":
            return "French (Mali)";
        case "fr_MQ":
            return "French (Martinique)";
        case "fr_MC":
            return "French (Monaco)";
        case "fr_NE":
            return "French (Niger)";
        case "fr_RW":
            return "French (Rwanda)";
        case "fr_RE":
            return "French (Réunion)";
        case "fr_BL":
            return "French (Saint Barthélemy)";
        case "fr_MF":
            return "French (Saint Martin)";
        case "fr_SN":
            return "French (Senegal)";
        case "fr_CH":
            return "French (Switzerland)";
        case "fr_TG":
            return "French (Togo)";
        case "fr":
            return "French";
        case "ff_SN":
            return "Fulah (Senegal)";
        case "ff":
            return "Fulah";
        case "gl_ES":
            return "Galician (Spain)";
        case "gl":
            return "Galician";
        case "lg_UG":
            return "Ganda (Uganda)";
        case "lg":
            return "Ganda";
        case "ka_GE":
            return "Georgian (Georgia)";
        case "ka":
            return "Georgian";
        case "de_AT":
            return "German (Austria)";
        case "de_BE":
            return "German (Belgium)";
        case "de_DE":
            return "German (Germany)";
        case "de_LI":
            return "German (Liechtenstein)";
        case "de_LU":
            return "German (Luxembourg)";
        case "de_CH":
            return "German (Switzerland)";
        case "de":
            return "German";
        case "el_CY":
            return "Greek (Cyprus)";
        case "el_GR":
            return "Greek (Greece)";
        case "el":
            return "Greek";
        case "gu_IN":
            return "Gujarati (India)";
        case "gu":
            return "Gujarati";
        case "guz_KE":
            return "Gusii (Kenya)";
        case "guz":
            return "Gusii";
        case "ha_Latn":
            return "Hausa (Latin)";
        case "ha_Latn_GH":
            return "Hausa (Latin, Ghana)";
        case "ha_Latn_NE":
            return "Hausa (Latin, Niger)";
        case "ha_Latn_NG":
            return "Hausa (Latin, Nigeria)";
        case "ha":
            return "Hausa";
        case "haw_US":
            return "Hawaiian (United States)";
        case "haw":
            return "Hawaiian";
        case "he_IL":
            return "Hebrew (Israel)";
        case "he":
            return "Hebrew";
        case "hi_IN":
            return "Hindi (India)";
        case "hi":
            return "Hindi";
        case "hu_HU":
            return "Hungarian (Hungary)";
        case "hu":
            return "Hungarian";
        case "is_IS":
            return "Icelandic (Iceland)";
        case "is":
            return "Icelandic";
        case "ig_NG":
            return "Igbo (Nigeria)";
        case "ig":
            return "Igbo";
        case "id_ID":
            return "Indonesian (Indonesia)";
        case "id":
            return "Indonesian";
        case "ga_IE":
            return "Irish (Ireland)";
        case "ga":
            return "Irish";
        case "it_IT":
            return "Italian (Italy)";
        case "it_CH":
            return "Italian (Switzerland)";
        case "it":
            return "Italian";
        case "ja_JP":
            return "Japanese (Japan)";
        case "ja":
            return "Japanese";
        case "kea_CV":
            return "Kabuverdianu (Cape Verde)";
        case "kea":
            return "Kabuverdianu";
        case "kab_DZ":
            return "Kabyle (Algeria)";
        case "kab":
            return "Kabyle";
        case "kl_GL":
            return "Kalaallisut (Greenland)";
        case "kl":
            return "Kalaallisut";
        case "kln_KE":
            return "Kalenjin (Kenya)";
        case "kln":
            return "Kalenjin";
        case "kam_KE":
            return "Kamba (Kenya)";
        case "kam":
            return "Kamba";
        case "kn_IN":
            return "Kannada (India)";
        case "kn":
            return "Kannada";
        case "kk_Cyrl":
            return "Kazakh (Cyrillic)";
        case "kk_Cyrl_KZ":
            return "Kazakh (Cyrillic, Kazakhstan)";
        case "kk":
            return "Kazakh";
        case "km_KH":
            return "Khmer (Cambodia)";
        case "km":
            return "Khmer";
        case "ki_KE":
            return "Kikuyu (Kenya)";
        case "ki":
            return "Kikuyu";
        case "rw_RW":
            return "Kinyarwanda (Rwanda)";
        case "rw":
            return "Kinyarwanda";
        case "kok_IN":
            return "Konkani (India)";
        case "kok":
            return "Konkani";
        case "ko_KR":
            return "Korean (South Korea)";
        case "ko":
            return "Korean";
        case "khq_ML":
            return "Koyra Chiini (Mali)";
        case "khq":
            return "Koyra Chiini";
        case "ses_ML":
            return "Koyraboro Senni (Mali)";
        case "ses":
            return "Koyraboro Senni";
        case "lag_TZ":
            return "Langi (Tanzania)";
        case "lag":
            return "Langi";
        case "lv_LV":
            return "Latvian (Latvia)";
        case "lv":
            return "Latvian";
        case "lt_LT":
            return "Lithuanian (Lithuania)";
        case "lt":
            return "Lithuanian";
        case "luo_KE":
            return "Luo (Kenya)";
        case "luo":
            return "Luo";
        case "luy_KE":
            return "Luyia (Kenya)";
        case "luy":
            return "Luyia";
        case "mk_MK":
            return "Macedonian (Macedonia)";
        case "mk":
            return "Macedonian";
        case "jmc_TZ":
            return "Machame (Tanzania)";
        case "jmc":
            return "Machame";
        case "kde_TZ":
            return "Makonde (Tanzania)";
        case "kde":
            return "Makonde";
        case "mg_MG":
            return "Malagasy (Madagascar)";
        case "mg":
            return "Malagasy";
        case "ms_BN":
            return "Malay (Brunei)";
        case "ms_MY":
            return "Malay (Malaysia)";
        case "ms":
            return "Malay";
        case "ml_IN":
            return "Malayalam (India)";
        case "ml":
            return "Malayalam";
        case "mt_MT":
            return "Maltese (Malta)";
        case "mt":
            return "Maltese";
        case "gv_GB":
            return "Manx (United Kingdom)";
        case "gv":
            return "Manx";
        case "mr_IN":
            return "Marathi (India)";
        case "mr":
            return "Marathi";
        case "mas_KE":
            return "Masai (Kenya)";
        case "mas_TZ":
            return "Masai (Tanzania)";
        case "mas":
            return "Masai";
        case "mer_KE":
            return "Meru (Kenya)";
        case "mer":
            return "Meru";
        case "mfe_MU":
            return "Morisyen (Mauritius)";
        case "mfe":
            return "Morisyen";
        case "naq_NA":
            return "Nama (Namibia)";
        case "naq":
            return "Nama";
        case "ne_IN":
            return "Nepali (India)";
        case "ne_NP":
            return "Nepali (Nepal)";
        case "ne":
            return "Nepali";
        case "nd_ZW":
            return "North Ndebele (Zimbabwe)";
        case "nd":
            return "North Ndebele";
        case "nb_NO":
            return "Norwegian Bokmål (Norway)";
        case "nb":
            return "Norwegian Bokmål";
        case "nn_NO":
            return "Norwegian Nynorsk (Norway)";
        case "nn":
            return "Norwegian Nynorsk";
        case "nyn_UG":
            return "Nyankole (Uganda)";
        case "nyn":
            return "Nyankole";
        case "or_IN":
            return "Oriya (India)";
        case "or":
            return "Oriya";
        case "om_ET":
            return "Oromo (Ethiopia)";
        case "om_KE":
            return "Oromo (Kenya)";
        case "om":
            return "Oromo";
        case "ps_AF":
            return "Pashto (Afghanistan)";
        case "ps":
            return "Pashto";
        case "fa_AF":
            return "Persian (Afghanistan)";
        case "fa_IR":
            return "Persian (Iran)";
        case "fa":
            return "Persian";
        case "pl_PL":
            return "Polish (Poland)";
        case "pl":
            return "Polish";
        case "pt_BR":
            return "Portuguese (Brazil)";
        case "pt_GW":
            return "Portuguese (Guinea-Bissau)";
        case "pt_MZ":
            return "Portuguese (Mozambique)";
        case "pt_PT":
            return "Portuguese (Portugal)";
        case "pt":
            return "Portuguese";
        case "pa_Arab":
            return "Punjabi (Arabic)";
        case "pa_Arab_PK":
            return "Punjabi (Arabic, Pakistan)";
        case "pa_Guru":
            return "Punjabi (Gurmukhi)";
        case "pa_Guru_IN":
            return "Punjabi (Gurmukhi, India)";
        case "pa":
            return "Punjabi";
        case "ro_MD":
            return "Romanian (Moldova)";
        case "ro_RO":
            return "Romanian (Romania)";
        case "ro":
            return "Romanian";
        case "rm_CH":
            return "Romansh (Switzerland)";
        case "rm":
            return "Romansh";
        case "rof_TZ":
            return "Rombo (Tanzania)";
        case "rof":
            return "Rombo";
        case "ru_MD":
            return "Russian (Moldova)";
        case "ru_RU":
            return "Russian (Russia)";
        case "ru_UA":
            return "Russian (Ukraine)";
        case "ru":
            return "Russian";
        case "rwk_TZ":
            return "Rwa (Tanzania)";
        case "rwk":
            return "Rwa";
        case "saq_KE":
            return "Samburu (Kenya)";
        case "saq":
            return "Samburu";
        case "sg_CF":
            return "Sango (Central African Republic)";
        case "sg":
            return "Sango";
        case "seh_MZ":
            return "Sena (Mozambique)";
        case "seh":
            return "Sena";
        case "sr_Cyrl":
            return "Serbian (Cyrillic)";
        case "sr_Cyrl_BA":
            return "Serbian (Cyrillic, Bosnia and Herzegovina)";
        case "sr_Cyrl_ME":
            return "Serbian (Cyrillic, Montenegro)";
        case "sr_Cyrl_RS":
            return "Serbian (Cyrillic, Serbia)";
        case "sr_Latn":
            return "Serbian (Latin)";
        case "sr_Latn_BA":
            return "Serbian (Latin, Bosnia and Herzegovina)";
        case "sr_Latn_ME":
            return "Serbian (Latin, Montenegro)";
        case "sr_Latn_RS":
            return "Serbian (Latin, Serbia)";
        case "sr":
            return "Serbian";
        case "sn_ZW":
            return "Shona (Zimbabwe)";
        case "sn":
            return "Shona";
        case "ii_CN":
            return "Sichuan Yi (China)";
        case "ii":
            return "Sichuan Yi";
        case "si_LK":
            return "Sinhala (Sri Lanka)";
        case "si":
            return "Sinhala";
        case "sk_SK":
            return "Slovak (Slovakia)";
        case "sk":
            return "Slovak";
        case "sl_SI":
            return "Slovenian (Slovenia)";
        case "sl":
            return "Slovenian";
        case "xog_UG":
            return "Soga (Uganda)";
        case "xog":
            return "Soga";
        case "so_DJ":
            return "Somali (Djibouti)";
        case "so_ET":
            return "Somali (Ethiopia)";
        case "so_KE":
            return "Somali (Kenya)";
        case "so_SO":
            return "Somali (Somalia)";
        case "so":
            return "Somali";
        case "es_AR":
            return "Spanish (Argentina)";
        case "es_BO":
            return "Spanish (Bolivia)";
        case "es_CL":
            return "Spanish (Chile)";
        case "es_CO":
            return "Spanish (Colombia)";
        case "es_CR":
            return "Spanish (Costa Rica)";
        case "es_DO":
            return "Spanish (Dominican Republic)";
        case "es_EC":
            return "Spanish (Ecuador)";
        case "es_SV":
            return "Spanish (El Salvador)";
        case "es_GQ":
            return "Spanish (Equatorial Guinea)";
        case "es_GT":
            return "Spanish (Guatemala)";
        case "es_HN":
            return "Spanish (Honduras)";
        case "es_419":
            return "Spanish (Latin America)";
        case "es_MX":
            return "Spanish (Mexico)";
        case "es_NI":
            return "Spanish (Nicaragua)";
        case "es_PA":
            return "Spanish (Panama)";
        case "es_PY":
            return "Spanish (Paraguay)";
        case "es_PE":
            return "Spanish (Peru)";
        case "es_PR":
            return "Spanish (Puerto Rico)";
        case "es_ES":
            return "Spanish (Spain)";
        case "es_US":
            return "Spanish (United States)";
        case "es_UY":
            return "Spanish (Uruguay)";
        case "es_VE":
            return "Spanish (Venezuela)";
        case "es":
            return "Spanish";
        case "sw_KE":
            return "Swahili (Kenya)";
        case "sw_TZ":
            return "Swahili (Tanzania)";
        case "sw":
            return "Swahili";
        case "sv_FI":
            return "Swedish (Finland)";
        case "sv_SE":
            return "Swedish (Sweden)";
        case "sv":
            return "Swedish";
        case "gsw_CH":
            return "Swiss German (Switzerland)";
        case "gsw":
            return "Swiss German";
        case "shi_Latn":
            return "Tachelhit (Latin)";
        case "shi_Latn_MA":
            return "Tachelhit (Latin, Morocco)";
        case "shi_Tfng":
            return "Tachelhit (Tifinagh)";
        case "shi_Tfng_MA":
            return "Tachelhit (Tifinagh, Morocco)";
        case "shi":
            return "Tachelhit";
        case "dav_KE":
            return "Taita (Kenya)";
        case "dav":
            return "Taita";
        case "ta_IN":
            return "Tamil (India)";
        case "ta_LK":
            return "Tamil (Sri Lanka)";
        case "ta":
            return "Tamil";
        case "te_IN":
            return "Telugu (India)";
        case "te":
            return "Telugu";
        case "teo_KE":
            return "Teso (Kenya)";
        case "teo_UG":
            return "Teso (Uganda)";
        case "teo":
            return "Teso";
        case "th_TH":
            return "Thai (Thailand)";
        case "th":
            return "Thai";
        case "bo_CN":
            return "Tibetan (China)";
        case "bo_IN":
            return "Tibetan (India)";
        case "bo":
            return "Tibetan";
        case "ti_ER":
            return "Tigrinya (Eritrea)";
        case "ti_ET":
            return "Tigrinya (Ethiopia)";
        case "ti":
            return "Tigrinya";
        case "to_TO":
            return "Tonga (Tonga)";
        case "to":
            return "Tonga";
        case "tr_TR":
            return "Turkish (Turkey)";
        case "tr":
            return "Turkish";
        case "uk_UA":
            return "Ukrainian (Ukraine)";
        case "uk":
            return "Ukrainian";
        case "ur_IN":
            return "Urdu (India)";
        case "ur_PK":
            return "Urdu (Pakistan)";
        case "ur":
            return "Urdu";
        case "uz_Arab":
            return "Uzbek (Arabic)";
        case "uz_Arab_AF":
            return "Uzbek (Arabic, Afghanistan)";
        case "uz_Cyrl":
            return "Uzbek (Cyrillic)";
        case "uz_Cyrl_UZ":
            return "Uzbek (Cyrillic, Uzbekistan)";
        case "uz_Latn":
            return "Uzbek (Latin)";
        case "uz_Latn_UZ":
            return "Uzbek (Latin, Uzbekistan)";
        case "uz":
            return "Uzbek";
        case "vi_VN":
            return "Vietnamese (Vietnam)";
        case "vi":
            return "Vietnamese";
        case "vun_TZ":
            return "Vunjo (Tanzania)";
        case "vun":
            return "Vunjo";
        case "cy_GB":
            return "Welsh (United Kingdom)";
        case "cy":
            return "Welsh";
        case "yo_NG":
            return "Yoruba (Nigeria)";
        case "yo":
            return "Yoruba";
        case "zu_ZA":
            return "Zulu (South Africa)";
        case "zu":
            return "Zulu";
    }
    return $locale;
}

function multiCurl($urls, $timeout=10) {
    $mh = curl_multi_init();
    $ch = $res = [];
    foreach($urls as $i => $item) {
        if (is_array($item)) {
            $url = $item[0];
            $header = $item[1];
        } else {
            $url = $item;
            $header = false;
        }
        write_log("Fetching URL: $url");
        $ch[$i] = curl_init($url);
        curl_setopt($ch[$i], CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch[$i],CURLOPT_CONNECTTIMEOUT,$timeout);
        curl_setopt($ch[$i],CURLOPT_TIMEOUT,$timeout);
        if ($header) {
            curl_setopt($ch[$i],CURLOPT_HTTPHEADER,$header);
        }
        curl_multi_add_handle($mh, $ch[$i]);
    }

    do {
        $execReturnValue = curl_multi_exec($mh, $runningHandles);
    } while ($execReturnValue == CURLM_CALL_MULTI_PERFORM);

    while ($runningHandles && $execReturnValue == CURLM_OK) {
        if (curl_multi_select($mh) != -1) {
            usleep(100);
        }

        do {
            $execReturnValue = curl_multi_exec($mh, $runningHandles);
        } while ($execReturnValue == CURLM_CALL_MULTI_PERFORM);
    }

    // Check for any errors
    if ($execReturnValue != CURLM_OK) {
        write_log("Curl multi read error $execReturnValue!", "ERROR");
    }

    // Extract the content
    foreach($urls as $i => $url) {
        write_log("Closing $i","INFO");
        // Check for errors
        $curlError = curl_error($ch[$i]);
        if($curlError == "") {
            $res[$i] = curl_multi_getcontent($ch[$i]);
        } else {
            write_log("Error handling curl for url: $url","ERROR");
        }
        curl_multi_remove_handle($mh, $ch[$i]);
        curl_close($ch[$i]);
    }
    curl_multi_close($mh);
    write_log("Res: ".json_encode($res));
    $results = [];
    foreach ($res as $url => $response) {
        $json = $xml = false;
        try {
            $json = json_decode($response,true);
            if (!is_array($json)) {
                $json = new JsonXmlElement($response);
                $json = $json->asArray();
            }
        } catch (Exception $e) {
            write_log("Exception: $e");
        }
        if (is_array($json)) $response = $json;
        $results["$url"] = $response;
    }
    unset($mh);
    unset($ch);
    return $results;
}

function plexHeaders($server=false) {
    $server = $server ? $server : findDevice(false,false,"Server");
    $token = $server['Token'];
    $name = deviceName();
    $headers = [
        "X-Plex-Product"=>$name,
        "X-Plex-Version"=>"2.0",
        "X-Plex-Client-Identifier"=>checkSetDeviceID(),
        "X-Plex-Platform"=>"Web",
        "X-Plex-Platform-Version"=>"2.0",
        "X-Plex-Sync-Version"=>"2",
        "X-Plex-Device"=>$name,
        "X-Plex-Device-Name"=>"Phlex",
        "X-Plex-Device-Screen-Resolution"=>"1920x1080",
        "X-Plex-Provider-Version"=>"1.2",
	    "X-Plex-Language"=>strtolower($_SESSION['appLanguage'] ?? "en")
    ];
    if ($token) $headers["X-Plex-Token"] = $token;
    return $headers;
}

function plexSignIn($token) {
	$url = "https://plex.tv/pins/$token.xml";
	$user = $token = false;
	$headers = headerRequestArray(plexHeaders());
	$result = curlGet($url,$headers);
	$data = $result ? flattenXML(new SimpleXMLElement($result)) : false;
	if ($data) {
		$token = $data['auth_token'] ?? false;
	}

	if ($token) {
		$user = verifyPlexToken($token);
	}
    return $user;
}

function protectMessage($string) {
	//return $string;
    if (($_SESSION['cleanLogs'] ?? true) && !isWebApp()) {
    	$str = $string;
	    preg_match_all('#\bhttps?://[^,\s()<>]+(?:\([\w\d]+\)|([^,[:punct:]\s]|/))#', $string, $urls);// Remove tokens and host from URL's
	    foreach($urls as $url) {
		    $url = $url[0] ?? "";
		    if (trim($url)) {
			    $parsed = parse_url($url);
			    if (isset($parsed['query'])) {
				    $qParts = explode("&", $parsed['query']);
				    foreach ($qParts as &$part) {
					    $params = explode("=", $part);
					    if ($params[0] == "X-Plex-Token" || $params[0] == "apiToken") $params[1] = '[REDACTED]';
					    $part = implode("=", $params);
				    }
				    $parsed['query'] = implode("&", $qParts);
			    }
			    if (isset($parsed['host'])) $parsed['host'] = '[REDACTED]';
			    $newUrl = http_build_url($parsed);
			    if ($newUrl !== $url) {
				    $str = str_replace($url, $newUrl, $str);
			    }
		    }
	    }

	    // Remove any API Tokens
	    if (session_started()) {
		    $prefs = getPreference('userdata', false, []);
		    $tokens = [];
		    foreach ($prefs as $user) {
			    $token = $user['apiToken'] ?? false;
			    if ($token) array_push($tokens, $token);
		    }
		    $str = str_replace($tokens, '[REDACTED]', $str);
	    }

	    // Search for JSON and remove instances of various keys
	    $matches = [];
	    $pattern = '/\{(?:[^{}]|(?R))*\}/x';
	    preg_match_all($pattern,$str,$matches);
	    foreach($matches as $match) if (isset($match[0])) {
	    	$decoded = json_decode($match[0],true);
	    	if (is_array($decoded)) {
	    		$keys = ['X-Plex-Token','apiToken','plexToken', 'authToken','token','email','username',
				    'uid','publicAddress','plexUserName','plexEmail','uri','address'];
	    		$cleaned = json_encode(arrayReplaceRecursive($decoded,$keys,'[REDACTED]'),true);
	    		if ($cleaned !== $match) $str = str_replace($match, $cleaned,$str);
		    }
	    }
	    $string = $str;
    }
    return $string;
}

function arrayReplaceRecursive($array,$keys,$replacement) {
	if (is_array($keys)) {
		foreach ($keys as $check) {
			$array = arrayReplaceRecursive($array,$check,$replacement);
		}
	} else {
		foreach($array as $key => $sub) {
			if (is_array($sub)) {
				$array = arrayReplaceRecursive($sub,$keys,$replacement);
			} else {
				if (strtolower($key) === strtolower($keys)) {
					$array[$key] = $replacement;
				}
			}
		}
	}
	return $array;
}

function proxyImage($url) {
    if (empty(trim($url))) return false;
    return "https://phlexchat.com/imageProxy.php?url=".urlencode($url);
}

function randomToken($length = 32) {
	write_log("Function fired.");
	if (!isset($length) || intval($length) <= 8) {
		$length = 32;
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

function serverProtocol() {
    return (((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443) ? 'https://' : 'http://');
}

function session_started() {
    return session_status() === PHP_SESSION_NONE ? false : true;
}

function setStartUrl() {
    $fileOut = dirname(__FILE__) . "/../manifest.json";
    $file = (file_exists($fileOut)) ? $fileOut : dirname(__FILE__) . "/../manifest_template.json";
    $json = json_decode(file_get_contents($file), true);
    $url = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    $url = parse_url($url);
    $url = $url['scheme']."://". $url['host'] . $url['path'];
    $url = str_replace("\api.php","",$url);

    if ($json['start_url'] !== $url) {
        $json['start_url'] = $url;
        file_put_contents($fileOut, json_encode($json, JSON_PRETTY_PRINT));
    }
}

function shuffle_assoc($list) {
    if (!is_array($list)) return $list;

    $keys = array_keys($list);
    shuffle($keys);
    $random = array();
    foreach ($keys as $key)
        $random[$key] = $list[$key];

    return $random;
}

function similarity($str1, $str2) {
    return similar_text($str1,$str2);
}

function sortArray($array, $key) {
    return usort($array, build_sorter($key));
}

function strOrdinalSwap($command) {
    $outArray = [];
    $commandArray = explode(" ", $command);
    foreach ($commandArray as $word) {
        write_log("Word in: " . $word);
        if (is_numeric($word)) {
            $word = strOrdinalFromInt($word);
        } else {
            $word = strOrdinalToInt($word);
        }
        write_log("Word out: " . $word);
        array_push($outArray, $word);
    }
    $command = implode(" ", $outArray);
    return $command;
}

function strOrdinalFromInt($data) {
    $search = [
        '0',
        '1',
        '2',
        '3',
        '4',
        '5',
        '6',
        '7',
        '8',
        '9',
        '10',
        '11',
        '12',
        '13',
        '14',
        '15',
        '16',
        '17',
        '18',
        '19',
        '20',
        '30',
        '40',
        '40',
        '50',
        '60',
        '70',
        '80',
        '90',
        '100',
        '1000',
        '1000000',
        '1000000000'
    ];
    $replace = [
        'zero',
        'one',
        'two',
        'three',
        'four',
        'five',
        'six',
        'seven',
        'eight',
        'nine',
        'ten',
        'eleven',
        'twelve',
        'thirteen',
        'fourteen',
        'fifteen',
        'sixteen',
        'seventeen',
        'eighteen',
        'nineteen',
        'twenty',
        'thirty',
        'forty',
        'fourty',
        'fifty',
        'sixty',
        'seventy',
        'eighty',
        'ninety',
        'hundred',
        'thousand',
        'million',
        'billion'
    ];
    $data = str_replace(array_reverse($search), array_reverse($replace), $data);
    return $data;
}

#TODO: Make an ordinal array in each language array for this
function strOrdinalToInt($data) {
    $search = [
        'zero',
        'one',
        'two',
        'three',
        'four',
        'five',
        'six',
        'seven',
        'eight',
        'nine',
        'ten',
        'eleven',
        'twelve',
        'thirteen',
        'fourteen',
        'fifteen',
        'sixteen',
        'seventeen',
        'eighteen',
        'nineteen',
        'twenty',
        'thirty',
        'forty',
        'fourty',
        'fifty',
        'sixty',
        'seventy',
        'eighty',
        'ninety',
        'hundred',
        'thousand',
        'million',
        'billion'
    ];
    $replace = [
        '0',
        '1',
        '2',
        '3',
        '4',
        '5',
        '6',
        '7',
        '8',
        '9',
        '10',
        '11',
        '12',
        '13',
        '14',
        '15',
        '16',
        '17',
        '18',
        '19',
        '20',
        '30',
        '40',
        '40',
        '50',
        '60',
        '70',
        '80',
        '90',
        '100',
        '1000',
        '1000000',
        '1000000000'
    ];
    $data = str_replace($search, $replace, $data);
    return $data;
}

function strQuote($strings) {
    $out = [];
    foreach ($strings as $string) {
        $out[] = "'$string'";
    }
    return $out;
}

function timeStamp() {
	return date(DATE_RFC2822, time());
}

function toBool($var) {
    $webApp = $_SESSION['webApp'] ?? false;
    if (is_bool($var) || is_int($var)) {
        return $var;
    }
    if (is_string($var)) {
        if (strtolower($var) === 'true') {
            return $webApp ? true : 'yes';
        }
        if (strtolower($var) === 'false') {
            return $webApp ? false : 'no';
        }
    }

    return $var;
}

function transcodeImage($path, $server, $full=false) {
    if (preg_match("/library/", $path) || preg_match("/resources/", $path)) {
        write_log("Tick");
        $token = $server['Token'];
        $size = $full ? 'width=1920&height=1920' : 'width=600&height=600';
        $serverAddress = $server['Uri'];
        $url = "$serverAddress/photo/:/transcode?$size&minSize=1&url=" . urlencode($path) . "&X-Plex-Token=$token";
	    $url = cacheImage($url);
        if (!preg_match("/https/",$url)) $url = "https://phlexchat.com/imageProxy.php?url=".urlencode($url);
        return $url;
    }
    write_log("Invalid image path, returning generic image.", "WARN");
    $path = 'https://phlexchat.com/img/avatar.png';
    return $path;
}

function translateControl($string, $searchArray) {
    foreach ($searchArray as $replace => $search) {
        $string = str_replace($search, $replace, $string);
    }
    return $string;
}

function write_log($text, $level = false, $caller = false, $force=false, $skip=false) {
    $log = file_build_path(dirname(__FILE__), '..', 'logs', "Phlex.log.php");
    $pp = false;
    if ($force && isset($_GET['fetchData'])) {
        $pp = true;
        unset($_GET['fetchData']);
    }
    if (!file_exists($log)) {
        touch($log);
        chmod($log, 0666);
        $authString = "; <?php die('Access denied'); ?>".PHP_EOL;
        file_put_contents($log,$authString);
    }
    if (filesize($log) > 10485760) {
        $oldLog = file_build_path(dirname(__FILE__),"..",'logs',"Phlex.log.php.old");
        if (file_exists($oldLog)) unlink($oldLog);
        rename($log, $oldLog);
        touch($log);
        chmod($log, 0666);
        $authString = "; <?php die('Access denied'); ?>".PHP_EOL;
        file_put_contents($log,$authString);
    }

    $aux =  microtime(true);
	$now = DateTime::createFromFormat('U.u', $aux);
	if (is_bool($now)) $now = DateTime::createFromFormat('U.u', $aux += 0.001);
	$date = $now->format("m-d-Y H:i:s.u");
    $level = $level ? $level : "DEBUG";
    $user = $_SESSION['plexUserName'] ?? false;
    $user = $user ? "[$user] " : "";
    $caller = $caller ? getCaller($caller) : getCaller();
    if (!$skip) $text = protectMessage(($text));

    if ((isset($_GET['fetchData']) || isset($_GET['passive'])) || ($text === "") || !file_exists($log)) return;

    $line = "[$date] [$level] ".$user."[$caller] - $text".PHP_EOL;

    if ($pp) $_SESSION['fetchData'] = true;
    if (!is_writable($log)) return;
    if (!$handle = fopen($log, 'a+')) return;
    if (fwrite($handle, $line) === FALSE) return;

    fclose($handle);
}

function writeSession($key, $value, $unset = false) {
	if ($unset) {
		unset($_SESSION[$key]);
	} else {
		write_log("Writing session value $key to $value");
	    $_SESSION[$key] = $value;
    }
}

function keyGen() {
	$token = rand(1000,99999);
	$token = $token * 7072775606;
	$token = $token * 2;
	$token = dechex($token);
	return $token;
}

function writeSessionArray($array, $unset = false) {
	if ($unset) {
		foreach($array as $key=>$value) {
			unset($_SESSION[$key]);
		}
	} else {
		foreach($array as $key=>$value) {
		    if ($key === 'updated' && empty($value)) {

            } else {
                $_SESSION["$key"] = $value;
            }
		}
	}
}

function xmlToJson($data) {
	$arr = $response = false;
	if (!$data) return [];
	try {
		$temp = new JsonXmlElement($data);
		$arr = $temp->asArray();
	} catch (Exception $e) {
		write_log("This is exceptional. '$e'","ERROR",false,true);
	}
	return is_array($arr) ? $arr : $response;
}
