<?php
/**
 * Campus custom post type registration.
 *
 * @package ContentConnectExampleData
 */

namespace ContentConnectExampleData\PostTypes;

/**
 * Campus post type registration class.
 */
class Campus {

	/**
	 * Post type key.
	 *
	 * @var string
	 */
	const POST_TYPE = 'campus';

	/**
	 * Registers the Campus post type.
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
				'label'        => __( 'Campuses', 'content-connect-example-data' ),
				'labels'       => array(
					'name'          => __( 'Campuses', 'content-connect-example-data' ),
					'singular_name' => __( 'Campus', 'content-connect-example-data' ),
					'add_new_item'  => __( 'Add New Campus', 'content-connect-example-data' ),
					'edit_item'     => __( 'Edit Campus', 'content-connect-example-data' ),
					'search_items'  => __( 'Search Campuses', 'content-connect-example-data' ),
				),
				'public'       => true,
				'show_in_rest' => true,
				'supports'     => array( 'title', 'editor', 'custom-fields' ),
				'has_archive'  => true,
				'rewrite'      => array( 'slug' => 'campuses' ),
			)
		);
	}
}
