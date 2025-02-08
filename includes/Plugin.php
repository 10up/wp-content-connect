<?php

namespace TenUp\ContentConnect;

use TenUp\ContentConnect\API;
use TenUp\ContentConnect\QueryIntegration\UserQueryIntegration;
use TenUp\ContentConnect\QueryIntegration\WPQueryIntegration;
use TenUp\ContentConnect\Relationships\DeletedItems;
use TenUp\ContentConnect\Tables\PostToPost;
use TenUp\ContentConnect\Tables\PostToUser;
use TenUp\ContentConnect\UI\MetaBox;

use function TenUp\ContentConnect\Helpers\get_post_relationship_data;

class Plugin {

	/**
	 * @var array
	 */
	public $tables = array();

	/**
	 * @var Registry
	 */
	public $registry;

	/**
	 * @var WPQueryIntegration
	 */
	public $wp_query_integration;

	/**
	 * @var UserQueryIntegration
	 */
	public $user_query_integration;

	/**
	 * @var MetaBox
	 */
	public $meta_box;

	/**
	 * @var Search
	 */
	public $search;

	/**
	 * @var DeletedItems
	 */
	public $deleted_items;

	/**
	 * The single instance of the class.
	 *
	 * @var Plugin
	 */
	private static $instance;

	/**
	 * Get class instance.
	 *
	 * @return Plugin
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
			self::$instance->setup();
		}
		return self::$instance;
	}

	public function get_registry() {
		return $this->registry;
	}

	public function get_table( $table ) {
		if ( isset( $this->tables[ $table ] ) ) {
			return $this->tables[ $table ];
		}

		return false;
	}

	public function setup() {
		$this->register_tables();

		$this->registry = new Registry();
		$this->registry->setup();

		$this->wp_query_integration = new WPQueryIntegration();
		$this->wp_query_integration->setup();

		$this->user_query_integration = new UserQueryIntegration();
		$this->user_query_integration->setup();

		$this->meta_box = new MetaBox();
		$this->meta_box->setup();

		$this->deleted_items = new DeletedItems();
		$this->deleted_items->setup();

		$routes = array(
			new API\V1\Search(),
			new API\V2\Relationships(),
			new API\V2\Search(),
		);

		foreach ( $routes as $route ) {
			$route->setup();
		}

		add_action( 'init', array( $this, 'init' ), 100 );
		add_action( 'rest_api_init', array( $this, 'rest_api_init' ) );
	}

	/**
	 * Initializes the plugin and fires an action other plugins can hook into.
	 *
	 * @return void
	 */
	public function init() {
		do_action( 'tenup-content-connect-init', $this->registry ); // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores
	}

	/**
	 * Register the REST API fields for the plugin.
	 *
	 * @return void
	 */
	public function rest_api_init() {
		$post_types = get_post_types( array( 'public' => true ) );

		foreach ( $post_types as $post_type ) {

			register_rest_field(
				$post_type,
				'relationships',
				array(
					'get_callback'    => array( $this, 'get_post_relationships' ),
					'update_callback' => null,
					'schema'          => array(
						'description' => __( 'Lists all relationships associated with this post.', 'tenup-content-connect' ),
						'type'        => 'array',
						'context'     => array( 'view', 'edit' ),
					),
				)
			);
		}
	}

	/**
	 * Register the tables for the plugin.
	 *
	 * @return void
	 */
	public function register_tables() {
		$this->tables['p2p'] = new PostToPost();
		$this->tables['p2p']->setup();

		$this->tables['p2u'] = new PostToUser();
		$this->tables['p2u']->setup();
	}

	/**
	 * Get post relationships.
	 *
	 * REST API callback for the 'relationships' field.
	 *
	 * @param  array $post_data Raw post data from the REST API request.
	 * @return array
	 */
	public function get_post_relationships( $post_data ) {

		if ( empty( $post_data['id'] ) ) {
			return array();
		}

		$post = get_post( $post_data['id'] );

		if ( ! $post ) {
			return array();
		}

		$relationships = get_post_relationship_data( $post );

		return $relationships;
	}
}
