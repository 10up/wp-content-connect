<?php
/**
 * Person custom post type registration.
 *
 * @package ContentConnectExampleData
 */

namespace ContentConnectExampleData\PostTypes;

/**
 * Person post type registration class.
 */
class Person {

	/**
	 * Post type key.
	 *
	 * @var string
	 */
	const POST_TYPE = 'person';

	/**
	 * Registers the Person post type.
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
				'label'        => __( 'People', 'content-connect-example-data' ),
				'labels'       => array(
					'name'          => __( 'People', 'content-connect-example-data' ),
					'singular_name' => __( 'Person', 'content-connect-example-data' ),
					'add_new_item'  => __( 'Add New Person', 'content-connect-example-data' ),
					'edit_item'     => __( 'Edit Person', 'content-connect-example-data' ),
					'search_items'  => __( 'Search People', 'content-connect-example-data' ),
				),
				'public'       => true,
				'show_in_rest' => true,
				'supports'     => array( 'title', 'editor', 'custom-fields' ),
				'has_archive'  => true,
				'rewrite'      => array( 'slug' => 'people' ),
			)
		);
	}
}
