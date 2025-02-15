<?php

namespace TenUp\ContentConnect\API\V2\Post\Route;

use function TenUp\ContentConnect\Helpers\get_registry;

/**
 * Class RelatedEntities
 *
 * REST API endpoint for post related entities (posts or users).
 *
 * @package TenUp\ContentConnect\API\V2\Post
 */
class RelatedEntities extends AbstractPostRoute {

	/**
	 * The relationship object.
	 *
	 * @since 1.7.0
	 *
	 * @var \TenUp\ContentConnect\Relationships\PostToPost|\TenUp\ContentConnect\Relationships\PostToUser
	 */
	protected $relationship = null;

	/**
	 * {@inheritDoc}
	 *
	 * @since 1.7.0
	 */
	public function register_routes() {

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)/related',
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
					'rel_key'   => array(
						'description'       => __( 'The relationship key.', 'tenup-content-connect' ),
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
						'validate_callback' => 'rest_validate_request_arg',
						'required'          => true,
						'minLength'         => 1,
					),
					'rel_type'  => array(
						'description'       => __( 'The relationship type.', 'tenup-content-connect' ),
						'type'              => 'string',
						'default'           => 'post-to-post',
						'sanitize_callback' => 'sanitize_text_field',
						'validate_callback' => 'rest_validate_request_arg',
						'enum'              => array( 'post-to-post', 'post-to-user' ),
					),
					'post_type' => array(
						'description'       => __( 'The type of post to query for.', 'tenup-content-connect' ),
						'type'              => 'string',
						'default'           => '',
						'sanitize_callback' => 'sanitize_text_field',
						'validate_callback' => array( $this, 'validate_post_type_request_arg' ),
					),
				),
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
					'args'                => array(
						'post_status' => array(
							'description'       => __( 'Limit result set to posts assigned one or more statuses.', 'tenup-content-connect' ),
							'type'              => 'array',
							'default'           => 'publish',
							'sanitize_callback' => 'sanitize_text_field',
							'validate_callback' => 'rest_validate_request_arg',
							'items'             => array(
								'enum' => array_merge( array_keys( get_post_stati() ), array( 'any' ) ),
								'type' => 'string',
							),
						),
						'page'        => array(
							'description'       => __( 'Current page of the collection.', 'tenup-content-connect' ),
							'type'              => 'integer',
							'default'           => 1,
							'sanitize_callback' => 'absint',
							'validate_callback' => 'rest_validate_request_arg',
							'minimum'           => 1,
						),
						'per_page'    => array(
							'description'       => __( 'Maximum number of items to be returned in result set.', 'tenup-content-connect' ),
							'type'              => 'integer',
							'default'           => 10,
							'minimum'           => 1,
							'maximum'           => 100,
							'sanitize_callback' => 'absint',
							'validate_callback' => 'rest_validate_request_arg',
						),
						'order'       => array(
							'description'       => __( 'Order sort attribute ascending or descending.', 'tenup-content-connect' ),
							'type'              => 'string',
							'default'           => 'asc',
							'enum'              => array( 'asc', 'desc' ),
							'sanitize_callback' => 'sanitize_text_field',
							'validate_callback' => 'rest_validate_request_arg',
						),
						'orderby'     => array(
							'description'       => __( 'Sort collection by relationship or object attribute.', 'tenup-content-connect' ),
							'type'              => 'string',
							'default'           => 'relationship',
							'sanitize_callback' => 'sanitize_text_field',
							'validate_callback' => array( $this, 'validate_orderby_request_arg' ),
						),
					),
				),
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'update_items' ),
					'permission_callback' => array( $this, 'update_items_permissions_check' ),
					'args'                => array(
						'related_ids' => array(
							'description'       => __( 'List of related IDs.', 'tenup-content-connect' ),
							'type'              => 'array',
							'default'           => array(),
							'sanitize_callback' => 'wp_parse_id_list',
							'validate_callback' => 'rest_validate_request_arg',
							'items'             => array(
								'type' => 'integer',
							),
						),
					),
				),
				array(
					'methods'             => 'PUT',
					'callback'            => array( $this, 'add_item' ),
					'permission_callback' => array( $this, 'add_item_permissions_check' ),
					'args'                => array(
						'related_id' => array(
							'description'       => __( 'The related ID.', 'tenup-content-connect' ),
							'type'              => 'integer',
							'sanitize_callback' => 'absint',
							'validate_callback' => 'rest_validate_request_arg',
						),
					),
				),
				array(
					'methods'             => \WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_item' ),
					'permission_callback' => array( $this, 'delete_item_permissions_check' ),
					'args'                => array(
						'related_id' => array(
							'description'       => __( 'The related ID.', 'tenup-content-connect' ),
							'type'              => 'integer',
							'sanitize_callback' => 'absint',
							'validate_callback' => 'rest_validate_request_arg',
						),
					),
				),
			),
		);
	}

	/**
	 * Retrieves a collection of related entities for a post.
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

		$rel_type = $request->get_param( 'rel_type' );

		$prepared_items = array();
		if ( 'post-to-user' === $rel_type ) {
			$prepared_items = $this->get_related_users( $post, $request );
		} else {
			$prepared_items = $this->get_related_posts( $post, $request );
		}

		$page     = (int) $request->get_param( 'page' );
		$per_page = (int) $request->get_param( 'per_page' );
		$total    = $prepared_items['total'];

		$max_pages = (int) ceil( $total / (int) $per_page );

		if ( $page > $max_pages && $total > 0 ) {
			return new \WP_Error(
				'rest_post_invalid_page_number',
				__( 'The page number requested is larger than the number of pages available.', 'tenup-content-connect' ),
				array( 'status' => 400 )
			);
		}

		$response = rest_ensure_response( $prepared_items['items'] );

		$response->header( 'X-WP-Total', (int) $total );
		$response->header( 'X-WP-TotalPages', (int) $max_pages );

		$request_params = $request->get_query_params();
		$url            = rest_url( sprintf( '/%s/%s/%d/related', $this->namespace, $this->rest_base, $request['id'] ) );
		$base           = add_query_arg( urlencode_deep( $request_params ), $url );

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
	 * Checks if a given request has access to retrieve related entities for a post.
	 *
	 * @since 1.7.0
	 *
	 * @param  \WP_REST_Request $request Full details about the request.
	 * @return true|\WP_Error True if the request has access, WP_Error object otherwise.
	 */
	public function get_items_permissions_check( \WP_REST_Request $request ) {

		if ( ! is_user_logged_in() ) {
			return new \WP_Error(
				'rest_forbidden',
				__( 'Sorry, you are not allowed to retrieve related entities for this post.', 'tenup-content-connect' ),
				array( 'status' => 401 )
			);
		}

		return $this->permissions_check( $request );
	}

	/**
	 * Updates related entities for a post.
	 *
	 * @since 1.7.0
	 *
	 * @param  \WP_REST_Request $request Full details about the request.
	 * @return \WP_REST_Response|\WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function update_items( $request ) {

		$post = $this->get_post( $request['id'] );

		if ( is_wp_error( $post ) ) {
			return $post;
		}

		$related_ids = $request->get_param( 'related_ids' );

		if ( empty( $related_ids ) ) {
			return new \WP_Error(
				'rest_invalid_param',
				__( 'No related entities provided.', 'tenup-content-connect' ),
				array( 'status' => 400 )
			);
		}

		$rel_type = $request->get_param( 'rel_type' );

		$prepared_items = array();
		if ( 'post-to-user' === $rel_type ) {
			$prepared_items = $this->update_related_users( $post, $request );
		} else {
			$prepared_items = $this->update_related_posts( $post, $request );
		}

		$response = rest_ensure_response( $prepared_items );

		return $response;
	}

	/**
	 * Checks if a given request has access to update related entities for a post.
	 *
	 * @since 1.7.0
	 *
	 * @param  \WP_REST_Request $request Full details about the request.
	 * @return true|\WP_Error True if the request has access, WP_Error object otherwise.
	 */
	public function update_items_permissions_check( $request ) {

		if ( ! is_user_logged_in() ) {
			return new \WP_Error(
				'rest_forbidden',
				__( 'Sorry, you are not allowed to update related entities for this post.', 'tenup-content-connect' ),
				array( 'status' => 401 )
			);
		}

		if ( ! current_user_can( 'edit_post', $request['id'] ) ) {
			return new \WP_Error(
				'rest_cannot_edit',
				__( 'Sorry, you are not allowed to update this post.', 'tenup-content-connect' ),
				array( 'status' => 401 )
			);
		}

		return $this->permissions_check( $request );
	}

	/**
	 * Adds a related entity for a post.
	 *
	 * @since 1.7.0
	 *
	 * @param  \WP_REST_Request $request Full details about the request.
	 * @return \WP_REST_Response|\WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function add_item( $request ) {

		$post = $this->get_post( $request['id'] );

		if ( is_wp_error( $post ) ) {
			return $post;
		}

		$related_id = $request->get_param( 'related_id' );

		if ( empty( $related_id ) ) {
			return new \WP_Error(
				'rest_invalid_param',
				__( 'No related entity provided.', 'tenup-content-connect' ),
				array( 'status' => 400 )
			);
		}

		$this->relationship->add_relationship( $post->ID, $related_id );

		$rel_type = $request->get_param( 'rel_type' );

		$prepared_items = array();
		if ( 'post-to-user' === $rel_type ) {
			$prepared_items = $this->get_related_users( $post, $request );
		} else {
			$prepared_items = $this->get_related_posts( $post, $request );
		}

		$response = rest_ensure_response( $prepared_items['items'] );

		return $response;
	}

	/**
	 * Checks if a given request has access to add a related entity for a post.
	 *
	 * @since 1.7.0
	 *
	 * @param  \WP_REST_Request $request Full details about the request.
	 * @return true|\WP_Error True if the request has access, WP_Error object otherwise.
	 */
	public function add_item_permissions_check( $request ) {
		return $this->update_items_permissions_check( $request );
	}

	/**
	 * Deletes a related entity for a post.
	 *
	 * @since 1.7.0
	 *
	 * @param  \WP_REST_Request $request Full details about the request.
	 * @return \WP_REST_Response|\WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function delete_item( $request ) {

		$post = $this->get_post( $request['id'] );

		if ( is_wp_error( $post ) ) {
			return $post;
		}

		$related_id = $request->get_param( 'related_id' );

		if ( empty( $related_id ) ) {
			return new \WP_Error(
				'rest_invalid_param',
				__( 'No related entity provided.', 'tenup-content-connect' ),
				array( 'status' => 400 )
			);
		}

		$this->relationship->delete_relationship( $post->ID, $related_id );

		$rel_type = $request->get_param( 'rel_type' );

		$prepared_items = array();
		if ( 'post-to-user' === $rel_type ) {
			$prepared_items = $this->get_related_users( $post, $request );
		} else {
			$prepared_items = $this->get_related_posts( $post, $request );
		}

		$response = rest_ensure_response( $prepared_items['items'] );

		return $response;
	}

	/**
	 * Checks if a given request has access to delete a related entity for a post.
	 *
	 * @since 1.7.0
	 *
	 * @param  \WP_REST_Request $request Full details about the request.
	 * @return true|\WP_Error True if the request has access, WP_Error object otherwise.
	 */
	public function delete_item_permissions_check( $request ) {
		return $this->update_items_permissions_check( $request );
	}

	/**
	 * Validate the `post_type` request parameter.
	 *
	 * @since 1.7.0
	 *
	 * @param mixed            $value   The value of the 'post_type' request parameter.
	 * @param \WP_REST_Request $request The request object.
	 * @return bool|\WP_Error True if valid, WP_Error otherwise.
	 */
	public function validate_post_type_request_arg( $value, \WP_REST_Request $request ) {
		$rel_type = $request->get_param( 'rel_type' );

		if ( 'post-to-post' === $rel_type ) {
			$valid_post_types = array_keys( get_post_types() );
			if ( ! in_array( $value, $valid_post_types, true ) ) {
				return new \WP_Error(
					'rest_invalid_param',
					sprintf(
						/* translators: %s: post type */
						__( 'Invalid post type: %s.', 'tenup-content-connect' ),
						$value
					),
					array( 'status' => 400 )
				);
			}
		}

		return true;
	}

	/**
	 * Validate the `orderby` request parameter.
	 *
	 * @since 1.7.0
	 *
	 * @param mixed            $value   The value of the 'orderby' request parameter.
	 * @param \WP_REST_Request $request The request object.
	 * @return array
	 */
	public function validate_orderby_request_arg( $value, \WP_REST_Request $request ) {
		$rel_type = $request->get_param( 'rel_type' );

		if ( 'post-to-post' === $rel_type ) {
			return array( 'relationship', 'date', 'id', 'title' );
		}

		return array( 'relationship', 'date', 'id', 'name' );
	}

	/**
	 * Performs a permissions check for managing related entities for a post.
	 *
	 * @since 1.7.0
	 *
	 * @param  \WP_REST_Request $request Full details about the request.
	 * @return true|\WP_Error True if the request has access, WP_Error object otherwise.
	 */
	protected function permissions_check( $request ) {

		$post = $this->get_post( $request['id'] );

		if ( is_wp_error( $post ) ) {
			return $post;
		}

		$registry = get_registry();
		$rel_key  = $request->get_param( 'rel_key' );
		$rel_type = $request->get_param( 'rel_type' );

		if ( 'post-to-user' === $rel_type ) {
			$this->relationship = $registry->get_post_to_user_relationship_by_key( $rel_key );
		} else {
			$this->relationship = $registry->get_post_to_post_relationship_by_key( $rel_key );
		}

		if ( empty( $this->relationship ) ) {
			return new \WP_Error(
				'rest_relationship_not_found',
				__( 'The requested relationship was not found.', 'tenup-content-connect' ),
				array( 'status' => 404 )
			);
		}

		return true;
	}

	/**
	 * Get posts related to a post.
	 *
	 * @since 1.7.0
	 *
	 * @param \WP_Post         $post    The post object.
	 * @param \WP_REST_Request $request The request object.
	 * @return array
	 */
	protected function get_related_posts( \WP_Post $post, \WP_REST_Request $request ) {

		$post_type   = $request->get_param( 'post_type' );
		$post_status = $request->get_param( 'post_status' );
		$page        = (int) $request->get_param( 'page' );
		$per_page    = (int) $request->get_param( 'per_page' );
		$order       = $request->get_param( 'order' );
		$orderby     = $request->get_param( 'orderby' );

		$query_args = array(
			'post_type'          => $post_type,
			'post_status'        => $post_status,
			'paged'              => $page,
			'posts_per_page'     => $per_page,
			'relationship_query' => array(
				array(
					'name'            => $this->relationship->name,
					'related_to_post' => $post->ID,
				),
			),
			'orderby'            => $orderby,
		);

		if ( 'relationship' !== $orderby ) {
			$query_args['order'] = $order;
		}

		$query = new \WP_Query( $query_args );

		$items = $query->get_posts();

		if ( empty( $items ) ) {
			return array(
				'items' => array(),
				'total' => 0,
			);
		}

		$prepared_items = $this->prepare_post_items( $items, $this->relationship );

		$total_items = $query->found_posts;

		if ( $total_items < 1 && $page > 1 ) {
			// Out-of-bounds, run the query again without LIMIT for total count.
			unset( $query_args['paged'] );

			$count_query = new \WP_Query();
			$count_query->query( $query_args );
			$total_items = $count_query->found_posts;
		}

		return array(
			'items' => $prepared_items,
			'total' => $total_items,
		);
	}

	/**
	 * Get users related to a post.
	 *
	 * @since 1.7.0
	 *
	 * @param \WP_Post         $post    The post object.
	 * @param \WP_REST_Request $request The request object.
	 * @return array
	 */
	protected function get_related_users( \WP_Post $post, \WP_REST_Request $request ) {

		$page     = (int) $request->get_param( 'page' );
		$per_page = (int) $request->get_param( 'per_page' );
		$order    = $request->get_param( 'order' );
		$orderby  = $request->get_param( 'orderby' );

		$query_args = array(
			'paged'              => $page,
			'number'             => $per_page,
			'relationship_query' => array(
				array(
					'name'            => $this->relationship->name,
					'related_to_post' => $post->ID,
				),
			),
			'orderby'            => $orderby,
		);

		if ( 'relationship' !== $orderby ) {
			$query_args['order'] = $order;
		}

		$query = new \WP_User_Query( $query_args );

		$items = $query->get_results();

		if ( empty( $items ) ) {
			return array(
				'items' => array(),
				'total' => 0,
			);
		}

		$prepared_items = $this->prepare_user_items( $items, $this->relationship );

		$total_items = $query->get_total();

		if ( $total_items < 1 ) {
			// Out-of-bounds, run the query again without LIMIT for total count.
			unset( $query_args['number'], $query_args['offset'] );

			$count_query = new \WP_User_Query( $query_args );
			$count_query->query( $query_args );
			$total_items = $count_query->get_total();
		}

		return array(
			'items' => $prepared_items,
			'total' => $total_items,
		);
	}

	/**
	 * Update posts related to a post.
	 *
	 * @since 1.7.0
	 *
	 * @param \WP_Post         $post    The post object.
	 * @param \WP_REST_Request $request The request object.
	 * @return array
	 */
	protected function update_related_posts( \WP_Post $post, \WP_REST_Request $request ) {

		$related_ids = $request->get_param( 'related_ids' );
		$related_ids = array_map( 'absint', $related_ids );
		$related_ids = array_unique( $related_ids );

		$this->relationship->replace_relationships( $post->ID, $related_ids );

		if ( $this->relationship->from_sortable ) {
			$this->relationship->save_sort_data( $post->ID, $related_ids );
		}

		$items = $this->relationship->get_related_object_ids( $post->ID, $this->relationship->from_sortable );

		$prepared_items = $this->prepare_post_items( $items, $this->relationship );

		return $prepared_items;
	}

	/**
	 * Update users related to a post.
	 *
	 * @since 1.7.0
	 *
	 * @param \WP_Post         $post    The post object.
	 * @param \WP_REST_Request $request The request object.
	 * @return array
	 */
	protected function update_related_users( \WP_Post $post, \WP_REST_Request $request ) {

		$related_ids = $request->get_param( 'related_ids' );
		$related_ids = array_map( 'absint', $related_ids );
		$related_ids = array_unique( $related_ids );

		$this->relationship->replace_post_to_user_relationships( $post->ID, $related_ids );

		if ( $this->relationship->from_sortable ) {
			$this->relationship->save_post_to_user_sort_data( $post->ID, $related_ids );
		}

		$items = $this->relationship->get_related_user_ids( $post->ID, $this->relationship->from_sortable );

		$prepared_items = $this->prepare_user_items( $items, $this->relationship );

		return $prepared_items;
	}
}
