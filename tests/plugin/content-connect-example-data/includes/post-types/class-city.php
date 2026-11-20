<?php
/**
 * City custom post type registration.
 *
 * @package ContentConnectExampleData
 */

namespace ContentConnectExampleData\PostTypes;

/**
 * City post type registration class.
 */
class City {

	/**
	 * Post type key.
	 *
	 * @var string
	 */
	const POST_TYPE = 'city';

	/**
	 * Registers the City post type.
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
				'label'        => __( 'Cities', 'content-connect-example-data' ),
				'labels'       => array(
					'name'          => __( 'Cities', 'content-connect-example-data' ),
					'singular_name' => __( 'City', 'content-connect-example-data' ),
					'add_new_item'  => __( 'Add New City', 'content-connect-example-data' ),
					'edit_item'     => __( 'Edit City', 'content-connect-example-data' ),
					'search_items'  => __( 'Search Cities', 'content-connect-example-data' ),
				),
				'public'       => true,
				'show_in_rest' => true,
				'supports'     => array( 'title', 'editor', 'custom-fields' ),
				'has_archive'  => true,
				'rewrite'      => array( 'slug' => 'cities' ),
			)
		);
	}
}
