<?php

namespace TenUp\ContentConnect\API\V2\Post\Route;

use function TenUp\ContentConnect\Helpers\get_registry;

/**
 * Class RelatedEntities
 *
 * REST API endpoint for post related entities (posts or users).
 *
 * @package TenUp\ContentConnect\API\V2\Post\Route
 */
class RelatedEntities extends AbstractPostRoute {

	/**
	 * {@inheritDoc}
	 */
	public function register_routes() {

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)/related',
			array(
				'args' => $this->get_route_params(),
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
					'args'                => array(
						'post_status' => array(
							'description'       => __( 'Limit result set to posts assigned one or more statuses.', 'tenup-content-connect' ),
							'type'              => 'array',
							'default'           => 'publish',
							'sanitize_callback' => function ( $value ) {
								if ( is_array( $value ) ) {
									return array_map( 'sanitize_text_field', $value );
								}
								return sanitize_text_field( $value );
							},
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
					'methods'             => \WP_REST_Server::EDITABLE,
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
	 * @since 2.0.0
	 *
	 * @param  \WP_REST_Request $request Full details about the request.
	 * @return \WP_REST_Response
	 */
	public function get_items( \WP_REST_Request $request ) {

		$post = $this->get_post( $request['id'] );

		if ( is_wp_error( $post ) ) {
			return $post;
		}

		$relationship = $this->resolve_relationship( $request );

		if ( is_wp_error( $relationship ) ) {
			return $relationship;
		}

		$rel_type = $request->get_param( 'rel_type' );

		$prepared_items = array();
		if ( 'post-to-user' === $rel_type ) {
			$prepared_items = $this->get_related_users( $post, $request, $relationship );
		} else {
			$prepared_items = $this->get_related_posts( $post, $request, $relationship );
		}

		$page     = (int) $request->get_param( 'page' );
		$per_page = (int) $request->get_param( 'per_page' );
		$total    = $prepared_items['total'];

		$max_pages = (int) ceil( $total / (int) $per_page );
		if ( $max_pages < 1 && $total > 0 ) {
			$max_pages = 1;
		}

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

		$url = rest_url(
			sprintf(
				'/%s/%s/%d/related',
				$this->namespace,
				$this->rest_base,
				$request['id']
			)
		);

		$base = add_query_arg( urlencode_deep( $request_params ), $url );

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
	 * @since 2.0.0
	 *
	 * @param  \WP_REST_Request $request Full details about the request.
	 * @return true|\WP_Error True if the request has access, WP_Error object otherwise.
	 */
	public function get_items_permissions_check( \WP_REST_Request $request ) {

		$post_id = $request->get_param( 'id' );

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return new \WP_Error(
				'rest_forbidden',
				__( 'Sorry, you are not allowed to retrieve related entities for this post.', 'tenup-content-connect' ),
				array( 'status' => 403 )
			);
		}

		return $this->permissions_check( $request );
	}

	/**
	 * Updates related entities for a post.
	 *
	 * @since 2.0.0
	 *
	 * @param  \WP_REST_Request $request Full details about the request.
	 * @return \WP_REST_Response
	 */
	public function update_items( $request ) {

		$post = $this->get_post( $request['id'] );

		if ( is_wp_error( $post ) ) {
			return $post;
		}

		$relationship = $this->resolve_relationship( $request );

		if ( is_wp_error( $relationship ) ) {
			return $relationship;
		}

		$rel_type = $request->get_param( 'rel_type' );

		$prepared_items = array();
		if ( 'post-to-user' === $rel_type ) {
			$prepared_items = $this->update_related_users( $post, $request, $relationship );
		} else {
			$prepared_items = $this->update_related_posts( $post, $request, $relationship );
		}

		if ( is_wp_error( $prepared_items ) ) {
			return $prepared_items;
		}

		$response = rest_ensure_response( $prepared_items );

		return $response;
	}

	/**
	 * Checks if a given request has access to update related entities for a post.
	 *
	 * @since 2.0.0
	 *
	 * @param  \WP_REST_Request $request Full details about the request.
	 * @return true|\WP_Error True if the request has access, WP_Error object otherwise.
	 */
	public function update_items_permissions_check( $request ) {

		$post_id = $request->get_param( 'id' );

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return new \WP_Error(
				'rest_cannot_edit',
				__( 'Sorry, you are not allowed to update this post.', 'tenup-content-connect' ),
				array( 'status' => 403 )
			);
		}

		return $this->permissions_check( $request );
	}

	/**
	 * Adds a related entity for a post.
	 *
	 * @since 2.0.0
	 *
	 * @param  \WP_REST_Request $request Full details about the request.
	 * @return \WP_REST_Response
	 */
	public function add_item( $request ) {

		$post = $this->get_post( $request['id'] );

		if ( is_wp_error( $post ) ) {
			return $post;
		}

		$relationship = $this->resolve_relationship( $request );

		if ( is_wp_error( $relationship ) ) {
			return $relationship;
		}

		$related_id = $request->get_param( 'related_id' );

		if ( empty( $related_id ) ) {
			return new \WP_Error(
				'rest_invalid_param',
				__( 'No related entity provided.', 'tenup-content-connect' ),
				array( 'status' => 400 )
			);
		}

		$validation = $this->validate_related_id( $related_id, $post, $relationship );

		if ( is_wp_error( $validation ) ) {
			return $validation;
		}

		$relationship->add_relationship( $post->ID, $related_id );

		$rel_type = $request->get_param( 'rel_type' );

		$prepared_items = array();
		if ( 'post-to-user' === $rel_type ) {
			$prepared_items = $this->get_related_users( $post, $request, $relationship );
		} else {
			$prepared_items = $this->get_related_posts( $post, $request, $relationship );
		}

		$response = new \WP_REST_Response( $prepared_items['items'], 201 );

		return $response;
	}

	/**
	 * Checks if a given request has access to add a related entity for a post.
	 *
	 * @since 2.0.0
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
	 * @since 2.0.0
	 *
	 * @param  \WP_REST_Request $request Full details about the request.
	 * @return \WP_REST_Response
	 */
	public function delete_item( $request ) {

		$post = $this->get_post( $request['id'] );

		if ( is_wp_error( $post ) ) {
			return $post;
		}

		$relationship = $this->resolve_relationship( $request );

		if ( is_wp_error( $relationship ) ) {
			return $relationship;
		}

		$related_id = $request->get_param( 'related_id' );

		if ( empty( $related_id ) ) {
			return new \WP_Error(
				'rest_invalid_param',
				__( 'No related entity provided.', 'tenup-content-connect' ),
				array( 'status' => 400 )
			);
		}

		$relationship->delete_relationship( $post->ID, (int) $related_id );

		$rel_type = $request->get_param( 'rel_type' );

		$prepared_items = array();
		if ( 'post-to-user' === $rel_type ) {
			$prepared_items = $this->get_related_users( $post, $request, $relationship );
		} else {
			$prepared_items = $this->get_related_posts( $post, $request, $relationship );
		}

		$response = rest_ensure_response( $prepared_items['items'] );

		return $response;
	}

	/**
	 * Checks if a given request has access to delete a related entity for a post.
	 *
	 * @since 2.0.0
	 *
	 * @param  \WP_REST_Request $request Full details about the request.
	 * @return true|\WP_Error True if the request has access, WP_Error object otherwise.
	 */
	public function delete_item_permissions_check( $request ) {
		return $this->update_items_permissions_check( $request );
	}

	/**
	 * Validate the `orderby` request parameter.
	 *
	 * @since 2.0.0
	 *
	 * @param mixed            $value   The value of the 'orderby' request parameter.
	 * @param \WP_REST_Request $request The request object.
	 * @return array
	 */
	public function validate_orderby_request_arg( $value, \WP_REST_Request $request ) {
		$rel_type = $request->get_param( 'rel_type' );

		$valid_values = array( 'relationship', 'date', 'id' );

		if ( 'post-to-post' === $rel_type ) {
			$valid_values[] = 'title';
		} else {
			$valid_values[] = 'name';
		}

		if ( ! in_array( $value, $valid_values, true ) ) {
			return new \WP_Error(
				'rest_invalid_param',
				sprintf(
					/* translators: %s: valid values */
					__( 'Invalid orderby value. Must be one of: %s', 'tenup-content-connect' ),
					implode( ', ', $valid_values )
				),
				array( 'status' => 400 )
			);
		}

		return true;
	}

	/**
	 * Performs a permissions check for managing related entities for a post.
	 *
	 * @since 2.0.0
	 *
	 * @param  \WP_REST_Request $request Full details about the request.
	 * @return true|\WP_Error True if the request has access, WP_Error object otherwise.
	 */
	protected function permissions_check( $request ) {

		$post = $this->get_post( $request['id'] );

		if ( is_wp_error( $post ) ) {
			return $post;
		}

		$relationship = $this->resolve_relationship( $request );

		if ( is_wp_error( $relationship ) ) {
			return $relationship;
		}

		return true;
	}

	/**
	 * Resolves the relationship object referenced by the request.
	 *
	 * @since 2.0.0
	 *
	 * @param  \WP_REST_Request $request Full details about the request.
	 * @return \TenUp\ContentConnect\Relationships\Relationship|\WP_Error
	 */
	protected function resolve_relationship( \WP_REST_Request $request ) {

		$registry = get_registry();
		$rel_key  = $request->get_param( 'rel_key' );
		$rel_type = $request->get_param( 'rel_type' );

		if ( 'post-to-user' === $rel_type ) {
			$relationship = $registry->get_post_to_user_relationship_by_key( $rel_key );
		} else {
			$relationship = $registry->get_post_to_post_relationship_by_key( $rel_key );
		}

		if ( empty( $relationship ) ) {
			return new \WP_Error(
				'rest_relationship_not_found',
				__( 'The requested relationship was not found.', 'tenup-content-connect' ),
				array( 'status' => 404 )
			);
		}

		return $relationship;
	}

	/**
	 * Validates that a related ID exists and is compatible with the relationship.
	 *
	 * For post-to-post: verifies the related post exists and its post_type matches one
	 * end of the relationship.
	 *
	 * For post-to-user: verifies the related user exists.
	 *
	 * @since 2.0.0
	 *
	 * @param  int                                                                                          $related_id   The related entity ID.
	 * @param  \WP_Post                                                                                     $post         The source post.
	 * @param  \TenUp\ContentConnect\Relationships\PostToPost|\TenUp\ContentConnect\Relationships\PostToUser $relationship The relationship object.
	 * @return true|\WP_Error True if valid, WP_Error otherwise.
	 */
	protected function validate_related_id( $related_id, \WP_Post $post, $relationship ) {

		$related_id = (int) $related_id;

		if ( $related_id <= 0 ) {
			return new \WP_Error(
				'rest_invalid_related_id',
				__( 'Invalid related ID.', 'tenup-content-connect' ),
				array( 'status' => 400 )
			);
		}

		if ( $relationship instanceof \TenUp\ContentConnect\Relationships\PostToUser ) {
			if ( ! get_userdata( $related_id ) ) {
				return new \WP_Error(
					'rest_invalid_related_id',
					__( 'Related user does not exist.', 'tenup-content-connect' ),
					array( 'status' => 400 )
				);
			}

			return true;
		}

		$related_post = get_post( $related_id );

		if ( ! $related_post ) {
			return new \WP_Error(
				'rest_invalid_related_id',
				__( 'Related post does not exist.', 'tenup-content-connect' ),
				array( 'status' => 400 )
			);
		}

		$allowed_types = array_merge( array( $relationship->from ), (array) $relationship->to );

		if ( ! in_array( $related_post->post_type, $allowed_types, true ) ) {
			return new \WP_Error(
				'rest_invalid_related_post_type',
				__( 'Related post type is not part of this relationship.', 'tenup-content-connect' ),
				array( 'status' => 400 )
			);
		}

		// Source and target must be on opposite sides of the relationship.
		$post_is_from = $post->post_type === $relationship->from;
		$other_is_to  = in_array( $related_post->post_type, (array) $relationship->to, true );
		$post_is_to   = in_array( $post->post_type, (array) $relationship->to, true );
		$other_is_from = $related_post->post_type === $relationship->from;

		if ( ! ( ( $post_is_from && $other_is_to ) || ( $post_is_to && $other_is_from ) ) ) {
			return new \WP_Error(
				'rest_invalid_related_post_type',
				__( 'Related post type is not part of this relationship.', 'tenup-content-connect' ),
				array( 'status' => 400 )
			);
		}

		return true;
	}

	/**
	 * Get posts related to a post.
	 *
	 * @since 2.0.0
	 *
	 * @param \WP_Post         $post    The post object.
	 * @param \WP_REST_Request $request The request object.
	 * @return array<string, mixed> Associative array containing:
	 *                              - 'items' (array) The related posts.
	 *                              - 'total' (int) The total number of posts found.
	 */
	protected function get_related_posts( \WP_Post $post, \WP_REST_Request $request, $relationship ) {

		$post_status = $request->get_param( 'post_status' );
		$page        = (int) $request->get_param( 'page' );
		$per_page    = (int) $request->get_param( 'per_page' );
		$order       = $request->get_param( 'order' );
		$orderby     = $request->get_param( 'orderby' );

		$query_args = array(
			'post_status'        => $post_status,
			'paged'              => $page,
			'posts_per_page'     => $per_page,
			'relationship_query' => array(
				array(
					'name'            => $relationship->name,
					'related_to_post' => $post->ID,
				),
			),
			'orderby'            => $orderby,
		);

		if ( $post->post_type === $relationship->from ) {
			$query_args['post_type'] = $relationship->to;
		} else {
			$query_args['post_type'] = $relationship->from;
		}

		if ( 'relationship' !== $orderby ) {
			$query_args['order'] = $order;
		}

		$query = new \WP_Query( $query_args );

		$items       = $query->get_posts();
		$total_items = $query->found_posts;

		if ( $total_items < 1 && $page > 1 ) {
			// Out-of-bounds, run the query again without LIMIT for total count.
			unset( $query_args['paged'] );

			$count_query = new \WP_Query();
			$count_query->query( $query_args );
			$total_items = $count_query->found_posts;
		}

		if ( empty( $items ) ) {
			return array(
				'items' => array(),
				'total' => $total_items,
			);
		}

		$prepared_items = $this->prepare_post_items( $items, $relationship );

		return array(
			'items' => $prepared_items,
			'total' => $total_items,
		);
	}

	/**
	 * Get users related to a post.
	 *
	 * @since 2.0.0
	 *
	 * @param \WP_Post         $post    The post object.
	 * @param \WP_REST_Request $request The request object.
	 * @return array<string, mixed> Associative array containing:
	 *                              - 'items' (array) The related users.
	 *                              - 'total' (int) The total number of users found.
	 */
	protected function get_related_users( \WP_Post $post, \WP_REST_Request $request, $relationship ) {

		$page     = (int) $request->get_param( 'page' );
		$per_page = (int) $request->get_param( 'per_page' );
		$order    = $request->get_param( 'order' );
		$orderby  = $request->get_param( 'orderby' );

		$query_args = array(
			'number'             => $per_page,
			'offset'             => ( $page - 1 ) * $per_page,
			'count_total'        => true,
			'relationship_query' => array(
				array(
					'name'            => $relationship->name,
					'related_to_post' => $post->ID,
				),
			),
			'orderby'            => $orderby,
		);

		if ( 'relationship' !== $orderby ) {
			$query_args['order'] = $order;
		}

		$query = new \WP_User_Query( $query_args );

		$items       = $query->get_results();
		$total_items = (int) $query->get_total();

		if ( empty( $items ) ) {
			return array(
				'items' => array(),
				'total' => $total_items,
			);
		}

		$prepared_items = $this->prepare_user_items( $items, $relationship );

		return array(
			'items' => $prepared_items,
			'total' => $total_items,
		);
	}

	/**
	 * Update posts related to a post.
	 *
	 * @since 2.0.0
	 *
	 * @param \WP_Post         $post    The post object.
	 * @param \WP_REST_Request $request The request object.
	 * @return array
	 */
	protected function update_related_posts( \WP_Post $post, \WP_REST_Request $request, $relationship ) {

		$related_ids = $request->get_param( 'related_ids' );
		$related_ids = array_map( 'absint', (array) $related_ids );
		$related_ids = array_filter( $related_ids );
		$related_ids = array_values( array_unique( $related_ids ) );

		$valid_ids   = array();
		$allowed_types = array_merge( array( $relationship->from ), (array) $relationship->to );

		foreach ( $related_ids as $related_id ) {
			$related_post = get_post( $related_id );

			if ( ! $related_post ) {
				continue;
			}

			if ( ! in_array( $related_post->post_type, $allowed_types, true ) ) {
				continue;
			}

			$post_is_from  = $post->post_type === $relationship->from;
			$other_is_to   = in_array( $related_post->post_type, (array) $relationship->to, true );
			$post_is_to    = in_array( $post->post_type, (array) $relationship->to, true );
			$other_is_from = $related_post->post_type === $relationship->from;

			if ( ! ( ( $post_is_from && $other_is_to ) || ( $post_is_to && $other_is_from ) ) ) {
				continue;
			}

			$valid_ids[] = (int) $related_id;
		}

		$relationship->replace_relationships( $post->ID, $valid_ids );

		$is_sortable = false;
		if ( $post->post_type === $relationship->from ) {
			$is_sortable = $relationship->from_sortable;
		} else {
			$is_sortable = $relationship->to_sortable;
		}

		if ( $is_sortable ) {
			$relationship->save_sort_data( $post->ID, $valid_ids );
		}

		$items = $relationship->get_related_object_ids( $post->ID, $is_sortable );

		$prepared_items = $this->prepare_post_items( $items, $relationship );

		return $prepared_items;
	}

	/**
	 * Update users related to a post.
	 *
	 * @since 2.0.0
	 *
	 * @param \WP_Post         $post    The post object.
	 * @param \WP_REST_Request $request The request object.
	 * @return array
	 */
	protected function update_related_users( \WP_Post $post, \WP_REST_Request $request, $relationship ) {

		$related_ids = $request->get_param( 'related_ids' );
		$related_ids = array_map( 'absint', (array) $related_ids );
		$related_ids = array_filter( $related_ids );
		$related_ids = array_values( array_unique( $related_ids ) );

		$valid_ids = array();
		foreach ( $related_ids as $related_id ) {
			if ( ! get_userdata( $related_id ) ) {
				continue;
			}
			$valid_ids[] = (int) $related_id;
		}

		$relationship->replace_post_to_user_relationships( $post->ID, $valid_ids );

		if ( $relationship->from_sortable ) {
			$relationship->save_post_to_user_sort_data( $post->ID, $valid_ids );
		}

		$items = $relationship->get_related_user_ids( $post->ID, $relationship->from_sortable );

		$prepared_items = $this->prepare_user_items( $items, $relationship );

		return $prepared_items;
	}
}
