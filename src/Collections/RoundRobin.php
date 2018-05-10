<?php declare(strict_types=1);
/**
 * @author hollodotme
 */

namespace hollodotme\FastCGI\Collections;

use hollodotme\FastCGI\Client;
use hollodotme\FastCGI\Exceptions\ClientNotFoundException;
use hollodotme\FastCGI\Exceptions\MissingConnectionsException;
use function in_array;

/**
 * Class RoundRobinConnections
 * @package hollodotme\FastCGI\Collections
 */
final class RoundRobin extends AbstractClientCollection
{
	private $nextIndex = 0;

	/**
	 * @throws ClientNotFoundException
	 * @throws MissingConnectionsException
	 * @return Client
	 */
	public function getClient() : Client
	{
		$this->guardHasClients();

		$client = $this->getClientWithIndex( $this->nextIndex );

		$this->nextIndex++;

		if ( !in_array( $this->nextIndex, $this->getIndices(), true ) )
		{
			$this->nextIndex = 0;
		}

		return $client;
	}
}
