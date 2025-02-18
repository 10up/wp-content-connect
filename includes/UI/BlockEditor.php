<?php

namespace TenUp\ContentConnect\UI;

/**
 * Class BlockEditor
 */
class BlockEditor {

	/**
	 * Setup the class.
	 */
	public function setup() {
		add_action( 'enqueue_block_editor_assets', [ $this, 'enqueue_block_editor_assets' ] );
	}

	/**
	 * Enqueue block editor assets.
	 */
	public function enqueue_block_editor_assets() {
		$asset_info = require CONTENT_CONNECT_PATH . 'dist/js/wp-content-connect.asset.php';

		wp_register_script(
			'wp-content-connect',
			CONTENT_CONNECT_URL . 'dist/js/wp-content-connect.js',
			$asset_info['dependencies'],
			$asset_info['version'],
			true
		);

		wp_enqueue_script( 'wp-content-connect' );
	}
}
