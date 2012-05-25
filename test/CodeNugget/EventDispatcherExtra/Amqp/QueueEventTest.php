<?php

namespace Test\CodeNugget\EventDispatcherExtra\Amqp;

use CodeNugget\EventDispatcherExtra\Amqp\QueueEvent;

class QueueEventTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->evt = new QueueEvent();
    }

    public function tearDown()
    {
        unset($this->evt);
    }

    public function testCreate()
    {
        $evt = new QueueEvent('test', array(
            'exchange' => array(
                'name'    => 'test-exchange',
                'type'    => 'fanout',
                'durable' => true,
            ),
            'queue' => array(
                'name'      => 'test-queue',
                'exclusive' => true,
            ),
        ));

        $this->assertEquals('test-exchange', $evt['exchange']['name']);
        $this->assertEquals('fanout', $evt['exchange']['type']);
        $this->assertTrue($evt['exchange']['durable']);
        $this->assertEquals('test-queue', $evt['queue']['name']);
        $this->assertTrue($evt['queue']['exclusive']);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testCreateWithInvalidExchangeType()
    {
        $evt = new QueueEvent('test', array(
            'exchange' => array('type' => 'foo')
        ));
    }

    public function testSetExchangeName()
    {
        $this->evt->setExchangeName('my-test-exchange');

        $this->assertEquals('my-test-exchange', $this->evt['exchange']['name']);
    }

    public function testSetExchangeType()
    {
        $this->evt->setExchangeType('fanout');

        $this->assertEquals('fanout', $this->evt['exchange']['type']);
        $this->assertEquals('amq.fanout', $this->evt['exchange']['name']);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testSetExchangeTypeWithInvalidType()
    {
        $this->evt->setExchangeType('foo');
    }

    public function testSetQueueName()
    {
        $this->evt->setQueueName('my-test-queue');

        $this->assertEquals('my-test-queue', $this->evt['queue']['name']);
    }
}
