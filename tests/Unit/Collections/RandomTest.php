<?php declare(strict_types=1);

namespace hollodotme\FastCGI\Tests\Unit\Collections;

use hollodotme\FastCGI\Client;
use hollodotme\FastCGI\Collections\Random;
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
	 * @throws Exception
	 * @throws ExpectationFailedException
	 * @throws InvalidArgumentException
	 * @throws \Exception
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

		$random = Random::fromConnections( $networkSocket, $unixDomainSocket );

		$networkSocketClient    = new Client( $networkSocket );
		$unixDomainSocketClient = new Client( $unixDomainSocket );

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
