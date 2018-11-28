<?php
/**
 * Plugin Name: WP Content Connect
 * Plugin URI: https://github.com/10up/wp-content-connect
 * Description: WordPress library that enables direct relationships for posts to posts and posts to users.
 * Version: 1.0.0
 * Author: Chris Marslender
 * Author URI:
 * License: MIT
 * License URI: https://opensource.org/licenses/MIT
 */

require_once __DIR__ . '/autoload.php';
wp_content_connect_autoloader();

// Kick things off
\TenUp\ContentConnect\Plugin::instance();
