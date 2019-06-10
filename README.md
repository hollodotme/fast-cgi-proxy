[![CircleCI](https://circleci.com/gh/hollodotme/fast-cgi-proxy.svg?style=svg)](https://circleci.com/gh/hollodotme/fast-cgi-proxy)
[![Latest Stable Version](https://poser.pugx.org/hollodotme/fast-cgi-proxy/v/stable)](https://packagist.org/packages/hollodotme/fast-cgi-proxy) 
[![Total Downloads](https://poser.pugx.org/hollodotme/fast-cgi-proxy/downloads)](https://packagist.org/packages/hollodotme/fast-cgi-proxy) 
[![codecov](https://codecov.io/gh/hollodotme/fast-cgi-proxy/branch/master/graph/badge.svg)](https://codecov.io/gh/hollodotme/fast-cgi-proxy)

# FastCGI Proxy

## Description

A proxy for distributing (a)sync requests to multiple fastCGI servers.

## Installation

```bash
composer require hollodotme/fast-cgi-proxy
```

## Usage

### Request distribution

The proxy can distribute requests to multiple fastCGI servers in the following ways:

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

$random = Random::fromConnections(
    new NetworkSocket( '127.0.0.1', 9000 ),
    new NetworkSocket( '10.100.10.42', 9000 ),
    new UnixDomainSocket( '/var/run/php7.3-fpm.sock' )	
);

$proxy = new Proxy( $random );
``` 

When sending requests now the proxy will randomly choose one of the fastCGI servers to process the request.

#### Round robin distribution

To set up round robin distribution use the following example code:

```php
<?php declare(strict_types=1);

namespace YourVendor\YourProject;

use hollodotme\FastCGI\Proxy;
use hollodotme\FastCGI\Collections\RoundRobin;
use hollodotme\FastCGI\SocketConnections\NetworkSocket;
use hollodotme\FastCGI\SocketConnections\UnixDomainSocket;

$roundRobin = RoundRobin::fromConnections(
    new NetworkSocket( '127.0.0.1', 9000 ),
    new NetworkSocket( '10.100.10.42', 9000 ),
    new UnixDomainSocket( '/var/run/php7.3-fpm.sock' )	
);

$proxy = new Proxy( $roundRobin );
```

The proxy will send your requests to the next fastCGI server in the same order they were added to the `RoundRobin` instance.
In this example it will send to:
1. `127.0.0.1:9001`
2. `10.100.10.42:9001`,
3. `/var/run/php7.1-fpm.sock`
4. `127.0.0.1:9001` (start from the beginning again)
5. and so on...

---

### Sending requests

The `Proxy` class has the same methods as the underlying [Client](https://github.com/hollodotme/fast-cgi-client/blob/2.x-stable/src/Client.php) class for sending (a)sync requests and retrieving responses (reactively).
So please consult the documentation of [hollodotme/fast-cgi-client](https://github.com/hollodotme/fast-cgi-client/blob/2.x-stable) for further information.

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
  
* `$proxy->hasUnhandledResponses() : bool`  
  Returns TRUE if there are unhandles responses left, otherwise FALSE.
  
* `$proxy->getRequestIdsHavingResponse() : array`  
  Returns all request IDs that have responses. (non-blocking)
  
* `$proxy->handleResponses(?int $timeoutMs = null, int ...$requestIds) : void`  
  Calls the corresponding response callbacks of already finished requests in the order of the given request Ids.  
  (If request IDs have a response must be checked before calling this method, see `$proxy->hasResponse(int $requestId)` or `$proxy->getRequestIdsHavingResponse() : array`.)
  
* `$proxy->handleReadyResponses(?int $timeoutMs = null) : void`  
  Calls the corresponding response callbacks in the order of finished requests.  
  (non-blocking, short for `$proxy->handleResponses($timeoutMs, int ...$proxy->getRequestIdsHavingResponse())`)

---

### Cluster requests

This feature is available since `v0.2.0` of this library.

In order to process a single request on a multitude of fastCGI servers, the `ClusterProxy` class was introduced.
So in order to distribute the request to one of the configured fastCGI servers, the cluster proxy will send the same 
request to ALL configured fastCGI servers and allows you to read/handle their responses (reactively).

As per concept of cluster requests, there is always a one-to-many relation for request & responses.
That's why the `ClusterProxy` class does not offer synchronous requests and reading of single responses based on a request ID.

To set up a cluster proxy, use the following example code:

```php
<?php declare(strict_types=1);

namespace YourVendor\YourProject;

use hollodotme\FastCGI\ClusterProxy;
use hollodotme\FastCGI\Collections\Cluster;
use hollodotme\FastCGI\SocketConnections\NetworkSocket;
use hollodotme\FastCGI\SocketConnections\UnixDomainSocket;

$cluster = Cluster::fromConnections(
    new NetworkSocket( '127.0.0.1', 9000 ),
    new NetworkSocket( '10.100.10.42', 9000 ),
    new UnixDomainSocket( '/var/run/php7.3-fpm.sock' )	
);

$clusterProxy = new ClusterProxy( $cluster );
```

The following reduced set of methods to send requests and handle responses are available in the cluster proxy class:
 
* `$clusterProxy->sendAsyncRequest(ProvidesRequestData $request) : void`  
  Sends an asynchronous request to all connections in the cluster. (non-blocking)
  
* `$clusterProxy->readReadyResponses(?int $timeoutMs = null) : \Generator|ProvidesResponseData[]`  
  Reads and yields the responses of all finished requests.  
  (non-blocking, meant to be used in a loop)
  
* `$clusterProxy->waitForResponses(?int $timeoutMs = null) : void`  
  Waits for the responses of the previously obtained request IDs in the order of finished requests and calls the corresponding response callbacks.  
  (blocking until all responses were read or read timed out)
  
* `$clusterProxy->hasUnhandledResponses() : bool`  
  Returns TRUE if there are unhandles responses left, otherwise FALSE.
  
* `$clusterProxy->handleReadyResponses(?int $timeoutMs = null) : void`  
  Calls the corresponding response callbacks in the order of finished requests.  
  (non-blocking, meant to be used in a loop in combination with `$clusterProxy->hasUnhandledResponses()`)

---

### Cluster status

This feature is available since `v0.2.0` of this library.

In order to retrieve the status of all fastCGI servers in a cluster, the method `ClusterProxy#getStatus()` was introduced.

Currently this method solely supports status response implementation for PHP-FPM, 
but can easily be extended for other fastCGI servers by implementing the interface 
[`hollodotme\FastCGI\Interfaces\ProvidesServerStatus`](src/Interfaces/ProvidesServerStatus.php).

```php
<?php declare(strict_types=1);

namespace hollodotme\FastCGI\Interfaces;

interface ProvidesServerStatus
{
	/**
     * Returns the original response object for the status request provided by hollodotme/fast-cgi-client
     * @see https://github.com/hollodotme/fast-cgi-client/blob/2.x-stable/src/Responses/Response.php
     * 
     * @return ProvidesResponseData
     */
	public function getResponse() : ProvidesResponseData;

    /**
     * Returns the connection object used for the status request 
     * in order to identify the server that produced the status response
     * 
     * @return ConfiguresSocketConnection
     */
	public function getConnection() : ConfiguresSocketConnection;

    /**
     * Returns any data structure representing the status information of the server 
     * @return mixed
     */
	public function getStatus();

    /**
     * Returns a list of any data structure representing current processes running on the server 
     * @return array 
     */
	public function getProcesses() : array;
}
```

#### Cluster status example

The following code reads the status of all 3 php-fpm containers that are part of the [docker-compose setup](./docker-compose.yml) of this library.

**Please note:** If the status endpoint is not enabled in the server's config (`pm.status_path` for PHP-FPM),
the `ClusterProxy#getStatus()` method will throw a `RuntimeException`.

**[examples/cluster_status.php](examples/cluster_status.php)**

```php
<?php declare(strict_types=1);

namespace hollodotme\FastCGI\Examples;

use hollodotme\FastCGI\ClusterProxy;
use hollodotme\FastCGI\Collections\Cluster;
use hollodotme\FastCGI\Responses\PhpFpmStatusResponse;
use hollodotme\FastCGI\SocketConnections\NetworkSocket;

require_once __DIR__ . '/../vendor/autoload.php';

$cluster = Cluster::fromConnections(
	new NetworkSocket( 'php71', 9001 ),
	new NetworkSocket( 'php72', 9001 ),
	new NetworkSocket( 'php73', 9001 )
);

$clusterProxy = new ClusterProxy( $cluster );

$statusResponses = $clusterProxy->getStatus( '/status?full' );
# If you do not want the list processes, use the following line to get the status only
# $statusResponses = $clusterProxy->getStatus( '/status' );

/** @var PhpFpmStatusResponse $statusResponse */
foreach ( $statusResponses as $statusResponse )
{
	$connection = $statusResponse->getConnection();
	$status     = $statusResponse->getStatus();
	$processes  = $statusResponse->getProcesses();
	$response   = $statusResponse->getResponse();

	echo '[ SERVER: ', $connection->getSocketAddress(), " ]\n";

	echo '- Pool name: ', $status->getPoolName(), "\n";
	echo '- Process manager: ', $status->getProcessManager(), "\n";
	echo '- Started at: ', $status->getStartTime()->format( 'c' ), "\n";
	echo '- Seconds since start: ', $status->getStartSince(), "\n";
	echo '- Number of accepted connections: ', $status->getAcceptedConnections(), "\n";
	echo '- Current listen queue: ', $status->getListenQueue(), "\n";
	echo '- Listen queue maximum: ', $status->getMaxListenQueue(), "\n";
	echo '- Listen queue length: ', $status->getListenQueueLength(), "\n";
	echo '- Number of idle processes: ', $status->getIdleProcesses(), "\n";
	echo '- Number of active processes: ', $status->getActiveProcesses(), "\n";
	echo '- Number of total processes: ', $status->getTotalProcesses(), "\n";
	echo '- Number of active processes maximum: ', $status->getMaxActiveProcesses(), "\n";
	echo '- Times max children reached: ', $status->getMaxChildrenReached(), "\n";
	echo '- Number of slow requests: ', $status->getSlowRequests(), "\n";

	echo "\nPrinting processes:\n\n";

	foreach ( $processes as $index => $process )
	{
		echo '- [ PROCESS #', ($index + 1), " ]\n";
		echo '  * PID: ', $process->getPid(), "\n";
		echo '  * State: ', $process->getState(), "\n";
		echo '  * Started at: ', $process->getStartTime()->format( 'c' ), "\n";
		echo '  * Seconds since start: ', $process->getStartSince(), "\n";
		echo '  * Number of requests processed: ', $process->getRequests(), "\n";
		echo '  * Last request duration: ', $process->getRequestDuration(), "\n";
		echo '  * Last request method: ', $process->getRequestMethod(), "\n";
		echo '  * Last request URI: ', $process->getRequestUri(), "\n";
		echo '  * Last content length: ', $process->getContentLength(), "\n";
		echo '  * Last user: ', $process->getUser(), "\n";
		echo '  * Last script: ', $process->getScript(), "\n";
		echo '  * CPU usage of last request: ', $process->getLastRequestCpu(), "\n";
		echo '  * Memory usage of last request: ', $process->getLastRequestMemory(), "\n";

		echo "\n\n---\n\n";
	}

	echo 'Processing duration: ', $response->getDuration(), " seconds\n\n";
}
```

This script produces for example the following output

```text
[ SERVER: tcp://php71:9001 ]
- Pool name: network
- Process manager: dynamic
- Started at: 2019-06-10T14:56:45+00:00
- Seconds since start: 18094
- Number of accepted connections: 81
- Current listen queue: 0
- Listen queue maximum: 0
- Listen queue length: 128
- Number of idle processes: 1
- Number of active processes: 1
- Number of total processes: 2
- Number of active processes maximum: 2
- Times max children reached: 0
- Number of slow requests: 0

Printing processes:

- [ PROCESS #1 ]
  * PID: 8
  * State: Idle
  * Started at: 2019-06-10T14:56:45+00:00
  * Seconds since start: 18094
  * Number of requests processed: 40
  * Last request duration: 190
  * Last request method: -
  * Last request URI: -
  * Last content length: 0
  * Last user: -
  * Last script: -
  * CPU usage of last request: 0
  * Memory usage of last request: 2097152


---

- [ PROCESS #2 ]
  * PID: 9
  * State: Running
  * Started at: 2019-06-10T14:56:45+00:00
  * Seconds since start: 18094
  * Number of requests processed: 41
  * Last request duration: 190
  * Last request method: GET
  * Last request URI: /status?full
  * Last content length: 0
  * Last user: -
  * Last script: -
  * CPU usage of last request: 0
  * Memory usage of last request: 0


---

Processing duration: 0.0137939453125 seconds

[ SERVER: tcp://php72:9001 ]
- Pool name: network
- Process manager: dynamic
- Started at: 2019-06-10T14:56:46+00:00
- Seconds since start: 18093
- Number of accepted connections: 75
- Current listen queue: 0
- Listen queue maximum: 0
- Listen queue length: 128
- Number of idle processes: 1
- Number of active processes: 1
- Number of total processes: 2
- Number of active processes maximum: 2
- Times max children reached: 0
- Number of slow requests: 0

Printing processes:

- [ PROCESS #1 ]
  * PID: 10
  * State: Idle
  * Started at: 2019-06-10T14:56:46+00:00
  * Seconds since start: 18093
  * Number of requests processed: 38
  * Last request duration: 217
  * Last request method: -
  * Last request URI: -
  * Last content length: 0
  * Last user: -
  * Last script: -
  * CPU usage of last request: 0
  * Memory usage of last request: 2097152


---

- [ PROCESS #2 ]
  * PID: 11
  * State: Running
  * Started at: 2019-06-10T14:56:46+00:00
  * Seconds since start: 18093
  * Number of requests processed: 37
  * Last request duration: 177
  * Last request method: GET
  * Last request URI: /status?full
  * Last content length: 0
  * Last user: -
  * Last script: -
  * CPU usage of last request: 0
  * Memory usage of last request: 0


---

Processing duration: 0.027499914169312 seconds

[ SERVER: tcp://php73:9001 ]
- Pool name: network
- Process manager: dynamic
- Started at: 2019-06-10T14:56:45+00:00
- Seconds since start: 18094
- Number of accepted connections: 1706
- Current listen queue: 0
- Listen queue maximum: 1
- Listen queue length: 128
- Number of idle processes: 2
- Number of active processes: 1
- Number of total processes: 3
- Number of active processes maximum: 23
- Times max children reached: 0
- Number of slow requests: 0

Printing processes:

- [ PROCESS #1 ]
  * PID: 331
  * State: Idle
  * Started at: 2019-06-10T17:00:25+00:00
  * Seconds since start: 10674
  * Number of requests processed: 383
  * Last request duration: 185
  * Last request method: -
  * Last request URI: -
  * Last content length: 0
  * Last user: -
  * Last script: -
  * CPU usage of last request: 0
  * Memory usage of last request: 2097152


---

- [ PROCESS #2 ]
  * PID: 497
  * State: Running
  * Started at: 2019-06-10T17:31:02+00:00
  * Seconds since start: 8837
  * Number of requests processed: 59
  * Last request duration: 244
  * Last request method: GET
  * Last request URI: /status?full
  * Last content length: 0
  * Last user: -
  * Last script: -
  * CPU usage of last request: 0
  * Memory usage of last request: 0


---

- [ PROCESS #3 ]
  * PID: 315
  * State: Idle
  * Started at: 2019-06-10T16:42:27+00:00
  * Seconds since start: 11752
  * Number of requests processed: 433
  * Last request duration: 230
  * Last request method: -
  * Last request URI: -
  * Last content length: 0
  * Last user: -
  * Last script: -
  * CPU usage of last request: 0
  * Memory usage of last request: 2097152


---

Processing duration: 0.029183149337769 seconds
```

## Contributing

Contributions are welcome and will be fully credited. Please see the [contribution guide](.github/CONTRIBUTING.md) for details.


