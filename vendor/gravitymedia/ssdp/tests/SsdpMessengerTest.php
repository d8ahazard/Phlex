<?php
/**
 * This file is part of the Ssdp project
 *
 * @author Daniel Schröder <daniel.schroeder@gravitymedia.de>
 */

namespace GravityMedia\SsdpTest;

use GravityMedia\Ssdp\SsdpEvent;
use GravityMedia\Ssdp\SsdpMessenger;
use PHPUnit_Framework_Assert as Assert;

/**
 * SSDP messenger test
 *
 * @package GravityMedia\SsdpTest
 */
class SsdpMessengerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers \GravityMedia\Ssdp\SsdpMessenger::alive()
     */
    public function testAlive()
    {
        $eventDispatcherMock = $this->getMock('Symfony\Component\EventDispatcher\EventDispatcher');
        $eventDispatcherMock
            ->expects($this->atLeastOnce())
            ->method('dispatch')
            ->with($this->equalTo(SsdpEvent::ALIVE), $this->isInstanceOf('GravityMedia\Ssdp\SsdpEvent'));

        /** @var \Symfony\Component\EventDispatcher\EventDispatcher $eventDispatcherMock */
        $ssdpMessenger = new SsdpMessenger($eventDispatcherMock);
        $ssdpMessenger->alive();
    }

    /**
     * @covers \GravityMedia\Ssdp\SsdpMessenger::byebye()
     */
    public function testByebye()
    {
        $eventDispatcherMock = $this->getMock('Symfony\Component\EventDispatcher\EventDispatcher');
        $eventDispatcherMock
            ->expects($this->atLeastOnce())
            ->method('dispatch')
            ->with($this->equalTo(SsdpEvent::BYEBYE), $this->isInstanceOf('GravityMedia\Ssdp\SsdpEvent'));

        /** @var \Symfony\Component\EventDispatcher\EventDispatcher $eventDispatcherMock */
        $ssdpMessenger = new SsdpMessenger($eventDispatcherMock);
        $ssdpMessenger->byebye();
    }

    /**
     * @covers \GravityMedia\Ssdp\SsdpMessenger::discover()
     */
    public function testDiscover()
    {
        $eventDispatcherMock = $this->getMock('Symfony\Component\EventDispatcher\EventDispatcher');
        $eventDispatcherMock
            ->expects($this->atLeastOnce())
            ->method('dispatch')
            ->with(
                Assert::logicalOr($this->equalTo(SsdpEvent::DISCOVER), $this->equalTo(SsdpEvent::EXCEPTION)),
                $this->isInstanceOf('GravityMedia\Ssdp\SsdpEvent')
            );

        /** @var \Symfony\Component\EventDispatcher\EventDispatcher $eventDispatcherMock */
        $ssdpMessenger = new SsdpMessenger($eventDispatcherMock);
        $ssdpMessenger->discover();
    }

    /**
     * @covers \GravityMedia\Ssdp\SsdpMessenger::update()
     */
    public function testUpdate()
    {
        $eventDispatcherMock = $this->getMock('Symfony\Component\EventDispatcher\EventDispatcher');
        $eventDispatcherMock
            ->expects($this->atLeastOnce())
            ->method('dispatch')
            ->with($this->equalTo(SsdpEvent::UPDATE), $this->isInstanceOf('GravityMedia\Ssdp\SsdpEvent'));

        /** @var \Symfony\Component\EventDispatcher\EventDispatcher $eventDispatcherMock */
        $ssdpMessenger = new SsdpMessenger($eventDispatcherMock);
        $ssdpMessenger->update();
    }
}
