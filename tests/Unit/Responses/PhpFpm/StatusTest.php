<?php declare(strict_types=1);

namespace hollodotme\FastCGI\Tests\Unit\Responses\PhpFpm;

use DateTimeImmutable;
use hollodotme\FastCGI\Responses\PhpFpm\Status;
use PHPUnit\Framework\TestCase;

final class StatusTest extends TestCase
{
	/**
	 * @throws \PHPUnit\Framework\ExpectationFailedException
	 * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
	 */
	public function testCanGetValuesFromGetters() : void
	{
		$status = new Status(
			[
				'pool'                 => 'network',
				'process manager'      => 'dynamic',
				'start time'           => new DateTimeImmutable( '08/Jun/2019:16:39:36 +0000' ),
				'start since'          => 88501,
				'accepted conn'        => 1383,
				'listen queue'         => 6,
				'max listen queue'     => 7,
				'listen queue len'     => 128,
				'idle processes'       => 2,
				'active processes'     => 1,
				'total processes'      => 3,
				'max active processes' => 21,
				'max children reached' => 4,
				'slow requests'        => 10,
			]
		);

		$this->assertSame( 'network', $status->getPoolName() );
		$this->assertSame( 'dynamic', $status->getProcessManager() );
		$this->assertEquals( new DateTimeImmutable( '08/Jun/2019:16:39:36 +0000' ), $status->getStartTime() );
		$this->assertSame( 88501, $status->getStartSince() );
		$this->assertSame( 1383, $status->getAcceptedConnections() );
		$this->assertSame( 6, $status->getListenQueue() );
		$this->assertSame( 7, $status->getMaxListenQueue() );
		$this->assertSame( 128, $status->getListenQueueLength() );
		$this->assertSame( 2, $status->getIdleProcesses() );
		$this->assertSame( 1, $status->getActiveProcesses() );
		$this->assertSame( 3, $status->getTotalProcesses() );
		$this->assertSame( 21, $status->getMaxActiveProcesses() );
		$this->assertSame( 4, $status->getMaxChildrenReached() );
		$this->assertSame( 10, $status->getSlowRequests() );
	}
}
