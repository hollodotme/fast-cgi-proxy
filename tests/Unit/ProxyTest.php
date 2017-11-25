<?php declare(strict_types=1);
/*
 * @author hollodotme
 */

namespace hollodotme\FastCGI\Tests\Unit;

use hollodotme\FastCGI\Client;
use hollodotme\FastCGI\Collections\RoundRobin;
use hollodotme\FastCGI\Proxy;
use hollodotme\FastCGI\Requests\PostRequest;
use hollodotme\FastCGI\SocketConnections\NetworkSocket;
use hollodotme\FastCGI\SocketConnections\UnixDomainSocket;
use PHPUnit\Framework\TestCase;

/**
 * Class ProxyTest
 * @package hollodotme\FastCGI\Tests\Unit
 */
final class ProxyTest extends TestCase
{
	/**
	 * @expectedException \hollodotme\FastCGI\Exceptions\ConnectException
	 */
	public function testConnectAttemptToNotExistingSocketThrowsException() : void
	{
		$collection = new RoundRobin();
		$collection->add(
			new UnixDomainSocket( '/tmp/not/existing.sock' )
		);

		$proxy = new Proxy( $collection );

		$proxy->sendRequest( new PostRequest( '/path/to/script.php', '' ) );
	}

	/**
	 * @expectedException \hollodotme\FastCGI\Exceptions\ConnectException
	 */
	public function testConnectAttemptToInvalidSocketThrowsException() : void
	{
		$testSocket = realpath( __DIR__ . '/Fixtures/test.sock' );
		$collection = new RoundRobin();
		$collection->add( new UnixDomainSocket( $testSocket ) );

		$proxy = new Proxy( $collection );

		$proxy->sendRequest( new PostRequest( '/path/to/script.php', '' ) );
	}

	/**
	 * @expectedException \hollodotme\FastCGI\Exceptions\ReadFailedException
	 * @expectedExceptionMessage Client not found for request ID: 12345
	 */
	public function testWaitingForUnknownRequestThrowsException() : void
	{
		$collection = new RoundRobin();
		$collection->add( new NetworkSocket( '127.0.0.1', 9001 ) );

		$proxy = new Proxy( $collection );

		$proxy->waitForResponse( 12345 );
	}

	/**
	 * @expectedException \hollodotme\FastCGI\Exceptions\ReadFailedException
	 * @expectedExceptionMessage No pending requests found.
	 */
	public function testWaitingForResponsesWithoutRequestsThrowsException() : void
	{
		$connection = new NetworkSocket( '127.0.0.1', 9001 );
		$client     = new Client( $connection );

		$client->waitForResponses();
	}

	/**
	 * @expectedException \hollodotme\FastCGI\Exceptions\ReadFailedException
	 * @expectedExceptionMessage Client not found for request ID: 12345
	 */
	public function testHandlingUnknownRequestThrowsException() : void
	{
		$collection = new RoundRobin();
		$collection->add( new NetworkSocket( '127.0.0.1', 9001 ) );

		$proxy = new Proxy( $collection );

		$proxy->handleResponse( 12345 );
	}

	/**
	 * @expectedException \hollodotme\FastCGI\Exceptions\ReadFailedException
	 * @expectedExceptionMessage Client not found for request ID: 12345
	 */
	public function testHandlingUnknownRequestsThrowsException() : void
	{
		$collection = new RoundRobin();
		$collection->add( new NetworkSocket( '127.0.0.1', 9001 ) );

		$proxy = new Proxy( $collection );

		$proxy->handleResponses( null, 12345, 12346 );
	}
}
