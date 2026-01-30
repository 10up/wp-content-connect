<?php
/**
 * PHPUnit bootstrap file
 *
 * @package TenUp\ContentConnect\Tests\Unit
 */

// Define constant to disable caching in helpers during tests.
define( 'CONTENT_CONNECT_DOING_TESTS', true );

require_once __DIR__ . '/../../vendor/autoload.php';

WP_Mock::bootstrap();

define( 'PHPUNIT_RUNNER', true );
