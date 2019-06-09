<?php declare(strict_types=1);

namespace hollodotme\FastCGI\Tests\Integration;

use Closure;
use hollodotme\FastCGI\Collections\Random;
use hollodotme\FastCGI\Exceptions\ConnectException;
use hollodotme\FastCGI\Exceptions\ReadFailedException;
use hollodotme\FastCGI\Exceptions\TimedoutException;
use hollodotme\FastCGI\Exceptions\WriteFailedException;
use hollodotme\FastCGI\Interfaces\ConfiguresSocketConnection;
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

final class RandomProxyTest extends TestCase
{
	use SocketDataProviding;

	private const WORKER = __DIR__ . '/Workers/worker.php';

	/**
	 * @throws Exception
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
		$proxy = $this->getProxy();

		/** @noinspection RepetitiveMethodCallsInspection */
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

		$this->assertContains( $responses, $expectedResponses );
	}

	private function getProxy() : Proxy
	{
		$random = new Random();
		$random->addConnections( ...$this->getConnections() );

		return new Proxy( $random );
	}

	/**
	 * @return array|ConfiguresSocketConnection
	 */
	private function getConnections() : array
	{
		return [
			new NetworkSocket(
				$this->getNetworkSocketHost(),
				$this->getNetworkSocketPort()
			),
			new UnixDomainSocket(
				$this->getUnixDomainSocket()
			),
		];
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
		$test     = $this;
		$proxy    = $this->getProxy();
		$callback = static function ( ProvidesResponseData $response ) use ( $test )
		{
			$test->assertContains(
				$response->getBody(),
				['Unit-Test-network-socket', 'Unit-Test-unix-domain-socket']
			);
		};

		$requestId1 = $proxy->sendAsyncRequest( $this->getRequestWithCallback( $callback ) );
		$requestId2 = $proxy->sendAsyncRequest( $this->getRequestWithCallback( $callback ) );

		$proxy->waitForResponse( $requestId1 );
		$proxy->waitForResponse( $requestId2 );
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
		$proxy = $this->getProxy();

		$requestId1 = $proxy->sendAsyncRequest( $this->getRequest() );
		$requestId2 = $proxy->sendAsyncRequest( $this->getRequest() );

		usleep( 500000 );

		$this->assertTrue( $proxy->hasResponse( $requestId1 ) );
		$this->assertTrue( $proxy->hasResponse( $requestId2 ) );
	}

	/**
	 * @throws ConnectException
	 * @throws ReadFailedException
	 * @throws TimedoutException
	 * @throws WriteFailedException
	 */
	public function testCanWaitForResponses() : void
	{
		$test     = $this;
		$proxy    = $this->getProxy();
		$callback = static function ( ProvidesResponseData $response ) use ( $test )
		{
			$test->assertContains(
				$response->getBody(),
				['Unit-Test-network-socket', 'Unit-Test-unix-domain-socket']
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
	 * @throws ConnectException
	 * @throws Exception
	 * @throws ExpectationFailedException
	 * @throws InvalidArgumentException
	 * @throws ReadFailedException
	 * @throws Throwable
	 * @throws TimedoutException
	 * @throws WriteFailedException
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
			$this->assertContains( $response->getBody(), $expectedResponses );
		}
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
				$this->assertContains( $response->getBody(), $expectedResponses );
			}
		}
	}
}
