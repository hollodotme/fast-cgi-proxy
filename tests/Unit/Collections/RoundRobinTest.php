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
use SebastianBergmann\RecursionContext\InvalidArgumentException;

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
		$roundRobin->getNextClient();
	}

	/**
	 * @throws ClientNotFoundException
	 * @throws ExpectationFailedException
	 * @throws MissingConnectionsException
	 * @throws InvalidArgumentException
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

		$roundRobin->addConnections( $networkSocket, $unixDomainSocket );

		$this->assertEquals( $networkSocketClient, $roundRobin->getNextClient() );
		$this->assertEquals( $unixDomainSocketClient, $roundRobin->getNextClient() );
		$this->assertEquals( $networkSocketClient, $roundRobin->getNextClient() );
		$this->assertEquals( $unixDomainSocketClient, $roundRobin->getNextClient() );
	}
}
