<?php declare(strict_types=1);
/**
 * @author hollodotme
 */

namespace hollodotme\FastCGI\Tests\Unit\Collections;

use hollodotme\FastCGI\Client;
use hollodotme\FastCGI\Collections\AbstractClientCollection;
use hollodotme\FastCGI\Exceptions\ClientNotFoundException;
use PHPUnit\Framework\TestCase;

/**
 * Class AbstractClientCollectionTest
 * @package hollodotme\FastCGI\Tests\Unit\Collections
 */
final class AbstractClientCollectionTest extends TestCase
{
	public function testThrowsExceptionOnAttemptToRetrieveANonExistingClient() : void
	{
		$collection = new class extends AbstractClientCollection
		{
			public function getClient() : Client
			{
				return $this->getClientWithIndex( 0 );
			}
		};

		$this->expectException( ClientNotFoundException::class );

		$collection->getClient();
	}
}
