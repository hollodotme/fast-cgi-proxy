<?php declare(strict_types=1);

namespace hollodotme\FastCGI\Tests\Unit\Collections;

use hollodotme\FastCGI\Collections\Cluster;
use hollodotme\FastCGI\SocketConnections\NetworkSocket;
use hollodotme\FastCGI\SocketConnections\UnixDomainSocket;
use PHPUnit\Framework\Exception;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;
use SebastianBergmann\RecursionContext\InvalidArgumentException;
use function iterator_to_array;

final class ClusterTest extends TestCase
{
	/**
	 * @throws ExpectationFailedException
	 * @throws InvalidArgumentException
	 */
	public function testGetIterator() : void
	{
		$networkSocket    = new NetworkSocket( 'localhost', 9000 );
		$unixDomainSocket = new UnixDomainSocket( '/var/run/php/fpm.sock' );

		$cluster = Cluster::fromConnections( $networkSocket, $unixDomainSocket );

		$expectedConnections = [$networkSocket, $unixDomainSocket];

		$connections = iterator_to_array( $cluster->getIterator(), false );

		$this->assertSame( $expectedConnections, $connections );
	}

	/**
	 * @throws Exception
	 * @throws ExpectationFailedException
	 * @throws InvalidArgumentException
	 */
	public function testCount() : void
	{
		$cluster = Cluster::fromConnections(
			new NetworkSocket( 'localhost', 9000 ),
			new UnixDomainSocket( '/var/run/php/fpm.sock' )
		);

		$this->assertSame( 2, $cluster->count() );
		$this->assertCount( 2, $cluster );
	}
}
