<?php
/**
 * This file is part of the Ssdp project
 *
 * @author Daniel Schröder <daniel.schroeder@gravitymedia.de>
 */

namespace GravityMedia\Ssdp;

use Rhumsaa\Uuid\Uuid;

/**
 * Unique service name
 *
 * @package GravityMedia\Ssdp
 */
class UniqueServiceName
{
    /**
     * @var Uuid
     */
    protected $identifier;

    /**
     * @var NotificationType
     */
    protected $notificationType;

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
     * Return unique service name as string
     *
     * @return string
     */
    public function toString()
    {
        $notificationType = $this->getNotificationType();
        if (NotificationType::UUID_TOKEN === $notificationType->getToken()) {
            return $notificationType->toString();
        }
        $identifier = $this->getIdentifier();
        if (is_null($identifier)) {
            return $notificationType->toString();
        }
        return sprintf(
            '%s::%s',
            $identifier,
            $notificationType
        );
    }

    /**
     * Create unique service name from string
     *
     * @param string $string
     *
     * @return $this
     */
    public static function fromString($string)
    {
        /** @var UniqueServiceName $uniqueServiceName */
        $uniqueServiceName = new static();
        $tuple = explode('::', $string, 2);
        $uniqueServiceName->setIdentifier(Uuid::fromString(array_shift($tuple)));
        if (!empty($tuple)) {
            $uniqueServiceName->setNotificationType(NotificationType::fromString(array_pop($tuple)));

        }
        return $uniqueServiceName;
    }

    /**
     * Get identifier
     *
     * @return Uuid
     */
    public function getIdentifier()
    {
        if (is_null($this->identifier)) {
            return Uuid::fromString(Uuid::NIL);
        }
        return $this->identifier;
    }

    /**
     * Set identifier
     *
     * @param Uuid $identifier
     *
     * @return $this
     */
    public function setIdentifier(Uuid $identifier)
    {
        $this->identifier = $identifier;
        return $this;
    }

    /**
     * Get notification type
     *
     * @return NotificationType
     */
    public function getNotificationType()
    {
        if (is_null($this->notificationType)) {
            return NotificationType::fromString();
        }
        return $this->notificationType;
    }

    /**
     * Set notification type
     *
     * @param NotificationType $notificationType
     *
     * @return $this
     */
    public function setNotificationType(NotificationType $notificationType)
    {
        $this->notificationType = $notificationType;
        return $this;
    }
}
