<?php

namespace CodeNugget\EventDispatcherExtra\Amqp;

use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * The QueueEvent class extends the GenericEvent base class to handle AMQP
 * specific configuration.
 *
 * @author Pascal Cremer <b00gizm@gmail.com>
 **/ 
class QueueEvent extends GenericEvent
{
    /**
     * Constructor
     *
     * @see Symfony\Component\EventDispatcher\GenericEvent::__construct
     **/
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

    /**
     * Convenience method & setter exchange name
     * 
     * @param   string  $name  The exchange name 
     **/
    public function setExchangeName($name)
    {
        $this->arguments['exchange']['name'] = $name;
    }

    /**
     * Convenience method & getter exchange name
     *
     * @return  string  The exchange name
     **/
    public function getExchangeName()
    {
        return $this->arguments['exchange']['name'];
    }

    /**
     * Convenience method & setter exchange type
     * 
     * Also ensures that $type is a valid exchange type and sets
     * a proper exchange name
     * 
     * @param   string  $type  The exchange type
     */
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

    /**
     * Convenience method & getter exchange type
     * 
     * @return  string  The exchange type
     */
    public function getExchangeType()
    {
        return $this->arguments['exchange']['type'];
    }

    /**
     * Convenience method & setter queue name
     * 
     * @param   string  $name  The queue name
     */
    public function setQueueName($name)
    {
        $this->arguments['queue']['name'] = $name;
    }

    /**
     * Convenience method & getter queue name
     * 
     * @return  string  The queue name
     */
    public function getQueueName()
    {
        return $this->arguments['queue']['name'];
    }

    /**
     * Ensures a proper exchange
     * 
     * @param   string  $type  The exchange type
     * 
     * @return  mixed   The exchange name to be used or false if invalid type
     */ 
    protected function ensureExchange($type)
    {
        if (in_array($type, array('direct', 'fanout', 'topic'))) {
            return $this->getExchangeName() ?: 'amq.'.$type;
        }

        return false;
    }
}
