<?php declare(strict_types=1);
/*
 * @author hollodotme
 */

echo $_REQUEST['test-key'] . '-' . getenv( 'POOL' );
