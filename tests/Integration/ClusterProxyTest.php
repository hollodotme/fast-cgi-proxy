<?php declare(strict_types=1);

namespace hollodotme\FastCGI\Tests\Integration;

use hollodotme\FastCGI\ClusterProxy;
use hollodotme\FastCGI\Collections\Cluster;
use hollodotme\FastCGI\Exceptions\ConnectException;
use hollodotme\FastCGI\Exceptions\ReadFailedException;
use hollodotme\FastCGI\Exceptions\TimedoutException;
use hollodotme\FastCGI\Exceptions\WriteFailedException;
use hollodotme\FastCGI\Interfaces\ProvidesRequestData;
use hollodotme\FastCGI\Interfaces\ProvidesResponseData;
use hollodotme\FastCGI\Requests\PostRequest;
use hollodotme\FastCGI\SocketConnections\NetworkSocket;
use hollodotme\FastCGI\SocketConnections\UnixDomainSocket;
use hollodotme\FastCGI\Tests\Traits\SocketDataProviding;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;
use SebastianBergmann\RecursionContext\InvalidArgumentException;
use function http_build_query;

final class ClusterProxyTest extends TestCase
{
	use SocketDataProviding;

	/** @var ClusterProxy */
	private $clusterProxy;

	protected function setUp() : void
	{
		$cluster = new Cluster();
		$cluster->addConnections(
			new NetworkSocket(
				$this->getNetworkSocketHost(),
				$this->getNetworkSocketPort()
			),
			new UnixDomainSocket(
				$this->getUnixDomainSocket()
			)
		);

		$this->clusterProxy = new ClusterProxy( $cluster );
	}

	protected function tearDown() : void
	{
		$this->clusterProxy = null;
	}

	/**
	 * @throws ExpectationFailedException
	 * @throws InvalidArgumentException
	 * @throws ConnectException
	 * @throws TimedoutException
	 * @throws WriteFailedException
	 */
	public function testHasUnhandledResponses() : void
	{
		$this->assertFalse( $this->clusterProxy->hasUnhandledResponses() );

		$this->clusterProxy->sendAsyncRequest( $this->getRequest() );

		$this->assertTrue( $this->clusterProxy->hasUnhandledResponses() );
	}

	private function getRequest( ?callable $responseCallback = null ) : ProvidesRequestData
	{
		$request = new PostRequest(
			__DIR__ . '/Workers/worker.php',
			http_build_query( ['test-key' => 'ClusterProxyTest'] )
		);

		if ( null !== $responseCallback )
		{
			$request->addResponseCallbacks( $responseCallback );
		}

		return $request;
	}

	/**
	 * @throws ConnectException
	 * @throws ExpectationFailedException
	 * @throws InvalidArgumentException
	 * @throws TimedoutException
	 * @throws WriteFailedException
	 * @throws ReadFailedException
	 */
	public function testWaitForResponses() : void
	{
		$responses        = [];
		$responseCallback = static function ( ProvidesResponseData $response ) use ( &$responses )
		{
			$responses[] = $response->getBody();
		};

		$expectedResponses = [
			'ClusterProxyTest-network-socket',
			'ClusterProxyTest-unix-domain-socket',
		];

		$this->clusterProxy->sendAsyncRequest( $this->getRequest( $responseCallback ) );

		$this->clusterProxy->waitForResponses();

		$this->assertEquals( $expectedResponses, $responses );
	}

	/**
	 * @throws ConnectException
	 * @throws ExpectationFailedException
	 * @throws InvalidArgumentException
	 * @throws ReadFailedException
	 * @throws TimedoutException
	 * @throws WriteFailedException
	 */
	public function testHandleReadyResponses() : void
	{
		$responses        = [];
		$responseCallback = static function ( ProvidesResponseData $response ) use ( &$responses )
		{
			$responses[] = $response->getBody();
		};

		$expectedResponses = [
			'ClusterProxyTest-network-socket',
			'ClusterProxyTest-unix-domain-socket',
		];

		$this->clusterProxy->sendAsyncRequest( $this->getRequest( $responseCallback ) );

		while ( $this->clusterProxy->hasUnhandledResponses() )
		{
			$this->clusterProxy->handleReadyResponses();
		}

		$this->assertEquals( $expectedResponses, $responses );
	}

	/**
	 * @throws ConnectException
	 * @throws ExpectationFailedException
	 * @throws InvalidArgumentException
	 * @throws ReadFailedException
	 * @throws TimedoutException
	 * @throws WriteFailedException
	 * @throws \Throwable
	 */
	public function testReadReadyResponses() : void
	{
		$responses         = [];
		$expectedResponses = [
			'ClusterProxyTest-network-socket',
			'ClusterProxyTest-unix-domain-socket',
		];

		$this->clusterProxy->sendAsyncRequest( $this->getRequest() );

		while ( $this->clusterProxy->hasUnhandledResponses() )
		{
			foreach ( $this->clusterProxy->readReadyResponses() as $response )
			{
				$responses[] = $response->getBody();
			}
		}

		$this->assertEquals( $expectedResponses, $responses );
	}

	/**
	 * @throws ConnectException
	 * @throws ExpectationFailedException
	 * @throws InvalidArgumentException
	 * @throws ReadFailedException
	 * @throws TimedoutException
	 * @throws WriteFailedException
	 */
	public function testEveryRequestIsSentToAllNodes() : void
	{
		$responses        = [];
		$responseCallback = static function ( ProvidesResponseData $response ) use ( &$responses )
		{
			$responses[] = $response->getBody();
		};

		$expectedResponses = [
			'ClusterProxyTest-network-socket',
			'ClusterProxyTest-unix-domain-socket',
		];

		$this->clusterProxy->sendAsyncRequest( $this->getRequest( $responseCallback ) );

		$this->clusterProxy->waitForResponses();

		$this->assertEquals( $expectedResponses, $responses );

		# Send the same request again and expect a response from all nodes again

		$expectedResponses[] = 'ClusterProxyTest-network-socket';
		$expectedResponses[] = 'ClusterProxyTest-unix-domain-socket';

		$this->clusterProxy->sendAsyncRequest( $this->getRequest( $responseCallback ) );

		$this->clusterProxy->waitForResponses();

		$this->assertEquals( $expectedResponses, $responses );
	}
}
