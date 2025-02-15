<?php

namespace TenUp\ContentConnect\API\V2\Post\Route;

use function TenUp\ContentConnect\Helpers\get_post_relationship_data;

/**
 * Class Relationships
 *
 * REST API endpoint for post relationships.
 *
 * @package TenUp\ContentConnect\API\V2\Post
 */
class Relationships extends AbstractPostRoute {

	/**
	 * {@inheritDoc}
	 *
	 * @since 1.7.0
	 */
	public function register_routes() {

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)/relationships',
			array(
				'args' => array(
					'id'        => array(
						'description'       => __( 'The current post ID.', 'tenup-content-connect' ),
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
						'validate_callback' => 'rest_validate_request_arg',
						'required'          => true,
						'minLength'         => 1,
					),
					'rel_type'  => array(
						'description'       => __( 'The relationship type to filter relatioships by.', 'tenup-content-connect' ),
						'type'              => 'string',
						'default'           => 'any',
						'sanitize_callback' => 'sanitize_text_field',
						'validate_callback' => 'rest_validate_request_arg',
						'enum'              => array( 'any', 'post-to-post', 'post-to-user' ),
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
	 * @since 1.7.0
	 *
	 * @param  \WP_REST_Request $request Full details about the request.
	 * @return \WP_REST_Response|\WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function get_items( \WP_REST_Request $request ) {

		$post = $this->get_post( $request['id'] );

		if ( is_wp_error( $post ) ) {
			return $post;
		}

		$rel_type  = $request->get_param( 'rel_type' );
		$post_type = $request->get_param( 'post_type' );

		$relationships = get_post_relationship_data( $post, $rel_type, $post_type );
		$response      = rest_ensure_response( $relationships );

		return $response;
	}

	/**
	 * Checks if a given request has access to retrieve relationships for a post.
	 *
	 * @since 1.7.0
	 *
	 * @param  \WP_REST_Request $request Full details about the request.
	 * @return true|WP_Error True if the request has access, WP_Error object otherwise.
	 */
	public function get_items_permissions_check( \WP_REST_Request $request ) {

		if ( ! is_user_logged_in() ) {
			return new \WP_Error(
				'rest_forbidden',
				__( 'Sorry, you are not allowed to view relationships for this post.', 'tenup-content-connect' ),
				array( 'status' => 401 )
			);
		}

		$post = $this->get_post( $request['id'] );

		if ( is_wp_error( $post ) ) {
			return $post;
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
