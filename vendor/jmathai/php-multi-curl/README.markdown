PHP-multi-curl
==============
High performance PHP curl wrapper to make parallel HTTP calls

[![Build Status](https://travis-ci.org/jmathai/php-multi-curl.svg)](https://travis-ci.org/jmathai/php-multi-curl) [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/jmathai/php-multi-curl/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/jmathai/php-multi-curl/?branch=master)

## Contents

* [Installation](#installation)
* [Usage](#usage)
* [Advanced Usage](#advanced-usage)
* [Documentation](#documentation)
  * [Methods](#methods)
    * [addUrl](#addurl)
    * [addCurl](#addcurl)
    * [renderAscii](#renderascii)
  * [Accessing Response](#accessing-response)
    * [response](#response)
    * [code](#code)
    * [headers](#headers)

## Installation

You can use composer to install this library from the command line.

```
composer require jmathai/php-multi-curl:dev-master -v
```

## Usage

Basic usage can be done using the `addUrl($url/*, $options*/)` method. This calls `GET $url` by passing in `$options` as the parameters.
```php
<?php
  // Include Composer's autoload file if not already included.
  require '../vendor/autoload.php';

  // Instantiate the MultiCurl class.
  $mc = JMathai\PhpMultiCurl\MultiCurl::getInstance();

  // Make a call to a URL.
  $call1 = $mc->addUrl('http://slowapi.herokuapp.com/delay/2.0');
  // Make another call to a URL.
  $call2 = $mc->addUrl('http://slowapi.herokuapp.com/delay/1.0');

  // Access the response for $call2.
  // This blocks until $call2 is complete without waiting for $call1
  echo "Call 2: {$call2->response}\n";

  // Access the response for $call1.
  echo "Call 1: {$call1->response}\n";

  // Output a call sequence diagram to see how the parallel calls performed.
  echo $mc->getSequence()->renderAscii();
```

This is what the output of that code will look like.

```
Call 2: consequatur id est
Call 1: in maiores et
(http://slowapi.herokuapp.com/delay/2.0 ::  code=200, start=1447701285.5536, end=1447701287.9512, total=2.397534)
[====================================================================================================]
(http://slowapi.herokuapp.com/delay/1.0 ::  code=200, start=1447701285.5539, end=1447701287.0871, total=1.532997)
[================================================================                                    ]
```

## Advanced Usage

You'll most likely want to configure your cURL calls for your specific purpose. This includes setting the call's HTTP method, parameters, headers and more. You can use the `addCurl($ch)` method and configuring your curl handle using any of PHP's `curl_*` functions.

```php
<?php
  $mc = JMathai\PhpMultiCurl\MultiCurl::getInstance();

  // Set up your cURL handle(s).
  $ch = curl_init('http://www.google.com');
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch, CURLOPT_POST, 1);
  
  // Add your cURL calls and begin non-blocking execution.
  $call = $mc->addCurl($ch);
  
  // Access response(s) from your cURL calls.
  $code = $call->code;
```

You can look at the [tests/example.php](https://github.com/jmathai/php-multi-curl/blob/master/src/example.php) file for working code and execute it from the command line.

## Documentation

### Methods
#### addUrl

`addUrl((string) $url/*, (array) $options*/)`

Makes a `GET` call to `$url` by passing the key/value array `$options` as parameters. This method automatically sets `CURLOPT_RETURNTRANSFER` to `1` internally.

```php
$call = $mc->addUrl('https://www.google.com', array('q' => 'github'));
echo $call->response;
```

#### addCurl

`addCurl((curl handle) $ch)`

Takes a curl handle `$ch` and executes it. This method, unlike `addUrl`, will not add anything to the cURL handle. You'll most likely want to set `CURLOPT_RETURNTRANSFER` yourself before passing the handle into `addCurl`.

```php
$ch = curl_init('http://www.mocky.io/v2/5185415ba171ea3a00704eed');
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');

$call = $mc->addCurl($ch);
echo $call->response;
```

### Accessing Response

The curl calls begin executing the moment you call `addUrl` or `addCurl`. Execution control is returned to your code immediately and blocking for the response does not occur until you access the `response` or `code` variables. The library only blocks for the call you're trying to access the response to and will allow longer running calls to continue to execute while returning control back to your code.

#### response

The `response` variable returns the `string` text of the response.

```php
echo $call->response;
```

#### code

The `code` variable returns the `integer` HTTP response code of the request.

```php
echo $call->code;
```

#### headers

The `headers` variable returns an `array` of HTTP headers from the response.

```php
var_dump($call->headers);
```

#### renderAscii

Return a `string` that prints out details of call latency and degree of being called in parallel. This method can be called indirectly through the multi-curl instance you're using.

```php
echo $mc->getSequence()->renderAscii();
```

## Authors
   * jmathai
   
### Contributors
   * Lewis Cowles (LewisCowles1986) - Usability for adding url's without needing to worry about CURL, but provisioning also for specifying additional parameters
   * Sam Thomson (samthomson) - Packaged it up
