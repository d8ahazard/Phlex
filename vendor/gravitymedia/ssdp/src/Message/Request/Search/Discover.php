<?php
/**
 * This file is part of the Ssdp project
 *
 * @author Daniel Schröder <daniel.schroeder@gravitymedia.de>
 */

namespace GravityMedia\Ssdp\Message\Request\Search;

use GravityMedia\Ssdp\Message\RequestInterface;
use GravityMedia\Ssdp\SsdpInterface;

/**
 * Discover search request message
 *
 * @package GravityMedia\Ssdp\Message\Request\Search
 */
class Discover extends AbstractMessage implements RequestInterface
{
    /**
     * @inheritdoc
     */
    public function toString()
    {
        return sprintf(
            'M-SEARCH * HTTP/1.1' . "\r\n"
            . 'HOST: %s:%d' . "\r\n"
            . 'MAN: "ssdp:discover"' . "\r\n"
            . 'MX: %d' . "\r\n"
            . 'ST: %s' . "\r\n"
            . 'USER-AGENT: %s' . "\r\n"
            . "\r\n",
            SsdpInterface::MULTICAST_ADDRESS,
            SsdpInterface::PORT,
            $this->getMaximumWaitTime(),
            $this->getSearchTarget(),
            $this->getUserAgentString()
        );
    }
}
