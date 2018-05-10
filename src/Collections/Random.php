<?php declare(strict_types=1);
/**
 * @author hollodotme
 */

namespace hollodotme\FastCGI\Collections;

use hollodotme\FastCGI\Client;
use hollodotme\FastCGI\Exceptions\ClientNotFoundException;
use hollodotme\FastCGI\Exceptions\MissingConnectionsException;
use function reset;
use function shuffle;

/**
 * Class Random
 * @package hollodotme\FastCGI\Collections
 */
final class Random extends AbstractClientCollection
{
	/**
	 * @throws ClientNotFoundException
	 * @throws MissingConnectionsException
	 * @return Client
	 */
	public function getClient() : Client
	{
		$this->guardHasClients();

		$indices = $this->getIndices();
		shuffle( $indices );

		return $this->getClientWithIndex( reset( $indices ) );
	}
}
