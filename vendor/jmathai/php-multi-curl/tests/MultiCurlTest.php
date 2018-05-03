<?php use JMathai\PhpMultiCurl\MultiCurl;

class MultiCurlTest extends PHPUnit_Framework_TestCase
{
  public function testConstructorThrowsExceptionWhenInvokedWithNew()
  {
    MultiCurl::$singleton = 0;
    $this->setExpectedException('JMathai\PhpMultiCurl\MultiCurlException');
    new MultiCurl();
  }

  public function testConstructorSucceedsAsSingleton()
  {
    MultiCurl::getInstance();
  }

  public function testAddUrl()
  {
    $mc = MultiCurl::getInstance();
    $res = $mc->addUrl('http://google.com');
    $this->assertInstanceOf('Jmathai\PhpMultiCurl\MultiCurlManager', $res);
  }

  public function testAddCurl()
  {
    $ch = curl_init('https://www.google.com');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

    $mc = MultiCurl::getInstance();
    $res = $mc->addCurl($ch);
    $this->assertInstanceOf('Jmathai\PhpMultiCurl\MultiCurlManager', $res);
  }

  public function testAddCurlWithNull()
  {
    $this->setExpectedException('JMathai\PhpMultiCurl\MultiCurlInvalidParameterException');
    $mc = MultiCurl::getInstance();
    $res = $mc->addCurl(null);
  }
}
