<?php declare(strict_types=1);
/**
 * @author hollodotme
 */

namespace hollodotme\FastCGI\Collections;

use hollodotme\FastCGI\Client;

/**
 * Class Random
 * @package hollodotme\FastCGI\Collections
 */
final class Random extends AbstractClientCollection
{
	public function getClient() : Client
	{
		$this->guardHasClients();

		$indices = $this->getIndices();
		\shuffle( $indices );

		return $this->getClientWithIndex( \reset( $indices ) );
	}
}
