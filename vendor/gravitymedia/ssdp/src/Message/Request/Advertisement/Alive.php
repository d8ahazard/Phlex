<?php
/**
 * This file is part of the Ssdp project
 *
 * @author Daniel Schröder <daniel.schroeder@gravitymedia.de>
 */

namespace GravityMedia\Ssdp\Message\Request\Advertisement;

use GravityMedia\Ssdp\Message\RequestInterface;
use GravityMedia\Ssdp\SsdpInterface;

/**
 * Alive advertisement request message
 *
 * @package GravityMedia\Ssdp\Message\Request\Advertisement
 */
class Alive extends AbstractMessage implements RequestInterface
{
    /**
     * @inheritdoc
     */
    public function toString()
    {
        $descriptionUrl = $this->getDescriptionUrl();
        return sprintf(
            'NOTIFY * HTTP/1.1' . "\r\n"
            . 'HOST: %s:%d' . "\r\n"
            . 'CACHE-CONTROL: max-age=%u' . "\r\n"
            . 'LOCATION: http://%s:%d%s' . "\r\n"
            . 'NT: %s' . "\r\n"
            . 'NTS: ssdp:alive' . "\r\n"
            . 'SERVER: %s' . "\r\n"
            . 'USN: %s' . "\r\n"
            . "\r\n",
            SsdpInterface::MULTICAST_ADDRESS,
            SsdpInterface::PORT,
            $this->getLifetime(),
            $descriptionUrl->getHost(),
            $descriptionUrl->getPort(),
            $descriptionUrl->getPath(),
            $this->getNotificationType(),
            $this->getServerString(),
            $this->getUniqueServiceName()
        );
    }
}
