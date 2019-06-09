<?php declare(strict_types=1);

namespace hollodotme\FastCGI\Tests\Unit\Collections;

use hollodotme\FastCGI\Client;
use hollodotme\FastCGI\Collections\AbstractClientCollection;
use hollodotme\FastCGI\Exceptions\ClientNotFoundException;
use PHPUnit\Framework\TestCase;

final class AbstractClientCollectionTest extends TestCase
{
	public function testThrowsExceptionOnAttemptToRetrieveANonExistingClient() : void
	{
		$collection = new class extends AbstractClientCollection
		{
			public function getNextClient() : Client
			{
				return $this->getClientAtIndex( 0 );
			}
		};

		$this->expectException( ClientNotFoundException::class );

		$collection->getNextClient();
	}
}
