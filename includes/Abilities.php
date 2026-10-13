<?php

namespace TenUp\ContentConnect;

/**
 * Class Abilities
 *
 * Registers read-only Abilities API entries for discovering relationships and
 * related entities. Each ability dispatches to the matching REST API v2 route,
 * so validation, permissions, and response shape come from a single code path.
 *
 * Registration runs only when the Abilities API is available (WordPress 6.9+).
 *
 * @package TenUp\ContentConnect
 */
class Abilities {

	/**
	 * Ability category slug.
	 *
	 * @since 2.1.0
	 *
	 * @var string
	 */
	const CATEGORY = 'content-connect';

	/**
	 * Setup the abilities module.
	 *
	 * @since 2.1.0
	 */
	public function setup() {
		add_action( 'wp_abilities_api_categories_init', array( $this, 'register_category' ) );
		add_action( 'wp_abilities_api_init', array( $this, 'register_abilities' ) );
	}

	/**
	 * Registers the ability category.
	 *
	 * @since 2.1.0
	 *
	 * @return void
	 */
	public function register_category() {

		if ( ! function_exists( 'wp_register_ability_category' ) ) {
			return;
		}

		wp_register_ability_category(
			self::CATEGORY,
			array(
				'label'       => __( 'Content Connect', 'wp-content-connect' ),
				'description' => __( 'Post-to-post and post-to-user relationships.', 'wp-content-connect' ),
			)
		);
	}

	/**
	 * Registers the abilities.
	 *
	 * @since 2.1.0
	 *
	 * @return void
	 */
	public function register_abilities() {

		if ( ! function_exists( 'wp_register_ability' ) ) {
			return;
		}

		wp_register_ability(
			'content-connect/get-relationships',
			array(
				'label'               => __( 'Get Relationships', 'wp-content-connect' ),
				'description'         => __( 'Lists the registered relationship definitions, optionally filtered by key, post type, or the from/to side of post-to-post relationships.', 'wp-content-connect' ),
				'category'            => self::CATEGORY,
				'input_schema'        => array(
					'type'                 => 'object',
					'properties'           => array(
						'rel_type'     => array(
							'type'        => 'string',
							'enum'        => array( 'post-to-post', 'post-to-user' ),
							'default'     => 'post-to-post',
							'description' => __( 'The relationship type to list.', 'wp-content-connect' ),
						),
						'filter_by'    => array(
							'type'        => 'string',
							'enum'        => array( 'key', 'post_type', 'from', 'to', 'any' ),
							'default'     => 'any',
							'description' => __( 'The criteria to filter relationships by. "from" and "to" apply to post-to-post relationships only.', 'wp-content-connect' ),
						),
						'filter_value' => array(
							'type'        => 'string',
							'default'     => '',
							'description' => __( 'The value to match against the selected filter, such as a relationship key or post type.', 'wp-content-connect' ),
						),
					),
					'additionalProperties' => false,
					'default'              => array(),
				),
				'output_schema'       => array(
					'type'  => 'array',
					'items' => self::get_relationship_schema(),
				),
				'execute_callback'    => array( $this, 'execute_get_relationships' ),
				'permission_callback' => array( $this, 'can_edit_posts' ),
				'meta'                => self::get_read_only_meta(),
			)
		);

		wp_register_ability(
			'content-connect/get-post-relationships',
			array(
				'label'               => __( 'Get Post Relationships', 'wp-content-connect' ),
				'description'         => __( 'Lists the relationships that apply to a post, optionally including the related posts and users for each one.', 'wp-content-connect' ),
				'category'            => self::CATEGORY,
				'input_schema'        => array(
					'type'                 => 'object',
					'properties'           => array(
						'post_id'   => array(
							'type'        => 'integer',
							'minimum'     => 1,
							'description' => __( 'The post ID.', 'wp-content-connect' ),
						),
						'rel_type'  => array(
							'type'        => 'string',
							'enum'        => array( 'any', 'post-to-post', 'post-to-user' ),
							'default'     => 'any',
							'description' => __( 'The relationship type to list.', 'wp-content-connect' ),
						),
						'post_type' => array(
							'type'        => 'string',
							'default'     => '',
							'description' => __( 'Limits post-to-post relationships to those connecting to this post type.', 'wp-content-connect' ),
						),
						'context'   => array(
							'type'        => 'string',
							'enum'        => array( 'view', 'embed' ),
							'default'     => 'view',
							'description' => __( '"embed" includes the related posts and users for each relationship.', 'wp-content-connect' ),
						),
					),
					'required'             => array( 'post_id' ),
					'additionalProperties' => false,
				),
				'output_schema'       => array(
					'type'  => 'array',
					'items' => self::get_relationship_schema(),
				),
				'execute_callback'    => array( $this, 'execute_get_post_relationships' ),
				'permission_callback' => array( $this, 'can_edit_input_post' ),
				'meta'                => self::get_read_only_meta(),
			)
		);

		wp_register_ability(
			'content-connect/get-related-items',
			array(
				'label'               => __( 'Get Related Items', 'wp-content-connect' ),
				'description'         => __( 'Lists the posts or users related to a post through one relationship, with pagination and ordering.', 'wp-content-connect' ),
				'category'            => self::CATEGORY,
				'input_schema'        => array(
					'type'                 => 'object',
					'properties'           => array(
						'post_id'     => array(
							'type'        => 'integer',
							'minimum'     => 1,
							'description' => __( 'The post ID.', 'wp-content-connect' ),
						),
						'rel_key'     => array(
							'type'        => 'string',
							'minLength'   => 1,
							'description' => __( 'The relationship key, as returned by content-connect/get-post-relationships.', 'wp-content-connect' ),
						),
						'rel_type'    => array(
							'type'        => 'string',
							'enum'        => array( 'post-to-post', 'post-to-user' ),
							'default'     => 'post-to-post',
							'description' => __( 'The relationship type.', 'wp-content-connect' ),
						),
						'post_status' => array(
							'type'        => 'array',
							'items'       => array( 'type' => 'string' ),
							'default'     => array( 'publish' ),
							'description' => __( 'Post statuses to include for post-to-post relationships. Use "any" for all statuses.', 'wp-content-connect' ),
						),
						'page'        => array(
							'type'        => 'integer',
							'minimum'     => 1,
							'default'     => 1,
							'description' => __( 'Current page of the collection.', 'wp-content-connect' ),
						),
						'per_page'    => array(
							'type'        => 'integer',
							'minimum'     => 1,
							'maximum'     => 100,
							'default'     => 10,
							'description' => __( 'Maximum number of items to return.', 'wp-content-connect' ),
						),
						'order'       => array(
							'type'        => 'string',
							'enum'        => array( 'asc', 'desc' ),
							'default'     => 'asc',
							'description' => __( 'Sort direction. Ignored when ordering by relationship.', 'wp-content-connect' ),
						),
						'orderby'     => array(
							'type'        => 'string',
							'enum'        => array( 'relationship', 'date', 'id', 'title', 'name' ),
							'default'     => 'relationship',
							'description' => __( 'Sort field. "relationship" keeps the saved sort order. "title" applies to post-to-post, "name" to post-to-user.', 'wp-content-connect' ),
						),
					),
					'required'             => array( 'post_id', 'rel_key' ),
					'additionalProperties' => false,
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'items'       => array(
							'type'  => 'array',
							'items' => array(
								'type'       => 'object',
								'properties' => array(
									'id'   => array( 'type' => 'integer' ),
									'name' => array( 'type' => 'string' ),
									'type' => array( 'type' => 'string' ),
								),
							),
						),
						'total'       => array( 'type' => 'integer' ),
						'total_pages' => array( 'type' => 'integer' ),
					),
				),
				'execute_callback'    => array( $this, 'execute_get_related_items' ),
				'permission_callback' => array( $this, 'can_edit_input_post' ),
				'meta'                => self::get_read_only_meta(),
			)
		);
	}

	/**
	 * Checks whether the current user can edit posts.
	 *
	 * @since 2.1.0
	 *
	 * @return bool
	 */
	public function can_edit_posts() {
		return current_user_can( 'edit_posts' );
	}

	/**
	 * Checks whether the current user can edit the post referenced by the input.
	 *
	 * @since 2.1.0
	 *
	 * @param  array|null $input Ability input.
	 * @return bool
	 */
	public function can_edit_input_post( $input = null ) {

		$post_id = (int) ( $input['post_id'] ?? 0 );

		if ( $post_id <= 0 ) {
			return false;
		}

		return current_user_can( 'edit_post', $post_id );
	}

	/**
	 * Lists the registered relationship definitions.
	 *
	 * @since 2.1.0
	 *
	 * @param  array|null $input Ability input.
	 * @return array|\WP_Error
	 */
	public function execute_get_relationships( $input = null ) {

		$response = self::dispatch( '/content-connect/v2/relationships', (array) $input );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		return array_values( (array) $response->get_data() );
	}

	/**
	 * Lists the relationships that apply to a post.
	 *
	 * @since 2.1.0
	 *
	 * @param  array|null $input Ability input.
	 * @return array|\WP_Error
	 */
	public function execute_get_post_relationships( $input = null ) {

		$params  = (array) $input;
		$post_id = (int) ( $params['post_id'] ?? 0 );

		unset( $params['post_id'] );

		$response = self::dispatch( sprintf( '/content-connect/v2/post/%d/relationships', $post_id ), $params );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		return array_values( (array) $response->get_data() );
	}

	/**
	 * Lists the entities related to a post through one relationship.
	 *
	 * @since 2.1.0
	 *
	 * @param  array|null $input Ability input.
	 * @return array|\WP_Error
	 */
	public function execute_get_related_items( $input = null ) {

		$params  = (array) $input;
		$post_id = (int) ( $params['post_id'] ?? 0 );

		unset( $params['post_id'] );

		$response = self::dispatch( sprintf( '/content-connect/v2/post/%d/related', $post_id ), $params );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$headers = $response->get_headers();

		return array(
			'items'       => array_values( (array) $response->get_data() ),
			'total'       => (int) ( $headers['X-WP-Total'] ?? 0 ),
			'total_pages' => (int) ( $headers['X-WP-TotalPages'] ?? 0 ),
		);
	}

	/**
	 * Builds the shared metadata for read-only abilities.
	 *
	 * @since 2.1.0
	 *
	 * @return array
	 */
	private static function get_read_only_meta() {
		return array(
			'show_in_rest' => true,
			'mcp'          => array(
				'public' => true,
			),
			'annotations'  => array(
				'readonly'    => true,
				'destructive' => false,
				'idempotent'  => true,
			),
		);
	}

	/**
	 * Builds the output schema for a single relationship entry.
	 *
	 * @since 2.1.0
	 *
	 * @return array
	 */
	private static function get_relationship_schema() {
		return array(
			'type'       => 'object',
			'properties' => array(
				'rel_key'     => array( 'type' => 'string' ),
				'rel_type'    => array( 'type' => 'string' ),
				'rel_name'    => array( 'type' => 'string' ),
				'object_type' => array( 'type' => 'string' ),
			),
		);
	}

	/**
	 * Dispatches a GET request to a REST API v2 route.
	 *
	 * The route runs its own argument validation and permission checks, and its
	 * error responses are returned as WP_Error objects.
	 *
	 * @since 2.1.0
	 *
	 * @param  string $route  The REST route.
	 * @param  array  $params Query parameters.
	 * @return \WP_REST_Response|\WP_Error
	 */
	private static function dispatch( $route, array $params ) {

		$request = new \WP_REST_Request( 'GET', $route );
		$request->set_query_params( $params );

		$response = rest_do_request( $request );

		if ( $response->is_error() ) {
			return $response->as_error();
		}

		return $response;
	}
}
