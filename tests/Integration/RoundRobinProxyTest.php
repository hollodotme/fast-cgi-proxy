<?php declare(strict_types=1);
/**
 * @author hollodotme
 */

namespace hollodotme\FastCGI\Tests\Integration;

use hollodotme\FastCGI\Collections\RoundRobin;
use hollodotme\FastCGI\Interfaces\ConfiguresSocketConnection;
use hollodotme\FastCGI\Interfaces\ProvidesRequestData;
use hollodotme\FastCGI\Interfaces\ProvidesResponseData;
use hollodotme\FastCGI\Proxy;
use hollodotme\FastCGI\Requests\PostRequest;
use hollodotme\FastCGI\SocketConnections\NetworkSocket;
use hollodotme\FastCGI\SocketConnections\UnixDomainSocket;
use PHPUnit\Framework\TestCase;

/**
 * Class RoundRobinProxyTest
 * @package hollodotme\FastCGI\Tests\Integration
 */
final class RoundRobinProxyTest extends TestCase
{
	private const WORKER = __DIR__ . '/Workers/worker.php';

	public function testCanSendSynchronousRequests() : void
	{
		$proxy = $this->getProxy();

		$responses = [
			$proxy->sendRequest( $this->getRequest() )->getBody(),
			$proxy->sendRequest( $this->getRequest() )->getBody(),
			$proxy->sendRequest( $this->getRequest() )->getBody(),
			$proxy->sendRequest( $this->getRequest() )->getBody(),
		];

		$expectedResponses = [
			'Unit-Test-network-socket',
			'Unit-Test-unix-domain-socket',
			'Unit-Test-network-socket',
			'Unit-Test-unix-domain-socket',
		];

		$this->assertSame( $expectedResponses, $responses );
	}

	private function getProxy() : Proxy
	{
		$roundRobin = new RoundRobin();
		$roundRobin->add( ...$this->getConnections() );

		return new Proxy( $roundRobin );
	}

	/**
	 * @return array|ConfiguresSocketConnection
	 */
	private function getConnections() : array
	{
		return [
			new NetworkSocket( '127.0.0.1', 9001 ),
			new UnixDomainSocket( '/var/run/php-uds.sock' ),
		];
	}

	private function getRequest() : ProvidesRequestData
	{
		return new PostRequest(
			self::WORKER,
			http_build_query( ['test-key' => 'Unit-Test'] )
		);
	}

	public function testCanWaitForResponse() : void
	{
		$test      = $this;
		$callback1 = function ( ProvidesResponseData $response ) use ( $test )
		{
			$test->assertSame( 'Unit-Test-network-socket', $response->getBody() );
		};
		$callback2 = function ( ProvidesResponseData $response ) use ( $test )
		{
			$test->assertSame( 'Unit-Test-unix-domain-socket', $response->getBody() );
		};
		$proxy     = $this->getProxy();

		$requestId1 = $proxy->sendAsyncRequest( $this->getRequestWithCallback( $callback1 ) );
		$requestId2 = $proxy->sendAsyncRequest( $this->getRequestWithCallback( $callback2 ) );

		$proxy->waitForResponse( $requestId1 );
		$proxy->waitForResponse( $requestId2 );
	}

	public function testCanCheckIfProxyHasResponse() : void
	{
		$proxy = $this->getProxy();

		$requestId1 = $proxy->sendAsyncRequest( $this->getRequest() );
		$requestId2 = $proxy->sendAsyncRequest( $this->getRequest() );

		usleep( 500000 );

		$this->assertTrue( $proxy->hasResponse( $requestId1 ) );
		$this->assertTrue( $proxy->hasResponse( $requestId2 ) );
	}

	public function testCanWaitForResponses() : void
	{
		$test      = $this;
		$callback1 = function ( ProvidesResponseData $response ) use ( $test )
		{
			$test->assertSame( 'Unit-Test-network-socket', $response->getBody() );
		};
		$callback2 = function ( ProvidesResponseData $response ) use ( $test )
		{
			$test->assertSame( 'Unit-Test-unix-domain-socket', $response->getBody() );
		};
		$proxy     = $this->getProxy();

		$proxy->sendAsyncRequest( $this->getRequestWithCallback( $callback1 ) );
		$proxy->sendAsyncRequest( $this->getRequestWithCallback( $callback2 ) );
		$proxy->sendAsyncRequest( $this->getRequestWithCallback( $callback1 ) );
		$proxy->sendAsyncRequest( $this->getRequestWithCallback( $callback2 ) );

		$proxy->waitForResponses();
	}

	private function getRequestWithCallback( \Closure $callback ) : ProvidesRequestData
	{
		$request = new PostRequest(
			self::WORKER,
			http_build_query( ['test-key' => 'Unit-Test'] )
		);

		$request->addResponseCallbacks( $callback );

		return $request;
	}

	public function testCanReadResponses() : void
	{
		$proxy      = $this->getProxy();
		$requestId1 = $proxy->sendAsyncRequest( $this->getRequest() );
		$requestId2 = $proxy->sendAsyncRequest( $this->getRequest() );
		$requestId3 = $proxy->sendAsyncRequest( $this->getRequest() );
		$requestId4 = $proxy->sendAsyncRequest( $this->getRequest() );

		$expectedResponses = [
			'Unit-Test-network-socket',
			'Unit-Test-unix-domain-socket',
			'Unit-Test-network-socket',
			'Unit-Test-unix-domain-socket',
		];

		$responses = [];
		foreach ( $proxy->readResponses( null, $requestId1, $requestId2, $requestId3, $requestId4 ) as $response )
		{
			$responses[] = $response->getBody();
		}

		$this->assertSame( $expectedResponses, $responses );
	}

	public function testCanReadReadyResponses() : void
	{
		$proxy = $this->getProxy();
		$proxy->sendAsyncRequest( $this->getRequest() );
		$proxy->sendAsyncRequest( $this->getRequest() );
		$proxy->sendAsyncRequest( $this->getRequest() );
		$proxy->sendAsyncRequest( $this->getRequest() );

		$expectedResponses = [
			'Unit-Test-network-socket',
			'Unit-Test-unix-domain-socket',
		];

		while ( $proxy->hasUnhandledResponses() )
		{
			foreach ( $proxy->readReadyResponses() as $response )
			{
				$this->assertTrue( \in_array( $response->getBody(), $expectedResponses, true ) );
			}
		}
	}
}
