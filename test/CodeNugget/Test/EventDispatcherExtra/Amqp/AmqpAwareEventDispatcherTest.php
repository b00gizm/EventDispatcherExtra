<?php

namespace CodeNugget\Test\EventDispatcherExtra\Amqp;

use CodeNugget\EventDispatcherExtra\Amqp\QueueEvent,
    CodeNugget\EventDispatcherExtra\Amqp\AmqpAwareEventDispatcher;

use CodeNugget\Test\EventDispatcherExtra\SubscriberStub;

use Symfony\Component\EventDispatcher\Event as StandardEvent;

class AmqpAwareEventDispatcherTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->conn = $this
            ->getMockBuilder('PhpAmqpLib\Connection\AMQPConnection')
            ->disableOriginalConstructor()
            ->getMock();

        $this->dispatcher = new AmqpAwareEventDispatcher($this->conn);
    }

    public function tearDown()
    {
        unset($this->dispatcher);
        unset($this->subscriber);
        unset($this->conn);
    }

    public function testDispatch()
    {
        $channel = $this
            ->getMockBuilder('PhpAmqpLib\Channel\AMQPChannel')
            ->disableOriginalConstructor()
            ->getMock();

        $channel
            ->expects($this->once())
            ->method('basic_publish')
            ->with(
                $this->isInstanceOf('PhpAmqpLib\Message\AMQPMessage'),
                $this->equalTo(null),
                $this->equalTo('my.test.event')
            );

        $channel
            ->expects($this->once())
            ->method('close');

        $this->conn
            ->expects($this->once())
            ->method('channel')
            ->will($this->returnValue($channel));

        $result = false;
        $listener = function() use (&$result) {
            $result = true;
        };

        $subscriber = new SubscriberStub();
        $this->dispatcher->addSubscriber($subscriber);
        $this->dispatcher->addListener('my.test.event', $listener);
        $this->dispatcher->dispatch('my.test.event', new QueueEvent());

        $this->assertTrue($subscriber->getResult());
        $this->assertTrue($result);
    }

    public function testDispatchWithWrongEventType()
    {
        $this->conn
            ->expects($this->never())
            ->method('channel');

        $result = false;
        $listener = function() use (&$result) {
            $result = true;
        };

        $subscriber = new SubscriberStub();
        $this->dispatcher->addSubscriber($subscriber);
        $this->dispatcher->addListener('my.test.event', $listener);
        $this->dispatcher->dispatch('my.test.event', new StandardEvent());

        $this->assertTrue($subscriber->getResult());
        $this->assertTrue($result);
    }

    public function testDispatchWithEventPropagationStopped()
    {
        $this->conn
            ->expects($this->never())
            ->method('channel');

        $evt = $this
            ->getMockBuilder('CodeNugget\EventDispatcherExtra\Amqp\QueueEvent')
            ->disableOriginalConstructor()
            ->getMock();

        $evt->expects($this->once())
            ->method('isPropagationStopped')
            ->will($this->returnValue(true));

        $this->dispatcher->dispatch('my.test.event', $evt);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testDispatchWithInvalidPublisher()
    {
        $this->dispatcher->dispatch('my.failing.event', new QueueEvent(), 'this-will-fail');
    }

    public function testDispatchWithClosureAsCustomPublisher()
    {
        $publisher = function($event, $conn) {
            $channel = $conn->channel();
            $channel->close();
        };

        $channel = $this
            ->getMockBuilder('PhpAmqpLib\Channel\AMQPChannel')
            ->disableOriginalConstructor()
            ->getMock();
 
        $channel
            ->expects($this->once())
            ->method('close');

        $this->conn
            ->expects($this->once())
            ->method('channel')
            ->will($this->returnValue($channel));

        $this->dispatcher->dispatch('my.test.event', new QueueEvent(), $publisher);
    }
}
