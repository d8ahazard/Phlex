<?php
/**
 * This file is part of the Ssdp project
 *
 * @author Daniel Schröder <daniel.schroeder@gravitymedia.de>
 */

namespace GravityMedia\Ssdp\SearchTarget;

/**
 * Device search target URN
 *
 * @package GravityMedia\Ssdp\SearchTarget
 */
class DeviceUrn extends AbstractUrn
{
    const CATEGORY = 'device';

    /**
     * Get category
     *
     * @return string
     */
    public function getCategory()
    {
        return self::CATEGORY;
    }
}
