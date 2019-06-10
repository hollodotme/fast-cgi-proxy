<?php declare(strict_types=1);

namespace hollodotme\FastCGI\Tests\Unit\Responses;

use DateTimeImmutable;
use Exception;
use hollodotme\FastCGI\Interfaces\ConfiguresSocketConnection;
use hollodotme\FastCGI\Interfaces\ProvidesResponseData;
use hollodotme\FastCGI\Responses\PhpFpm\Process;
use hollodotme\FastCGI\Responses\PhpFpm\Status;
use hollodotme\FastCGI\Responses\PhpFpmStatusResponse;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;
use SebastianBergmann\RecursionContext\InvalidArgumentException;

final class PhpFpmStatusResponseTest extends TestCase
{
	/**
	 * @throws ExpectationFailedException
	 * @throws InvalidArgumentException
	 * @throws Exception
	 */
	public function testCanGetValuesFromOriginalResponse() : void
	{
		$response       = $this->getResponseMock();
		$connection     = $this->getConnectionMock();
		$statusResponse = new PhpFpmStatusResponse( $response, $connection );

		$this->assertSame( $response, $statusResponse->getResponse() );
	}

	private function getResponseMock() : ProvidesResponseData
	{
		return new class implements ProvidesResponseData
		{
			public function getRequestId() : int
			{
				return 12345;
			}

			public function getHeaders() : array
			{
				return [
					'X-Powered-By'  => 'PHP/7.3.6',
					'Expires'       => 'Thu, 01 Jan 1970 00:00:00 GMT',
					'Cache-Control' => 'no-cache, no-store, must-revalidate, max-age=0',
					'Content-type'  => 'text/plain;charset=UTF-8',
				];
			}

			public function getHeader( string $headerKey ) : string
			{
				return $this->getHeaders()[ $headerKey ] ?? '';
			}

			public function getBody() : string
			{
				return "pool:                 network\n"
				       . "process manager:      dynamic\n"
				       . "start time:           08/Jun/2019:16:39:36 +0000\n"
				       . "start since:          88501\n"
				       . "accepted conn:        1383\n"
				       . "listen queue:         6\n"
				       . "max listen queue:     1\n"
				       . "listen queue len:     128\n"
				       . "idle processes:       2\n"
				       . "active processes:     1\n"
				       . "total processes:      3\n"
				       . "max active processes: 21\n"
				       . "max children reached: 4\n"
				       . "slow requests:        10\n"
				       . "\n"
				       . "************************\n"
				       . "pid:                  9\n"
				       . "state:                Running\n"
				       . "start time:           10/Jun/2019:14:56:45 +0000\n"
				       . "start since:          6035\n"
				       . "requests:             112\n"
				       . "request duration:     163\n"
				       . "request method:       GET\n"
				       . "request URI:          /status?full\n"
				       . "content length:       0\n"
				       . "user:                 tester\n"
				       . "script:               test-script.php\n"
				       . "last request cpu:     0.12\n"
				       . "last request memory:  12345\n"
				       . "\n"
				       . "************************\n"
				       . "pid:                  10\n"
				       . "state:                Idle\n"
				       . "start time:           10/Jun/2019:14:56:45 +0000\n"
				       . "start since:          6035\n"
				       . "requests:             114\n"
				       . "request duration:     206\n"
				       . "request method:       -\n"
				       . "request URI:          -\n"
				       . "content length:       0\n"
				       . "user:                 -\n"
				       . "script:               -\n"
				       . "last request cpu:     0.00\n"
				       . "last request memory:  2097152\n";
			}

			public function getRawResponse() : string
			{
				return $this->getOutput();
			}

			public function getOutput() : string
			{
				return "X-Powered-By: PHP/7.3.6\n"
				       . "Expires: Thu, 01 Jan 1970 00:00:00 GMT\n"
				       . "Cache-Control: no-cache, no-store, must-revalidate, max-age=0\n"
				       . "Content-type: text/plain;charset=UTF-8\n"
				       . "\n"
				       . "pool:                 network\n"
				       . "process manager:      dynamic\n"
				       . "start time:           08/Jun/2019:16:39:36 +0000\n"
				       . "start since:          88501\n"
				       . "accepted conn:        1383\n"
				       . "listen queue:         6\n"
				       . "max listen queue:     1\n"
				       . "listen queue len:     128\n"
				       . "idle processes:       2\n"
				       . "active processes:     1\n"
				       . "total processes:      3\n"
				       . "max active processes: 21\n"
				       . "max children reached: 4\n"
				       . "slow requests:        10\n"
				       . "\n"
				       . "************************\n"
				       . "pid:                  9\n"
				       . "state:                Running\n"
				       . "start time:           10/Jun/2019:14:56:45 +0000\n"
				       . "start since:          6035\n"
				       . "requests:             112\n"
				       . "request duration:     163\n"
				       . "request method:       GET\n"
				       . "request URI:          /status?full\n"
				       . "content length:       0\n"
				       . "user:                 tester\n"
				       . "script:               test-script.php\n"
				       . "last request cpu:     0.12\n"
				       . "last request memory:  12345\n"
				       . "\n"
				       . "************************\n"
				       . "pid:                  10\n"
				       . "state:                Idle\n"
				       . "start time:           10/Jun/2019:14:56:45 +0000\n"
				       . "start since:          6035\n"
				       . "requests:             114\n"
				       . "request duration:     206\n"
				       . "request method:       -\n"
				       . "request URI:          -\n"
				       . "content length:       0\n"
				       . "user:                 -\n"
				       . "script:               -\n"
				       . "last request cpu:     0.00\n"
				       . "last request memory:  2097152\n";
			}

			public function getError() : string
			{
				return 'ERROR';
			}

			public function getDuration() : float
			{
				return 0.12345;
			}
		};
	}

	private function getConnectionMock() : ConfiguresSocketConnection
	{
		return new class implements ConfiguresSocketConnection
		{
			public function getSocketAddress() : string
			{
				return 'tcp://127.0.0.1:9000';
			}

			public function getConnectTimeout() : int
			{
				return 5000;
			}

			public function getReadWriteTimeout() : int
			{
				return 5000;
			}
		};
	}

	/**
	 * @throws ExpectationFailedException
	 * @throws InvalidArgumentException
	 * @throws Exception
	 */
	public function testCanGetParsedStatusData() : void
	{
		$statusResponse = new PhpFpmStatusResponse(
			$this->getResponseMock(),
			$this->getConnectionMock()
		);

		$expectedStatus = new Status(
			[
				'pool'                 => 'network',
				'process manager'      => 'dynamic',
				'start time'           => new DateTimeImmutable( '08/Jun/2019:16:39:36 +0000' ),
				'start since'          => 88501,
				'accepted conn'        => 1383,
				'listen queue'         => 6,
				'max listen queue'     => 1,
				'listen queue len'     => 128,
				'idle processes'       => 2,
				'active processes'     => 1,
				'total processes'      => 3,
				'max active processes' => 21,
				'max children reached' => 4,
				'slow requests'        => 10,
			]
		);

		$this->assertEquals( $expectedStatus, $statusResponse->getStatus() );
	}

	/**
	 * @throws ExpectationFailedException
	 * @throws InvalidArgumentException
	 * @throws Exception
	 */
	public function testCanGetConnectionInfo() : void
	{
		$connection     = $this->getConnectionMock();
		$statusResponse = new PhpFpmStatusResponse( $this->getResponseMock(), $connection );

		$this->assertSame( $connection, $statusResponse->getConnection() );
	}

	/**
	 * @throws ExpectationFailedException
	 * @throws InvalidArgumentException
	 * @throws Exception
	 */
	public function testCanGetProcesses() : void
	{
		$statusResponse = new PhpFpmStatusResponse(
			$this->getResponseMock(),
			$this->getConnectionMock()
		);

		$expectedProcesses = [
			new Process(
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
			),
			new Process(
				[
					'pid'                 => 10,
					'state'               => 'Idle',
					'start time'          => new DateTimeImmutable( '10/Jun/2019:14:56:45 +0000' ),
					'start since'         => 6035,
					'requests'            => 114,
					'request duration'    => 206,
					'request method'      => '-',
					'request URI'         => '-',
					'content length'      => 0,
					'user'                => '-',
					'script'              => '-',
					'last request cpu'    => 0.00,
					'last request memory' => 2097152,
				]
			),
		];

		$this->assertEquals( $expectedProcesses, $statusResponse->getProcesses() );
	}
}
