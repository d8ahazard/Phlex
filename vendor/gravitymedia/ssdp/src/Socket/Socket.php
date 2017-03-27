<?php
/**
 * This file is part of the Ssdp project
 *
 * @author Daniel Schröder <daniel.schroeder@gravitymedia.de>
 */

namespace GravityMedia\Ssdp\Socket;

use GravityMedia\Ssdp\Socket\Exception\SocketException;

/**
 * Socket
 *
 * @package GravityMedia\Ssdp\Socket
 */
class Socket
{
    /**
     * Constructor
     *
     * @param int $domain
     * @param int $type
     * @param int $protocol
     *
     * @throws SocketException
     */
    public function __construct($domain, $type, $protocol)
    {
        $this->socket = socket_create($domain, $type, $protocol);
        if (false === $this->socket) {
            throw new SocketException();
        }
    }

    /**
     * Bind a name to the socket
     *
     * @param string $address
     * @param int $port
     *
     * @throws SocketException
     * @return $this
     */
    public function bind($address, $port = 0)
    {
        if (false === socket_bind($this->socket, $address, $port)) {
            $this->close();
            throw new SocketException();
        }
        return $this;
    }

    /**
     * Close socket
     *
     * @return $this
     */
    public function close()
    {
        if (is_resource($this->socket)) {
            socket_close($this->socket);
        }
        return $this;
    }

    /**
     * Set socket option
     *
     * @param int $level
     * @param int $name
     * @param mixed $value
     *
     * @throws SocketException
     * @return $this
     */
    public function setOption($level, $name, $value)
    {
        if (false === socket_set_option($this->socket, $level, $name, $value)) {
            $this->close();
            throw new SocketException();
        }
        return $this;
    }

    /**
     * Get socket option
     *
     * @param int $level
     * @param int $name
     *
     * @throws SocketException
     * @return mixed
     */
    public function getOption($level, $name)
    {
        $value = socket_get_option($this->socket, $level, $name);
        if (false === $value) {
            $this->close();
            throw new SocketException();
        }
        return $value;
    }

    /**
     * Receives data from the socket, whether it is connected or not
     *
     * @param int $length
     * @param int $flags
     * @param string $name
     * @param int $port
     *
     * @return string
     */
    public function receiveFrom($length, $flags, &$name = '', &$port = null)
    {
        if (false === socket_recvfrom($this->socket, $message, $length, $flags, $name, $port)) {
            // catch timeout
            if (SocketException::EDEADLK !== socket_last_error()) {
                $this->close();
                throw new SocketException();
            }
        }
        return $message;
    }

    /**
     * Send a message to the socket, whether it is connected or not
     *
     * @param string $message
     * @param int $length
     * @param int $flags
     * @param string $address
     * @param int $port
     *
     * @throws SocketException
     * @return $this
     */
    public function sendTo($message, $length, $flags, $address, $port = 0)
    {
        if (false === socket_sendto($this->socket, $message, $length, $flags, $address, $port)) {
            $this->close();
            throw new SocketException();
        }
        return $this;
    }
}
