<?php declare(strict_types=1);
/**
 * @author hollodotme
 */

namespace hollodotme\FastCGI;

use hollodotme\FastCGI\Exceptions\ReadFailedException;
use hollodotme\FastCGI\Interfaces\ProvidesClients;
use hollodotme\FastCGI\Interfaces\ProvidesRequestData;
use hollodotme\FastCGI\Interfaces\ProvidesResponseData;

/**
 * Class Proxy
 * @package hollodotme\FastCGI
 */
class Proxy
{
	/** @var ProvidesClients */
	private $collection;

	/** @var array|Client[] */
	private $requestIdClientMap;

	public function __construct( ProvidesClients $collection )
	{
		$this->collection         = $collection;
		$this->requestIdClientMap = [];
	}

	public function sendAsyncRequest( ProvidesRequestData $request ) : int
	{
		$client    = $this->collection->getClient();
		$requestId = $client->sendAsyncRequest( $request );

		$this->mapRequestIdToClient( $requestId, $client );

		return $requestId;
	}

	private function mapRequestIdToClient( int $requestId, Client $client ) : void
	{
		$this->requestIdClientMap[ $requestId ] = $client;
	}

	public function sendRequest( ProvidesRequestData $request ) : ProvidesResponseData
	{
		$requestId = $this->sendAsyncRequest( $request );

		return $this->readResponse( $requestId );
	}

	private function getClientForRequestId( int $requestId ) : Client
	{
		if ( !isset( $this->requestIdClientMap[ $requestId ] ) )
		{
			throw new ReadFailedException( 'Client not found for request ID: ' . $requestId );
		}

		return $this->requestIdClientMap[ $requestId ];
	}

	public function readResponse( int $requestId, ?int $timeoutMs = null ) : ProvidesResponseData
	{
		$client   = $this->getClientForRequestId( $requestId );
		$response = $client->readResponse( $requestId, $timeoutMs );

		$this->removeRequestIdsFromMap( $requestId );

		return $response;
	}

	private function removeRequestIdsFromMap( int ...$requestIds ) : void
	{
		foreach ( $requestIds as $requestId )
		{
			unset( $this->requestIdClientMap[ $requestId ] );
		}
	}

	public function waitForResponse( int $requestId, ?int $timeoutMs = null ) : void
	{
		$client = $this->getClientForRequestId( $requestId );

		$client->waitForResponse( $requestId, $timeoutMs );

		$this->removeRequestIdsFromMap( $requestId );
	}

	public function waitForResponses( ?int $timeoutMs = null ) : void
	{
		while ( $this->hasUnhandledResponses() )
		{
			$this->handleReadyResponses( $timeoutMs );
		}
	}

	private function getClientsUnique() : array
	{
		$clients = [];
		foreach ( $this->requestIdClientMap as $client )
		{
			if ( !\in_array( $client, $clients, true ) )
			{
				$clients[] = $client;
			}
		}

		return $clients;
	}

	public function hasUnhandledResponses() : bool
	{
		return \count( $this->requestIdClientMap ) > 0;
	}

	public function hasResponse( int $requestId ) : bool
	{
		$client = $this->getClientForRequestId( $requestId );

		return $client->hasResponse( $requestId );
	}

	public function getRequestIdsHavingResponse() : array
	{
		$requestIds = [];

		/** @var Client $client */
		foreach ( $this->getClientsUnique() as $client )
		{
			foreach ( $client->getRequestIdsHavingResponse() as $requestId )
			{
				$requestIds[] = $requestId;
			}
		}

		return $requestIds;
	}

	/**
	 * @param int|null $timeoutMs
	 * @param int[]    ...$requestIds
	 *
	 * @return \Generator|ProvidesResponseData[]
	 */
	public function readResponses( ?int $timeoutMs = null, int ...$requestIds ) : \Generator
	{
		foreach ( $requestIds as $requestId )
		{
			yield $this->readResponse( $requestId, $timeoutMs );
		}
	}

	/**
	 * @param int|null $timeoutMs
	 *
	 * @return \Generator|ProvidesResponseData[]
	 */
	public function readReadyResponses( ?int $timeoutMs = null ) : \Generator
	{
		/** @var Client $client */
		foreach ( $this->getClientsUnique() as $client )
		{
			yield from $this->readResponses( $timeoutMs, ...$client->getRequestIdsHavingResponse() );
		}
	}

	public function handleResponse( int $requestId, ?int $timeoutMs = null ) : void
	{
		$client = $this->getClientForRequestId( $requestId );

		$client->handleResponse( $requestId, $timeoutMs );

		$this->removeRequestIdsFromMap( $requestId );
	}

	public function handleResponses( ?int $timeoutMs = null, int ...$requestIds ) : void
	{
		foreach ( $requestIds as $requestId )
		{
			$this->handleResponse( $requestId, $timeoutMs );
		}
	}

	public function handleReadyResponses( ?int $timeoutMs = null ) : void
	{
		$this->handleResponses( $timeoutMs, ...$this->getRequestIdsHavingResponse() );
	}
}
