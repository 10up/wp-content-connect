<?php

namespace TenUp\ContentConnect\UI;

use function TenUp\ContentConnect\Helpers\get_post_to_post_relationships_by;
use function TenUp\ContentConnect\Helpers\get_post_to_user_relationships_by;

/**
 * Class BlockEditor
 *
 * @package TenUp\ContentConnect\UI
 */
class BlockEditor {

	/**
	 * Meta key written by the JS layer to mark a post as dirty when relationships change.
	 *
	 * Must be registered (with `show_in_rest=true` and an auth callback) so that
	 * `dispatch( 'core/editor' ).editPost({ meta: { ... } })` round-trips through
	 * the REST save without being silently dropped by `_protected_meta` filtering.
	 *
	 * @since 2.0.0
	 *
	 * @var string
	 */
	const EDIT_LOCK_META_KEY = '_content_connect_edit_lock';

	/**
	 * Setup the block editor module.
	 *
	 * @since 2.0.0
	 */
	public function setup() {
		add_action( 'init', array( $this, 'register_edit_lock_meta' ), 110 );
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_block_editor_assets' ) );
	}

	/**
	 * Register the edit-lock post meta on every post type that participates in any relationship.
	 *
	 * @since 2.0.0
	 */
	public function register_edit_lock_meta() {

		$post_types = get_post_types( array( 'show_in_rest' => true ), 'names' );

		foreach ( $post_types as $post_type ) {
			$has_p2p = ! empty( get_post_to_post_relationships_by( 'post_type', $post_type ) );
			$has_p2u = ! empty( get_post_to_user_relationships_by( 'post_type', $post_type ) );

			if ( ! $has_p2p && ! $has_p2u ) {
				continue;
			}

			register_post_meta(
				$post_type,
				self::EDIT_LOCK_META_KEY,
				array(
					'show_in_rest'  => true,
					'single'        => true,
					'type'          => 'integer',
					'auth_callback' => function ( $allowed, $meta_key, $post_id ) {
						return current_user_can( 'edit_post', $post_id );
					},
				)
			);
		}
	}

	/**
	 * Enqueue block editor assets when the current post type has at least one relationship.
	 *
	 * @since 2.0.0
	 */
	public function enqueue_block_editor_assets() {

		if ( ! $this->current_post_type_has_relationships() ) {
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

	/**
	 * Determine whether the current admin screen targets a post type that participates in any relationship.
	 *
	 * @since 2.0.0
	 *
	 * @return bool
	 */
	protected function current_post_type_has_relationships() {

		if ( ! function_exists( 'get_current_screen' ) ) {
			return false;
		}

		$screen = get_current_screen();

		if ( empty( $screen ) || empty( $screen->post_type ) ) {
			return false;
		}

		$post_type = $screen->post_type;

		if ( ! empty( get_post_to_post_relationships_by( 'post_type', $post_type ) ) ) {
			return true;
		}

		if ( ! empty( get_post_to_user_relationships_by( 'post_type', $post_type ) ) ) {
			return true;
		}

		return false;
	}
}
