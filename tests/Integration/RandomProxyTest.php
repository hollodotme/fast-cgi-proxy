<?php declare(strict_types=1);

namespace hollodotme\FastCGI\Tests\Integration;

use Closure;
use hollodotme\FastCGI\Collections\Random;
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

final class RandomProxyTest extends TestCase
{
	use SocketDataProviding;

	private const WORKER = __DIR__ . '/Workers/worker.php';

	/** @var Proxy */
	private $proxy;

	protected function setUp() : void
	{
		$random = Random::fromConnections(
			new NetworkSocket(
				$this->getNetworkSocketHost(),
				$this->getNetworkSocketPort()
			),
			new UnixDomainSocket(
				$this->getUnixDomainSocket()
			)
		);

		$this->proxy = new Proxy( $random );
	}

	protected function tearDown() : void
	{
		$this->proxy = null;
	}

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
		/** @noinspection RepetitiveMethodCallsInspection */
		$responses = [
			$this->proxy->sendRequest( $this->getRequest() )->getBody(),
			$this->proxy->sendRequest( $this->getRequest() )->getBody(),
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
		$callback = static function ( ProvidesResponseData $response ) use ( $test )
		{
			$test->assertContains(
				$response->getBody(),
				['Unit-Test-network-socket', 'Unit-Test-unix-domain-socket']
			);
		};

		$requestId1 = $this->proxy->sendAsyncRequest( $this->getRequestWithCallback( $callback ) );
		$requestId2 = $this->proxy->sendAsyncRequest( $this->getRequestWithCallback( $callback ) );

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
		$test     = $this;
		$callback = static function ( ProvidesResponseData $response ) use ( $test )
		{
			$test->assertContains(
				$response->getBody(),
				['Unit-Test-network-socket', 'Unit-Test-unix-domain-socket']
			);
		};

		$this->proxy->sendAsyncRequest( $this->getRequestWithCallback( $callback ) );
		$this->proxy->sendAsyncRequest( $this->getRequestWithCallback( $callback ) );
		$this->proxy->sendAsyncRequest( $this->getRequestWithCallback( $callback ) );
		$this->proxy->sendAsyncRequest( $this->getRequestWithCallback( $callback ) );

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
		$requestIds   = [];
		$requestIds[] = $this->proxy->sendAsyncRequest( $this->getRequest() );
		$requestIds[] = $this->proxy->sendAsyncRequest( $this->getRequest() );
		$requestIds[] = $this->proxy->sendAsyncRequest( $this->getRequest() );
		$requestIds[] = $this->proxy->sendAsyncRequest( $this->getRequest() );

		$expectedResponses = [
			'Unit-Test-network-socket',
			'Unit-Test-unix-domain-socket',
		];

		foreach ( $this->proxy->readResponses( null, ...$requestIds ) as $response )
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
