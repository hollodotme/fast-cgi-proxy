<?php declare(strict_types=1);

namespace hollodotme\FastCGI\Tests\Unit\Collections;

use hollodotme\FastCGI\Client;
use hollodotme\FastCGI\Collections\Random;
use hollodotme\FastCGI\Exceptions\ClientNotFoundException;
use hollodotme\FastCGI\Exceptions\MissingConnectionsException;
use hollodotme\FastCGI\SocketConnections\NetworkSocket;
use hollodotme\FastCGI\SocketConnections\UnixDomainSocket;
use hollodotme\FastCGI\Tests\Traits\SocketDataProviding;
use PHPUnit\Framework\Exception;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;
use SebastianBergmann\RecursionContext\InvalidArgumentException;
use function method_exists;

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
		$random->getNextClient();
	}

	/**
	 * @throws ClientNotFoundException
	 * @throws Exception
	 * @throws ExpectationFailedException
	 * @throws InvalidArgumentException
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

		$random->addConnections( $networkSocket, $unixDomainSocket );

		$this->assertContainsEqualObjects(
			$random->getNextClient(),
			[$networkSocketClient, $unixDomainSocketClient]
		);
	}

	/**
	 * @param          $needle
	 * @param iterable $haystack
	 * @param string   $message
	 *
	 * @throws Exception
	 * @throws ExpectationFailedException
	 * @throws InvalidArgumentException
	 */
	private function assertContainsEqualObjects( $needle, iterable $haystack, string $message = '' ) : void
	{
		/** @noinspection ClassMemberExistenceCheckInspection */
		if ( method_exists( $this, 'assertContainsEquals' ) )
		{
			$this->assertContainsEquals( $needle, $haystack, $message );

			return;
		}

		$this->assertContains( $needle, $haystack, $message, false, false );
	}
}
