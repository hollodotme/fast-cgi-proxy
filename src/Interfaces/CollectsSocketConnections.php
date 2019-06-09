<?php declare(strict_types=1);

namespace hollodotme\FastCGI\Interfaces;

interface CollectsSocketConnections
{
	public function addConnections(
		ConfiguresSocketConnection $connection,
		ConfiguresSocketConnection ...$connections
	) : void;
}
