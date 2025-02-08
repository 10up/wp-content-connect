<?php

namespace TenUp\ContentConnect\API\V2;

use TenUp\ContentConnect\API\Route;

class Search extends Route {

	/**
	 * {@inheritDoc}
	 */
	protected $rest_base = 'search';

	/**
	 * {@inheritDoc}
	 */
	public function register_routes() {

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'get_items' ),
				'permission_callback' => array( $this, 'get_items_permission_check' ),
			)
		);
	}

	/**
	 * Retrieves a collection of search results.
	 *
	 * @param  \WP_REST_Request $request Full details about the request.
	 * @return \WP_REST_Response|\WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function get_items( $request ) {

		// @todo Implement search functionality.
		return new \WP_REST_Response( array(), 200 );
	}

	/**
	 * Checks if a given request has access to search content.
	 *
	 * @param  \WP_REST_Request $request Full details about the request.
	 * @return true|\WP_Error True if the request has search access, WP_Error object otherwise.
	 */
	public function get_items_permission_check( $request ) {
		return is_user_logged_in();
	}
}
