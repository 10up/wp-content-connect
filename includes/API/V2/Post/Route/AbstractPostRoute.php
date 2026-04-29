<?php

namespace TenUp\ContentConnect\API\V2\Post\Route;

use TenUp\ContentConnect\API\V2\AbstractRoute;

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
	 * @return array
	 */
	public function get_route_params() {
		return array(
			'id'       => array(
				'description'       => __( 'The current post ID.', 'tenup-content-connect' ),
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
				'validate_callback' => 'rest_validate_request_arg',
				'required'          => true,
				'minimum'           => 1,
			),
			'rel_key'  => array(
				'description'       => __( 'The relationship key.', 'tenup-content-connect' ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => 'rest_validate_request_arg',
				'required'          => true,
				'minLength'         => 1,
			),
			'rel_type' => array(
				'description'       => __( 'The relationship type.', 'tenup-content-connect' ),
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
	 * @param array  $items        Post objects.
	 * @param string $relationship Relationship name.
	 * @return array
	 */
	protected function prepare_post_items( $items, $relationship ) {

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
	 * @param array  $items        User objects.
	 * @param string $relationship Relationship name.
	 * @return array
	 */
	protected function prepare_user_items( $items, $relationship ) {

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
	 * @param int|\WP_Post $item         Post object or ID.
	 * @param string       $relationship Relationship name.
	 * @return array
	 */
	protected function prepare_post_item( $item, $relationship ) {

		if ( is_numeric( $item ) ) {
			$item = get_post( $item );
		}

		$item_data = array(
			'id'   => $item->ID,
			'name' => $item->post_title,
			'type' => $item->post_type,
		);

		/** This filter is documented in includes/Helpers.php */
		$item_data = apply_filters( 'tenup_content_connect_final_post', $item_data, $relationship );

		/** This filter is documented in includes/Helpers.php */
		$item_data = apply_filters( 'tenup_content_connect_post_item_data', $item_data, $item, $relationship );

		if ( empty( $item_data['type'] ) ) { // This is required for the 10up Content Picker component.
			$item_data['type'] = $item->post_type;
		}

		if ( empty( $item_data['uuid'] ) ) { // This is required for the 10up Content Picker component.
			$item_data['uuid'] = $this->generate_deterministic_uuid( $relationship, 'post', $item->ID );
		}

		return $item_data;
	}

	/**
	 * Prepare a single user item for the REST API.
	 *
	 * @since 2.0.0
	 *
	 * @param int|\WP_User $item         User object or ID.
	 * @param string       $relationship Relationship name.
	 * @return array
	 */
	protected function prepare_user_item( $item, $relationship ) {

		if ( is_numeric( $item ) ) {
			$item = get_user_by( 'ID', $item );
		}

		$item_name = $item->display_name;

		if ( empty( $item_name ) ) {
			$item_name = array( $item->first_name, $item->last_name );
			$item_name = array_filter( $item_name );
			$item_name = implode( ' ', $item_name );
		}

		$item_data = array(
			'id'   => $item->ID,
			'name' => $item_name,
			'type' => 'user',
		);

		/** This filter is documented in includes/Helpers.php */
		$item_data = apply_filters( 'tenup_content_connect_final_user', $item_data, $relationship );

		/** This filter is documented in includes/Helpers.php */
		$item_data = apply_filters( 'tenup_content_connect_user_item_data', $item_data, $item, $relationship );

		if ( empty( $item_data['type'] ) ) { // This is required for the 10up Content Picker component.
			$item_data['type'] = 'user';
		}

		if ( empty( $item_data['uuid'] ) ) { // This is required for the 10up Content Picker component.
			$item_data['uuid'] = $this->generate_deterministic_uuid( $relationship, 'user', $item->ID );
		}

		return $item_data;
	}

	/**
	 * Generate a deterministic UUID-shaped identifier for an item within a relationship.
	 *
	 * Built from the relationship name, the item type, and the item ID so the same
	 * tuple always produces the same UUID. This keeps REST responses cacheable
	 * (CDN/edge layers) while still satisfying the 10up Content Picker requirement
	 * for a stable per-item `uuid` key.
	 *
	 * The output matches the canonical UUID 8-4-4-4-12 hex shape but is not RFC 4122
	 * compliant. Consumers should treat it as an opaque identifier.
	 *
	 * @since 2.0.0
	 *
	 * @param mixed  $relationship Relationship object (must expose a `name` property) or relationship name string.
	 * @param string $item_type    'post' or 'user'.
	 * @param int    $item_id      Item ID.
	 * @return string
	 */
	protected function generate_deterministic_uuid( $relationship, $item_type, $item_id ) {

		$rel_name = is_object( $relationship ) && isset( $relationship->name )
			? (string) $relationship->name
			: (string) $relationship;

		$hash = md5( $rel_name . '|' . $item_type . '|' . (int) $item_id );

		return sprintf(
			'%s-%s-%s-%s-%s',
			substr( $hash, 0, 8 ),
			substr( $hash, 8, 4 ),
			substr( $hash, 12, 4 ),
			substr( $hash, 16, 4 ),
			substr( $hash, 20, 12 )
		);
	}
}
