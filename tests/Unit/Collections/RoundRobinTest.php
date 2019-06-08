<?php declare(strict_types=1);

namespace hollodotme\FastCGI\Tests\Unit\Collections;

use hollodotme\FastCGI\Client;
use hollodotme\FastCGI\Collections\RoundRobin;
use hollodotme\FastCGI\Exceptions\ClientNotFoundException;
use hollodotme\FastCGI\Exceptions\MissingConnectionsException;
use hollodotme\FastCGI\SocketConnections\NetworkSocket;
use hollodotme\FastCGI\SocketConnections\UnixDomainSocket;
use hollodotme\FastCGI\Tests\Traits\SocketDataProviding;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;

final class RoundRobinTest extends TestCase
{
	use SocketDataProviding;

	/**
	 * @throws ClientNotFoundException
	 * @throws MissingConnectionsException
	 */
	public function testThrowsExceptionWhenAttemptToGetClientFromEmptyCollection() : void
	{
		$roundRobin = new RoundRobin();

		$this->expectException( MissingConnectionsException::class );

		/** @noinspection UnusedFunctionResultInspection */
		$roundRobin->getClient();
	}

	/**
	 * @throws MissingConnectionsException
	 * @throws ExpectationFailedException
	 * @throws ClientNotFoundException
	 */
	public function testCanAddConnections() : void
	{
		$roundRobin       = new RoundRobin();
		$networkSocket    = new NetworkSocket(
			$this->getNetworkSocketHost(),
			$this->getNetworkSocketPort()
		);
		$unixDomainSocket = new UnixDomainSocket(
			$this->getUnixDomainSocket()
		);

		$networkSocketClient    = new Client( $networkSocket );
		$unixDomainSocketClient = new Client( $unixDomainSocket );

		$roundRobin->add( $networkSocket, $unixDomainSocket );

		$this->assertEquals( $networkSocketClient, $roundRobin->getClient() );
		$this->assertEquals( $unixDomainSocketClient, $roundRobin->getClient() );
		$this->assertEquals( $networkSocketClient, $roundRobin->getClient() );
		$this->assertEquals( $unixDomainSocketClient, $roundRobin->getClient() );
	}
}
