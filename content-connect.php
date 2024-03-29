<?php
/**
 * Plugin Name: WP Content Connect
 * Plugin URI: https://github.com/10up/wp-content-connect
 * Description: WordPress library that enables direct relationships for posts to posts and posts to users.
 * Version: 1.5.0
 * Author: Chris Marslender
 * Author URI:
 * License: GPLv3
 * License URI: https://opensource.org/licenses/GPL-3.0
 * Update URI: https://github.com/10up/wp-content-connect
 * 
 */

require_once __DIR__ . '/autoload.php';
wp_content_connect_autoloader();

// Kick things off
\TenUp\ContentConnect\Plugin::instance();
