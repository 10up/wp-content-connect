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
					'id'        => array(
						'description'       => __( 'The post ID.', 'tenup-content-connect' ),
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
						'validate_callback' => 'rest_validate_request_arg',
						'required'          => true,
						'minLength'         => 1,
					),
					'reltype'   => array(
						'description'       => __( 'The relationship type to filter relatioships by.', 'tenup-content-connect' ),
						'type'              => 'string',
						'default'           => 'all',
						'sanitize_callback' => 'sanitize_text_field',
						'validate_callback' => 'rest_validate_request_arg',
						'enum'              => array( 'all', 'post-to-post', 'post-to-user' ),
					),
					'post_type' => array(
						'description'       => __( 'The post type to filter relationships by.', 'tenup-content-connect' ),
						'type'              => 'string',
						'default'           => '',
						'sanitize_callback' => 'sanitize_text_field',
						'validate_callback' => 'rest_validate_request_arg',
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

		$reltype   = $request->get_param( 'reltype' );
		$post_type = $request->get_param( 'post_type' );

		$relationships = get_post_relationship_data( $object, $reltype, $post_type );
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

		$post_type = $request->get_param( 'post_type' );

		if ( ! empty( $post_type ) ) {

			$post_types = get_post_types();

			if ( ! in_array( $post_type, $post_types, true ) ) {
				return new \WP_Error(
					'rest_invalid_post_type',
					__( 'Invalid post type.', 'tenup-content-connect' ),
					array( 'status' => 400 )
				);
			}
		}

		return true;
	}
}
