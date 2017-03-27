<?php
/**
 * This file is part of the Ssdp project
 *
 * @author Daniel Schröder <daniel.schroeder@gravitymedia.de>
 */

namespace GravityMedia\Ssdp\Message\Response\Search;

use DateTime;
use GravityMedia\Ssdp\Message\ResponseInterface;
use GravityMedia\Ssdp\SearchTarget;
use GravityMedia\Ssdp\UniqueServiceName;
use Guzzle\Http\Url;

/**
 * Discover response response message
 *
 * @package GravityMedia\Ssdp\Message\Response\Search
 */
class Discover extends AbstractMessage implements ResponseInterface
{
    /**
     * @inheritdoc
     */
    public function toString()
    {
        $date = $this->getDate();
        $descriptionUrl = $this->getDescriptionUrl();
        return sprintf(
            'HTTP/1.1 200 OK' . "\r\n"
            . 'CACHE-CONTROL: max-age=%u' . "\r\n"
            . 'DATE: %s' . "\r\n"
            . 'EXT:' . "\r\n"
            . 'LOCATION: http://%s:%d%s' . "\r\n"
            . 'SERVER: %s' . "\r\n"
            . 'ST: %s' . "\r\n"
            . 'USN: %s' . "\r\n"
            . "\r\n",
            $this->getLifetime(),
            $date->format(DATE_RFC822),
            $descriptionUrl->getHost(),
            $descriptionUrl->getPort(),
            $descriptionUrl->getPath(),
            $this->getServerString(),
            $this->getSearchTarget(),
            $this->getUniqueServiceName()
        );
    }

    /**
     * Create response from string
     *
     * @param string $string
     *
     * @return $this
     */
    public static function fromString($string)
    {
        /** @var Discover $message */
        $message = new static();
        foreach (explode(PHP_EOL, trim($string)) as $line) {
            $tuple = explode(':', trim($line), 2);
            if (2 == count($tuple)) {
                $value = trim(array_pop($tuple));
                switch (strtoupper(array_pop($tuple))) {
                    case 'CACHE-CONTROL':
                        $message->setLifetime(intval(substr($value, strpos($value, '=') + 1)));
                        break;
                    case 'DATE':
                        $message->setDate(new DateTime($value));
                        break;
                    case 'LOCATION':
                        $message->setDescriptionUrl(Url::factory($value));
                        break;
                    case 'SERVER':
                        $message->setServerString($value);
                        break;
                    case 'ST':
                        $message->setSearchTarget(SearchTarget::fromString($value));
                        break;
                    case 'USN':
                        $message->setUniqueServiceName(UniqueServiceName::fromString($value));
                        break;
                }
            }
        }
        return $message;
    }
}
