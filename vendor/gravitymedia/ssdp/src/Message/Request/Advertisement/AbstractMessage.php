<?php
/**
 * This file is part of the Ssdp project
 *
 * @author Daniel Schröder <daniel.schroeder@gravitymedia.de>
 */

namespace GravityMedia\Ssdp\Message\Request\Advertisement;

use GravityMedia\Ssdp\SsdpInterface;
use GravityMedia\Ssdp\NotificationType;
use GravityMedia\Ssdp\UniqueServiceName;
use Guzzle\Http\Url;

/**
 * Abstract advertisement request message
 *
 * @package GravityMedia\Ssdp\Message\Request\Advertisement
 */
abstract class AbstractMessage extends \GravityMedia\Ssdp\Message\AbstractMessage
{
    /**
     * @var int
     */
    protected $lifetime;

    /**
     * @var Url
     */
    protected $descriptionUrl;

    /**
     * @var NotificationType
     */
    protected $notificationType;

    /**
     * @var string
     */
    protected $serverString;

    /**
     * @var UniqueServiceName
     */
    protected $uniqueServiceName;

    /**
     * Get lifetime
     *
     * @return int
     */
    public function getLifetime()
    {
        if (is_null($this->lifetime)) {
            return SsdpInterface::DEFAULT_MESSAGE_LIFETIME;
        }
        return $this->lifetime;
    }

    /**
     * Set lifetime
     *
     * @param int $lifetime
     *
     * @return $this
     */
    public function setLifetime($lifetime)
    {
        $this->lifetime = $lifetime;
        return $this;
    }

    /**
     * Get description URL
     *
     * @return Url
     */
    public function getDescriptionUrl()
    {
        if (is_null($this->descriptionUrl)) {
            return Url::factory(SsdpInterface::DEFAULT_DESCRIPTION_URL);
        }
        return $this->descriptionUrl;
    }

    /**
     * Set description URL
     *
     * @param Url $descriptionUrl
     *
     * @return $this
     */
    public function setDescriptionUrl(Url $descriptionUrl)
    {
        $this->descriptionUrl = $descriptionUrl;
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

    /**
     * Get server string
     *
     * @return string
     */
    public function getServerString()
    {
        if (is_null($this->serverString)) {
            return sprintf(
                '%s/%s UPnP/1.1 %s/%s',
                PHP_OS,
                php_uname('r'),
                SsdpInterface::NAME,
                SsdpInterface::VERSION
            );
        }
        return $this->serverString;
    }

    /**
     * Set server string
     *
     * @param string $serverString
     *
     * @return $this
     */
    public function setServerString($serverString)
    {
        $this->serverString = $serverString;
        return $this;
    }

    /**
     * Get unique service name
     *
     * @return UniqueServiceName
     */
    public function getUniqueServiceName()
    {
        return $this->uniqueServiceName;
    }

    /**
     * Set unique service name
     *
     * @param UniqueServiceName $uniqueServiceName
     *
     * @return $this
     */
    public function setUniqueServiceName($uniqueServiceName)
    {
        $this->uniqueServiceName = $uniqueServiceName;
        return $this;
    }
}
