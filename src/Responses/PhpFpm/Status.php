<?php declare(strict_types=1);

namespace hollodotme\FastCGI\Responses\PhpFpm;

use DateTimeImmutable;

final class Status
{
	/** @var array */
	private $statusData;

	public function __construct( array $statusData )
	{
		$this->statusData = $statusData;
	}

	public function getPoolName() : string
	{
		return $this->statusData['pool'];
	}

	public function getProcessManager() : string
	{
		return $this->statusData['process manager'];
	}

	public function getStartTime() : DateTimeImmutable
	{
		return $this->statusData['start time'];
	}

	public function getStartSince() : int
	{
		return $this->statusData['start since'];
	}

	public function getAcceptedConnections() : int
	{
		return $this->statusData['accepted conn'];
	}

	public function getListenQueue() : int
	{
		return $this->statusData['listen queue'];
	}

	public function getMaxListenQueue() : int
	{
		return $this->statusData['max listen queue'];
	}

	public function getListenQueueLength() : int
	{
		return $this->statusData['listen queue len'];
	}

	public function getIdleProcesses() : int
	{
		return $this->statusData['idle processes'];
	}

	public function getActiveProcesses() : int
	{
		return $this->statusData['active processes'];
	}

	public function getTotalProcesses() : int
	{
		return $this->statusData['total processes'];
	}

	public function getMaxActiveProcesses() : int
	{
		return $this->statusData['max active processes'];
	}

	public function getMaxChildrenReached() : int
	{
		return $this->statusData['max children reached'];
	}

	public function getSlowRequests() : int
	{
		return $this->statusData['slow requests'];
	}
}