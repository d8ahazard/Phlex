<?php
/**
 * This file is part of the Ssdp project
 *
 * @author Daniel Schröder <daniel.schroeder@gravitymedia.de>
 */

namespace GravityMedia\Ssdp;

/**
 * Search target
 *
 * @package GravityMedia\Ssdp
 */
class SearchTarget extends NotificationType
{
    /**
     * Default search target
     */
    const DEFAULT_SEARCH_TARGET = 'ssdp:all';

    /**
     * SSDP token
     */
    const SSDP_TOKEN = 'ssdp';

    /**
     * @inheritdoc
     */
    public static function fromString($string = self::DEFAULT_SEARCH_TARGET)
    {
        return parent::fromString($string);
    }

    /**
     * @inheritdoc
     */
    protected function getValidTokens()
    {
        $validTokens = parent::getValidTokens();
        array_unshift($validTokens, self::SSDP_TOKEN);
        return $validTokens;
    }
}
