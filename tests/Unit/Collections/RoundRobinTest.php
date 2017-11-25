<?php declare(strict_types=1);
/**
 * @author hollodotme
 */

namespace hollodotme\FastCGI\Tests\Unit\Collections;

use hollodotme\FastCGI\Client;
use hollodotme\FastCGI\Collections\RoundRobin;
use hollodotme\FastCGI\Exceptions\MissingConnectionsException;
use hollodotme\FastCGI\SocketConnections\NetworkSocket;
use hollodotme\FastCGI\SocketConnections\UnixDomainSocket;
use PHPUnit\Framework\TestCase;

/**
 * Class RoundRobinTest
 * @package hollodotme\FastCGI\Tests\Unit\Collections
 */
final class RoundRobinTest extends TestCase
{
	public function testThrowsExceptionWhenAttemptToGetClientFromEmptyCollection() : void
	{
		$roundRobin = new RoundRobin();

		$this->expectException( MissingConnectionsException::class );

		$roundRobin->getClient();
	}

	public function testCanAddConnections() : void
	{
		$roundRobin  = new RoundRobin();
		$connection1 = new NetworkSocket( '127.0.0.1', 9001 );
		$connection2 = new UnixDomainSocket( '/var/run/php-uds.sock' );

		$client1 = new Client( $connection1 );
		$client2 = new Client( $connection2 );

		$roundRobin->add( $connection1, $connection2 );

		$this->assertInstanceOf( Client::class, $roundRobin->getClient() );
		$this->assertEquals( $client2, $roundRobin->getClient() );
		$this->assertEquals( $client1, $roundRobin->getClient() );
		$this->assertEquals( $client2, $roundRobin->getClient() );
	}
}
