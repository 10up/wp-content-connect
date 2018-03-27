<?php

function wp_content_connect_autoloader() {
	static $loaded = false;

	if ( ! $loaded ) {
		$composer_autoloader = __DIR__ . '/vendor/autoload.php';

		if ( file_exists( $composer_autoloader ) ) {
			require_once $composer_autoloader;
		} else {
			spl_autoload_register( 'wp_content_connect_autoload' );
			require_once __DIR__ . '/includes/Helpers.php';
		}

		$loaded = true;
	}
}

function wp_content_connect_autoload( $class_path ) {
	if ( strpos( $class_path, 'ContentConnect\\' ) !== false ) {
		$class_file  = __DIR__ . '/includes/';
		$class_file .= str_replace( '\\', '/', $class_path );
		$class_file .= '.php';

		// We don't have TenUp/ContentConnect/ in the directory structure, but its in the namespace
		$class_file = str_replace( 'TenUp/ContentConnect/', '', $class_file );

		if ( file_exists( $class_file ) ) {
			require_once $class_file;
		}
	}
}
