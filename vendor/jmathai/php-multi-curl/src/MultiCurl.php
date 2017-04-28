<?php namespace JMathai\PhpMultiCurl;
/*if(!class_exists('MultiCurlManager'))
  include 'MultiCurlManager.php';
if(!class_exists('MultiCurlSequence'))
  include 'MultiCurlSequence.php';
if(!class_exists('MultiCurlException'))
  include 'MultiCurlException.php';*/

/**
 * MultiCurl multicurl http client
 *
 * @author Jaisen Mathai <jaisen@jmathai.com>
 */
class MultiCurl
{
  const timeout = 3;
  private static $inst = null;
  /* @TODO make this private and add a method to set it to 0 */
  public static $singleton = 0;

  private $mc;
  private $running;
  private $execStatus;
  private $sleepIncrement = 1.1;
  private $requests = array();
  private $responses = array();
  private $properties = array();
  private static $timers = array();

  public function __construct()
  {
    if(self::$singleton === 0)
    {
      throw new MultiCurlException('This class cannot be instantiated by the new keyword.  You must instantiate it using: $obj = MultiCurl::getInstance();');
    }

    $this->mc = curl_multi_init();
    $this->properties = array(
      'code'  => CURLINFO_HTTP_CODE,
      'time'  => CURLINFO_TOTAL_TIME,
      'length'=> CURLINFO_CONTENT_LENGTH_DOWNLOAD,
      'type'  => CURLINFO_CONTENT_TYPE,
      'url'   => CURLINFO_EFFECTIVE_URL
      );
  }
  
  public function reset(){
      $this->requests = array();
      $this->responses = array();
      self::$timers = array();
  }

  public function addUrl($url, $options = array())
  {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    foreach($options as $option=>$value)
    {
        curl_setopt($ch, $option, $value);
    }
    return $this->addCurl($ch);
  }

  public function addCurl($ch)
  {
    if(gettype($ch) !== 'resource')
    {
      throw new MultiCurlInvalidParameterException('Parameter must be a valid curl handle');
    }

    $key = $this->getKey($ch);
    $this->requests[$key] = $ch;
    curl_setopt($ch, CURLOPT_HEADERFUNCTION, array($this, 'headerCallback'));

    $code = curl_multi_add_handle($this->mc, $ch);
    $this->startTimer($key);
    
    // (1)
    if($code === CURLM_OK || $code === CURLM_CALL_MULTI_PERFORM)
    {
      do
      {
        $this->execStatus = curl_multi_exec($this->mc, $this->running);
      } while ($this->execStatus === CURLM_CALL_MULTI_PERFORM);

      return new MultiCurlManager($key);
    }
    else
    {
      return $code;
    }
  }

  public function getResult($key = null)
  {
    if($key != null)
    {
      if(isset($this->responses[$key]['code']))
      {
        return $this->responses[$key];
      }

      $innerSleepInt = $outerSleepInt = 1;
      while($this->running && ($this->execStatus == CURLM_OK || $this->execStatus == CURLM_CALL_MULTI_PERFORM))
      {
        usleep(intval($outerSleepInt));
        $outerSleepInt = intval(max(1, ($outerSleepInt*$this->sleepIncrement)));
        $ms=curl_multi_select($this->mc, 0);

        // bug in PHP 5.3.18+ where curl_multi_select can return -1
        // https://bugs.php.net/bug.php?id=63411
        if($ms === -1)
          usleep(100000);

        // see pull request https://github.com/jmathai/php-multi-curl/pull/17
        // details here http://curl.haxx.se/libcurl/c/libcurl-errors.html
        if($ms >= CURLM_CALL_MULTI_PERFORM)
        {
          do{
            $this->execStatus = curl_multi_exec($this->mc, $this->running);
            usleep(intval($innerSleepInt));
            $innerSleepInt = intval(max(1, ($innerSleepInt*$this->sleepIncrement)));
          }while($this->execStatus==CURLM_CALL_MULTI_PERFORM);
          $innerSleepInt = 1;
        }
        $this->storeResponses();
        if(isset($this->responses[$key]['data']))
        {
          return $this->responses[$key];
        }
      }
      return null;
    }
    return false;
  }

  public static function getSequence()
  {
    return new MultiCurlSequence(self::$timers);
  }

  public static function getTimers()
  {
    return self::$timers;
  }

  public function inject($key, $value)
  {
    $this->$key = $value;
  }

  private function getKey($ch)
  {
    return (string)$ch;
  }

  private function headerCallback($ch, $header)
  {
    $_header = trim($header);
    $colonPos= strpos($_header, ':');
    if($colonPos > 0)
    {
      $key = substr($_header, 0, $colonPos);
      $val = preg_replace('/^\W+/','',substr($_header, $colonPos));
      $this->responses[$this->getKey($ch)]['headers'][$key] = $val;
    }
    return strlen($header);
  }

  private function storeResponses()
  {
    while($done = curl_multi_info_read($this->mc))
    {
      $this->storeResponse($done);
    }
  }

  private function storeResponse($done, $isAsynchronous = true)
  {
    $key = $this->getKey($done['handle']);
    $this->stopTimer($key, $done);
    if($isAsynchronous)
      $this->responses[$key]['data'] = curl_multi_getcontent($done['handle']);
    else
      $this->responses[$key]['data'] = curl_exec($done['handle']);

    $this->responses[$key]['response'] = $this->responses[$key]['data'];

    foreach($this->properties as $name => $const)
    {
      $this->responses[$key][$name] = curl_getinfo($done['handle'], $const);
    }
    if($isAsynchronous)
      curl_multi_remove_handle($this->mc, $done['handle']);
    curl_close($done['handle']);
  }

  private function startTimer($key)
  {
    self::$timers[$key]['start'] = microtime(true);
  }

  private function stopTimer($key, $done)
  {
      self::$timers[$key]['end'] = microtime(true);
      self::$timers[$key]['api'] = curl_getinfo($done['handle'], CURLINFO_EFFECTIVE_URL);
      self::$timers[$key]['time'] = curl_getinfo($done['handle'], CURLINFO_TOTAL_TIME);
      self::$timers[$key]['code'] = curl_getinfo($done['handle'], CURLINFO_HTTP_CODE);
  }

  public static function getInstance()
  {
    if(self::$inst == null)
    {
      self::$singleton = 1;
      self::$inst = new MultiCurl();
    }

    return self::$inst;
  }
}

/*
 * Credits:
 *  - (1) Alistair pointed out that curl_multi_add_handle can return CURLM_CALL_MULTI_PERFORM on success.
 */
