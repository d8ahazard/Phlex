<?php
/**
 * This file is part of the Ssdp project
 *
 * @author Daniel Schröder <daniel.schroeder@gravitymedia.de>
 */

namespace GravityMedia\Ssdp\Socket\Exception;

/**
 * Socket exception
 *
 * @package GravityMedia\Ssdp\Socket\Exception
 */
class SocketException extends \RuntimeException implements ExceptionInterface
{
    /**
     * Constructor
     *
     * @param int $code
     */
    public function __construct($code = -1)
    {
        if ($code < 0) {
            $code = socket_last_error();
        }
        parent::__construct(socket_strerror($code), $code);
    }
}
