<?php

namespace Test\CodeNugget\EventDispatcherExtra\Amqp;

use CodeNugget\EventDispatcherExtra\Amqp\QueueEvent,
    CodeNugget\EventDispatcherExtra\Amqp\AmqpAwareEventDispatcher;

class AmqpAwareEventDispatcherTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->conn = $this->getMockBuilder('PhpAmqpLib\Connection\AMQPConnection')
                           ->disableOriginalConstructor()
                           ->getMock();

        $this->dispatcher = new AmqpAwareEventDispatcher($this->conn);
    }

    public function tearDown()
    {
        unset($this->dispatcher);
        unset($this->conn);
    }

    public function testDispatch()
    {
        $channel = $this->getMockBuilder('PhpAmqpLib\Channel\AMQPChannel')
                        ->disableOriginalConstructor()
                        ->getMock();

        $channel->expects($this->once())
                ->method('basic_publish')
                ->with(
                    $this->isInstanceOf('PhpAmqpLib\Message\AMQPMessage'),
                    $this->equalTo(null),
                    $this->equalTo('my.test.event')
                );

        $channel->expects($this->once())
                ->method('close');

        $this->conn->expects($this->once())
                   ->method('channel')
                   ->will($this->returnValue($channel));

        $this->dispatcher->dispatch('my.test.event', new QueueEvent());
    }

    public function testDispatchWithWrongEventType()
    {
        $this->conn->expects($this->never())
                   ->method('channel');

        $this->dispatcher->dispatch('my.test.event', new \Symfony\Component\EventDispatcher\Event());
    }

    public function testDispatchWithEventPropagationStopped()
    {
        $this->conn->expects($this->never())
                   ->method('channel');

        $evt = $this->getMockBuilder('CodeNugget\EventDispatcherExtra\Amqp\QueueEvent')
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

        $channel = $this->getMockBuilder('PhpAmqpLib\Channel\AMQPChannel')
                        ->disableOriginalConstructor()
                        ->getMock();
 
        $channel->expects($this->once())
                ->method('close');

        $this->conn->expects($this->once())
                   ->method('channel')
                   ->will($this->returnValue($channel));

        $this->dispatcher->dispatch('my.test.event', new QueueEvent(), $publisher);
    }
}