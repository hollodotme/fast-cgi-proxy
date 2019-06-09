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
	/** @var Cluster */
	private $cluster;

	protected function setUp() : void
	{
		$this->cluster = new Cluster();
	}

	protected function tearDown() : void
	{
		$this->cluster = null;
	}

	/**
	 * @throws Exception
	 * @throws ExpectationFailedException
	 * @throws InvalidArgumentException
	 */
	public function testAddConnections() : void
	{
		$this->cluster->addConnections(
			new NetworkSocket( 'localhost', 9000 ),
			new NetworkSocket( 'localhost', 9001 ),
			new UnixDomainSocket( '/var/run/php/fpm.sock' )
		);

		$this->assertCount( 3, $this->cluster );
	}

	/**
	 * @throws ExpectationFailedException
	 * @throws InvalidArgumentException
	 */
	public function testGetIterator() : void
	{
		$networkSocket    = new NetworkSocket( 'localhost', 9000 );
		$unixDomainSocket = new UnixDomainSocket( '/var/run/php/fpm.sock' );

		$this->cluster->addConnections( $networkSocket, $unixDomainSocket );

		$expectedConnections = [$networkSocket, $unixDomainSocket];

		$connections = iterator_to_array( $this->cluster->getIterator(), false );

		$this->assertSame( $expectedConnections, $connections );
	}

	/**
	 * @throws Exception
	 * @throws ExpectationFailedException
	 * @throws InvalidArgumentException
	 */
	public function testCount() : void
	{
		$this->assertSame( 0, $this->cluster->count() );
		$this->assertCount( 0, $this->cluster );
	}
}
