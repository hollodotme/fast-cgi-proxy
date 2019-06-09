<?php declare(strict_types=1);

namespace hollodotme\FastCGI\Tests\Unit;

use hollodotme\FastCGI\Collections\RoundRobin;
use hollodotme\FastCGI\Exceptions\ConnectException;
use hollodotme\FastCGI\Exceptions\ReadFailedException;
use hollodotme\FastCGI\Exceptions\TimedoutException;
use hollodotme\FastCGI\Exceptions\WriteFailedException;
use hollodotme\FastCGI\Proxy;
use hollodotme\FastCGI\Requests\PostRequest;
use hollodotme\FastCGI\SocketConnections\NetworkSocket;
use hollodotme\FastCGI\SocketConnections\UnixDomainSocket;
use hollodotme\FastCGI\Tests\Traits\SocketDataProviding;
use PHPUnit\Framework\TestCase;
use Throwable;

final class ProxyTest extends TestCase
{
	use SocketDataProviding;

	/**
	 * @throws ConnectException
	 * @throws ReadFailedException
	 * @throws Throwable
	 * @throws TimedoutException
	 * @throws WriteFailedException
	 */
	public function testConnectAttemptToNotExistingSocketThrowsException() : void
	{
		$collection = new RoundRobin();
		$collection->addConnections(
			new UnixDomainSocket( '/tmp/not/existing.sock' )
		);

		$proxy = new Proxy( $collection );

		$this->expectException( ConnectException::class );

		/** @noinspection UnusedFunctionResultInspection */
		$proxy->sendRequest( new PostRequest( '/path/to/script.php', '' ) );
	}

	/**
	 * @throws ConnectException
	 * @throws ReadFailedException
	 * @throws Throwable
	 * @throws TimedoutException
	 * @throws WriteFailedException
	 */
	public function testConnectAttemptToInvalidSocketThrowsException() : void
	{
		$testSocket = realpath( __DIR__ . '/Fixtures/test.sock' );
		$collection = new RoundRobin();
		$collection->addConnections( new UnixDomainSocket( $testSocket ) );

		$proxy = new Proxy( $collection );

		$this->expectException( ConnectException::class );

		/** @noinspection UnusedFunctionResultInspection */
		$proxy->sendRequest( new PostRequest( '/path/to/script.php', '' ) );
	}

	/**
	 * @throws ReadFailedException
	 */
	public function testWaitingForUnknownRequestThrowsException() : void
	{
		$collection = new RoundRobin();
		$collection->addConnections(
			new NetworkSocket(
				$this->getNetworkSocketHost(),
				$this->getNetworkSocketPort()
			)
		);

		$proxy = new Proxy( $collection );

		$this->expectException( ReadFailedException::class );
		$this->expectExceptionMessage( 'Client not found for request ID: 12345' );

		$proxy->waitForResponse( 12345 );
	}

	/**
	 * @throws ReadFailedException
	 */
	public function testHandlingUnknownRequestThrowsException() : void
	{
		$collection = new RoundRobin();
		$collection->addConnections(
			new NetworkSocket(
				$this->getNetworkSocketHost(),
				$this->getNetworkSocketPort()
			)
		);

		$proxy = new Proxy( $collection );

		$this->expectException( ReadFailedException::class );
		$this->expectExceptionMessage( 'Client not found for request ID: 12345' );

		$proxy->handleResponse( 12345 );
	}

	/**
	 * @throws ReadFailedException
	 */
	public function testHandlingUnknownRequestsThrowsException() : void
	{
		$collection = new RoundRobin();
		$collection->addConnections(
			new NetworkSocket(
				$this->getNetworkSocketHost(),
				$this->getNetworkSocketPort()
			)
		);

		$proxy = new Proxy( $collection );

		$this->expectException( ReadFailedException::class );
		$this->expectExceptionMessage( 'Client not found for request ID: 12345' );

		$proxy->handleResponses( null, 12345, 12346 );
	}
}
