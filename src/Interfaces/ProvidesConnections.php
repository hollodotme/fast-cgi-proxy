<?php declare(strict_types=1);

namespace hollodotme\FastCGI\Interfaces;

use Countable;
use IteratorAggregate;

interface ProvidesConnections extends Countable, IteratorAggregate
{

}