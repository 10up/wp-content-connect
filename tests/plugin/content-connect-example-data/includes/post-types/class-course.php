<?php
/**
 * Course custom post type registration.
 *
 * @package ContentConnectExampleData
 */

namespace ContentConnectExampleData\PostTypes;

/**
 * Course post type registration class.
 */
class Course {

	/**
	 * Post type key.
	 *
	 * @var string
	 */
	const POST_TYPE = 'course';

	/**
	 * Registers the Course post type.
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
				'label'        => __( 'Courses', 'content-connect-example-data' ),
				'labels'       => array(
					'name'          => __( 'Courses', 'content-connect-example-data' ),
					'singular_name' => __( 'Course', 'content-connect-example-data' ),
					'add_new_item'  => __( 'Add New Course', 'content-connect-example-data' ),
					'edit_item'     => __( 'Edit Course', 'content-connect-example-data' ),
					'search_items'  => __( 'Search Courses', 'content-connect-example-data' ),
				),
				'public'       => true,
				'show_in_rest' => true,
				'supports'     => array( 'title', 'editor', 'custom-fields' ),
				'has_archive'  => true,
				'rewrite'      => array( 'slug' => 'courses' ),
			)
		);
	}
}
