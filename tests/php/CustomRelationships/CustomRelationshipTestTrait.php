<?php
/**
 * Helpers for creating custom relationships in tests.
 *
 * @package TenUp\ContentConnect\Tests\CustomRelationships
 */

namespace TenUp\ContentConnect\Tests\CustomRelationships;

use TenUp\ContentConnect\CustomRelationships\PostType;

/**
 * Creates `cc_relationship` posts with their meta.
 */
trait CustomRelationshipTestTrait {

	/**
	 * Registers the custom relationships post type and rebuilds the REST server.
	 *
	 * The test case unregisters custom post types after each test, so the post
	 * type has to be registered again for every test that uses it.
	 *
	 * @return void
	 */
	protected function register_custom_relationship_post_type() {
		global $wp_rest_server;

		( new PostType() )->register();

		$wp_rest_server = null; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
	}

	/**
	 * Creates a custom relationship.
	 *
	 * @param  array $meta   Meta values, merged over a post-to-post default.
	 * @param  array $postarr Optional post fields.
	 * @return int The custom relationship post ID.
	 */
	protected function create_custom_relationship( $meta = array(), $postarr = array() ) {

		$meta = array_merge(
			array(
				'rel_type'  => 'post-to-post',
				'rel_from'  => 'car',
				'rel_to'    => array( 'tire' ),
				'rel_name'  => 'stored',
				'from_args' => array(
					'enable_ui' => true,
					'sortable'  => true,
					'max_items' => 5,
					'labels'    => array( 'name' => 'Tires' ),
				),
				'to_args'   => array(
					'enable_ui' => true,
					'sortable'  => false,
					'max_items' => 10,
					'labels'    => array( 'name' => 'Cars' ),
				),
			),
			$meta
		);

		$post_id = wp_insert_post(
			array_merge(
				array(
					'post_type'   => PostType::POST_TYPE,
					'post_status' => 'publish',
					'post_title'  => 'Stored relationship',
				),
				$postarr
			)
		);

		foreach ( $meta as $key => $value ) {
			update_post_meta( $post_id, $key, $value );
		}

		return $post_id;
	}
}
