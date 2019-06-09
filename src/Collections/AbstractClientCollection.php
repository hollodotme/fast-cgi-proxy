<?php declare(strict_types=1);

namespace hollodotme\FastCGI\Collections;

use hollodotme\FastCGI\Client;
use hollodotme\FastCGI\Exceptions\ClientNotFoundException;
use hollodotme\FastCGI\Exceptions\MissingConnectionsException;
use hollodotme\FastCGI\Interfaces\CollectsSocketConnections;
use hollodotme\FastCGI\Interfaces\ConfiguresSocketConnection;
use hollodotme\FastCGI\Interfaces\ProvidesNextClient;
use function count;

abstract class AbstractClientCollection implements CollectsSocketConnections, ProvidesNextClient
{
	/** @var array|Client[] */
	private $clients = [];

	public function addConnections(
		ConfiguresSocketConnection $connection,
		ConfiguresSocketConnection ...$connections
	) : void
	{
		$this->clients[] = new Client( $connection );

		foreach ( $connections as $conn )
		{
			$this->clients[] = new Client( $conn );
		}
	}

	final public function count() : int
	{
		return count( $this->clients );
	}

	/**
	 * @param int $index
	 *
	 * @return Client
	 * @throws ClientNotFoundException
	 */
	final protected function getClientAtIndex( int $index ) : Client
	{
		if ( !isset( $this->clients[ $index ] ) )
		{
			throw new ClientNotFoundException( 'Client not found at index: ' . $index );
		}

		return $this->clients[ $index ];
	}

	/**
	 * @throws MissingConnectionsException
	 */
	final protected function guardHasClients() : void
	{
		if ( 0 === $this->count() )
		{
			throw new MissingConnectionsException( 'No connections/clients added to collection.' );
		}
	}
}
