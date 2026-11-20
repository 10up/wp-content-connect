<?php
/**
 * PHPUnit bootstrap file
 *
 * @package TenUp\ContentConnect\Tests
 */

$_tests_dir = getenv( 'WP_TESTS_DIR' );

// Load Composer autoloader.
$plugin_dir = dirname( __DIR__, 2 );
if ( file_exists( $plugin_dir . '/vendor/autoload.php' ) ) {
	require_once $plugin_dir . '/vendor/autoload.php';
}

// Load WordPress test suite functions.
require_once $_tests_dir . '/includes/functions.php';

function _manually_load_plugin() {
	require __DIR__ . '/../../content-connect.php';

	$plugin = \TenUp\ContentConnect\Plugin::instance();

	if ( ! empty( $plugin->tables ) ) {
		foreach ( $plugin->tables as $table ) {
			$table->upgrade( true );
		}
	}
}
tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

// Bootstrap WordPress test suite.
require $_tests_dir . '/includes/bootstrap.php';
