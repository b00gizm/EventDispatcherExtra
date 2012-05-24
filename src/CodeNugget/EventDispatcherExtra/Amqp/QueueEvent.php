<?php

namespace CodeNugget\EventDispatcherExtra\Amqp;

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
        if (!$this->checkExchangeType($arguments['exchange']['type'])) {
            throw new \InvalidArgumentException(sprintf(
                "Error: '%s' is not a valid type.",
                $arguments['exchange']['type']
            ));
        }

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
        if (!$this->checkExchangeType($type)) {
            throw new \InvalidArgumentException(sprintf(
                "Error: '%s' is not a valid type.",
                $type
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

    protected function checkExchangeType($type)
    {
        return in_array($type, array('direct', 'fanout', 'topic'));
    }
}
