<?php

namespace TenUp\ContentConnect;

use TenUp\ContentConnect\API\V2\Post\Route\RelatedEntities as RelatedEntitiesRoute;
use TenUp\ContentConnect\API\V2\Post\Route\Relationships as PostRelationshipsRoute;
use TenUp\ContentConnect\API\V2\Route\Relationships as RelationshipsRoute;

/**
 * Class Abilities
 *
 * Registers read-only Abilities API entries for discovering relationships and
 * related entities. Each ability builds its input schema from the matching
 * REST API v2 route params and dispatches to that route, which runs its own
 * validation and permission checks. The ability permission callbacks add a
 * capability check before dispatching.
 *
 * The Abilities API hooks fire only when the API is available (WordPress 6.9+).
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

		// Lets static analysis see the WordPress 6.9 requirement; the hook fires only on 6.9+.
		if ( ! function_exists( 'wp_register_ability_category' ) ) {
			return;
		}

		wp_register_ability_category(
			self::CATEGORY,
			array(
				'label'       => __( 'Content Connect', 'wp-content-connect' ),
				'description' => __( 'Post-to-post and post-to-user relationships. These abilities read relationship data. To display related posts on the site, use a Query Loop (core/query) block filtered by the relationship, which stays in sync when relationships change. content-connect/get-related-query-block returns the markup for one.', 'wp-content-connect' ),
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

		// Lets static analysis see the WordPress 6.9 requirement; the hook fires only on 6.9+.
		if ( ! function_exists( 'wp_register_ability' ) ) {
			return;
		}

		wp_register_ability(
			'content-connect/get-relationships',
			array(
				'label'               => __( 'Get Relationships', 'wp-content-connect' ),
				'description'         => __( 'Lists the registered relationship definitions, optionally filtered by key, post type, or the from/to side of post-to-post relationships.', 'wp-content-connect' ),
				'category'            => self::CATEGORY,
				'input_schema'        => array_merge(
					self::get_input_schema( ( new RelationshipsRoute() )->get_collection_params() ),
					array( 'default' => array() )
				),
				'output_schema'       => array(
					'type'  => 'array',
					'items' => self::get_relationship_definition_schema(),
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
				'description'         => __( 'Lists the relationships that apply to a post, optionally including the related posts and users for each one. To display related posts on the site, use the markup from content-connect/get-related-query-block instead of copying the related IDs into content.', 'wp-content-connect' ),
				'category'            => self::CATEGORY,
				'input_schema'        => self::get_input_schema( self::get_post_input_args( ( new PostRelationshipsRoute() )->get_collection_params() ) ),
				'output_schema'       => array(
					'type'  => 'array',
					'items' => self::get_post_relationship_schema(),
				),
				'execute_callback'    => array( $this, 'execute_get_post_relationships' ),
				'permission_callback' => array( $this, 'can_edit_input_post' ),
				'meta'                => self::get_read_only_meta(),
			)
		);

		$related_route = new RelatedEntitiesRoute();
		$related_args  = array_merge( $related_route->get_route_params(), $related_route->get_collection_params() );

		// The ability lists published items only, so the status filter is not exposed.
		unset( $related_args['post_status'] );

		wp_register_ability(
			'content-connect/get-related-items',
			array(
				'label'               => __( 'Get Related Items', 'wp-content-connect' ),
				'description'         => __( 'Lists the published posts or the users related to a post through one relationship, with pagination and ordering. Use it to inspect a relationship, not to build content: to display related posts on the site, use the Query Loop (core/query) markup from content-connect/get-related-query-block. A Query Loop updates when relationships change; IDs copied into a hand-picked list do not.', 'wp-content-connect' ),
				'category'            => self::CATEGORY,
				'input_schema'        => self::get_input_schema( self::get_post_input_args( $related_args ) ),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'items'       => array(
							'type'  => 'array',
							'items' => self::get_item_schema(),
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

		wp_register_ability(
			'content-connect/get-related-query-block',
			array(
				'label'               => __( 'Get Related Query Block', 'wp-content-connect' ),
				'description'         => __( 'Returns the markup for a Query Loop (core/query) block that lists the posts related to a post through one post-to-post relationship. Insert the markup into that post\'s content to display its related posts; the list stays in sync when relationships change. The inner blocks are a starting point and can be changed.', 'wp-content-connect' ),
				'category'            => self::CATEGORY,
				'input_schema'        => array(
					'type'                 => 'object',
					'properties'           => array(
						'post_id'   => array(
							'type'        => 'integer',
							'minimum'     => 1,
							'description' => __( 'The post whose related posts the Query Loop lists. The markup is meant for this post\'s content.', 'wp-content-connect' ),
						),
						'rel_key'   => array(
							'type'        => 'string',
							'description' => __( 'The key of a post-to-post relationship that applies to the post, as returned by content-connect/get-post-relationships.', 'wp-content-connect' ),
						),
						'post_type' => array(
							'type'        => 'string',
							'description' => __( 'The related post type to list. Defaults to the first related post type of the relationship.', 'wp-content-connect' ),
						),
						'per_page'  => array(
							'type'        => 'integer',
							'minimum'     => 1,
							'maximum'     => 100,
							'default'     => 3,
							'description' => __( 'The number of posts to show.', 'wp-content-connect' ),
						),
					),
					'required'             => array( 'post_id', 'rel_key' ),
					'additionalProperties' => false,
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'markup'     => array(
							'type'        => 'string',
							'description' => __( 'Serialized block markup, ready to insert into post content.', 'wp-content-connect' ),
						),
						'attributes' => array(
							'type'        => 'object',
							'description' => __( 'The core/query block attributes used in the markup.', 'wp-content-connect' ),
						),
					),
				),
				'execute_callback'    => array( $this, 'execute_get_related_query_block' ),
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

		$relationships = array_values( (array) $response->get_data() );

		foreach ( $relationships as $index => $relationship ) {
			if ( isset( $relationship['related'] ) ) {
				$relationships[ $index ]['related'] = self::prepare_items( (array) $relationship['related'] );
			}
		}

		return $relationships;
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

		$params['post_status'] = 'publish';

		$response = self::dispatch( sprintf( '/content-connect/v2/post/%d/related', $post_id ), $params );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$headers = $response->get_headers();

		return array(
			'items'       => self::prepare_items( (array) $response->get_data() ),
			'total'       => (int) ( $headers['X-WP-Total'] ?? 0 ),
			'total_pages' => (int) ( $headers['X-WP-TotalPages'] ?? 0 ),
		);
	}

	/**
	 * Builds Query Loop markup that lists the posts related to a post.
	 *
	 * The block attributes match what the block editor's Query Loop extension
	 * stores, so the block opens in the editor unchanged and renders through
	 * the `query_loop_block_query_vars` integration.
	 *
	 * @since 2.1.0
	 *
	 * @param  array|null $input Ability input.
	 * @return array|\WP_Error
	 */
	public function execute_get_related_query_block( $input = null ) {

		$params  = (array) $input;
		$post_id = (int) ( $params['post_id'] ?? 0 );
		$rel_key = (string) ( $params['rel_key'] ?? '' );

		$response = self::dispatch(
			sprintf( '/content-connect/v2/post/%d/relationships', $post_id ),
			array( 'rel_type' => 'post-to-post' )
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$relationships = wp_list_filter( (array) $response->get_data(), array( 'rel_key' => $rel_key ) );
		$relationship  = reset( $relationships );

		if ( empty( $relationship ) ) {
			return new \WP_Error(
				'rest_relationship_not_found',
				__( 'No post-to-post relationship with this key applies to the post.', 'wp-content-connect' ),
				array( 'status' => 404 )
			);
		}

		$post_types = (array) $relationship['post_type'];
		$post_type  = (string) ( $params['post_type'] ?? reset( $post_types ) );

		if ( ! in_array( $post_type, $post_types, true ) ) {
			return new \WP_Error(
				'rest_invalid_param',
				/* translators: %s: Comma-separated list of post types. */
				sprintf( __( 'The post type must be one of: %s.', 'wp-content-connect' ), implode( ', ', $post_types ) ),
				array( 'status' => 400 )
			);
		}

		$attributes = array(
			'query'           => array(
				'perPage'             => (int) ( $params['per_page'] ?? 3 ),
				'pages'               => 0,
				'offset'              => 0,
				'postType'            => $post_type,
				'order'               => 'desc',
				'orderBy'             => 'date',
				'author'              => '',
				'search'              => '',
				'exclude'             => array(),
				'sticky'              => '',
				'inherit'             => false,
				'orderByRelationship' => ! empty( $relationship['sortable'] ),
				'relationshipQuery'   => array(
					array(
						'name'            => $relationship['rel_name'],
						'related_to_post' => $post_id,
					),
				),
			),
			'showRelated'     => true,
			'relationshipKey' => $rel_key,
		);

		$block = array(
			'blockName'    => 'core/query',
			'attrs'        => $attributes,
			'innerBlocks'  => array(
				array(
					'blockName'    => 'core/post-template',
					'attrs'        => array(),
					'innerBlocks'  => array(
						array(
							'blockName'    => 'core/post-title',
							'attrs'        => array( 'isLink' => true ),
							'innerBlocks'  => array(),
							'innerHTML'    => '',
							'innerContent' => array(),
						),
					),
					'innerHTML'    => "\n\n",
					'innerContent' => array( "\n", null, "\n" ),
				),
			),
			'innerHTML'    => '<div class="wp-block-query"></div>',
			'innerContent' => array( '<div class="wp-block-query">', null, '</div>' ),
		);

		return array(
			'markup'     => serialize_block( $block ),
			'attributes' => $attributes,
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
	 * Builds an ability input schema from REST route args.
	 *
	 * Keeps the JSON Schema keywords of each arg and drops the REST-only
	 * callbacks. Args flagged as required are listed in the schema's
	 * `required` array.
	 *
	 * @since 2.1.0
	 *
	 * @param  array $args REST route args, keyed by name.
	 * @return array
	 */
	private static function get_input_schema( array $args ) {

		$properties = array();
		$required   = array();

		foreach ( $args as $name => $arg ) {

			if ( ! empty( $arg['required'] ) ) {
				$required[] = $name;
			}

			unset( $arg['required'], $arg['sanitize_callback'], $arg['validate_callback'], $arg['arg_options'] );

			$properties[ $name ] = $arg;
		}

		$schema = array(
			'type'                 => 'object',
			'properties'           => $properties,
			'additionalProperties' => false,
		);

		if ( ! empty( $required ) ) {
			$schema['required'] = $required;
		}

		return $schema;
	}

	/**
	 * Renames the `id` route arg to `post_id`.
	 *
	 * @since 2.1.0
	 *
	 * @param  array $args REST route args, keyed by name.
	 * @return array
	 */
	private static function get_post_input_args( array $args ) {

		$post_id_arg = $args['id'];

		unset( $args['id'] );

		return array_merge( array( 'post_id' => $post_id_arg ), $args );
	}

	/**
	 * Reduces related items to their ID, name, and type.
	 *
	 * @since 2.1.0
	 *
	 * @param  array $items Related item data from the REST API.
	 * @return array
	 */
	private static function prepare_items( array $items ) {
		return array_map(
			static function ( $item ) {
				return array(
					'id'   => (int) ( $item['id'] ?? 0 ),
					'name' => (string) ( $item['name'] ?? '' ),
					'type' => (string) ( $item['type'] ?? '' ),
				);
			},
			array_values( $items )
		);
	}

	/**
	 * Builds the output schema for a related post or user.
	 *
	 * @since 2.1.0
	 *
	 * @return array
	 */
	private static function get_item_schema() {
		return array(
			'type'       => 'object',
			'properties' => array(
				'id'   => array( 'type' => 'integer' ),
				'name' => array( 'type' => 'string' ),
				'type' => array(
					'type'        => 'string',
					'description' => __( 'The post type, or "user" for users.', 'wp-content-connect' ),
				),
			),
		);
	}

	/**
	 * Builds the output schema for the settings of one side of a relationship.
	 *
	 * @since 2.1.0
	 *
	 * @return array
	 */
	private static function get_relationship_side_schema() {
		return array(
			'labels'    => array( 'type' => 'object' ),
			'max_items' => array( 'type' => 'integer' ),
			'sortable'  => array( 'type' => 'boolean' ),
			'enable_ui' => array( 'type' => 'boolean' ),
		);
	}

	/**
	 * Builds the output schema for a registered relationship definition.
	 *
	 * @since 2.1.0
	 *
	 * @return array
	 */
	private static function get_relationship_definition_schema() {

		$side = self::get_relationship_side_schema();

		return array(
			'type'       => 'object',
			'properties' => array(
				'rel_key'     => array( 'type' => 'string' ),
				'rel_type'    => array( 'type' => 'string' ),
				'rel_name'    => array(
					'type'        => 'string',
					'description' => __( 'The relationship name, used as `name` in a relationship query.', 'wp-content-connect' ),
				),
				'object_type' => array( 'type' => 'string' ),
				'labels'      => $side['labels'],
				'sortable'    => $side['sortable'],
				'enable_ui'   => $side['enable_ui'],
				'from'        => array(
					'type'        => 'object',
					'description' => __( 'The "from" side settings. Post-to-post relationships only.', 'wp-content-connect' ),
					'properties'  => array_merge(
						array( 'object_type' => array( 'type' => 'string' ) ),
						$side
					),
				),
				'to'          => array(
					'type'        => 'object',
					'description' => __( 'The "to" side settings. Post-to-post relationships only.', 'wp-content-connect' ),
					'properties'  => array_merge(
						array(
							'object_types' => array(
								'type'  => 'array',
								'items' => array( 'type' => 'string' ),
							),
						),
						$side
					),
				),
			),
		);
	}

	/**
	 * Builds the output schema for a relationship that applies to a post.
	 *
	 * @since 2.1.0
	 *
	 * @return array
	 */
	private static function get_post_relationship_schema() {
		return array(
			'type'       => 'object',
			'properties' => array_merge(
				array(
					'rel_key'     => array( 'type' => 'string' ),
					'rel_type'    => array( 'type' => 'string' ),
					'rel_name'    => array(
						'type'        => 'string',
						'description' => __( 'The relationship name, used as `name` in a relationship query.', 'wp-content-connect' ),
					),
					'object_type' => array( 'type' => 'string' ),
					'post_type'   => array(
						'type'        => 'array',
						'items'       => array( 'type' => 'string' ),
						'description' => __( 'The related post types. Post-to-post relationships only.', 'wp-content-connect' ),
					),
					'related'     => array(
						'type'        => 'array',
						'items'       => self::get_item_schema(),
						'description' => __( 'The related posts or users. Present only when the context is "embed".', 'wp-content-connect' ),
					),
				),
				self::get_relationship_side_schema()
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
