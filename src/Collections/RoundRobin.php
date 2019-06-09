<?php declare(strict_types=1);

namespace hollodotme\FastCGI\Collections;

use hollodotme\FastCGI\Client;
use hollodotme\FastCGI\Interfaces\ConfiguresSocketConnection;
use hollodotme\FastCGI\Interfaces\ProvidesNextClient;

final class RoundRobin implements ProvidesNextClient
{
	private $nextIndex = 0;

	/** @var array|Client[] */
	private $clients = [];

	private function __construct()
	{
	}

	public static function fromConnections(
		ConfiguresSocketConnection $connection,
		ConfiguresSocketConnection ...$connections
	) : self
	{
		$roundRobin = new self();

		$roundRobin->clients[] = new Client( $connection );

		foreach ( $connections as $conn )
		{
			$roundRobin->clients[] = new Client( $conn );
		}

		return $roundRobin;
	}

	/**
	 * @return Client
	 */
	public function getNextClient() : Client
	{
		$client = $this->clients[ $this->nextIndex ];

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

	public function count() : int
	{
		return count( $this->clients );
	}
}
