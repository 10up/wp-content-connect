<?php

namespace TenUp\ContentConnect\API\V2\Post\Route;

use function TenUp\ContentConnect\Helpers\get_registry;

/**
 * Class Search
 *
 * REST API endpoint for searching for entities (posts or users).
 *
 * @package TenUp\ContentConnect\API\V2\Post\Route
 */
class Search extends AbstractPostRoute {

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
	 */
	public function register_routes() {

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)/search',
			array(
				'args' => $this->get_route_params(),
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'get_items_permission_check' ),
					'args'                => array(
						'search'   => array(
							'description'       => __( 'Limit results to those matching a string.', 'tenup-content-connect' ),
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_text_field',
							'validate_callback' => 'rest_validate_request_arg',
							'required'          => true,
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
				),
			)
		);
	}

	/**
	 * Retrieves a collection of search results.
	 *
	 * @since 1.7.0
	 *
	 * @param \WP_REST_Request $request The REST request.
	 * @return \WP_REST_Response
	 */
	public function get_items( $request ) {

		$rel_type = $request->get_param( 'rel_type' );

		$prepared_items = array();
		if ( 'post-to-user' === $rel_type ) {
			$prepared_items = $this->search_users( $request );
		} else {
			$prepared_items = $this->search_posts( $request );
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
		$base           = add_query_arg(
			urlencode_deep( $request_params ),
			rest_url(
				sprintf(
					'/%s/%s/%d/search',
					$this->namespace,
					$this->rest_base,
					$request['id']
				)
			)
		);

		if ( $page > 1 ) {
			$prev_link = add_query_arg( 'page', $page - 1, $base );
			$response->link_header( 'prev', $prev_link );
		}
		if ( $page < $max_pages ) {
			$next_link = add_query_arg( 'page', $page + 1, $base );
			$response->link_header( 'next', $next_link );
		}

		return $response;
	}

	/**
	 * Check if a given request has access to search for entities.
	 *
	 * @since 1.7.0
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 * @return true|\WP_Error True if the request has access, WP_Error object otherwise.
	 */
	public function get_items_permission_check( $request ) {

		if ( ! is_user_logged_in() ) {
			return new \WP_Error(
				'rest_forbidden',
				__( 'Sorry, you are not allowed to search for entities.', 'tenup-content-connect' ),
				array( 'status' => 401 )
			);
		}

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
	 * Search for posts.
	 *
	 * @since 1.7.0
	 *
	 * @param \WP_REST_Request $request The REST request.
	 * @return array<string, mixed> Associative array containing:
	 *                              - 'items' (array) The matching posts.
	 *                              - 'total' (int) The total number of posts found.
	 */
	protected function search_posts( $request ) {

		$search   = $request->get_param( 'search' );
		$page     = (int) $request->get_param( 'page' );
		$per_page = (int) $request->get_param( 'per_page' );

		$query_args = array(
			'post_type'      => $this->relationship->to,
			's'              => $search,
			'paged'          => $page,
			'posts_per_page' => $per_page,
		);

		/**
		 * Filters the search posts query args.
		 *
		 * @since 1.5.0
		 *
		 * @param  array $query_args The \WP_Query args.
		 * @param  array $args       Optional. The search posts args. Default empty.
		 * @return array
		 */
		$query_args = apply_filters(
			'tenup_content_connect_search_posts_query_args',
			$query_args,
			array(
				'paged'             => $page, // Keept for backwards compatibility.
				'relationship_name' => $this->relationship->name,
				'current_post_id'   => $request->get_param( 'id' ),
			)
		);

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

		return array(
			'items' => $prepared_items,
			'total' => $total_items,
		);
	}

	/**
	 * Search for users.
	 *
	 * @since 1.7.0
	 *
	 * @param \WP_REST_Request $request The REST request.
	 * @return array<string, mixed> Associative array containing:
	 *                              - 'items' (array) The matching users.
	 *                              - 'total' (int) The total number of users found.
	 */
	protected function search_users( $request ) {

		$search   = $request->get_param( 'search' );
		$page     = (int) $request->get_param( 'page' );
		$per_page = (int) $request->get_param( 'per_page' );

		$query_args = array(
			'search' => "*{$search}*",
			'paged'  => $page,
			'number' => $per_page,
		);

		/**
		 * Filters the search users query args.
		 *
		 * @since 1.5.0
		 *
		 * @param  array $query_args The \WP_User_Query args.
		 * @param  array $args       Optional. The search users args. Default empty.
		 * @return array
		 */
		$query_args = apply_filters(
			'tenup_content_connect_search_users_query_args',
			$query_args,
			array(
				'paged'             => $page, // Keept for backwards compatibility.
				'relationship_name' => $this->relationship->name,
				'current_post_id'   => $request->get_param( 'id' ),
			)
		);

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

		return array(
			'items' => $prepared_items,
			'total' => $total_items,
		);
	}
}
