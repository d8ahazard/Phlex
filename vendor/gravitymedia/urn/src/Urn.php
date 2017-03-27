<?php
/**
 * This file is part of the Urn project
 *
 * @author Daniel Schröder <daniel.schroeder@gravitymedia.de>
 */

namespace GravityMedia\Urn;

/**
 * URN class.
 *
 * @package GravityMedia\Urn
 */
class Urn implements UrnInterface
{
    /**
     * Namespace identifier regex pattern.
     *
     * @const string
     */
    const NID_PATTERN = '/^[A-Za-z0-9-][A-Za-z0-9-]{0,31}$/';

    /**
     * Namespace specific string regex pattern.
     */
    const NSS_PATTERN = '/^[A-Za-z0-9()+,\-.:=@;$_!*\'%\/?#]+$/';

    /**
     * The namespace identifier.
     *
     * @var string
     */
    protected $namespaceIdentifier;

    /**
     * The namespace specific string.
     *
     * @var string
     */
    protected $namespaceSpecificString;

    /**
     * The protected constructor.
     */
    protected function __construct()
    {
        // to prevent object construction without nid and/or nss
    }

    /**
     * Create URN object from array.
     *
     * @param array $array
     *
     * @return static
     * @throws \InvalidArgumentException
     */
    public static function fromArray(array $array)
    {
        if (!isset($array['nid'])) {
            throw new \InvalidArgumentException('The namespace identifier was not specified');
        }

        if (!isset($array['nss'])) {
            throw new \InvalidArgumentException('The namespace specific string was not specified');
        }

        /** @var Urn $urn */
        $urn = new static();
        $urn = $urn->withNamespaceIdentifier($array['nid']);
        $urn = $urn->withNamespaceSpecificString($array['nss']);

        return $urn;
    }

    /**
     * Create URN object from string.
     *
     * @param string $string
     *
     * @return static
     * @throws \InvalidArgumentException
     */
    public static function fromString($string)
    {
        $array = explode(':', $string, 3);
        if (3 !== count($array) || 'urn' !== strtolower($array[0])) {
            throw new \InvalidArgumentException('The string argument appears to be malformed');
        }

        if (1 !== preg_match(static::NID_PATTERN, $array[1])) {
            throw new \InvalidArgumentException('The string does not contain a valid namespace identifier');
        }

        if (1 !== preg_match(static::NSS_PATTERN, $array[2])) {
            throw new \InvalidArgumentException('The string does not contain a valid namespace specific string');
        }

        $array['nid'] = $array[1];
        $array['nss'] = $array[2];

        return static::fromArray($array);
    }

    /**
     * {@inheritdoc}
     */
    public function getNamespaceIdentifier()
    {
        return $this->namespaceIdentifier;
    }

    /**
     * {@inheritdoc}
     */
    public function getNamespaceSpecificString()
    {
        return $this->namespaceSpecificString;
    }

    /**
     * {@inheritdoc}
     */
    public function withNamespaceIdentifier($nid)
    {
        if (1 !== preg_match(static::NID_PATTERN, $nid)) {
            throw new \InvalidArgumentException('The string does not contain a valid namespace identifier');
        }

        $urn = clone $this;
        $urn->namespaceIdentifier = (string)$nid;

        return $urn;
    }

    /**
     * {@inheritdoc}
     */
    public function withNamespaceSpecificString($nss)
    {
        if (1 !== preg_match(static::NSS_PATTERN, $nss)) {
            throw new \InvalidArgumentException('The string does not contain a valid namespace specific string');
        }

        $urn = clone $this;
        $urn->namespaceSpecificString = (string)$nss;

        return $urn;
    }

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        return $this->toString();
    }

    /**
     * Convert URN into a string representation.
     *
     * @return string
     */
    public function toString()
    {
        $urn = 'urn';
        $urn .= ':' . $this->getNamespaceIdentifier();
        $urn .= ':' . $this->getNamespaceSpecificString();

        return $urn;
    }
}
