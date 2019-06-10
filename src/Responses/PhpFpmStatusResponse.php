<?php declare(strict_types=1);

namespace hollodotme\FastCGI\Responses;

use DateTimeImmutable;
use Exception;
use hollodotme\FastCGI\Interfaces\ConfiguresSocketConnection;
use hollodotme\FastCGI\Interfaces\ProvidesResponseData;
use hollodotme\FastCGI\Responses\PhpFpm\Process;
use hollodotme\FastCGI\Responses\PhpFpm\Status;
use function array_filter;
use function array_shift;
use function explode;
use function preg_match;
use function trim;

final class PhpFpmStatusResponse
{
	/** @var ProvidesResponseData */
	private $response;

	/** @var ConfiguresSocketConnection */
	private $connection;

	/** @var Status */
	private $status;

	/** @var array|Process[] */
	private $processes;

	/**
	 * @param ProvidesResponseData       $response
	 * @param ConfiguresSocketConnection $connection
	 *
	 * @throws Exception
	 */
	public function __construct( ProvidesResponseData $response, ConfiguresSocketConnection $connection )
	{
		$this->response   = $response;
		$this->connection = $connection;
		$this->processes  = [];

		$this->parseBody();
	}

	/**
	 * @throws Exception
	 */
	private function parseBody() : void
	{
		$bodyLines  = $this->getCleanBodyLines();
		$statusData = [];

		# Parse into status array
		$line = array_shift( $bodyLines );
		while ( null !== $line && !$this->lineIsProcessSeparator( $line ) )
		{
			[$name, $value] = $this->getNameAndValueFromLine( $line );

			$statusData[ $name ] = $this->castStatusValue( $name, $value );

			$line = array_shift( $bodyLines );
		}

		$this->status = new Status( $statusData );

		if ( [] === $bodyLines )
		{
			return;
		}

		# Parse into processes
		$currentProcess = [];

		$line = array_shift( $bodyLines );
		while ( null !== $line )
		{
			if ( $this->lineIsProcessSeparator( $line ) )
			{
				$this->processes[] = new Process( $currentProcess );
				$currentProcess    = [];
				$line              = array_shift( $bodyLines );
				continue;
			}

			[$name, $value] = $this->getNameAndValueFromLine( $line );

			$currentProcess[ $name ] = $this->castProcessValue( $name, $value );

			$line = array_shift( $bodyLines );
		}

		$this->processes[] = new Process( $currentProcess );
		unset( $currentProcess );
	}

	private function getCleanBodyLines() : array
	{
		return array_filter(
			explode( "\n", trim( $this->response->getBody() ) )
		);
	}

	private function lineIsProcessSeparator( string $line ) : bool
	{
		return (bool)preg_match( '#^\*+$#', trim( $line ) );
	}

	private function getNameAndValueFromLine( string $line ) : array
	{
		[$name, $value] = explode( ':', trim( $line ), 2 );

		return [trim( $name ), trim( $value )];
	}

	/**
	 * @param string $name
	 * @param string $value
	 *
	 * @return DateTimeImmutable|int|string
	 * @throws Exception
	 */
	private function castStatusValue( string $name, string $value )
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

	/**
	 * @param string $name
	 * @param string $value
	 *
	 * @return DateTimeImmutable|float|int|string
	 * @throws Exception
	 */
	private function castProcessValue( string $name, string $value )
	{
		switch ( $name )
		{
			case 'pid':
			case 'start since':
			case 'requests':
			case 'request duration':
			case 'content length':
			case 'last request memory':
				return (int)$value;
			case 'start time':
				return new DateTimeImmutable( $value );
			case 'last request cpu':
				return (float)$value;
			case 'state':
			case 'request method':
			case 'user':
			case 'script':
			default:
				return $value;
		}
	}

	public function getResponse() : ProvidesResponseData
	{
		return $this->response;
	}

	public function getConnection() : ConfiguresSocketConnection
	{
		return $this->connection;
	}

	public function getStatus() : Status
	{
		return $this->status;
	}

	/**
	 * @return array|Process[]
	 */
	public function getProcesses() : array
	{
		return $this->processes;
	}
}