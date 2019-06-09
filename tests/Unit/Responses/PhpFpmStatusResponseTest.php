<?php declare(strict_types=1);

namespace hollodotme\FastCGI\Tests\Unit\Responses;

use DateTimeImmutable;
use Exception;
use hollodotme\FastCGI\Interfaces\ProvidesResponseData;
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
		$statusResponse = new PhpFpmStatusResponse( $response );

		$this->assertSame( $response->getRequestId(), $statusResponse->getRequestId() );
		$this->assertSame( $response->getHeaders(), $statusResponse->getHeaders() );
		$this->assertSame( $response->getHeader( 'X-Powered-By' ), $statusResponse->getHeader( 'X-Powered-By' ) );
		$this->assertSame( $response->getBody(), $statusResponse->getBody() );
		$this->assertSame( $response->getError(), $statusResponse->getError() );
		$this->assertSame( $response->getRawResponse(), $statusResponse->getRawResponse() );
		$this->assertSame( $response->getOutput(), $statusResponse->getOutput() );
		$this->assertSame( $response->getDuration(), $statusResponse->getDuration() );
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
				       . "slow requests:        10\n";
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
				       . "slow requests:        10\n";
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

	/**
	 * @throws ExpectationFailedException
	 * @throws InvalidArgumentException
	 * @throws Exception
	 */
	public function testCanGetParsedStatusData() : void
	{
		$statusResponse = new PhpFpmStatusResponse( $this->getResponseMock() );

		$this->assertSame( 'network', $statusResponse->getPoolName() );
		$this->assertSame( 'dynamic', $statusResponse->getProcessManager() );
		$this->assertEquals( new DateTimeImmutable( '08/Jun/2019:16:39:36 +0000' ), $statusResponse->getStartTime() );
		$this->assertSame( 88501, $statusResponse->getStartSince() );
		$this->assertSame( 1383, $statusResponse->getAcceptedConnections() );
		$this->assertSame( 6, $statusResponse->getListenQueue() );
		$this->assertSame( 1, $statusResponse->getMaxListenQueue() );
		$this->assertSame( 128, $statusResponse->getListenQueueLength() );
		$this->assertSame( 2, $statusResponse->getIdleProcesses() );
		$this->assertSame( 1, $statusResponse->getActiveProcesses() );
		$this->assertSame( 3, $statusResponse->getTotalProcesses() );
		$this->assertSame( 21, $statusResponse->getMaxActiveProcesses() );
		$this->assertSame( 4, $statusResponse->getMaxChildrenReached() );
		$this->assertSame( 10, $statusResponse->getSlowRequests() );
	}
}
