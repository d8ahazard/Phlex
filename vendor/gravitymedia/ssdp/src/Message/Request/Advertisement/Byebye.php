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
 * Byebye advertisement request message
 *
 * @package GravityMedia\Ssdp\Message\Request\Advertisement
 */
class Byebye extends AbstractMessage implements RequestInterface
{
    /**
     * @inheritdoc
     */
    public function toString()
    {
        return sprintf(
            'NOTIFY * HTTP/1.1' . "\r\n"
            . 'HOST: %s:%d' . "\r\n"
            . 'NT: %s' . "\r\n"
            . 'NTS: ssdp:byebye' . "\r\n"
            . 'USN: %s' . "\r\n"
            . "\r\n",
            SsdpInterface::MULTICAST_ADDRESS,
            SsdpInterface::PORT,
            $this->getNotificationType(),
            $this->getUniqueServiceName()
        );
    }
}
