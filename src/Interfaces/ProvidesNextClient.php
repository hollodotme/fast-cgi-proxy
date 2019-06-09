<?php declare(strict_types=1);

namespace hollodotme\FastCGI\Interfaces;

use Countable;
use hollodotme\FastCGI\Client;

interface ProvidesNextClient extends Countable
{
	public function getNextClient() : Client;
}
