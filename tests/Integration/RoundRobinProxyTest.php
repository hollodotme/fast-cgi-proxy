<?php declare(strict_types=1);

namespace hollodotme\FastCGI\Tests\Integration;

use Closure;
use hollodotme\FastCGI\Collections\RoundRobin;
use hollodotme\FastCGI\Exceptions\ConnectException;
use hollodotme\FastCGI\Exceptions\ReadFailedException;
use hollodotme\FastCGI\Exceptions\TimedoutException;
use hollodotme\FastCGI\Exceptions\WriteFailedException;
use hollodotme\FastCGI\Interfaces\ProvidesRequestData;
use hollodotme\FastCGI\Interfaces\ProvidesResponseData;
use hollodotme\FastCGI\Proxy;
use hollodotme\FastCGI\Requests\PostRequest;
use hollodotme\FastCGI\SocketConnections\NetworkSocket;
use hollodotme\FastCGI\SocketConnections\UnixDomainSocket;
use hollodotme\FastCGI\Tests\Traits\SocketDataProviding;
use PHPUnit\Framework\Exception;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;
use SebastianBergmann\RecursionContext\InvalidArgumentException;
use Throwable;
use function http_build_query;
use function usleep;

final class RoundRobinProxyTest extends TestCase
{
	use SocketDataProviding;

	private const WORKER = __DIR__ . '/Workers/worker.php';

	/** @var Proxy */
	private $proxy;

	protected function setUp() : void
	{
		$roundRobin = RoundRobin::fromConnections(
			new NetworkSocket(
				$this->getNetworkSocketHost(),
				$this->getNetworkSocketPort()
			),
			new UnixDomainSocket(
				$this->getUnixDomainSocket()
			)
		);

		$this->proxy = new Proxy( $roundRobin );
	}

	protected function tearDown() : void
	{
		$this->proxy = null;
	}

	/**
	 * @throws ExpectationFailedException
	 * @throws InvalidArgumentException
	 * @throws Throwable
	 * @throws ConnectException
	 * @throws ReadFailedException
	 * @throws TimedoutException
	 * @throws WriteFailedException
	 */
	public function testCanSendSynchronousRequests() : void
	{
		/** @noinspection RepetitiveMethodCallsInspection */
		$responses = [
			$this->proxy->sendRequest( $this->getRequest() )->getBody(),
			$this->proxy->sendRequest( $this->getRequest() )->getBody(),
			$this->proxy->sendRequest( $this->getRequest() )->getBody(),
			$this->proxy->sendRequest( $this->getRequest() )->getBody(),
		];

		$expectedResponses = [
			'Unit-Test-network-socket',
			'Unit-Test-unix-domain-socket',
			'Unit-Test-network-socket',
			'Unit-Test-unix-domain-socket',
		];

		$this->assertSame( $expectedResponses, $responses );
	}

	private function getRequest() : ProvidesRequestData
	{
		return new PostRequest(
			self::WORKER,
			http_build_query( ['test-key' => 'Unit-Test'] )
		);
	}

	/**
	 * @throws ConnectException
	 * @throws ReadFailedException
	 * @throws TimedoutException
	 * @throws WriteFailedException
	 */
	public function testCanWaitForResponse() : void
	{
		$test      = $this;
		$callback1 = static function ( ProvidesResponseData $response ) use ( $test )
		{
			$test->assertSame( 'Unit-Test-network-socket', $response->getBody() );
		};
		$callback2 = static function ( ProvidesResponseData $response ) use ( $test )
		{
			$test->assertSame( 'Unit-Test-unix-domain-socket', $response->getBody() );
		};

		$requestId1 = $this->proxy->sendAsyncRequest( $this->getRequestWithCallback( $callback1 ) );
		$requestId2 = $this->proxy->sendAsyncRequest( $this->getRequestWithCallback( $callback2 ) );

		$this->proxy->waitForResponse( $requestId1 );
		$this->proxy->waitForResponse( $requestId2 );
	}

	/**
	 * @throws ConnectException
	 * @throws ExpectationFailedException
	 * @throws InvalidArgumentException
	 * @throws ReadFailedException
	 * @throws TimedoutException
	 * @throws WriteFailedException
	 */
	public function testCanCheckIfProxyHasResponse() : void
	{
		$requestId1 = $this->proxy->sendAsyncRequest( $this->getRequest() );
		$requestId2 = $this->proxy->sendAsyncRequest( $this->getRequest() );

		usleep( 500000 );

		$this->assertTrue( $this->proxy->hasResponse( $requestId1 ) );
		$this->assertTrue( $this->proxy->hasResponse( $requestId2 ) );
	}

	/**
	 * @throws ConnectException
	 * @throws ReadFailedException
	 * @throws TimedoutException
	 * @throws WriteFailedException
	 */
	public function testCanWaitForResponses() : void
	{
		$test      = $this;
		$callback1 = static function ( ProvidesResponseData $response ) use ( $test )
		{
			$test->assertSame( 'Unit-Test-network-socket', $response->getBody() );
		};
		$callback2 = static function ( ProvidesResponseData $response ) use ( $test )
		{
			$test->assertSame( 'Unit-Test-unix-domain-socket', $response->getBody() );
		};

		$this->proxy->sendAsyncRequest( $this->getRequestWithCallback( $callback1 ) );
		$this->proxy->sendAsyncRequest( $this->getRequestWithCallback( $callback2 ) );
		$this->proxy->sendAsyncRequest( $this->getRequestWithCallback( $callback1 ) );
		$this->proxy->sendAsyncRequest( $this->getRequestWithCallback( $callback2 ) );

		$this->proxy->waitForResponses();
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
	 * @throws ConnectException
	 * @throws ExpectationFailedException
	 * @throws InvalidArgumentException
	 * @throws ReadFailedException
	 * @throws Throwable
	 * @throws TimedoutException
	 * @throws WriteFailedException
	 */
	public function testCanReadResponses() : void
	{
		$requestIds   = [];
		$requestIds[] = $this->proxy->sendAsyncRequest( $this->getRequest() );
		$requestIds[] = $this->proxy->sendAsyncRequest( $this->getRequest() );
		$requestIds[] = $this->proxy->sendAsyncRequest( $this->getRequest() );
		$requestIds[] = $this->proxy->sendAsyncRequest( $this->getRequest() );

		$expectedResponses = [
			'Unit-Test-network-socket',
			'Unit-Test-unix-domain-socket',
			'Unit-Test-network-socket',
			'Unit-Test-unix-domain-socket',
		];

		$responses = [];
		foreach ( $this->proxy->readResponses( null, ...$requestIds ) as $response )
		{
			$responses[] = $response->getBody();
		}

		$this->assertSame( $expectedResponses, $responses );
	}

	/**
	 * @throws ConnectException
	 * @throws Exception
	 * @throws ExpectationFailedException
	 * @throws InvalidArgumentException
	 * @throws ReadFailedException
	 * @throws Throwable
	 * @throws TimedoutException
	 * @throws WriteFailedException
	 */
	public function testCanReadReadyResponses() : void
	{
		$this->proxy->sendAsyncRequest( $this->getRequest() );
		$this->proxy->sendAsyncRequest( $this->getRequest() );
		$this->proxy->sendAsyncRequest( $this->getRequest() );
		$this->proxy->sendAsyncRequest( $this->getRequest() );

		$expectedResponses = [
			'Unit-Test-network-socket',
			'Unit-Test-unix-domain-socket',
		];

		while ( $this->proxy->hasUnhandledResponses() )
		{
			foreach ( $this->proxy->readReadyResponses() as $response )
			{
				$this->assertContains( $response->getBody(), $expectedResponses );
			}
		}
	}
}
