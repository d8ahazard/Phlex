<?php
/**
 * This file is part of the Ssdp project
 *
 * @author Daniel Schröder <daniel.schroeder@gravitymedia.de>
 */

namespace GravityMedia\Ssdp;

use Exception;
use GravityMedia\Ssdp\Message\RequestInterface;
use GravityMedia\Ssdp\Message\ResponseInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Message aware event
 *
 * @package GravityMedia\Ssdp\Event
 */
class SsdpEvent extends Event
{
    const ALIVE = 'sspd:alive';
    const BYEBYE = 'sspd:byebye';
    const DISCOVER = 'sspd:discover';
    const EXCEPTION = 'sspd:exception';
    const UPDATE = 'sspd:update';

    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var ResponseInterface
     */
    protected $response;

    /**
     * @var Exception
     */
    protected $exception;

    /**
     * Get request
     *
     * @return RequestInterface
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * Get request
     *
     * @param RequestInterface $request
     *
     * @return $this
     */
    public function setRequest(RequestInterface $request)
    {
        $this->request = $request;
        return $this;
    }

    /**
     * Get response
     *
     * @return ResponseInterface
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * Set response
     *
     * @param ResponseInterface $response
     *
     * @return $this
     */
    public function setResponse(ResponseInterface $response)
    {
        $this->response = $response;
        return $this;
    }

    /**
     * Get exception
     *
     * @return Exception
     */
    public function getException()
    {
        return $this->exception;
    }

    /**
     * Set exception
     *
     * @param Exception $exception
     *
     * @return $this
     */
    public function setException(Exception $exception)
    {
        $this->exception = $exception;
        return $this;
    }
}
