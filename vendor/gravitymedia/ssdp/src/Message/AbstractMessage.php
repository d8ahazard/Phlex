<?php
/**
 * This file is part of the Ssdp project
 *
 * @author Daniel Schröder <daniel.schroeder@gravitymedia.de>
 */

namespace GravityMedia\Ssdp\Message;

/**
 * Abstract message
 *
 * @package GravityMedia\Ssdp\Message
 */
abstract class AbstractMessage
{
    /**
     * Return string representation
     *
     * @return string
     */
    public function __toString()
    {
        return $this->toString();
    }

    /**
     * Return advertisement request as string
     *
     * @return string
     */
    abstract public function toString();
}
