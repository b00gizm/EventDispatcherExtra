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
                'type'        => null,
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

        // Make sure the exchange is set up properly
        // (Not quite happy with this yet)
        if (null !== $arguments['exchange']['type']) {
            $this->setExchangeType($arguments['exchange']['type']);
        }
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
        $res = $this->ensureExchange($type);
        if (false === $res) {
            throw new \InvalidArgumentException(sprintf(
                "Error: '%s' is not a valid type.",
                $type
            ));
        }
        $this->arguments['exchange']['type'] = $type;
        $this->setExchangeName($res);
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

    protected function ensureExchange($type)
    {
        if (in_array($type, array('direct', 'fanout', 'topic'))) {
            return $this->getExchangeName() ?: 'amq.'.$type;
        }

        return false;
    }
}
