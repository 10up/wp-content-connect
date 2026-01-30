<?php

namespace TenUp\ContentConnect\UI;

/**
 * Class BlockEditor
 *
 * @package TenUp\ContentConnect\UI
 */
class BlockEditor {

	/**
	 * Setup the block editor module.
	 *
	 * @since 2.0.0
	 */
	public function setup() {
		add_action( 'enqueue_block_editor_assets', [ $this, 'enqueue_block_editor_assets' ] );
	}

	/**
	 * Enqueue block editor assets.
	 *
	 * @since 2.0.0
	 */
	public function enqueue_block_editor_assets() {

		if ( file_exists( CONTENT_CONNECT_PATH . 'dist/js/block-editor.asset.php' ) ) {
			$asset_info = require CONTENT_CONNECT_PATH . 'dist/js/block-editor.asset.php';

			wp_register_script(
				'wp-content-connect-block-editor',
				CONTENT_CONNECT_URL . 'dist/js/block-editor.js',
				$asset_info['dependencies'],
				$asset_info['version'],
				true
			);

			wp_enqueue_script( 'wp-content-connect-block-editor' );
		}
	}
}
