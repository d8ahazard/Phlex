<?php
/**
 * This file is part of the Ssdp project
 *
 * @author Daniel Schröder <daniel.schroeder@gravitymedia.de>
 */

namespace GravityMedia\Ssdp\Message\Request\Search;

use GravityMedia\Ssdp\SearchTarget;
use GravityMedia\Ssdp\SsdpInterface;

/**
 * Abstract search request message
 *
 * @package GravityMedia\Ssdp\Message\Request\Search
 */
abstract class AbstractMessage extends \GravityMedia\Ssdp\Message\AbstractMessage
{
    /**
     * @var int
     */
    protected $maximumWaitTime;

    /**
     * @var SearchTarget
     */
    protected $searchTarget;

    /**
     * @var string
     */
    protected $userAgentString;

    /**
     * Get maximum wait time
     *
     * @return int
     */
    public function getMaximumWaitTime()
    {
        if (is_null($this->maximumWaitTime)) {
            return SsdpInterface::DEFAULT_MAXIMUM_WAIT_TIME;
        }
        return $this->maximumWaitTime;
    }

    /**
     * Set maximum wait time
     *
     * @param int $maximumWaitTime
     *
     * @return $this
     */
    public function setMaximumWaitTime($maximumWaitTime)
    {
        $this->maximumWaitTime = $maximumWaitTime;
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
     * Get user agent string
     *
     * @return string
     */
    public function getUserAgentString()
    {
        if (is_null($this->userAgentString)) {
            return sprintf(
                '%s/%s UPnP/1.1 %s/%s',
                PHP_OS,
                php_uname('r'),
                SsdpInterface::NAME,
                SsdpInterface::VERSION
            );
        }
        return $this->userAgentString;
    }

    /**
     * Set user agent string
     *
     * @param string $userAgentString
     *
     * @return $this
     */
    public function setUserAgentString($userAgentString)
    {
        $this->userAgentString = $userAgentString;
        return $this;
    }
}
