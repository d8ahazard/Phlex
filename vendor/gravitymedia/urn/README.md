# Uniform Resource Names (URN)

[![Latest Version on Packagist](https://img.shields.io/packagist/v/gravitymedia/urn.svg)](https://packagist.org/packages/gravitymedia/urn)
[![Software License](https://img.shields.io/packagist/l/gravitymedia/urn.svg)](LICENSE.md)
[![Build Status](https://img.shields.io/travis/GravityMedia/Urn.svg)](https://travis-ci.org/GravityMedia/Urn)
[![Coverage Status](https://img.shields.io/scrutinizer/coverage/g/GravityMedia/Urn.svg)](https://scrutinizer-ci.com/g/GravityMedia/Urn/code-structure)
[![Quality Score](https://img.shields.io/scrutinizer/g/GravityMedia/Urn.svg)](https://scrutinizer-ci.com/g/GravityMedia/Urn)
[![Total Downloads](https://img.shields.io/packagist/dt/gravitymedia/urn.svg)](https://packagist.org/packages/gravitymedia/urn)
[![PHP Dependencies](https://img.shields.io/versioneye/d/php/gravitymedia:urn.svg)](https://www.versioneye.com/user/projects/54a6c39d27b014005400004b)

A PHP library for generating RFC 2141 compliant uniform resource names (URN).

## Requirements

This library has the following requirements:

 - PHP 5.6+

## Installation

Install Composer in your project:

```bash
$ curl -s https://getcomposer.org/installer | php
```

Require the package via Composer:

```bash
$ php composer.phar require gravitymedia/urn
```

## Usage

```php
// require autoloader
require 'vendor/autoload.php';

// import classes
use GravityMedia\Urn\Urn;

// create URN object from string
$urn = Urn::fromString('urn:example-namespace-id:just_an_example');

// dump namespace identifier
var_dump($urn->getNamespaceIdentifier()); // string(20) "example-namespace-id"

// dump namespace specific string
var_dump($urn->getNamespaceSpecificString()); // string(15) "just_an_example"
```
