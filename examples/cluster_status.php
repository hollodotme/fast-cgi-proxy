<?php declare(strict_types=1);

namespace hollodotme\FastCGI\Examples;

use hollodotme\FastCGI\ClusterProxy;
use hollodotme\FastCGI\Collections\Cluster;
use hollodotme\FastCGI\Responses\PhpFpmStatusResponse;
use hollodotme\FastCGI\SocketConnections\NetworkSocket;

require_once __DIR__ . '/../vendor/autoload.php';

$cluster = Cluster::fromConnections(
	new NetworkSocket( 'php71', 9001 ),
	new NetworkSocket( 'php72', 9001 ),
	new NetworkSocket( 'php73', 9001 )
);

$clusterProxy = new ClusterProxy( $cluster );

/** @noinspection PhpUnhandledExceptionInspection */
$statusResponses = $clusterProxy->getStatus( '/status?full' );
# If you do not want the list processes, use the following line to get the status only
# $statusResponses = $clusterProxy->getStatus( '/status' );

/** @var PhpFpmStatusResponse $statusResponse */
foreach ( $statusResponses as $statusResponse )
{
	$connection = $statusResponse->getConnection();
	$status     = $statusResponse->getStatus();
	$processes  = $statusResponse->getProcesses();
	$response   = $statusResponse->getResponse();

	echo '[ SERVER: ', $connection->getSocketAddress(), " ]\n";

	echo '- Pool name: ', $status->getPoolName(), "\n";
	echo '- Process manager: ', $status->getProcessManager(), "\n";
	echo '- Started at: ', $status->getStartTime()->format( 'c' ), "\n";
	echo '- Seconds since start: ', $status->getStartSince(), "\n";
	echo '- Number of accepted connections: ', $status->getAcceptedConnections(), "\n";
	echo '- Current listen queue: ', $status->getListenQueue(), "\n";
	echo '- Listen queue maximum: ', $status->getMaxListenQueue(), "\n";
	echo '- Listen queue length: ', $status->getListenQueueLength(), "\n";
	echo '- Number of idle processes: ', $status->getIdleProcesses(), "\n";
	echo '- Number of active processes: ', $status->getActiveProcesses(), "\n";
	echo '- Number of total processes: ', $status->getTotalProcesses(), "\n";
	echo '- Number of active processes maximum: ', $status->getMaxActiveProcesses(), "\n";
	echo '- Times max children reached: ', $status->getMaxChildrenReached(), "\n";
	echo '- Number of slow requests: ', $status->getSlowRequests(), "\n";

	echo "\nPrinting processes:\n\n";

	foreach ( $processes as $index => $process )
	{
		echo '- [ PROCESS #', ($index + 1), " ]\n";
		echo '  * PID: ', $process->getPid(), "\n";
		echo '  * State: ', $process->getState(), "\n";
		echo '  * Started at: ', $process->getStartTime()->format( 'c' ), "\n";
		echo '  * Seconds since start: ', $process->getStartSince(), "\n";
		echo '  * Number of requests processed: ', $process->getRequests(), "\n";
		echo '  * Last request duration: ', $process->getRequestDuration(), "\n";
		echo '  * Last request method: ', $process->getRequestMethod(), "\n";
		echo '  * Last request URI: ', $process->getRequestUri(), "\n";
		echo '  * Last content length: ', $process->getContentLength(), "\n";
		echo '  * Last user: ', $process->getUser(), "\n";
		echo '  * Last script: ', $process->getScript(), "\n";
		echo '  * CPU usage of last request: ', $process->getLastRequestCpu(), "\n";
		echo '  * Memory usage of last request: ', $process->getLastRequestMemory(), "\n";

		echo "\n\n---\n\n";
	}

	echo 'Processing duration: ', $response->getDuration(), " seconds\n\n";
}


