<?php namespace JMathai\PhpMultiCurl;
/**
 * MultiCurlManager manages multicurl handles
 *
 * @author Jaisen Mathai <jaisen@jmathai.com>
 */
class MultiCurlManager
{
  private $key;
  private $epiCurl;

  public function __construct($key)
  {
    $this->key = $key;
    $this->epiCurl = MultiCurl::getInstance();
  }

  public function __get($name)
  {
    $responses = $this->epiCurl->getResult($this->key);
    return isset($responses[$name]) ? $responses[$name] : null;
  }

  public function __isset($name)
  {
    $val = self::__get($name);
    return empty($val);
  }
}
