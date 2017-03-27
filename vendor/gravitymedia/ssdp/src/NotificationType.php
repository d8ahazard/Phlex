<?php
/**
 * This file is part of the Ssdp project
 *
 * @author Daniel Schröder <daniel.schroeder@gravitymedia.de>
 */

namespace GravityMedia\Ssdp;

use GravityMedia\Urn\Urn;
use InvalidArgumentException;
use Rhumsaa\Uuid\Uuid;

/**
 * Notification type
 *
 * @package GravityMedia\Ssdp
 */
class NotificationType
{
    /**
     * Default notification type
     */
    const DEFAULT_NOTIFICATION_TYPE = 'upnp:rootdevice';

    /**
     * UPNP token
     */
    const UPNP_TOKEN = 'upnp';

    /**
     * UUID token
     */
    const UUID_TOKEN = 'uuid';

    /**
     * URN token
     */
    const URN_TOKEN = 'urn';

    /**
     * @var string
     */
    protected $token;

    /**
     * @var string|Uuid|Urn
     */
    protected $value;

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
     * Return notification type as string
     *
     * @return string
     */
    public function toString()
    {
        return sprintf(
            '%s:%s',
            $this->getToken(),
            $this->getValue()
        );
    }

    /**
     * Create notification type from string
     *
     * @param string $string
     *
     * @throws InvalidArgumentException
     * @return $this
     */
    public static function fromString($string = self::DEFAULT_NOTIFICATION_TYPE)
    {
        $tuple = explode(':', $string, 2);
        $token = array_shift($tuple);
        if (self::UUID_TOKEN === strtolower($token)) {
            return self::fromUuid(Uuid::fromString($string));
        }
        if (self::URN_TOKEN === strtolower($token)) {
            return self::fromUrn(Urn::fromString($string));
        }
        $notificationType = new static();
        $notificationType->setToken($token);
        $notificationType->setValue(array_pop($tuple));
        return $notificationType;
    }

    /**
     * Create notification type from UUID
     *
     * @param Uuid $uuid
     *
     * @throws InvalidArgumentException
     * @return $this
     */
    public static function fromUuid(Uuid $uuid)
    {
        $notificationType = new static();
        $notificationType->setToken(self::UUID_TOKEN);
        $notificationType->setValue($uuid);
        return $notificationType;
    }

    /**
     * Create notification type from URN
     *
     * @param Urn $urn
     *
     * @throws InvalidArgumentException
     * @return $this
     */
    public static function fromUrn(Urn $urn)
    {
        $notificationType = new static();
        $notificationType->setToken(self::URN_TOKEN);
        $notificationType->setValue($urn);
        return $notificationType;
    }

    /**
     * Get valid tokens
     *
     * @return string[]
     */
    protected function getValidTokens()
    {
        return array(self::UPNP_TOKEN, self::UUID_TOKEN, self::URN_TOKEN);
    }

    /**
     * Get token
     *
     * @return string
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * Set token
     *
     * @param string $token
     *
     * @throws InvalidArgumentException
     * @return $this
     */
    public function setToken($token)
    {
        $token = strtolower($token);
        if (!in_array($token, $this->getValidTokens())) {
            throw new InvalidArgumentException(sprintf('Invalid token argument "%s"', $token));
        }
        $this->token = $token;
        return $this;
    }

    /**
     * Get value
     *
     * @return string|Uuid|Urn
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Set value
     *
     * @param string|Uuid|Urn $value
     *
     * @return $this
     */
    public function setValue($value)
    {
        $this->value = $value;
        return $this;
    }
}
