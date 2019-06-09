<?php declare(strict_types=1);

namespace hollodotme\FastCGI\Collections;

use Exception;
use hollodotme\FastCGI\Client;
use hollodotme\FastCGI\Interfaces\ConfiguresSocketConnection;
use hollodotme\FastCGI\Interfaces\ProvidesNextClient;
use function random_int;

final class Random implements ProvidesNextClient
{
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
		$random = new self();

		$random->clients[] = new Client( $connection );

		foreach ( $connections as $conn )
		{
			$random->clients[] = new Client( $conn );
		}

		return $random;
	}

	/**
	 * @return Client
	 * @throws Exception
	 */
	public function getNextClient() : Client
	{
		$index = random_int( 0, $this->count() - 1 );

		return $this->clients[ $index ];
	}

	public function count() : int
	{
		return count( $this->clients );
	}
}
