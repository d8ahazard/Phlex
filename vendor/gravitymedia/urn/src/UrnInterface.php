<?php
/**
 * This file is part of the Urn project
 *
 * @author Daniel Schröder <daniel.schroeder@gravitymedia.de>
 */

namespace GravityMedia\Urn;

/**
 * URN interface.
 *
 * @package GravityMedia\Urn
 */
interface UrnInterface
{
    /**
     * Get namespace identifier.
     *
     * @return string
     */
    public function getNamespaceIdentifier();

    /**
     * Get namespace specific string.
     *
     * @return string
     */
    public function getNamespaceSpecificString();

    /**
     * Return an instance with the specified namespace identifier.
     *
     * @param string $nid
     *
     * @return static
     * @throws \InvalidArgumentException
     */
    public function withNamespaceIdentifier($nid);

    /**
     * Return an instance with the specified specific string
     *
     * @param string $nss
     *
     * @return static
     * @throws \InvalidArgumentException
     */
    public function withNamespaceSpecificString($nss);

    /**
     * Return the string representation as a URN reference.
     *
     * @return string
     */
    public function __toString();
}
