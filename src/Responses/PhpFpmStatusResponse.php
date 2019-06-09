<?php declare(strict_types=1);

namespace hollodotme\FastCGI\Responses;

use DateTimeImmutable;
use Exception;
use hollodotme\FastCGI\Interfaces\ProvidesResponseData;
use function explode;

final class PhpFpmStatusResponse implements ProvidesResponseData
{
	/** @var ProvidesResponseData */
	private $response;

	/** @var array */
	private $statusArray;

	/**
	 * @param ProvidesResponseData $response
	 *
	 * @throws Exception
	 */
	public function __construct( ProvidesResponseData $response )
	{
		$this->response    = $response;
		$this->statusArray = [];
		$this->parseBody();
	}

	/**
	 * @throws Exception
	 */
	private function parseBody() : void
	{
		foreach ( explode( "\n", trim( $this->response->getBody() ) ) as $line )
		{
			[$name, $value] = explode( ':', trim( $line ), 2 );
			$name  = trim( $name );
			$value = trim( $value );

			$this->statusArray[ $name ] = $this->castValue( $name, $value );
		}
	}

	/**
	 * @param string $name
	 * @param string $value
	 *
	 * @return DateTimeImmutable|int|string
	 * @throws Exception
	 */
	private function castValue( string $name, string $value )
	{
		switch ( $name )
		{
			case 'start time':
				return new DateTimeImmutable( $value );

			case 'start since':
			case 'accepted conn':
			case 'listen queue':
			case 'max listen queue':
			case 'listen queue len':
			case 'idle processes':
			case 'active processes':
			case 'total processes':
			case 'max active processes':
			case 'max children reached':
			case 'slow requests':
				return (int)$value;

			case 'pool':
			case 'process manager':
			default:
				return $value;
		}
	}

	public function getRequestId() : int
	{
		return $this->response->getRequestId();
	}

	public function getHeaders() : array
	{
		return $this->response->getHeaders();
	}

	public function getHeader( string $headerKey ) : string
	{
		return $this->response->getHeader( $headerKey );
	}

	public function getBody() : string
	{
		return $this->response->getBody();
	}

	/**
	 * @return string
	 * @deprecated Use getOutput() instead.
	 */
	public function getRawResponse() : string
	{
		return $this->response->getRawResponse();
	}

	public function getOutput() : string
	{
		return $this->response->getOutput();
	}

	public function getError() : string
	{
		return $this->response->getError();
	}

	public function getDuration() : float
	{
		return $this->response->getDuration();
	}

	public function getPoolName() : string
	{
		return $this->statusArray['pool'];
	}

	public function getProcessManager() : string
	{
		return $this->statusArray['process manager'];
	}

	public function getStartTime() : DateTimeImmutable
	{
		return $this->statusArray['start time'];
	}

	public function getStartSince() : int
	{
		return $this->statusArray['start since'];
	}

	public function getAcceptedConnections() : int
	{
		return $this->statusArray['accepted conn'];
	}

	public function getListenQueue() : int
	{
		return $this->statusArray['listen queue'];
	}

	public function getMaxListenQueue() : int
	{
		return $this->statusArray['max listen queue'];
	}

	public function getListenQueueLength() : int
	{
		return $this->statusArray['listen queue len'];
	}

	public function getIdleProcesses() : int
	{
		return $this->statusArray['idle processes'];
	}

	public function getActiveProcesses() : int
	{
		return $this->statusArray['active processes'];
	}

	public function getTotalProcesses() : int
	{
		return $this->statusArray['total processes'];
	}

	public function getMaxActiveProcesses() : int
	{
		return $this->statusArray['max active processes'];
	}

	public function getMaxChildrenReached() : int
	{
		return $this->statusArray['max children reached'];
	}

	public function getSlowRequests() : int
	{
		return $this->statusArray['slow requests'];
	}

	public function getStatusArray() : array
	{
		return $this->statusArray;
	}
}