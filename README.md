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
use Machinateur\SSE\EventSource;
use Machinateur\SSE\Event;
use Machinateur\SSE\Util;

// ...

// TODO
```

Make sure to read about [when](#when-to-use-this-package) and [why](#why-use-this-package) to use this package, to make
sure it fits your use-case.

Below are additional explanations of common use-case scenarios.

### Logger support

TODO

### Custom event source

TODO

### Custom events

TODO

### Framework integration

TODO

### Usage with other SSE implementations

This library was inspired and influenced by [`hhxsv5/php-sse`](https://github.com/hhxsv5/php-sse), so here is how to
achieve interoperability between the two:

TODO

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

TODO

### When to use this package?

TODO

## License

It's MIT.
