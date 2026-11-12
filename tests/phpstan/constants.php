<?php
/**
 * Constants for PHPStan. The plugin defines its own at runtime in Plugin::setup_constants(),
 * and WordPress defines WPINC, which the WordPress stubs do not include.
 *
 * @package TenUp\ContentConnect
 */

define( 'CONTENT_CONNECT_VERSION', '' );
define( 'CONTENT_CONNECT_URL', '' );
define( 'CONTENT_CONNECT_PATH', '' );
define( 'WPINC', 'wp-includes' );
