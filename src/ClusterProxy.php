<?php declare(strict_types=1);

namespace hollodotme\FastCGI;

use Generator;
use hollodotme\FastCGI\Collections\RoundRobin;
use hollodotme\FastCGI\Exceptions\ConnectException;
use hollodotme\FastCGI\Exceptions\ReadFailedException;
use hollodotme\FastCGI\Exceptions\TimedoutException;
use hollodotme\FastCGI\Exceptions\WriteFailedException;
use hollodotme\FastCGI\Interfaces\ProvidesConnections;
use hollodotme\FastCGI\Interfaces\ProvidesRequestData;
use hollodotme\FastCGI\Interfaces\ProvidesResponseData;
use hollodotme\FastCGI\Interfaces\ProvidesServerStatus;
use hollodotme\FastCGI\Requests\GetRequest;
use hollodotme\FastCGI\Responses\PhpFpmStatusResponse;
use Throwable;
use function parse_url;
use const PHP_URL_PATH;

class ClusterProxy
{
	/** @var ProvidesConnections */
	private $cluster;

	/** @var Proxy */
	private $proxy;

	public function __construct( ProvidesConnections $cluster )
	{
		$this->cluster = $cluster;
		$this->proxy   = new Proxy(
			RoundRobin::fromConnections( ...$this->cluster->getIterator() )
		);
	}

	/**
	 * @param ProvidesRequestData $request
	 *
	 * @throws Exceptions\ConnectException
	 * @throws Exceptions\TimedoutException
	 * @throws Exceptions\WriteFailedException
	 */
	public function sendAsyncRequest( ProvidesRequestData $request ) : void
	{
		for ( $i = 0; $i < $this->cluster->count(); $i++ )
		{
			$this->proxy->sendAsyncRequest( $request );
		}
	}

	/**
	 * @param int|null $timeoutMs
	 *
	 * @throws ReadFailedException
	 */
	public function waitForResponses( ?int $timeoutMs = null ) : void
	{
		$this->proxy->waitForResponses( $timeoutMs );
	}

	public function hasUnhandledResponses() : bool
	{
		return $this->proxy->hasUnhandledResponses();
	}

	/**
	 * @param int|null $timeoutMs
	 *
	 * @return Generator|ProvidesResponseData[]
	 * @throws Throwable
	 * @throws ReadFailedException
	 */
	public function readReadyResponses( ?int $timeoutMs = null ) : Generator
	{
		yield from $this->proxy->readReadyResponses( $timeoutMs );
	}

	/**
	 * @param int|null $timeoutMs
	 *
	 * @throws ReadFailedException
	 */
	public function handleReadyResponses( ?int $timeoutMs = null ) : void
	{
		$this->proxy->handleReadyResponses( $timeoutMs );
	}

	/**
	 * @param string $statusEndpoint
	 * @param string $responseClass
	 *
	 * @return array|ProvidesServerStatus[]
	 * @throws Throwable
	 * @throws TimedoutException
	 * @throws WriteFailedException
	 * @throws ConnectException
	 * @throws ReadFailedException
	 */
	public function getStatus( string $statusEndpoint, string $responseClass = PhpFpmStatusResponse::class ) : array
	{
		$statusResponses = [];
		$queryString     = (string)parse_url( $statusEndpoint, PHP_URL_QUERY );
		$path            = (string)parse_url( $statusEndpoint, PHP_URL_PATH );

		foreach ( $this->cluster->getIterator() as $connection )
		{
			$request = new GetRequest( $path, '' );
			$request->addCustomVars(
				[
					'SCRIPT_NAME'  => $path,
					'QUERY_STRING' => $queryString,
					'REQUEST_URI'  => $statusEndpoint,
				]
			);

			$request->addResponseCallbacks(
				static function ( ProvidesResponseData $response )
				use ( $responseClass, $connection, &$statusResponses )
				{
					$statusResponses[] = new $responseClass( $response, $connection );
				}
			);

			$this->proxy->sendAsyncRequest( $request );
		}

		$this->waitForResponses();

		return $statusResponses;
	}
}