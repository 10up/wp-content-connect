<?php
/**
 * Plugin Name:       WP Content Connect
 * Plugin URI:        https://github.com/10up/wp-content-connect
 * Description:       WordPress library that enables direct relationships for posts to posts and posts to users.
 * Version:           1.7.0
 * Requires at least: 6.5
 * Requires PHP:      7.4
 * Author:            Chris Marslender, 10up
 * Author URI:        https://10up.com
 * License:           GPL-3.0
 * License URI:       https://opensource.org/licenses/GPL-3.0
 * Update URI:        https://github.com/10up/wp-content-connect
 */

define( 'CONTENT_CONNECT_VERSION', '1.7.0' );
define( 'CONTENT_CONNECT_URL', plugin_dir_url( __FILE__ ) );
define( 'CONTENT_CONNECT_PATH', plugin_dir_path( __FILE__ ) );

require_once __DIR__ . '/autoload.php';

wp_content_connect_autoloader();

// Kick things off
\TenUp\ContentConnect\Plugin::instance();
