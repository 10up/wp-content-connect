<?php

namespace TenUp\ContentConnect\API\V2\Post\Route;

use function TenUp\ContentConnect\Helpers\get_post_to_post_relationships_by;
use function TenUp\ContentConnect\Helpers\get_post_to_user_relationships_by;
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
	 * {@inheritDoc}
	 */
	public function register_routes() {

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)/related',
			array(
				'args' => array(
					'id'          => array(
						'description'       => __( 'The post ID.', 'tenup-content-connect' ),
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
						'validate_callback' => 'rest_validate_request_arg',
						'required'          => true,
						'minLength'         => 1,
					),
					'rel_type'    => array(
						'description'       => __( 'The relationship type.', 'tenup-content-connect' ),
						'type'              => 'string',
						'default'           => 'post-to-post',
						'sanitize_callback' => 'sanitize_text_field',
						'validate_callback' => 'rest_validate_request_arg',
						'enum'              => array( 'post-to-post', 'post-to-user' ),
					),
					'rel_name'    => array(
						'description'       => __( 'The relationship name.', 'tenup-content-connect' ),
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
						'validate_callback' => 'rest_validate_request_arg',
						'required'          => true,
						'minLength'         => 1,
					),
					'post_type'   => array(
						'description'       => __( 'The type of post to query for.', 'tenup-content-connect' ),
						'type'              => 'string',
						'default'           => '',
						'sanitize_callback' => 'sanitize_text_field',
						'validate_callback' => array( $this, 'validate_post_type_request_arg' ),
					),
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
	 * Retrieves a collection of related entities for a post.
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

		$result = array();
		if ( 'post-to-user' === $rel_type ) {
			$result = $this->get_users( $post, $request );
		} else {
			$result = $this->get_posts( $post, $request );
		}

		$page     = (int) $request->get_param( 'page' );
		$per_page = (int) $request->get_param( 'per_page' );
		$total    = $result['total'];

		$max_pages = (int) ceil( $total / (int) $per_page );

		if ( $page > $max_pages && $total > 0 ) {
			return new \WP_Error(
				'rest_post_invalid_page_number',
				__( 'The page number requested is larger than the number of pages available.', 'tenup-content-connect' ),
				array( 'status' => 400 )
			);
		}

		$response = rest_ensure_response( $result['items'] );

		$response->header( 'X-WP-Total', (int) $total );
		$response->header( 'X-WP-TotalPages', (int) $max_pages );

		$request_params    = $request->get_query_params();
		$relationships_url = rest_url( sprintf( '/%s/%s/%d/related', $this->namespace, $this->rest_base, $request['id'] ) );
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
	 * Checks if a given request has access to retrieve related entities for a post.
	 *
	 * @param  \WP_REST_Request $request Full details about the request.
	 * @return bool|\WP_Error True if the request has read access, WP_Error object or false otherwise.
	 */
	public function get_items_permissions_check( \WP_REST_Request $request ) {

		if ( ! is_user_logged_in() ) {
			return new \WP_Error(
				'rest_forbidden',
				__( 'Sorry, you are not allowed to view related entities for this post.', 'tenup-content-connect' ),
				array( 'status' => 401 )
			);
		}

		$post = $this->get_post( $request['id'] );

		if ( is_wp_error( $post ) ) {
			return $post;
		}

		$rel_type = $request->get_param( 'rel_type' );

		$relationships = array();
		if ( 'post-to-user' === $rel_type ) {
			$relationships = get_registry()->get_post_to_user_relationships();
		} else {
			$relationships = get_registry()->get_post_to_post_relationships();
		}

		if ( empty( $relationships ) ) {
			return new \WP_Error(
				'rest_relationships_not_found',
				__( 'Relationships not found.', 'tenup-content-connect' ),
				array( 'status' => 404 )
			);
		}

		$rel_name  = $request->get_param( 'rel_name' );
		$rel_names = wp_list_pluck( $relationships, 'name' );

		if ( ! in_array( $rel_name, $rel_names, true ) ) {
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
	 * Validate the `post_type` request parameter.
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
	 * Get posts related to a post.
	 *
	 * @param \WP_Post         $post    The post object.
	 * @param \WP_REST_Request $request The request object.
	 * @return array
	 */
	protected function get_posts( \WP_Post $post, \WP_REST_Request $request ) {

		$rel_name    = $request->get_param( 'rel_name' );
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
					'name'            => $rel_name,
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

		$relationship = get_post_to_post_relationships_by( 'to', $post_type );
		$relationship = reset( $relationship );

		$prepared_items = array();
		foreach ( $items as $item ) {

			$item_data = array(
				'ID'   => $item->ID,
				'name' => $item->post_title,
			);

			/** This filter is documented in includes/Helpers.php */
			$item_data = apply_filters( 'tenup_content_connect_post_ui_item_data', $item_data, $item, $relationship );

			$prepared_items[] = $item_data;
		}

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
	 * @param \WP_Post         $post    The post object.
	 * @param \WP_REST_Request $request The request object.
	 * @return array
	 */
	protected function get_users( \WP_Post $post, \WP_REST_Request $request ) {

		$rel_name = $request->get_param( 'rel_name' );
		$page     = (int) $request->get_param( 'page' );
		$per_page = (int) $request->get_param( 'per_page' );
		$order    = $request->get_param( 'order' );
		$orderby  = $request->get_param( 'orderby' );

		$query_args = array(
			'paged'              => $page,
			'number'             => $per_page,
			'relationship_query' => array(
				array(
					'name'            => $rel_name,
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

		$relationship = get_post_to_user_relationships_by( 'post_type', $post->post_type );
		$relationship = reset( $relationship );

		$prepared_items = array();
		foreach ( $items as $item ) {

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

			$prepared_items[] = $item_data;
		}

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
}
