<?php
/**
 *
 */

if ( ! file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
	throw new Exception( "Composer dependencies missing for the P2P Library. Run `composer install`" );
}

require_once __DIR__ . '/vendor/autoload.php';

// Kick things off
\TenUp\P2P\Plugin::instance();
