<?php declare(strict_types=1);

namespace hollodotme\FastCGI\Collections;

use hollodotme\FastCGI\Interfaces\CollectsSocketConnections;
use hollodotme\FastCGI\Interfaces\ConfiguresSocketConnection;
use hollodotme\FastCGI\Interfaces\ProvidesConnections;
use Iterator;

final class Cluster implements CollectsSocketConnections, ProvidesConnections
{
	private $connections = [];

	public function addConnections(
		ConfiguresSocketConnection $connection,
		ConfiguresSocketConnection ...$connections
	) : void
	{
		$this->connections[] = $connection;

		foreach ( $connections as $conn )
		{
			$this->connections[] = $conn;
		}
	}

	public function getIterator() : Iterator
	{
		yield from $this->connections;
	}

	public function count()
	{
		return count( $this->connections );
	}
}