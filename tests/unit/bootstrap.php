<?php
/**
 * PHPUnit bootstrap file
 *
 * @package TenUp\ContentConnect\Tests\Unit
 */

require_once __DIR__ . '/../../vendor/autoload.php';

WP_Mock::bootstrap();

define( 'PHPUNIT_RUNNER', true );
