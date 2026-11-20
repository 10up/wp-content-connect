<?php

namespace TenUp\ContentConnect\API\V2\Post\Route;

use TenUp\ContentConnect\API\V2\AbstractRoute;
use function TenUp\ContentConnect\Helpers\get_related_post_item_data;
use function TenUp\ContentConnect\Helpers\get_related_user_item_data;

/**
 * Abstract class for post REST API routes.
 *
 * This class provides a common setup method for registering post REST API routes.
 *
 * @package TenUp\ContentConnect\API\V2\Post\Route
 */
abstract class AbstractPostRoute extends AbstractRoute {

	/**
	 * {@inheritDoc}
	 */
	protected $rest_base = 'post';

	/**
	 * Retrieves the default params for a post route.
	 *
	 * @since 2.0.0
	 *
	 * @return array
	 */
	public function get_route_params() {
		return array(
			'id'       => array(
				'description'       => __( 'The current post ID.', 'wp-content-connect' ),
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
				'validate_callback' => 'rest_validate_request_arg',
				'required'          => true,
				'minimum'           => 1,
			),
			'rel_key'  => array(
				'description'       => __( 'The relationship key.', 'wp-content-connect' ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => 'rest_validate_request_arg',
				'required'          => true,
				'minLength'         => 1,
			),
			'rel_type' => array(
				'description'       => __( 'The relationship type.', 'wp-content-connect' ),
				'type'              => 'string',
				'default'           => 'post-to-post',
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => 'rest_validate_request_arg',
				'enum'              => array( 'post-to-post', 'post-to-user' ),
			),
		);
	}

	/**
	 * Prepare a collection of post items for the REST API.
	 *
	 * @since 2.0.0
	 *
	 * @param array                                            $items        Post objects.
	 * @param \TenUp\ContentConnect\Relationships\Relationship $relationship Relationship object.
	 * @return array
	 */
	protected function prepare_post_items( $items, $relationship ) {

		// Prime the post cache in a single query so preparing each item does not
		// trigger a get_post() query per item (the write path passes IDs).
		$post_ids = array_filter(
			array_map(
				static function ( $item ) {
					return $item instanceof \WP_Post ? $item->ID : (int) $item;
				},
				$items
			)
		);

		if ( ! empty( $post_ids ) ) {
			// Only the post objects are needed (ID, title, type); skip term/meta caches.
			_prime_post_caches( $post_ids, false, false );
		}

		$prepared_items = array();

		foreach ( $items as $item ) {
			$prepared_items[] = $this->prepare_post_item( $item, $relationship );
		}

		return $prepared_items;
	}

	/**
	 * Prepare a collection of user items for the REST API.
	 *
	 * @since 2.0.0
	 *
	 * @param array                                            $items        User objects.
	 * @param \TenUp\ContentConnect\Relationships\Relationship $relationship Relationship object.
	 * @return array
	 */
	protected function prepare_user_items( $items, $relationship ) {

		// Prime the user cache (objects + meta) in a single query so preparing
		// each item does not trigger a get_user_by() query per item.
		$user_ids = array_filter(
			array_map(
				static function ( $item ) {
					return $item instanceof \WP_User ? $item->ID : (int) $item;
				},
				$items
			)
		);

		if ( ! empty( $user_ids ) ) {
			cache_users( $user_ids );
		}

		$prepared_items = array();

		foreach ( $items as $item ) {
			$prepared_items[] = $this->prepare_user_item( $item, $relationship );
		}

		return $prepared_items;
	}

	/**
	 * Prepare a single post item for the REST API.
	 *
	 * @since 2.0.0
	 *
	 * @param int|\WP_Post                                     $item         Post object or ID.
	 * @param \TenUp\ContentConnect\Relationships\Relationship $relationship Relationship object.
	 * @return array
	 */
	protected function prepare_post_item( $item, $relationship ) {
		return get_related_post_item_data( $item, $relationship );
	}

	/**
	 * Prepare a single user item for the REST API.
	 *
	 * @since 2.0.0
	 *
	 * @param int|\WP_User                                     $item         User object or ID.
	 * @param \TenUp\ContentConnect\Relationships\Relationship $relationship Relationship object.
	 * @return array
	 */
	protected function prepare_user_item( $item, $relationship ) {
		return get_related_user_item_data( $item, $relationship );
	}
}
