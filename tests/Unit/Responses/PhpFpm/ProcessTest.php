<?php declare(strict_types=1);

namespace hollodotme\FastCGI\Tests\Unit\Responses\PhpFpm;

use DateTimeImmutable;
use hollodotme\FastCGI\Responses\PhpFpm\Process;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;
use SebastianBergmann\RecursionContext\InvalidArgumentException;

final class ProcessTest extends TestCase
{
	/**
	 * @throws ExpectationFailedException
	 * @throws InvalidArgumentException
	 */
	public function testCanGetValuesFromGetters() : void
	{
		$process = new Process(
			[
				'pid'                 => 9,
				'state'               => 'Running',
				'start time'          => new DateTimeImmutable( '10/Jun/2019:14:56:45 +0000' ),
				'start since'         => 6035,
				'requests'            => 112,
				'request duration'    => 163,
				'request method'      => 'GET',
				'request URI'         => '/status?full',
				'content length'      => 0,
				'user'                => 'tester',
				'script'              => 'test-script.php',
				'last request cpu'    => 0.12,
				'last request memory' => 12345,
			]
		);

		$this->assertSame( 9, $process->getPid() );
		$this->assertSame( 'Running', $process->getState() );
		$this->assertEquals( new DateTimeImmutable( '10/Jun/2019:14:56:45 +0000' ), $process->getStartTime() );
		$this->assertSame( 6035, $process->getStartSince() );
		$this->assertSame( 112, $process->getRequests() );
		$this->assertSame( 163, $process->getRequestDuration() );
		$this->assertSame( 'GET', $process->getRequestMethod() );
		$this->assertSame( '/status?full', $process->getRequestUri() );
		$this->assertSame( 0, $process->getContentLength() );
		$this->assertSame( 'tester', $process->getUser() );
		$this->assertSame( 'test-script.php', $process->getScript() );
		$this->assertSame( 0.12, $process->getLastRequestCpu() );
		$this->assertSame( 12345, $process->getLastRequestMemory() );
	}

	/**
	 * @throws ExpectationFailedException
	 * @throws InvalidArgumentException
	 */
	public function testCanGetDefaultValuesFromEmptyArray() : void
	{
		$process = new Process( [] );

		$this->assertSame( 0, $process->getPid() );
		$this->assertSame( '-', $process->getState() );
		$this->assertEquals( new DateTimeImmutable( '01/Jan/1970:00:00:00 +0000' ), $process->getStartTime() );
		$this->assertSame( 0, $process->getStartSince() );
		$this->assertSame( 0, $process->getRequests() );
		$this->assertSame( 0, $process->getRequestDuration() );
		$this->assertSame( '-', $process->getRequestMethod() );
		$this->assertSame( '-', $process->getRequestUri() );
		$this->assertSame( 0, $process->getContentLength() );
		$this->assertSame( '-', $process->getUser() );
		$this->assertSame( '-', $process->getScript() );
		$this->assertSame( 0.0, $process->getLastRequestCpu() );
		$this->assertSame( 0, $process->getLastRequestMemory() );
	}
}
