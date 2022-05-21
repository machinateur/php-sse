# php-sse

This package implements Server-sent events in PHP, in a framework-agnostic, low-dependency and object-oriented way.

## Prerequisites

* [PHP 5.6](https://www.php.net/downloads.php)
* [Composer](https://getcomposer.org/download/)

Yes, you've read that right, PHP 5.6 is the minimum version requirement to be able to use this package. That way, even
legacy projects can provide SSE support to client applications.

It might also be a good idea to gobble up all information
on [SSE](https://developer.mozilla.org/en-US/docs/Web/API/Server-sent_events) from the MDN web docs to get yourself up
to speed on the technology. I found it to be an excellent starting point on this topic along with low-level examples.

## Installation

Via composer:

```bash
# install the latest version
composer require "machinateur/php-sse"
```

## Usage

The following is a simple use-case in plain PHP. For a full example (with client code), have a look around
the [demo](/demo) directory.

```php
<?php

namespace App;

use Machinateur\SSE\MessageStream;
use Machinateur\SSE\Exception\TimeoutException;

// ...

$stream = new MessageStream();

// ...

// Count to 10, then quit at 5.
$stream->run(function () {
    foreach (\range(1, 10, 1) as $i) {
        if ($i > 5) {
            throw TimeoutException::toTriggerStreamShutdown();
        }

        yield $i;

        \sleep(1);
    }
});
```

Make sure to read about [when](#when-to-use-this-package) and [why](#why-use-this-package) to use this package, to make
sure it fits your use-case.

Below are additional explanations of common use-case scenarios.

### Recommended headers

Recommended headers can be retrieved from the default implementation of `\Machinateur\SSE\MessageStreamInterface`.

```php
$recommendedHeaders = \Machinateur\SSE\MessageStream::getRecommendedHeaders();
```

### Logger support

A custom logger can be set using the setter.

```php
$stream->setLogger($myLogger);
```

### Custom message stream

It's easily possible to create a custom message stream by implementing the `\Machinateur\SSE\MessageStreamInterface`
interface.

```php
<?php

namespace App;

use Machinateur\SSE\Exception\TimeoutException;
use Machinateur\SSE\MessageStream;
use Machinateur\SSE\MessageStreamInterface;
use Machinateur\SSE\Message\MessageInterface;

/**
 * A naive implementation of {@see MessageStreamInterface}.
 */
class CustomMessageStream extends MessageStream implements MessageStreamInterface
{
    /**
     * @inheritDoc
     */
    public function run(callable $callback)
    {
        try {
            foreach ($callback() as $message) {
                \assert($message instanceof MessageInterface);

                $this->printOutput($message->getMessageFormat());
                $this->checkConnection();
            }
        } catch (TimeoutException $exception) {
        }
    }
}

// ...

$stream = new CustomMessageStream();

```

When implementing a custom message stream, bear in mind the requirement to support array result and generator function,
as imposed by the interface.

> ```
> Any implementation must support passing a callback function with array result or a generator function in its
>  stead. In the latter case, a {@see \Machinateur\SSE\Exception\TimeoutException} throw must shut down the
>  stream (due to time-out). A proper client implementation will resume the connection after its `retry` period.
> ```

### Custom messages

It's also possible to create custom messages by simply implementing the `\Machinateur\SSE\Message\MessageInterface`
interface.

```php
<?php

namespace App;

use Machinateur\SSE\Format\StreamFormat;
use Machinateur\SSE\Message\MessageInterface;

/**
 * Custom message to be yielded by a generator function. It holds a data array, which is given to `json_encode()`
 *  when sent by the message stream.
 */
class CustomMessage implements MessageInterface
{
    const FLAGS = \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE | \JSON_PRESERVE_ZERO_FRACTION;

    /** @var array */
    private $data = array();
    
    /**
     * @param array $data
     */
    public function setData($data)
    {
        $this->data = $data;
    }

    /**
     * @inheritDoc
     */
    public function getStreamFormat()
    {
        return [
            StreamFormat::FIELD_COMMENT => 'source: ' . self::class,
            StreamFormat::FIELD_DATA => \json_encode($this->data, self::FLAGS)
        ];
    }
}

```

### Array vs generator

The callback function, which is passed to the `\Machinateur\SSE\MessageStreamInterface::run()` method, may return an
array or be itself a [generator function](https://www.php.net/manual/en/language.generators.overview.php). That
freedom of implementation naturally raises the question of werther to use a generator function or not.

It really depends on your use-case, I'd say. For polling situations on the server-side (e.g. consume a message queue)
the obvious choice would be the latter, since collecting a bunch of messages and returning them all at once, would make
the use of SSE redundant. Why then event support an array return value than? Supporting both could ease the integration
and adoption as well as making testing somewhat easier.

It deserves to be mentioned the `\Machinateur\SSE\MessageStream` (a simple but sufficient implementation of the
interface), internally converts any array return value to a generator anyway.

### Framework integration

The library does not automatically integrate with frameworks on purpose, to keep it simple. That does not mean it's
impossible to use it with a framework.

Using `\Symfony\Component\HttpFoundation\Response` ([symfony](https://symfony.com/doc/current/components/http_foundation.html#response))
or `\Illuminate\Http\Response` ([laravel](https://laravel.com/docs/9.x/responses#response-objects)) is pretty
straightforward.

- Example #1: `\Symfony\Component\HttpFoundation\Response`

```php
use Machinateur\SSE\MessageStream;

// ...

$headers = array();

foreach (MessageStream::getRecommendedHeaders() as $header) {
    list($key, $value) = \explode(':', $header, 2);
    $headers[$key] = $value;
}

$response->headers->add($headers);
```

- Example #2: `\Illuminate\Http\Response` (extends `\Symfony\Component\HttpFoundation\Response`)

```php
use Machinateur\SSE\MessageStream;

// ...

$headers = array();

foreach (MessageStream::getRecommendedHeaders() as $header) {
    list($key, $value) = \explode(':', $header, 2);
    $headers[$key] = $value;
}

$response->withHeaders($headers);
// or
$response->headers->add($headers);
```

### Usage with other SSE implementations

This library was inspired and influenced by [`hhxsv5/php-sse`](https://github.com/hhxsv5/php-sse), so here is how to
achieve interoperability between the two. This example is based
on [the existing php-fpm example from `hhxsv5/php-sse`](https://github.com/hhxsv5/php-sse#php-demo).

```php
<?php

namespace App;

use Hhxsv5\SSE\Event;
use Hhxsv5\SSE\StopSSEException;
use Machinateur\SSE\Exception\TimeoutException;
use Machinateur\SSE\Format\StreamFormat;
use Machinateur\SSE\MessageStream;

foreach (MessageStream::getRecommendedHeaders() as $header) {
    \header($header);
}

// The callback for `hhxsv5/php-sse`.
$callback = function () {
    $id = \mt_rand(1, 1000);

    // Get news from database or service.
    $news = [
        [
            'id' => $id,
            'title' => 'title ' . $id,
            'content' => 'content ' . $id,
        ],
    ];

    // Stop here when no news available.
    if (empty($news)) {
        return false;
    }
    
    // In case something went wrong.
    $shouldStop = false;
    if ($shouldStop) {
        throw new StopSSEException();
    }
    
    return \json_encode(\compact('news'));
    // return ['event' => 'ping', 'data' => 'ping data'];
    // return ['id' => uniqid(), 'data' => json_encode(compact('news'))];
};

$event = new Event($callback, 'news');
unset($callback);

/**
 * Wrapper for better access to protected fields of `\Hhxsv5\SSE\Event`.
 * 
 * @property string $id
 * @property string $event
 * @property string $data
 * @property string $retry
 * @property string $comment
 */
class EventWrapper extends Event
{
    /** @var Event */
    protected $eventObject;

    public function __construct(Event $event)
    {
        $this->eventObject = $event;
    }

    /**
     * @inheritDoc
     */
    public function __get($name)
    {
        if (\in_array($name, ['id', 'event', 'data', 'retry', 'comment']) && \property_exists($this, $name)) {
            return $this->eventObject->{$name};
        }

        throw new LogicException(\sprintf('Unknown property: %s', $name));
    }

    /**
     * @inheritDoc
     */
    public function fill()
    {
        $this->eventObject->fill();
    }

    public function __toString()
    {
        return $this->eventObject->__toString();
    }
}

$event = new EventWrapper($event);

// The callback for `machinateur/php-sse`.
$callback = function () use ($event) {
    try {
        $event->fill();
        yield [
            StreamFormat::FIELD_COMMENT => $event->comment;
            StreamFormat::FIELD_ID => $event->id;
            StreamFormat::FIELD_RETRY => $event->retry;
            StreamFormat::FIELD_EVENT => $event->event;
            StreamFormat::FIELD_DATA => $event->data;
        ];
    } catch (StopSSEException $exception) {
        throw TimeoutException::toTriggerStreamShutdown();
    }
};

$messageStream = new MessageStream();
$messageStream->setLogger($myLogger);
$messageStream->run($callback);

```

## About

Here's some basic information on this package itself and the intent behind it.

### What is SSE actually?

> Traditionally, a web page has to send a request to the server to receive new data; that is, the page requests data
> from the server. With server-sent events, it's possible for a server to send new data to a web page at any time, by
> pushing messages to the web page.

(from ["Server-sent events"](https://developer.mozilla.org/en-US/docs/Web/API/Server-sent_events) on the MDN web docs)

> [...] server-sent events are unidirectional; that is, data messages are delivered in one direction, from the server to
> the client (such as a user's web browser). That makes them an excellent choice when there's no need to send data from
> the client to the server in message form. For example, EventSource is a useful approach for handling things like
> social
> media status updates, news feeds, or delivering data into a client-side storage [...].

(from ["EventSource"](https://developer.mozilla.org/en-US/docs/Web/API/EventSource) on the MDN web docs)

Simply put, SSE allows us to send events to the client web page from the server.

### Why use this package?

This package...

* ... is framework-agnostic.
* ... avoids dependencies.
* ... uses an object-oriented approach.
* ... is extensible.
* ... is compatible with PHP `>= 5.6`.

### When to use this package?

This package can be used to implement simple SSE on the server-side.

The client-side may use an SSE polyfill, like
Remy Sharp's [EventSource polyfill](https://github.com/remy/polyfills/blob/master/EventSource.js)) or
Yaffle's [EventSource polyfill](https://github.com/Yaffle/EventSource),
the [native browser implementation](https://developer.mozilla.org/en-US/docs/Web/API/EventSource)
(see [caniuse](https://caniuse.com/?search=EventSource)).

For more complex use-cases, a more flexible alternative like might [Mercure](https://mercure.rocks/docs/mercure) be
preferable thought.

## Useful read

* [HTML Standard 9.2, Server-sent Events](https://html.spec.whatwg.org/multipage/server-sent-events.html)

## License

It's MIT.
