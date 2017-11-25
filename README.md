[![Build Status](https://travis-ci.org/hollodotme/fast-cgi-proxy.svg?branch=master)](https://travis-ci.org/hollodotme/fast-cgi-proxy)
[![Latest Stable Version](https://poser.pugx.org/hollodotme/fast-cgi-proxy/v/stable)](https://packagist.org/packages/hollodotme/fast-cgi-proxy) 
[![Total Downloads](https://poser.pugx.org/hollodotme/fast-cgi-proxy/downloads)](https://packagist.org/packages/hollodotme/fast-cgi-proxy) 
[![Coverage Status](https://coveralls.io/repos/github/hollodotme/fast-cgi-proxy/badge.svg?branch=master)](https://coveralls.io/github/hollodotme/fast-cgi-proxy?branch=master)

# FastCGI Proxy

## Description

A proxy for distributing (a)sync requests to multiple php-fpm sockets/pools.

## Installation

```bash
composer require hollodotme/fast-cgi-proxy
```

## Usage

### Request distribution

The proxy can distribute requests to multiple php-fpm sockets/pools in the following ways:

1. Randomly
2. Via round robin

#### Random distribution

To set up random distribution use the following example code:

```php
<?php declare(strict_types=1);

namespace YourVendor\YourProject;

use hollodotme\FastCGI\Proxy;
use hollodotme\FastCGI\Collections\Random;
use hollodotme\FastCGI\SocketConnections\NetworkSocket;
use hollodotme\FastCGI\SocketConnections\UnixDomainSocket;

$random = new Random();
$random->add(
    new NetworkSocket( '127.0.0.1', 9001 ),
    new NetworkSocket( '10.100.10.42', 9001 ),
    new UnixDomainSocket( '/var/run/php7.1-fpm.sock' )	
);

$proxy = new Proxy( $random );
``` 

When sending requests now the proxy will randomly choose one of the php-fpm sockets/pools.

#### Round robin distribution

To set up round robin distribution use the following example code:

```php
<?php declare(strict_types=1);

namespace YourVendor\YourProject;

use hollodotme\FastCGI\Proxy;
use hollodotme\FastCGI\Collections\RoundRobin;
use hollodotme\FastCGI\SocketConnections\NetworkSocket;
use hollodotme\FastCGI\SocketConnections\UnixDomainSocket;

$roundRobin = new RoundRobin();
$roundRobin->add(
    new NetworkSocket( '127.0.0.1', 9001 ),
    new NetworkSocket( '10.100.10.42', 9001 ),
    new UnixDomainSocket( '/var/run/php7.1-fpm.sock' )	
);

$proxy = new Proxy( $roundRobin );
```

The proxy will send your requests to the next php-fpm socket/pool in the same order they were added.
In this example it will send to:
1. `127.0.0.1:9001`
2. `10.100.10.42:9001`,
3. `/var/run/php7.1-fpm.sock`
4. `127.0.0.1:9001` (start from the beginning again)
5. and so on...

### Sending requests

The `Proxy` class has the same methods as the underlying [Client](https://github.com/hollodotme/fast-cgi-client/blob/v2.4.1/src/Client.php) class for sending (a)sync requests and retrieving responses (reactively).
So please consult the documentation of [hollodotme/fast-cgi-client](https://github.com/hollodotme/fast-cgi-client) for further information.

Here is just a short list of available methods:

* `$proxy->sendRequest(ProvidesRequestData $request) : ProvidesResponseData`  
  Sends a synchronous request and returns the response. (blocking)
  
* `$proxy->sendAsyncRequest(ProvidesRequestData $request) : int`  
  Sends an asynchronous request and returns the request ID. (non-blocking)
  
* `$proxy->readResponse(int $requestId, ?int $timeoutMs = null) : ProvidesResponseData`  
  Reads and returns the response of a previously obtained request ID.  
  (blocking until response was read or read timed out) 

* `$proxy->readResponses(?int $timeoutMs = null, int ...$requestIds) : \Generator|ProvidesResponseData[]`  
  Reads and yields the responses of previously obtained request IDs in the order of the given request IDs.  
  (blocking until all responses were read or read timed out)
  
* `$proxy->readReadyResponses(?int $timeoutMs = null) : \Generator|ProvidesResponseData[]`  
  Reads and yields the responses of all finished requests.  
  (non-blocking, meant to be used in a loop)
  
* `$proxy->waitForResponse(int $requestId, ?int $timeoutMs = null) : void`  
  Waits for the response of a previously obtained request ID and calls the request's response callback.  
  (blocking until response was read or read timed out)
  
* `$proxy->waitForResponses(?int $timeoutMs = null) : void`  
  Waits for the responses of the previously obtained request IDs in the order of finished requests and calls the corresponding response callbacks.  
  (blocking until all responses were read or read timed out)
  
* `$proxy->hasResponse(int $requestId) : bool`  
  Returns whether the given request ID has a response or not. (non-blocking) 
  
* `$proxy->handleResponse(int $requestId, ?int $timeoutMs = null) : void`  
  Calls the corresponding response callback of an already finished request.  
  (If request ID has a response must be checked before calling this method, see `$proxy->hasResponse(int $requestId)`).
  
* `$proxy->getRequestIdsHavingResponse() : array`  
  Returns all request IDs that have responses. (non-blocking)
  
* `$proxy->handleResponses(?int $timeoutMs = null, int ...$requestIds) : void`  
  Calls the corresponding response callbacks of already finished requests in the order of the given request Ids.  
  (If request IDs have a response must be checked before calling this method, see `$proxy->hasResponse(int $requestId)` or `$proxy->getRequestIdsHavingResponse() : array`.)
  
* `$proxy->handleReadyResponses(?int $timeoutMs = null) : void`  
  Calls the corresponding response callbacks in the order of finished requests.  
  (non-blocking, short for `$proxy->handleResponses($timeoutMs, int ...$proxy->getRequestIdsHavingResponse())`)

## Contributing

Contributions are welcome and will be fully credited. Please see the [contribution guide](CONTRIBUTING.md) for details.


