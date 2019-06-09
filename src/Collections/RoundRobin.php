<?php declare(strict_types=1);
/**
 * @author hollodotme
 */

namespace hollodotme\FastCGI\Collections;

use hollodotme\FastCGI\Client;
use hollodotme\FastCGI\Exceptions\ClientNotFoundException;
use hollodotme\FastCGI\Exceptions\MissingConnectionsException;

/**
 * Class RoundRobinConnections
 * @package hollodotme\FastCGI\Collections
 */
final class RoundRobin extends AbstractClientCollection
{
	private $nextIndex = 0;

	/**
	 * @return Client
	 * @throws MissingConnectionsException
	 * @throws ClientNotFoundException
	 */
	public function getNextClient() : Client
	{
		$this->guardHasClients();

		$client = $this->getClientAtIndex( $this->nextIndex );

		$this->updateIndex();

		return $client;
	}

	private function updateIndex() : void
	{
		$this->nextIndex++;

		if ( $this->nextIndex >= $this->count() )
		{
			$this->nextIndex = 0;
		}
	}
}
