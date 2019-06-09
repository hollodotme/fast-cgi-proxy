<?php declare(strict_types=1);

namespace hollodotme\FastCGI\Tests\Unit\Collections;

use hollodotme\FastCGI\Client;
use hollodotme\FastCGI\Collections\RoundRobin;
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
	 * @throws ExpectationFailedException
	 * @throws InvalidArgumentException
	 */
	public function testCanGetNextClient() : void
	{
		$networkSocket    = new NetworkSocket(
			$this->getNetworkSocketHost(),
			$this->getNetworkSocketPort()
		);
		$unixDomainSocket = new UnixDomainSocket(
			$this->getUnixDomainSocket()
		);

		$roundRobin = RoundRobin::fromConnections( $networkSocket, $unixDomainSocket );

		$networkSocketClient    = new Client( $networkSocket );
		$unixDomainSocketClient = new Client( $unixDomainSocket );

		$this->assertEquals( $networkSocketClient, $roundRobin->getNextClient() );
		$this->assertEquals( $unixDomainSocketClient, $roundRobin->getNextClient() );
		$this->assertEquals( $networkSocketClient, $roundRobin->getNextClient() );
		$this->assertEquals( $unixDomainSocketClient, $roundRobin->getNextClient() );
	}
}
