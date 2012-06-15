<?php

namespace CodeNugget\Test\EventDispatcherExtra;

use Symfony\Component\EventDispatcher\Event,
    Symfony\Component\EventDispatcher\EventSubscriberInterface;

class SubscriberStub implements EventSubscriberInterface
{
    private $result = false;

    static function getSubscribedEvents()
    {
        return array(
            'my.test.event' => 'doStuff',
        );
    }

    public function doStuff(Event $event)
    {
        $this->result = true;
    }

    public function getResult()
    {
        return $this->result;
    }
}
