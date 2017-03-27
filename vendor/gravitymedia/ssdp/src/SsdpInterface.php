<?php
/**
 * This file is part of the Ssdp project
 *
 * @author Daniel Schröder <daniel.schroeder@gravitymedia.de>
 */

namespace GravityMedia\Ssdp;

/**
 * SSDP interface
 *
 * @package GravityMedia\Ssdp
 */
class SsdpInterface
{
    /**
     * Default description URL (URL for UPnP description for root device)
     */
    const DEFAULT_DESCRIPTION_URL = 'http://127.0.0.1:80/description.xml';

    /**
     * Default message lifetime (seconds until advertisement expires)
     */
    const DEFAULT_MESSAGE_LIFETIME = 1800;

    /**
     * Default maximum wait time (seconds to delay response)
     */
    const DEFAULT_MAXIMUM_WAIT_TIME = 1;

    /**
     * Multicast address
     */
    const MULTICAST_ADDRESS = '239.255.255.250';

    /**
     * Port
     */
    const PORT = 1900;

    /**
     * Name
     */
    const NAME = 'GravityMedia-Ssdp';

    /**
     * Version
     */
    const VERSION = '0.0.1-alpha';
}
