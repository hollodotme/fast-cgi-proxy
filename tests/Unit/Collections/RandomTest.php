<?php declare(strict_types=1);

namespace hollodotme\FastCGI\Tests\Unit\Collections;

use hollodotme\FastCGI\Client;
use hollodotme\FastCGI\Collections\Random;
use hollodotme\FastCGI\Exceptions\ClientNotFoundException;
use hollodotme\FastCGI\Exceptions\MissingConnectionsException;
use hollodotme\FastCGI\SocketConnections\NetworkSocket;
use hollodotme\FastCGI\SocketConnections\UnixDomainSocket;
use hollodotme\FastCGI\Tests\Traits\SocketDataProviding;
use PHPUnit\Framework\TestCase;

final class RandomTest extends TestCase
{
	use SocketDataProviding;

	/**
	 * @throws MissingConnectionsException
	 * @throws ClientNotFoundException
	 */
	public function testThrowsExceptionWhenAttemptToGetClientFromEmptyCollection() : void
	{
		$random = new Random();

		$this->expectException( MissingConnectionsException::class );

		/** @noinspection UnusedFunctionResultInspection */
		$random->getClient();
	}

	/**
	 * @throws ClientNotFoundException
	 * @throws MissingConnectionsException
	 */
	public function testCanAddConnections() : void
	{
		$random           = new Random();
		$networkSocket    = new NetworkSocket(
			$this->getNetworkSocketHost(),
			$this->getNetworkSocketPort()
		);
		$unixDomainSocket = new UnixDomainSocket(
			$this->getUnixDomainSocket()
		);

		$networkSocketClient    = new Client( $networkSocket );
		$unixDomainSocketClient = new Client( $unixDomainSocket );

		$random->add( $networkSocket, $unixDomainSocket );

		$this->assertContainsEquals(
			$random->getClient(),
			[$networkSocketClient, $unixDomainSocketClient]
		);
	}
}
