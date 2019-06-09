<?php declare(strict_types=1);

namespace hollodotme\FastCGI;

use Generator;
use hollodotme\FastCGI\Exceptions\ConnectException;
use hollodotme\FastCGI\Exceptions\ReadFailedException;
use hollodotme\FastCGI\Exceptions\TimedoutException;
use hollodotme\FastCGI\Exceptions\WriteFailedException;
use hollodotme\FastCGI\Interfaces\ProvidesConnections;
use hollodotme\FastCGI\Interfaces\ProvidesResponseData;
use hollodotme\FastCGI\Requests\GetRequest;
use hollodotme\FastCGI\Responses\PhpFpmStatusResponse;
use Throwable;

class ClusterStatus
{
	/** @var ClusterProxy */
	private $clusterProxy;

	public function __construct( ProvidesConnections $cluster )
	{
		$this->clusterProxy = new ClusterProxy( $cluster );
	}

	/**
	 * @param string $statusEndpoint
	 * @param string $responseClass
	 *
	 * @return array|ProvidesResponseData[]
	 * @throws ReadFailedException
	 * @throws Throwable
	 * @throws TimedoutException
	 * @throws WriteFailedException
	 * @throws ConnectException
	 */
	public function getStatus( string $statusEndpoint, string $responseClass = PhpFpmStatusResponse::class ) : array
	{
		$statusResponses = [];

		$request = new GetRequest( $statusEndpoint, '' );
		$request->addCustomVars(
			[
				'SCRIPT_NAME'  => $statusEndpoint,
				'QUERY_STRING' => '',
				'REQUEST_URI'  => $statusEndpoint,
				'DOCUMENT_URI' => $statusEndpoint,
			]
		);

		$this->clusterProxy->sendAsyncRequest( $request );

		foreach ( $this->readParsedResponses( $responseClass ) as $statusResponse )
		{
			$statusResponses[] = $statusResponse;
		}

		return $statusResponses;
	}

	/**
	 * @param string $responseClass
	 *
	 * @return Generator|ProvidesResponseData[]
	 * @throws Throwable
	 * @throws ReadFailedException
	 */
	private function readParsedResponses( string $responseClass ) : Generator
	{
		while ( $this->clusterProxy->hasUnhandledResponses() )
		{
			yield from $this->parseReadyResponses( $responseClass );
		}
	}

	/**
	 * @param string $responseClass
	 *
	 * @return Generator|ProvidesResponseData[]
	 * @throws Throwable
	 * @throws ReadFailedException
	 */
	private function parseReadyResponses( string $responseClass ) : Generator
	{
		foreach ( $this->clusterProxy->readReadyResponses() as $response )
		{
			yield new $responseClass( $response );
		}
	}
}