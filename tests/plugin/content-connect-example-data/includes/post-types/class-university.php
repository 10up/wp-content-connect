<?php
/**
 * University custom post type registration.
 *
 * @package ContentConnectExampleData
 */

namespace ContentConnectExampleData\PostTypes;

/**
 * University post type registration class.
 */
class University {

	/**
	 * Post type key.
	 *
	 * @var string
	 */
	const POST_TYPE = 'university';

	/**
	 * Registers the University post type.
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
				'label'        => __( 'Universities', 'content-connect-example-data' ),
				'labels'       => array(
					'name'          => __( 'Universities', 'content-connect-example-data' ),
					'singular_name' => __( 'University', 'content-connect-example-data' ),
					'add_new_item'  => __( 'Add New University', 'content-connect-example-data' ),
					'edit_item'     => __( 'Edit University', 'content-connect-example-data' ),
					'search_items'  => __( 'Search Universities', 'content-connect-example-data' ),
				),
				'public'       => true,
				'show_in_rest' => true,
				'supports'     => array( 'title', 'editor', 'custom-fields' ),
				'has_archive'  => true,
				'rewrite'      => array( 'slug' => 'universities' ),
			)
		);
	}
}
