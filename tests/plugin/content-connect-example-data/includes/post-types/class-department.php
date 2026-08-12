<?php
/**
 * Department custom post type registration.
 *
 * Intentionally uses the CLASSIC editor (block editor disabled) so the E2E
 * suite can exercise Content Connect's classic-editor meta-box path. REST is
 * still enabled so Content Connect's own REST routes and the ContentPicker
 * search continue to work.
 *
 * @package ContentConnectExampleData
 */

namespace ContentConnectExampleData\PostTypes;

/**
 * Department post type registration class.
 */
class Department {

	/**
	 * Post type key.
	 *
	 * @var string
	 */
	const POST_TYPE = 'department';

	/**
	 * Registers the Department post type and forces the classic editor for it.
	 *
	 * @return void
	 */
	public static function register(): void {
		if ( post_type_exists( self::POST_TYPE ) ) {
			return;
		}

		register_post_type(
			self::POST_TYPE,
			array(
				'label'        => __( 'Departments', 'content-connect-example-data' ),
				'labels'       => array(
					'name'          => __( 'Departments', 'content-connect-example-data' ),
					'singular_name' => __( 'Department', 'content-connect-example-data' ),
					'add_new_item'  => __( 'Add New Department', 'content-connect-example-data' ),
					'edit_item'     => __( 'Edit Department', 'content-connect-example-data' ),
					'search_items'  => __( 'Search Departments', 'content-connect-example-data' ),
				),
				'public'       => true,
				'show_in_rest' => true,
				'supports'     => array( 'title', 'editor', 'custom-fields' ),
				'has_archive'  => true,
				'rewrite'      => array( 'slug' => 'departments' ),
			)
		);

		// Force the classic editor for this post type only.
		add_filter( 'use_block_editor_for_post_type', array( __CLASS__, 'disable_block_editor' ), 10, 2 );
	}

	/**
	 * Disables the block editor for the Department post type.
	 *
	 * @param bool   $use_block_editor Whether the post type uses the block editor.
	 * @param string $post_type        The post type being checked.
	 * @return bool
	 */
	public static function disable_block_editor( $use_block_editor, $post_type ) {
		if ( self::POST_TYPE === $post_type ) {
			return false;
		}

		return $use_block_editor;
	}
}
