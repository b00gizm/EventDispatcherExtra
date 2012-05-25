# EventDispatcherExtra

This package provides some useful additions and tweaks to the Symfony's [EventDispatcher component](http://symfony.com/doc/current/components/event_dispatcher/introduction.html).

## Install

Install of the library and its dependencies via [Composer](http://getcomposer.org/).

    curl -s http://getcomposer.org/installer | php
    php composer.phar update

## RabbitMQ / AMQP additions

The `AmqpAwareEventDispatcher` class inherits from the standard `EventDispatcher` class and adds a thin layer for dispatching events to your [RabbitMQ](http://www.rabbitmq.com/) or any other messaging system based on the [AMQP](http://www.amqp.org/) standard.

If you fire an event of type `QueueEvent` the `AmqpAwareEventDispatcher` will dipatch it to your message queue after all other regular listeners have been processed. Any other event (which don't inherit from `QueueEvent`) won't be dispatched the message queue, so it's fully compatible to the standard event dispatcher. 

### Usage examples

    ```php
    <?php

    use CodeNugget\EventDispatcherExtra\Amqp\QueueEvent,
        CodeNugget\EventDispatcherExtra\Amqp\AmqpAwareEventDispatcher;

    // Create the event
    $event = new QueueEvent('PING!');
    $event->setQueueName('my-queue');

    // Dispatch it
    $dispatcher = new AmqpAwareEventDispatcher();
    $dispatcher->dispatch('my-event', $event);
    ```

This is a pretty basic usage example: It creates a new `QueueEvent` object with a string "PING!" as subject. When it's dispatched by the event dispatcher, it will be send to the AMQP compatible message queue at `localhost` on port `5672`. It will be delivered via a direct exchange to the queue "my-queue".

Of course, you can fully customize your connection parameters and the way how the message will be delivered:

    ```php
    <?php

    use PhpAmqpLib\Connection\AMQPConnection;

    use CodeNugget\EventDispatcherExtra\Amqp\QueueEvent,
        CodeNugget\EventDispatcherExtra\Amqp\AmqpAwareEventDispatcher;

    $conn = new AMQPConnection('rabbit.example.org', 12345, 'user', 's3cr3t', '/');
    $dispatcher = new AmqpAwareEventDispatcher($conn);

    $event = new QueueEvent('PING!', array(
        'exchange' => array(
            'name'        => 'my-exhange',
            'type'        => 'fanout',
            'passive'     => false,
            'exclusive'   => false,
            'durable'     => true,
            'auto_delete' => false,
        ),
        'queue' => array(
            'name'        => 'my-queue',
            'passive'     => false,
            'durable'     => true,
            'exclusive'   => false,
            'auto_delete' => false,
        ),
    ));

    $dispatcher->dispatch('my-event', $event);
    ```

If your subject is anything other than a string, the `AmqpAwareEventDispatcher` will try to serialize it for you before delivering it through the message queue. The following rules apply here:

* If the event subject is from type **array**, it will be sent as [JSON](http://www.json.org/)-encoded string with content type `application/json`
* If the event subject is from type **object**, `AmqpAwareEventDispatcher` searches for the methods `toArray` and `asArray` which would return an array representation of said object. If one of those methods could be found, it will be sent as array (see above)
* If the event subject is from type **object** and does neither have a `toArray` nor `asArray` method, it will be `serialize()`'d and sent with content type `text/plain`
* Any **other type** will be sent as its string value with content type `text/plain`

For example, consider the following simple model/entity class:

    ```php
    <?php

    class User
    {
        private $username;
        private $email;

        public function __construct($username, $email)
        {
            $this->username = $username;
            $this->email    = $email;
        }

        public function toArray()
        {
            return array(
                'username' => $this->username,
                'email'    => $this->email,
            );
        }

        // more methods ...
    }
    ```

Noticed the `toArray` method? Now, we'll use a `User` object as event subject:

    ```php
    <?php

    $user = new User('johndoe', 'john@example.org');
    $event = new QueueEvent($user);

    $dispatcher->dispatch('myapp.user-event', $event);
    ```

This will result in the following serialized message body (content type: `application/json`):

    "{"username":"johndoe","email":"john@example.org"}"

For full control for sending messages, you can use a provide a custom publisher as optional thrid parameter to `AmqpAwareEventDispatcher::dispatch()`. Your custom publisher can be any valid PHP callable, like a closure:

    ```php
    <?php

    $dispatcher->dispatch('myapp.user-event', $event, function($event, $conn) {

        $channel = $conn->channel();

        // You're on your own here ...

        $channel->close();

    });

### Routing keys

At the moment, the event name is used as routing key. Therefore it's recommended to use kind of a [reverse domain name notation](http://en.wikipedia.org/wiki/Reverse_domain_name_notation) style for your event names (which is also a best practice used in Symfony), if you want to use [topic exchanges](http://www.rabbitmq.com/tutorials/tutorial-five-python.html):

    ```
    <?php

    $dispatcher->dispatch('myapp.user.upload.avatar', new QueueEvent($subject));
    ```

If you are using a custom publisher, you can retrieve the routing key by using `$event['routing_key']`.

## Copyright

Copyright (c) 2012 Pascal Cremer (b00gizm). See LICENSE for details.