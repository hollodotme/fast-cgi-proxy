<?php declare(strict_types=1);

echo $_REQUEST['test-key'] . '-' . getenv( 'POOL' );
