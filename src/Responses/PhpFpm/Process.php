<?php declare(strict_types=1);

namespace hollodotme\FastCGI\Responses\PhpFpm;

use DateTimeImmutable;

final class Process
{
	/** @var array */
	private $processData;

	public function __construct( array $processData )
	{
		$this->processData = $processData;
	}

	public function getPid() : int
	{
		return $this->processData['pid'] ?? 0;
	}

	public function getState() : string
	{
		return $this->processData['state'] ?? '-';
	}

	public function getStartTime() : DateTimeImmutable
	{
		return $this->processData['start time'] ?? new DateTimeImmutable( '01/Jan/1970:00:00:00 +0000' );
	}

	public function getStartSince() : int
	{
		return $this->processData['start since'] ?? 0;
	}

	public function getRequests() : int
	{
		return $this->processData['requests'] ?? 0;
	}

	public function getRequestDuration() : int
	{
		return $this->processData['request duration'] ?? 0;
	}

	public function getRequestMethod() : string
	{
		return $this->processData['request method'] ?? '-';
	}

	public function getRequestUri() : string
	{
		return $this->processData['request URI'] ?? '-';
	}

	public function getContentLength() : int
	{
		return $this->processData['content length'] ?? 0;
	}

	public function getUser() : string
	{
		return $this->processData['user'] ?? '-';
	}

	public function getScript() : string
	{
		return $this->processData['script'] ?? '-';
	}

	public function getLastRequestCpu() : float
	{
		return $this->processData['last request cpu'] ?? 0.0;
	}

	public function getLastRequestMemory() : int
	{
		return $this->processData['last request memory'] ?? 0;
	}
}