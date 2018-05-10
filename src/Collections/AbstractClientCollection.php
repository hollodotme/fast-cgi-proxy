<?php declare(strict_types=1);
/**
 * @author hollodotme
 */

namespace hollodotme\FastCGI\Collections;

use hollodotme\FastCGI\Client;
use hollodotme\FastCGI\Exceptions\ClientNotFoundException;
use hollodotme\FastCGI\Exceptions\MissingConnectionsException;
use hollodotme\FastCGI\Interfaces\CollectsSocketConnections;
use hollodotme\FastCGI\Interfaces\ConfiguresSocketConnection;
use hollodotme\FastCGI\Interfaces\ProvidesClients;
use function array_keys;
use function count;

/**
 * Class AbstractClientCollection
 * @package hollodotme\FastCGI\Collections
 */
abstract class AbstractClientCollection implements CollectsSocketConnections, ProvidesClients
{
	/** @var array|Client[] */
	private $clients = [];

	public function add( ConfiguresSocketConnection $connection, ConfiguresSocketConnection ...$connections ) : void
	{
		$this->clients[] = new Client( $connection );

		foreach ( $connections as $conn )
		{
			$this->clients[] = new Client( $conn );
		}
	}

	final protected function getIndices() : array
	{
		return array_keys( $this->clients );
	}

	final protected function countClients() : int
	{
		return count( $this->clients );
	}

	/**
	 * @param int $index
	 *
	 * @throws ClientNotFoundException
	 * @return Client
	 */
	final protected function getClientWithIndex( int $index ) : Client
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
		if ( 0 === $this->countClients() )
		{
			throw new MissingConnectionsException( 'No connections/clients added to collection.' );
		}
	}
}
