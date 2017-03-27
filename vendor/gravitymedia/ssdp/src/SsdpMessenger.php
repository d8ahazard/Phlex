<?php
/**
 * This file is part of the Ssdp project
 *
 * @author Daniel Schröder <daniel.schroeder@gravitymedia.de>
 */

namespace GravityMedia\Ssdp;

use Exception;
use GravityMedia\Ssdp\Message\Request\Advertisement\Alive as AliveRequest;
use GravityMedia\Ssdp\Message\Request\Advertisement\Byebye as ByebyeRequest;
use GravityMedia\Ssdp\Message\Request\Advertisement\Update as UpdateRequest;
use GravityMedia\Ssdp\Message\Request\Search\Discover as DiscoverRequest;
use GravityMedia\Ssdp\Message\Response\Search\Discover as DiscoverResponse;
use GravityMedia\Ssdp\Socket\Socket;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * SSDP messenger
 *
 * @package GravityMedia\Ssdp
 */
class SsdpMessenger
{
    /**
     * @var EventDispatcher
     */
    protected $eventDispatcher;

    /**
     * Constructor
     *
     * @param null|EventDispatcher $eventDispatcher
     */
    public function __construct(EventDispatcher $eventDispatcher = null)
    {
        if (is_null($eventDispatcher)) {
            $eventDispatcher = new EventDispatcher();
        }
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * Get event dispatcher
     *
     * @return EventDispatcher
     */
    public function getEventDispatcher()
    {
        return $this->eventDispatcher;
    }

    /**
     * Set event dispatcher
     *
     * @param EventDispatcher $eventDispatcher
     *
     * @return $this
     */
    public function setEventDispatcher($eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
        return $this;
    }

    /**
     * Get known notification types
     *
     * @return NotificationType[]
     */
    protected function getKnownNotificationTypes()
    {
        return array(
            NotificationType::fromString(), // default notification type
            NotificationType::fromString('urn:schemas-upnp-org:device:MediaServer:1'),
            NotificationType::fromString('urn:schemas-upnp-org:service:ContentDirectory:1'),
            NotificationType::fromString('urn:schemas-upnp-org:service:ConnectionManager:1')
        );
    }

    /**
     * Prepare socket for broadcast
     *
     * @param Socket $socket
     *
     * @return Socket
     */
    protected function prepareSocketForBroadcast(Socket $socket)
    {
        return $socket
            ->setOption(SOL_SOCKET, SO_BROADCAST, 1);
    }

    /**
     * Prepare socket for notification
     *
     * @param Socket $socket
     *
     * @return Socket
     */
    protected function prepareSocketForNotification(Socket $socket)
    {
        return $this->prepareSocketForBroadcast($socket)
            ->setOption(IPPROTO_IP, IP_MULTICAST_IF, 0)
            ->setOption(IPPROTO_IP, IP_MULTICAST_LOOP, 0)
            ->setOption(IPPROTO_IP, IP_MULTICAST_TTL, 4)
            ->setOption(
                IPPROTO_IP,
                MCAST_JOIN_GROUP,
                array('group' => SsdpInterface::MULTICAST_ADDRESS, 'interface' => 0)
            )
            ->bind('0.0.0.0');
    }

    /**
     * Alive notification request
     *
     * @param null|AliveRequest $request
     *
     * @return $this
     */
    public function alive(AliveRequest $request = null)
    {
        if (is_null($request)) {
            $request = new AliveRequest();
        }
        $socket = $this->prepareSocketForNotification(new Socket(AF_INET, SOCK_DGRAM, SOL_UDP));

        foreach ($this->getKnownNotificationTypes() as $notificationType) {
            $request->setNotificationType($notificationType);
            $socket->sendTo($request, strlen($request), 0, SsdpInterface::MULTICAST_ADDRESS, SsdpInterface::PORT);

            $event = new SsdpEvent();
            $event->setRequest($request);
            $this->getEventDispatcher()->dispatch(SsdpEvent::ALIVE, $event);
        }

        $socket->close();

        return $this;
    }

    /**
     * Byebye notification request
     *
     * @param null|ByebyeRequest $request
     *
     * @return $this
     */
    public function byebye(ByebyeRequest $request = null)
    {
        if (is_null($request)) {
            $request = new ByebyeRequest();
        }
        $socket = $this->prepareSocketForNotification(new Socket(AF_INET, SOCK_DGRAM, SOL_UDP));

        foreach ($this->getKnownNotificationTypes() as $notificationType) {
            $request->setNotificationType($notificationType);
            $socket->sendTo($request, strlen($request), 0, SsdpInterface::MULTICAST_ADDRESS, SsdpInterface::PORT);

            $event = new SsdpEvent();
            $event->setRequest($request);
            $this->getEventDispatcher()->dispatch(SsdpEvent::BYEBYE, $event);
        }

        $socket->close();

        return $this;
    }

    /**
     * Discover search request
     *
     * @param null|DiscoverRequest $request
     * @param int $timeout Timeout in seconds
     *
     * @return $this
     */
    public function discover(DiscoverRequest $request = null, $timeout = 5)
    {
        if (is_null($request)) {
            $request = new DiscoverRequest();
        }
        $socket = $this->prepareSocketForBroadcast(new Socket(AF_INET, SOCK_DGRAM, SOL_UDP))
            ->sendTo($request, strlen($request), 0, SsdpInterface::MULTICAST_ADDRESS, SsdpInterface::PORT)
            ->setOption(SOL_SOCKET, SO_RCVTIMEO, array('sec' => $timeout, 'usec' => 0));

        while (null !== ($buffer = $socket->receiveFrom(1024, MSG_WAITALL))) {
            $event = new SsdpEvent();
            $event->setRequest($request);
            try {
                $event->setResponse(DiscoverResponse::fromString($buffer));
                $this->getEventDispatcher()->dispatch(SsdpEvent::DISCOVER, $event);
            } catch (Exception $exception) {
                $event->setException($exception);
                $this->getEventDispatcher()->dispatch(SsdpEvent::EXCEPTION, $event);
            }
        }

        $socket->close();

        return $this;
    }

    /**
     * Update notification request
     *
     * @param null|UpdateRequest $request
     *
     * @return $this
     */
    public function update(UpdateRequest $request = null)
    {
        if (is_null($request)) {
            $request = new UpdateRequest();
        }
        $socket = $this->prepareSocketForNotification(new Socket(AF_INET, SOCK_DGRAM, SOL_UDP));

        foreach ($this->getKnownNotificationTypes() as $notificationType) {
            $request->setNotificationType($notificationType);
            $socket->sendTo($request, strlen($request), 0, SsdpInterface::MULTICAST_ADDRESS, SsdpInterface::PORT);

            $event = new SsdpEvent();
            $event->setRequest($request);
            $this->getEventDispatcher()->dispatch(SsdpEvent::UPDATE, $event);
        }

        $socket->close();

        return $this;
    }
}
