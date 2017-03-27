#Ssdp

Simple Service Discovery Protocol (SSDP) library for PHP

[![Packagist](https://img.shields.io/packagist/v/gravitymedia/ssdp.svg)](https://packagist.org/packages/gravitymedia/ssdp)
[![Downloads](https://img.shields.io/packagist/dt/gravitymedia/ssdp.svg)](https://packagist.org/packages/gravitymedia/ssdp)
[![License](https://img.shields.io/packagist/l/gravitymedia/ssdp.svg)](https://packagist.org/packages/gravitymedia/ssdp)
[![Build](https://img.shields.io/travis/GravityMedia/Ssdp.svg)](https://travis-ci.org/GravityMedia/Ssdp)
[![Code Quality](https://img.shields.io/scrutinizer/g/GravityMedia/Ssdp.svg)](https://scrutinizer-ci.com/g/GravityMedia/Ssdp/?branch=master)
[![Coverage](https://img.shields.io/scrutinizer/coverage/g/GravityMedia/Ssdp.svg)](https://scrutinizer-ci.com/g/GravityMedia/Ssdp/?branch=master)
[![PHP Dependencies](https://www.versioneye.com/user/projects/54a6c39d27b014005400004b/badge.svg)](https://www.versioneye.com/user/projects/54a6c39d27b014005400004b)

##Requirements##

This library has the following requirements:

 - PHP 5.4+

##Installation##

Install composer in your project:

```bash
$ curl -s https://getcomposer.org/installer | php
```

Create a `composer.json` file in your project root:

```json
{
    "require": {
        "gravitymedia/ssdp": "dev-master"
    }
}
```

Install via composer:

```bash
$ php composer.phar install
```

##Usage##

```php
require 'vendor/autoload.php';

use GravityMedia\Ssdp\SsdpEvent;
use GravityMedia\Ssdp\SsdpMessenger;
use Symfony\Component\EventDispatcher\EventDispatcher;

// create event dispatcher
$eventDispatcher = new EventDispatcher();

// create SSDP messenger
$ssdpMessenger = new SsdpMessenger($eventDispatcher);

// add discovery listener
$eventDispatcher->addListener(
    SsdpEvent::DISCOVER,
    function (SsdpEvent $event) {
    
        // dump response
        var_dump($event->getResponse());
    }
);

// discover devices and services
$ssdpMessenger->discover();
```
