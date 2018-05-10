<?php declare(strict_types=1);
/**
 * @author hollodotme
 */

namespace hollodotme\FastCGI\Tests\Unit\Collections;

use hollodotme\FastCGI\Client;
use hollodotme\FastCGI\Collections\Random;
use hollodotme\FastCGI\Exceptions\MissingConnectionsException;
use hollodotme\FastCGI\SocketConnections\NetworkSocket;
use hollodotme\FastCGI\SocketConnections\UnixDomainSocket;
use PHPUnit\Framework\TestCase;
use function in_array;

/**
 * Class RandomTest
 * @package hollodotme\FastCGI\Tests\Unit\Collections
 */
final class RandomTest extends TestCase
{
	/**
	 * @throws MissingConnectionsException
	 * @throws \hollodotme\FastCGI\Exceptions\ClientNotFoundException
	 */
	public function testThrowsExceptionWhenAttemptToGetClientFromEmptyCollection() : void
	{
		$random = new Random();

		$this->expectException( MissingConnectionsException::class );

		$random->getClient();
	}

	/**
	 * @throws MissingConnectionsException
	 * @throws \PHPUnit\Framework\ExpectationFailedException
	 * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
	 * @throws \hollodotme\FastCGI\Exceptions\ClientNotFoundException
	 */
	public function testCanAddConnections() : void
	{
		$random      = new Random();
		$connection1 = new NetworkSocket( '127.0.0.1', 9001 );
		$connection2 = new UnixDomainSocket( '/var/run/php-uds.sock' );

		$client1 = new Client( $connection1 );
		$client2 = new Client( $connection2 );

		$random->add( $connection1, $connection2 );

		$this->assertInstanceOf( Client::class, $random->getClient() );
		$this->assertTrue( in_array( $random->getClient(), [$client1, $client2], false ) );
	}
}
