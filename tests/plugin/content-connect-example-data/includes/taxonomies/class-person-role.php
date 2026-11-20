<?php
/**
 * Person Role taxonomy registration.
 *
 * @package ContentConnectExampleData
 */

namespace ContentConnectExampleData\Taxonomies;

/**
 * Person Role taxonomy registration class.
 */
class Person_Role {

	/**
	 * Taxonomy key.
	 *
	 * @var string
	 */
	const TAXONOMY = 'person_role';

	/**
	 * Registers the Person Role taxonomy on the person post type.
	 *
	 * @return void
	 */
	public static function register(): void {
		if ( taxonomy_exists( self::TAXONOMY ) ) {
			return;
		}

		register_taxonomy(
			self::TAXONOMY,
			'person',
			array(
				'label'        => __( 'Roles', 'content-connect-example-data' ),
				'labels'       => array(
					'name'          => __( 'Roles', 'content-connect-example-data' ),
					'singular_name' => __( 'Role', 'content-connect-example-data' ),
				),
				'public'       => true,
				'hierarchical' => false,
				'show_in_rest' => true,
			)
		);
	}
}
