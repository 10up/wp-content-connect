<?php

namespace TenUp\ContentConnect\UI;

use TenUp\ContentConnect\Plugin;

use function TenUp\ContentConnect\Helpers\get_post_to_post_relationships_data;

/**
 * Class ClassicEditor
 *
 * @package TenUp\ContentConnect\UI
 */
class ClassicEditor {

	/**
	 * Setup the classic editor module.
	 *
	 * @since 1.7.0
	 */
	public function setup() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_classic_editor_assets' ) );
		add_action( 'add_meta_boxes', array( $this, 'add_relationships_meta_boxes' ), 10, 2 );
	}

	/**
	 * Enqueue classic editor assets.
	 *
	 * @since 1.7.0
	 *
	 * @param string $hook_suffix The current admin page.
	 * @return void
	 */
	public function enqueue_classic_editor_assets( $hook_suffix ) {
		global $post;

		if ( 'post-new.php' !== $hook_suffix && 'post.php' !== $hook_suffix ) {
			return;
		}

		if ( ! $post instanceof \WP_Post ) {
			return;
		}

		$use_block_editor = use_block_editor_for_post( $post );

		if ( $use_block_editor ) {
			return;
		}

		$relationships = get_post_to_post_relationships_data( $post );

		if ( empty( $relationships ) ) {
			return;
		}

		$asset_info = require CONTENT_CONNECT_PATH . 'dist/js/classic-editor.asset.php';

		wp_register_script(
			'wp-content-connect-classic-editor',
			CONTENT_CONNECT_URL . 'dist/js/classic-editor.js',
			$asset_info['dependencies'],
			$asset_info['version'],
			true
		);

		wp_enqueue_script( 'wp-content-connect-classic-editor' );

		wp_enqueue_style( 'wp-components' );
	}

	/**
	 * Adds the relationships meta boxes to the classic editor.
	 *
	 * @since 1.7.0
	 *
	 * @param string   $post_type The post type.
	 * @param \WP_Post $post      The post object.
	 * @return void
	 */
	public function add_relationships_meta_boxes( $post_type, $post ) {

		if ( ! $post instanceof \WP_Post ) {
			return;
		}

		$use_block_editor = use_block_editor_for_post( $post );

		if ( $use_block_editor ) {
			return;
		}

		$relationships = get_post_to_post_relationships_data( $post );

		if ( empty( $relationships ) ) {
			return;
		}

		foreach ( $relationships as $rel_key => $relationship ) {
			add_meta_box(
				'wp-content-connect-relationship-' . $rel_key,
				$relationship['labels']['name'],
				array( $this, 'render_relationship_meta_box' ),
				$post_type,
				'advanced',
				'high',
				array( 'relationship' => $relationship )
			);
		}
	}

	/**
	 * Renders a relationship meta box.
	 *
	 * @since 1.7.0
	 *
	 * @param \WP_Post $post The post object.
	 * @param array    $args The meta box arguments.
	 * @return void
	 */
	public function render_relationship_meta_box( $post, $args ) {

		if ( empty( $args['args']['relationship'] ) ) {
			return;
		}

		?>
		<div
			data-content-connect
			data-post-id="<?php echo esc_attr( $post->ID ); ?>"
			data-relationship="<?php echo esc_attr( wp_json_encode( $args['args']['relationship'] ) ); ?>"
			style="margin-top: 12px;"
		></div>
		<?php
	}
}
