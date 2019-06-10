<?php declare(strict_types=1);

namespace hollodotme\FastCGI\Tests\Integration;

use hollodotme\FastCGI\ClusterProxy;
use hollodotme\FastCGI\Collections\Cluster;
use hollodotme\FastCGI\Exceptions\ConnectException;
use hollodotme\FastCGI\Exceptions\ReadFailedException;
use hollodotme\FastCGI\Exceptions\TimedoutException;
use hollodotme\FastCGI\Exceptions\WriteFailedException;
use hollodotme\FastCGI\Responses\PhpFpmStatusResponse;
use hollodotme\FastCGI\SocketConnections\NetworkSocket;
use hollodotme\FastCGI\SocketConnections\UnixDomainSocket;
use hollodotme\FastCGI\Tests\Traits\SocketDataProviding;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;
use SebastianBergmann\RecursionContext\InvalidArgumentException;
use Throwable;

final class ClusterStatusTest extends TestCase
{
	use SocketDataProviding;

	/** @var ClusterProxy */
	private $clusterProxy;

	protected function setUp() : void
	{
		$cluster = Cluster::fromConnections(
			new NetworkSocket(
				$this->getNetworkSocketHost(),
				$this->getNetworkSocketPort()
			),
			new UnixDomainSocket(
				$this->getUnixDomainSocket()
			)
		);

		$this->clusterProxy = new ClusterProxy( $cluster );
	}

	protected function tearDown() : void
	{
		$this->clusterProxy = null;
	}

	/**
	 * @throws ExpectationFailedException
	 * @throws InvalidArgumentException
	 * @throws Throwable
	 * @throws ConnectException
	 * @throws ReadFailedException
	 * @throws TimedoutException
	 * @throws WriteFailedException
	 */
	public function testGetStatus() : void
	{
		$statusResponses = $this->clusterProxy->getStatus(
			'/status',
			PhpFpmStatusResponse::class
		);

		$this->assertContainsOnlyInstancesOf( PhpFpmStatusResponse::class, $statusResponses );

		$expectedPoolNames = ['network', 'uds'];
		$poolNames         = [
			$statusResponses[0]->getStatus()->getPoolName(),
			$statusResponses[1]->getStatus()->getPoolName(),
		];

		$this->assertSame( $expectedPoolNames, $poolNames );
	}
}
