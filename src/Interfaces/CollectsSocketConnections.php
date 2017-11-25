<?php declare(strict_types=1);
/**
 * @author hollodotme
 */

namespace hollodotme\FastCGI\Interfaces;

/**
 * Interface CollectsSocketConnections
 * @package hollodotme\FastCGI\Interfaces
 */
interface CollectsSocketConnections
{
	public function add( ConfiguresSocketConnection ...$connections ) : void;
}
