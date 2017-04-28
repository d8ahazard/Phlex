#!/usr/bin/env php
<?php
if (php_sapi_name() !== 'cli')
  die();

require '../vendor/autoload.php';
$mc = JMathai\PhpMultiCurl\MultiCurl::getInstance();

$ch1 = curl_init('http://www.yahoo.com');
curl_setopt($ch1, CURLOPT_RETURNTRANSFER, 1);
$yahoo = $mc->addCurl($ch1); // call yahoo
$ch2 = curl_init('http://www.google.com');
curl_setopt($ch2, CURLOPT_RETURNTRANSFER, 1);
$google = $mc->addCurl($ch2); // call google
$ch3 = curl_init('http://www.ebay.com');
curl_setopt($ch3, CURLOPT_RETURNTRANSFER, 1);
$ebay = $mc->addCurl($ch3); // call ebay

// fetch response from yahoo and google
echo "The response code from Yahoo! was {$yahoo->code}\n";
echo "The response code from Google was {$google->code}\n";

$ch4 = curl_init('http://www.microsoft.com');
curl_setopt($ch4, CURLOPT_RETURNTRANSFER, 1);
$microsoft = $mc->addCurl($ch4); // call microsoft

// fetch response from ebay and microsoft
echo "The response code from Ebay was {$ebay->code}\n";
echo "The response code from Microsoft was {$microsoft->code}\n";

echo $mc->getSequence()->renderAscii();
