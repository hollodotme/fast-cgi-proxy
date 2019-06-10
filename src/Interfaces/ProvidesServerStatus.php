<?php declare(strict_types=1);

namespace hollodotme\FastCGI\Interfaces;

interface ProvidesServerStatus
{
	public function getResponse() : ProvidesResponseData;

	public function getConnection() : ConfiguresSocketConnection;

	public function getStatus();

	public function getProcesses() : array;
}