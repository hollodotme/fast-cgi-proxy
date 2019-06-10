<?php declare(strict_types=1);

namespace hollodotme\FastCGI\Collections;

use hollodotme\FastCGI\Interfaces\ConfiguresSocketConnection;
use hollodotme\FastCGI\Interfaces\ProvidesConnections;
use Iterator;

final class Cluster implements ProvidesConnections
{
	private $connections = [];

	private function __construct()
	{
	}

	public static function fromConnections(
		ConfiguresSocketConnection $connection,
		ConfiguresSocketConnection ...$connections
	) : self
	{
		$cluster = new self();

		$cluster->connections[] = $connection;

		foreach ( $connections as $conn )
		{
			$cluster->connections[] = $conn;
		}

		return $cluster;
	}

	/**
	 * @return Iterator|ConfiguresSocketConnection[]
	 */
	public function getIterator() : Iterator
	{
		yield from $this->connections;
	}

	public function count() : int
	{
		return count( $this->connections );
	}
}