<?php declare(strict_types=1);
/**
 * @author hollodotme
 */

namespace hollodotme\FastCGI\Tests\Integration;

use Closure;
use hollodotme\FastCGI\Collections\Random;
use hollodotme\FastCGI\Interfaces\ConfiguresSocketConnection;
use hollodotme\FastCGI\Interfaces\ProvidesRequestData;
use hollodotme\FastCGI\Interfaces\ProvidesResponseData;
use hollodotme\FastCGI\Proxy;
use hollodotme\FastCGI\Requests\PostRequest;
use hollodotme\FastCGI\SocketConnections\NetworkSocket;
use hollodotme\FastCGI\SocketConnections\UnixDomainSocket;
use PHPUnit\Framework\TestCase;
use function http_build_query;
use function in_array;
use function usleep;

/**
 * Class ProxyTest
 * @package hollodotme\FastCGI\Tests\Integration
 */
final class RandomProxyTest extends TestCase
{
	private const WORKER = __DIR__ . '/Workers/worker.php';

	/**
	 * @throws \PHPUnit\Framework\ExpectationFailedException
	 * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
	 */
	public function testCanSendSynchronousRequests() : void
	{
		$proxy = $this->getProxy();

		$responses = [
			$proxy->sendRequest( $this->getRequest() )->getBody(),
			$proxy->sendRequest( $this->getRequest() )->getBody(),
		];

		$expectedResponses = [
			[
				'Unit-Test-network-socket',
				'Unit-Test-unix-domain-socket',
			],
			[
				'Unit-Test-network-socket',
				'Unit-Test-network-socket',
			],
			[
				'Unit-Test-unix-domain-socket',
				'Unit-Test-unix-domain-socket',
			],
			[
				'Unit-Test-unix-domain-socket',
				'Unit-Test-network-socket',
			],
		];

		$this->assertTrue( in_array( $responses, $expectedResponses, false ) );
	}

	private function getProxy() : Proxy
	{
		$random = new Random();
		$random->add( ...$this->getConnections() );

		return new Proxy( $random );
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
		$test     = $this;
		$proxy    = $this->getProxy();
		$callback = function ( ProvidesResponseData $response ) use ( $test )
		{
			$test->assertTrue(
				in_array( $response->getBody(), ['Unit-Test-network-socket', 'Unit-Test-unix-domain-socket'], true )
			);
		};

		$requestId1 = $proxy->sendAsyncRequest( $this->getRequestWithCallback( $callback ) );
		$requestId2 = $proxy->sendAsyncRequest( $this->getRequestWithCallback( $callback ) );

		$proxy->waitForResponse( $requestId1 );
		$proxy->waitForResponse( $requestId2 );
	}

	/**
	 * @throws \PHPUnit\Framework\ExpectationFailedException
	 * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
	 */
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
		$test     = $this;
		$proxy    = $this->getProxy();
		$callback = function ( ProvidesResponseData $response ) use ( $test )
		{
			$test->assertTrue(
				in_array( $response->getBody(), ['Unit-Test-network-socket', 'Unit-Test-unix-domain-socket'], true )
			);
		};

		$proxy->sendAsyncRequest( $this->getRequestWithCallback( $callback ) );
		$proxy->sendAsyncRequest( $this->getRequestWithCallback( $callback ) );
		$proxy->sendAsyncRequest( $this->getRequestWithCallback( $callback ) );
		$proxy->sendAsyncRequest( $this->getRequestWithCallback( $callback ) );

		$proxy->waitForResponses();
	}

	private function getRequestWithCallback( Closure $callback ) : ProvidesRequestData
	{
		$request = new PostRequest(
			self::WORKER,
			http_build_query( ['test-key' => 'Unit-Test'] )
		);

		$request->addResponseCallbacks( $callback );

		return $request;
	}

	/**
	 * @throws \PHPUnit\Framework\ExpectationFailedException
	 * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
	 */
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
		];

		foreach ( $proxy->readResponses( null, $requestId1, $requestId2, $requestId3, $requestId4 ) as $response )
		{
			$this->assertTrue( in_array( $response->getBody(), $expectedResponses, true ) );
		}
	}

	/**
	 * @throws \PHPUnit\Framework\ExpectationFailedException
	 * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
	 */
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
				$this->assertTrue( in_array( $response->getBody(), $expectedResponses, true ) );
			}
		}
	}
}
