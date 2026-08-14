<?php

namespace TenUp\ContentConnect\UI;

use function TenUp\ContentConnect\Helpers\get_post_relationships_data;

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
		global $post;

		if ( ! $post instanceof \WP_Post ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post->ID ) ) {
			return;
		}

		// Skip loading the bundle for post types that have no registered relationships.
		$relationships = get_post_relationships_data( $post );

		if ( empty( $relationships ) ) {
			return;
		}

		if ( file_exists( CONTENT_CONNECT_PATH . 'dist/js/block-editor.asset.php' ) ) {
			$asset_info = require CONTENT_CONNECT_PATH . 'dist/js/block-editor.asset.php';

			wp_enqueue_script(
				'wp-content-connect-block-editor',
				CONTENT_CONNECT_URL . 'dist/js/block-editor.js',
				$asset_info['dependencies'],
				$asset_info['version'],
				true
			);
		}

		if ( file_exists( CONTENT_CONNECT_PATH . 'dist/css/admin-styles.asset.php' ) ) {
			$asset_info = require CONTENT_CONNECT_PATH . 'dist/css/admin-styles.asset.php';

			wp_enqueue_style(
				'wp-content-connect-admin-styles',
				CONTENT_CONNECT_URL . 'dist/css/admin-styles.css',
				$asset_info['dependencies'],
				$asset_info['version']
			);
		}
	}
}
