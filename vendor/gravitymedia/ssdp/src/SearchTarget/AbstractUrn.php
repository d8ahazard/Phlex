<?php
/**
 * This file is part of the Ssdp project
 *
 * @author Daniel Schröder <daniel.schroeder@gravitymedia.de>
 */

namespace GravityMedia\Ssdp\SearchTarget;

use GravityMedia\Urn\Urn;
use InvalidArgumentException;

/**
 * Abstract search target URN
 *
 * @package GravityMedia\Ssdp\SearchTarget
 */
abstract class AbstractUrn extends Urn
{
    const DEFAULT_NAMESPACE_IDENTIFIER = 'schemas-upnp-org';

    /**
     * @var string
     */
    protected $type;

    /**
     * @var int
     */
    protected $version;

    /**
     * @inheritdoc
     */
    public function getNamespaceIdentifier()
    {
        if (is_null($this->namespaceIdentifier)) {
            return self::DEFAULT_NAMESPACE_IDENTIFIER;
        }
        return parent::getNamespaceIdentifier();
    }

    /**
     * @inheritdoc
     */
    public function getNamespaceSpecificString()
    {
        return sprintf(
            '%s:%s:%d',
            $this->getCategory(),
            $this->getType(),
            $this->getVersion()
        );
    }

    /**
     * @inheritdoc
     */
    public function setNamespaceSpecificString($namespaceSpecificString)
    {
        parent::setNamespaceSpecificString($namespaceSpecificString);
        $tuple = explode(':', parent::getNamespaceSpecificString(), 3);
        $category = array_shift($tuple);
        if (2 !== count($tuple) || $category !== $this->getCategory()) {
            throw new InvalidArgumentException(
                sprintf('Invalid namespace specific string "%s"', $namespaceSpecificString)
            );
        }
        $this->setType(array_shift($tuple));
        $this->setVersion(intval(array_pop($tuple)));
        return $this;
    }

    /**
     * Get category
     *
     * @return string
     */
    abstract public function getCategory();

    /**
     * Get type
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set type
     *
     * @param string $type
     *
     * @return $this
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * Get version
     *
     * @return int
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * Set version
     *
     * @param int $version
     *
     * @return $this
     */
    public function setVersion($version)
    {
        $this->version = $version;
        return $this;
    }
}
