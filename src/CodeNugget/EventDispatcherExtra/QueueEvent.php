<?php

namespace CodeNugget\EventDispatcherExtra;

use Symfony\Component\EventDispatcher\GenericEvent;

class QueueEvent extends GenericEvent
{
    public function __construct($subject = null, $arguments = array())
    {
        $defaults = array(
            'exchange' => array(
                'name'        => null,
                'type'        => 'direct',
                'passive'     => false,
                'exclusive'   => false,
                'durable'     => false,
                'auto_delete' => true,
            ),
            'queue' => array(
                'name'        => null,
                'passive'     => false,
                'durable'     => false,
                'exclusive'   => false,
                'auto_delete' => true,
            ),
        );

        $arguments = array_replace_recursive($defaults, $arguments);
        parent::__construct($subject, $arguments);
    }

    public function setExchangeName($name)
    {
        $this->arguments['exchange']['name'] = $name;
    }

    public function getExchangeName()
    {
        return $this->arguments['exchange']['name'];
    }

    public function setExchangeType($type)
    {
        $types = array('direct', 'fanout', 'topic');
        if (!in_array($type, $types)) {
            throw new \InvalidArgumentException(sprintf(
                "Error: '%s' is not a valid type. (Available types: %s)",
                $type,
                implode(', ', $types)
            ));
        }
        $this->arguments['exchange']['type'] = $type;
    }

    public function getExchangeType()
    {
        return $this->arguments['exchange']['type'];
    }

    public function setQueueName($name)
    {
        $this->arguments['queue']['name'] = $name;
    }

    public function getQueueName()
    {
        return $this->arguments['queue']['name'];
    }
}