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
 * Update advertisement request message
 *
 * @package GravityMedia\Ssdp\Message\Request\Advertisement
 */
class Update extends AbstractMessage implements RequestInterface
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
            . 'LOCATION: http://%s:%d%s' . "\r\n"
            . 'NT: %s' . "\r\n"
            . 'NTS: ssdp:update' . "\r\n"
            . 'USN: %s' . "\r\n"
            . "\r\n",
            SsdpInterface::MULTICAST_ADDRESS,
            SsdpInterface::PORT,
            $descriptionUrl->getHost(),
            $descriptionUrl->getPort(),
            $descriptionUrl->getPath(),
            $this->getNotificationType(),
            $this->getUniqueServiceName()
        );
    }
}
