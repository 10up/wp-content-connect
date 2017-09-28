<?php
/**
 * Plugin Name: WP Content Connect
 * Plugin URI: https://github.com/10up/wp-content-connect
 * Description: WordPress library that enables direct relationships for posts to posts and posts to users.
 * Version: 0.0.0
 * Author: Chris Marslender
 * Author URI:
 * License: MIT
 * License URI: https://opensource.org/licenses/MIT
 */

if ( ! file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
	throw new Exception( "Composer dependencies missing for the Content Connect Library. Run `composer install`" );
}

require_once __DIR__ . '/vendor/autoload.php';

// Kick things off
\TenUp\ContentConnect\Plugin::instance();
