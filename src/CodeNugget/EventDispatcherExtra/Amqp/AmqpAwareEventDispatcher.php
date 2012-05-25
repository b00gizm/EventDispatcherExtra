<?php

namespace CodeNugget\EventDispatcherExtra\Amqp;

use Symfony\Component\EventDispatcher\Event,
    Symfony\Component\EventDispatcher\EventDispatcher;

use PhpAmqpLib\Message\AMQPMessage,
    PhpAmqpLib\Connection\AMQPConnection;

/**
 * The AmqpAwareEventDispatcher adds the functionality to dispatch events
 * to an AMQP message queue (like RabbitMQ) after they have been processed
 * by all connected listeners.
 *
 * @author Pascal Cremer <b00gizm@gmail.com>
 **/
class AmqpAwareEventDispatcher extends EventDispatcher
{
    /**
     * @var AMQPConnection
     */
    protected $conn;

    /**
     * Constructor
     * 
     * @param   AMQPConnection  $conn  An AMQP connection
     */
    public function __construct(AMQPConnection $conn = null)
    {
        if (null === $conn) {
            $conn = new AMQPConnection('localhost', 5672, 'guest', 'guest', '/');
        }
        $this->conn = $conn;
    }

    /**
     * Dispatches and event
     * 
     * @param   string    $eventName  The event name
     * @param   Event     $event      An event object
     * @param   callable  $publisher  A valid PHP callable
     */
    public function dispatch($eventName, Event $event = null, $publisher = null)
    {
        parent::dispatch($eventName, $event);

        if ($event instanceof QueueEvent && !$event->isPropagationStopped()) {
            $event['routing_key'] = $eventName;
            if (null === $publisher) {
                $publisher = array($this, 'publishEvent');
            }
            if (!is_callable($publisher)) {
                throw new \InvalidArgumentException(
                    "The third parameter should either be null or a valid PHP callable"
                );
            }
            call_user_func($publisher, $event, $this->conn);
        }
    }

    /**
     * Setter connection
     * 
     * @param   AMQPConnection  $conn  An AMQP connection
     */
    public function setConnection(AMQPConnection $conn)
    {
        $this->conn = $conn;
    }

    /**
     * Getter connection
     * 
     * @return  AMQPConnection  The AMQP connection
     */
    public function getConnection()
    {
        return $this->conn;
    }

    /**
     * Default publisher
     * 
     * @param   QueueEvent      $event  A QueueEvent
     * @param   AMQPConnection  $conn   An AMQP connection
     */
    protected function publishEvent(QueueEvent $event, AMQPConnection $conn)
    {
        $channel = $conn->channel();

        $channel->queue_declare(
            $event->getQueueName(),
            $event['exchange']['passive'],
            $event['exchange']['durable'],
            $event['exchange']['exclusive'],
            $event['exchange']['auto_delete']
        );

        if (null !== $event->getExchangeName()) {
            // Thou shall not redeclare the default exchanges!
            if (!preg_match('/^amq\./', $event->getExchangeName())) {
                    $channel->exchange_declare(
                    $event->getExchangeName(),
                    $event->getExchangeType(),
                    $event['exchange']['passive'],
                    $event['exchange']['durable'],
                    $event['exchange']['auto_delete']
                );
            }
            $channel->queue_bind(
                $event->getQueueName(),
                $event->getExchangeName()
            );
        }

        $body = null;
        $contentType = "text/plain";
        $subject = $event->getSubject();
        if (is_array($subject)) {
            $body = json_encode($subject);
            $contentType = 'application/json';
        } else if (is_object($subject)) {
            $reflectionClass = new \ReflectionClass($subject);
            $methods = array('toArray', 'asArray');
            foreach ($methods as $method) {
                if ($reflectionClass->hasMethod($method)) {
                    $body = json_encode(call_user_func(array($subject, $method)));
                    $contentType = 'application/json';
                    break;
                }
                $body = serialize($subject);
            } 
        } else {
            $body = strval($subject);
        }

        $message = new AMQPMessage($body, array(
            'content_type'    => $contentType,
            'delivery_method' => 2,
        ));

        $channel->basic_publish($message, $event->getExchangeName(), $event['routing_key']);
        $channel->close();
    }

    /**
     * Destructor
     */
    public function __destruct()
    {
        $this->conn->close();
    }
}
