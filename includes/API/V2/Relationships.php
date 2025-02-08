<?php

namespace TenUp\ContentConnect\API\V2;

use TenUp\ContentConnect\API\Route;

use function TenUp\ContentConnect\Helpers\get_registry;

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
					'id'       => array(
						'description'       => __( 'Unique identifier for the post on a post to post relationship, or user on a post to user relationship.', 'tenup-content-connect' ),
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
						'validate_callback' => 'rest_validate_request_arg',
						'required'          => true,
						'minLength'         => 1,
					),
					'type'     => array(
						'description'       => __( 'The relationship type.', 'tenup-content-connect' ),
						'type'              => 'string',
						'default'           => 'post-to-post',
						'sanitize_callback' => 'sanitize_text_field',
						'validate_callback' => 'rest_validate_request_arg',
						'enum'              => array( 'post-to-post', 'post-to-user' ),
					),
					'name'     => array(
						'description'       => __( 'The unique name for the relationship.', 'tenup-content-connect' ),
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
						'validate_callback' => 'rest_validate_request_arg',
						'required'          => true,
						'minLength'         => 1,
					),
					'relation' => array(
						'description'       => __( 'How all of the segments in the relationship should be combined.', 'tenup-content-connect' ),
						'type'              => 'string',
						'default'           => 'AND',
						'sanitize_callback' => 'sanitize_text_field',
						'validate_callback' => 'rest_validate_request_arg',
						'enum'              => array( 'AND', 'OR' ),
					),
					'status'   => array(
						'description'       => __( 'The status of the post.', 'tenup-content-connect' ),
						'type'              => 'string',
						'default'           => 'publish',
						'sanitize_callback' => 'sanitize_text_field',
						'validate_callback' => 'rest_validate_request_arg',
						'enum'              => array_keys( get_post_stati( array( 'internal' => false ) ) ),
					),
					'page'     => array(
						'description'       => __( 'Current page of the collection.', 'tenup-content-connect' ),
						'type'              => 'integer',
						'default'           => 1,
						'sanitize_callback' => 'absint',
						'validate_callback' => 'rest_validate_request_arg',
						'minimum'           => 1,
					),
					'per_page' => array(
						'description'       => __( 'Maximum number of items to be returned in result set.', 'tenup-content-connect' ),
						'type'              => 'integer',
						'default'           => 10,
						'minimum'           => 1,
						'maximum'           => 100,
						'sanitize_callback' => 'absint',
						'validate_callback' => 'rest_validate_request_arg',
					),
				),
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
				),
				array(
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_items' ),
					'permission_callback' => array( $this, 'update_items_permissions_check' ),
				),
			),
		);
	}

	/**
	 * Retrieves a collection of posts.
	 *
	 * @param  \WP_REST_Request $request Full details about the request.
	 * @return \WP_REST_Response|\WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function get_items( $request ) {

		$type = $request->get_param( 'type' );

		$object = false;
		if ( 'post-to-user' === $type ) {
			$object = $this->get_user( $request['id'] );
		} else {
			$object = $this->get_post( $request['id'] );
		}

		if ( is_wp_error( $object ) ) {
			return $object;
		}

		$relationship_name = $request->get_param( 'name' );
		$relation          = $request->get_param( 'relation' );
		$paged             = $request->get_param( 'page' );
		$status            = $request->get_param( 'post_status' );
		$paged             = $request->get_param( 'page' );
		$posts_per_page    = $request->get_param( 'per_page' );

		$relationship_query = array(
			'relation' => $relation,
		);

		switch ( $type ) {
			case 'post-to-post':
				$relationship_query[] = array(
					'name'            => $relationship_name,
					'related_to_post' => $object->ID,
				);
				break;
			case 'post-to-user':
				$relationship_query[] = array(
					'name'            => $relationship_name,
					'related_to_user' => $object->ID,
				);
				break;
		}

		$query_args = array(
			'fields'             => 'ids',
			'post_type'          => 'post',
			'post_status'        => $status,
			'paged'              => $paged,
			'posts_per_page'     => $posts_per_page,
			'relationship_query' => $relationship_query,
			'orderby'            => 'relationship',
		);

		$query = new \WP_Query( $query_args );

		$results = array();
		if ( $query->have_posts() ) {
			$results = $query->get_posts();
		}

		$page        = (int) $query_args['paged'];
		$total_posts = $query->found_posts;

		if ( $total_posts < 1 && $page > 1 ) {
			// Out-of-bounds, run the query again without LIMIT for total count.
			unset( $query_args['paged'] );

			$count_query = new \WP_Query();
			$count_query->query( $query_args );
			$total_posts = $count_query->found_posts;
		}

		$max_pages = (int) ceil( $total_posts / (int) $query->query_vars['posts_per_page'] );

		if ( $page > $max_pages && $total_posts > 0 ) {
			return new \WP_Error(
				'rest_post_invalid_page_number',
				__( 'The page number requested is larger than the number of pages available.', 'tenup-content-connect' ),
				array( 'status' => 400 )
			);
		}

		$response = rest_ensure_response( $results );

		$response->header( 'X-WP-Total', (int) $total_posts );
		$response->header( 'X-WP-TotalPages', (int) $max_pages );

		$request_params    = $request->get_query_params();
		$relationships_url = rest_url( sprintf( '/%s/%s', $this->namespace, $this->rest_base ) );
		$base              = add_query_arg( urlencode_deep( $request_params ), $relationships_url );

		if ( $page > 1 ) {
			$prev_page = $page - 1;

			if ( $prev_page > $max_pages ) {
				$prev_page = $max_pages;
			}

			$prev_link = add_query_arg( 'page', $prev_page, $base );
			$response->link_header( 'prev', $prev_link );
		}
		if ( $max_pages > $page ) {
			$next_page = $page + 1;
			$next_link = add_query_arg( 'page', $next_page, $base );

			$response->link_header( 'next', $next_link );
		}

		return $response;
	}

	/**
	 * Checks if a given request has access to read posts.
	 *
	 * @param  \WP_REST_Request $request Full details about the request.
	 * @return bool|WP_Error True if the request has read access for the item, WP_Error object or false otherwise.
	 */
	public function get_items_permissions_check( $request ) {

		$type = $request->get_param( 'type' );

		$object = false;
		if ( 'post-to-user' === $type ) {
			$object = $this->get_user( $request['id'] );
		} else {
			$object = $this->get_post( $request['id'] );
		}

		if ( is_wp_error( $object ) ) {
			return $object;
		}

		$relationship_name = $request->get_param( 'name' );

		$relationships = array();
		if ( 'post-to-user' === $type ) {
			$relationships = get_registry()->get_post_to_user_relationships();
		} else {
			$relationships = get_registry()->get_post_to_post_relationships();
		}

		if ( empty( $relationships ) ) {
			return new \WP_Error(
				'rest_relationship_not_found',
				__( 'The requested relationship was not found.', 'tenup-content-connect' ),
				array( 'status' => 404 )
			);
		}

		$relationship_names = wp_list_pluck( $relationships, 'name' );

		if ( ! in_array( $relationship_name, $relationship_names, true ) ) {
			return new \WP_Error(
				'rest_relationship_not_found',
				__( 'The requested relationship was not found.', 'tenup-content-connect' ),
				array( 'status' => 404 )
			);
		}

		return true;
	}

	public function update_items() {
		// @todo Implement this.
	}

	public function update_items_permissions_check() {
		// @todo Implement this.
	}

	/**
	 * Get the post, if the ID is valid.
	 *
	 * @param  int $id Supplied ID.
	 * @return \WP_Post|\WP_Error Post object if ID is valid, WP_Error otherwise.
	 */
	protected function get_post( $id ) {

		$error = new \WP_Error(
			'rest_post_invalid_id',
			__( 'Invalid post ID.', 'tenup-content-connect' ),
			array( 'status' => 404 )
		);

		if ( (int) $id <= 0 ) {
			return $error;
		}

		$post = get_post( (int) $id );

		if ( empty( $post ) || empty( $post->ID ) ) {
			return $error;
		}

		return $post;
	}

	/**
	 * Get the user, if the ID is valid.
	 *
	 * @param  int $id Supplied ID.
	 * @return \WP_User|\WP_Error True if ID is valid, WP_Error otherwise.
	 */
	protected function get_user( $id ) {

		$error = new \WP_Error(
			'rest_user_invalid_id',
			__( 'Invalid user ID.', 'tenup-content-connect' ),
			array( 'status' => 404 )
		);

		if ( (int) $id <= 0 ) {
			return $error;
		}

		$user = get_userdata( (int) $id );

		if ( empty( $user ) || ! $user->exists() ) {
			return $error;
		}

		if ( is_multisite() && ! is_user_member_of_blog( $user->ID ) ) {
			return $error;
		}

		return $user;
	}
}
