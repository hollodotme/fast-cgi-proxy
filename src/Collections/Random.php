<?php declare(strict_types=1);

namespace hollodotme\FastCGI\Collections;

use Exception;
use hollodotme\FastCGI\Client;
use hollodotme\FastCGI\Exceptions\ClientNotFoundException;
use hollodotme\FastCGI\Exceptions\MissingConnectionsException;
use function random_int;

final class Random extends AbstractClientCollection
{
	/**
	 * @return Client
	 * @throws MissingConnectionsException
	 * @throws Exception
	 * @throws ClientNotFoundException
	 */
	public function getNextClient() : Client
	{
		$this->guardHasClients();

		$index = random_int( 0, $this->count() - 1 );

		return $this->getClientAtIndex( $index );
	}
}
