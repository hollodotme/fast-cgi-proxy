<?php declare(strict_types=1);
/**
 * @author hollodotme
 */

namespace hollodotme\FastCGI\Interfaces;

use hollodotme\FastCGI\Client;

/**
 * Interface ProvidesClients
 * @package hollodotme\FastCGI\Interfaces
 */
interface ProvidesClients
{
	public function getClient() : Client;
}
