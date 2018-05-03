<?php use JMathai\PhpMultiCurl\MultiCurl;

class CallsTest extends PHPUnit_Framework_TestCase
{
  public function testGetCode()
  {
    $mc = MultiCurl::getInstance();
    $google = $mc->addURL('http://www.google.com'); // call google
    
    $this->assertInternalType('integer', $google->code);
  }

  public function testGetCode200()
  {
    $mc = MultiCurl::getInstance();
    $google = $mc->addUrl('http://www.google.com');
    
    $this->assertEquals(200, $google->code);
  }

  public function testGetCode404()
  {
    $mc = MultiCurl::getInstance();
    $google = $mc->addUrl('http://www.example.com/404');
    
    $this->assertEquals(404, $google->code);
  }

  public function testResponseJson()
  {
    $mc = MultiCurl::getInstance();
    $call = $mc->addUrl('http://jsonplaceholder.typicode.com/users');
    $response = json_decode($call->response, true);
    $this->assertInternalType('array', $response);
  }

  public function testResponseHeaders()
  {
    $mc = MultiCurl::getInstance();
    $google = $mc->addUrl('http://www.example.com/404');
    
    $this->assertInternalType('array', $google->headers);
    $this->assertNotEmpty($google->headers['Etag']);
  }
}
