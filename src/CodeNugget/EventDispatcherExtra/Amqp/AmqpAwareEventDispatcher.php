<?php

namespace CodeNugget\EventDispatcherExtra\Amqp;

use Symfony\Component\EventDispatcher\Event,
    Symfony\Component\EventDispatcher\EventDispatcher;

use PhpAmqpLib\Message\AMQPMessage,
    PhpAmqpLib\Connection\AMQPConnection;

class AmqpAwareEventDispatcher extends EventDispatcher
{
    protected $conn;

    public function __construct(AMQPConnection $conn = null)
    {
        if (null === $conn) {
            $conn = new AMQPConnection('localhost', 5672, 'guest', 'guest', '/');
        }
        $this->conn = $conn;
    }

    public function setConnection(AMQPConnection $conn)
    {
        $this->conn = $conn;
    }

    public function getConnection()
    {
        return $this->conn;
    }

    public function dispatch($eventName, Event $event = null, $publisher = null)
    {
        parent::dispatch($eventName, $event);

        if ($event instanceof QueueEvent && !$event->isPropagationStopped()) {
            $event['routing_key'] = $eventName;
            if (null !== $publisher) {
                if (!is_callable($publisher)) {
                    throw new \InvalidArgumentException(
                        "'publisher' show be either null or a valid callable"
                    );
                }
                call_user_func($publisher, $event, $this->conn);

                return;
            }

            $this->publishEvent($event);
        }
    }

    protected function publishEvent(QueueEvent $event)
    {
        $channel = $this->conn->channel();

        $channel->exchange_declare(
            $event->getExchangeName(),
            $event->getExchangeType(),
            $event['exchange']['passive'],
            $event['exchange']['durable'],
            $event['exchange']['auto_delete']
        );

        $channel->queue_declare(
            $event->getQueueName(),
            $event['exchange']['passive'],
            $event['exchange']['durable'],
            $event['exchange']['exclusive'],
            $event['exchange']['auto_delete']
        );

        $channel->queue_bind($event->getQueueName(), $event->getExchangeName());

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
                    continue;
                }
                $body = serialize($subject);
            } 
        } else {
            $body = strval($subject);
        }

        $routingKey = $event->getExchangeType() == 'direct' ? '' : $event['routing_key'];
        $message = new AMQPMessage($body, array(
            'content_type'    => $contentType,
            'delivery_method' => 2,
        ));
        $channel->basic_publish($message, $event->getExchangeName(), $routingKey);
        $channel->close();
    }

    public function __destruct()
    {
        $this->conn->close();
    }
}
