<?php
/**
 * This file is part of the Ssdp project
 *
 * @author Daniel Schröder <daniel.schroeder@gravitymedia.de>
 */

namespace GravityMedia\Ssdp\Message\Response\Search;

use DateTime;
use GravityMedia\Ssdp\SsdpInterface;
use GravityMedia\Ssdp\SearchTarget;
use GravityMedia\Ssdp\UniqueServiceName;
use Guzzle\Http\Url;

/**
 * Abstract search response message
 *
 * @package GravityMedia\Ssdp\Message\Response\Search
 */
abstract class AbstractMessage extends \GravityMedia\Ssdp\Message\AbstractMessage
{
    /**
     * @var int
     */
    protected $lifetime;

    /**
     * @var DateTime
     */
    protected $date;

    /**
     * @var Url
     */
    protected $descriptionUrl;

    /**
     * @var string
     */
    protected $serverString;

    /**
     * @var SearchTarget
     */
    protected $searchTarget;

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
     * Get date
     *
     * @return DateTime
     */
    public function getDate()
    {
        if (is_null($this->date)) {
            return new DateTime();
        }
        return $this->date;
    }

    /**
     * Set date
     *
     * @param DateTime $date
     *
     * @return $this
     */
    public function setDate($date)
    {
        $this->date = $date;
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
     * Get server string
     *
     * @return string
     */
    public function getServerString()
    {
        if (is_null($this->serverString)) {
            return sprintf(
                '%s/%s UPnP/1.0 %s/%s',
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
     * Get search target
     *
     * @return SearchTarget
     */
    public function getSearchTarget()
    {
        if (is_null($this->searchTarget)) {
            return SearchTarget::fromString();
        }
        return $this->searchTarget;
    }

    /**
     * Set search target
     *
     * @param SearchTarget $searchTarget
     *
     * @return $this
     */
    public function setSearchTarget(SearchTarget $searchTarget)
    {
        $this->searchTarget = $searchTarget;
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
