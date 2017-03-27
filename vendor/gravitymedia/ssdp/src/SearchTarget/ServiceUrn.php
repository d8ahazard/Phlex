<?php
/**
 * This file is part of the Ssdp project
 *
 * @author Daniel Schröder <daniel.schroeder@gravitymedia.de>
 */

namespace GravityMedia\Ssdp\SearchTarget;

/**
 * Service search target URN
 *
 * @package GravityMedia\Ssdp\SearchTarget
 */
class ServiceUrn extends AbstractUrn
{
    const CATEGORY = 'service';

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
