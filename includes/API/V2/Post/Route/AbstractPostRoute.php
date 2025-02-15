<?php

namespace TenUp\ContentConnect\API\V2\Post\Route;

use TenUp\ContentConnect\API\V2\AbstractRoute;

/**
 * Abstract class for post REST API routes.
 *
 * This class provides a common setup method for registering post REST API routes.
 *
 * @package TenUp\ContentConnect\API\V2\Post
 */
abstract class AbstractPostRoute extends AbstractRoute {

	/**
	 * {@inheritDoc}
	 *
	 * @since 1.7.0
	 */
	protected $rest_base = 'post';

	/**
	 * Prepare a collection of post items for the REST API.
	 *
	 * @since 1.7.0
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
	 * @since 1.7.0
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
	 * @since 1.7.0
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
			'ID'   => $item->ID,
			'name' => $item->post_title,
		);

		/** This filter is documented in includes/Helpers.php */
		$item_data = apply_filters( 'tenup_content_connect_post_ui_item_data', $item_data, $item, $relationship );

		return $item_data;
	}

	/**
	 * Prepare a single user item for the REST API.
	 *
	 * @since 1.7.0
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
			'ID'   => $item->ID,
			'name' => $item_name,
		);

		/** This filter is documented in includes/Helpers.php */
		$item_data = apply_filters( 'tenup_content_connect_user_ui_item_data', $item_data, $item, $relationship );

		return $item_data;
	}
}
