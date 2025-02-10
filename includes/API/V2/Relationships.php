<?php

namespace TenUp\ContentConnect\API\V2;

use TenUp\ContentConnect\API\Route;

use function TenUp\ContentConnect\Helpers\get_post_relationship_data;

class Relationships extends Route {

	/**
	 * {@inheritDoc}
	 */
	protected $rest_base = 'relationships';

	/**
	 * {@inheritDoc}
	 */
	public function register_routes() {

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)',
			array(
				'args' => array(
					'id' => array(
						'description'       => __( 'The post ID.', 'tenup-content-connect' ),
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
						'validate_callback' => 'rest_validate_request_arg',
						'required'          => true,
						'minLength'         => 1,
					),
				),
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
				),
			),
		);
	}

	/**
	 * Retrieves a collection of relationships for a post.
	 *
	 * @param  \WP_REST_Request $request Full details about the request.
	 * @return \WP_REST_Response|\WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function get_items( $request ) {

		$object = $this->get_post( $request['id'] );

		if ( is_wp_error( $object ) ) {
			return $object;
		}

		$relationships = get_post_relationship_data( $object );
		$response      = rest_ensure_response( $relationships );

		return $response;
	}

	/**
	 * Checks if a given request has access to retrieve relationships for a post.
	 *
	 * @param  \WP_REST_Request $request Full details about the request.
	 * @return bool|WP_Error True if the request has read access for the item, WP_Error object or false otherwise.
	 */
	public function get_items_permissions_check( $request ) {

		$object = $this->get_post( $request['id'] );

		if ( is_wp_error( $object ) ) {
			return $object;
		}

		return true;
	}
}
