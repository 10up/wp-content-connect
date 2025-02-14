<?php
/**
 * Plugin Name:       WP Content Connect
 * Plugin URI:        https://github.com/10up/wp-content-connect
 * Description:       WordPress library that enables direct relationships for posts to posts and posts to users.
 * Version:           1.6.0
 * Requires at least: 6.5
 * Requires PHP:      7.4
 * Author:            10up
 * Author URI:        https://10up.com
 * License:           GPL-3.0-or-later
 * License URI:       https://spdx.org/licenses/GPL-3.0-or-later.html
 * Update URI:        https://github.com/10up/wp-content-connect
 */

require_once __DIR__ . '/autoload.php';
wp_content_connect_autoloader();

// Kick things off
\TenUp\ContentConnect\Plugin::instance();
