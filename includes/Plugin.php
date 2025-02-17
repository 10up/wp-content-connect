<?php

namespace TenUp\ContentConnect;

use TenUp\ContentConnect\API;
use TenUp\ContentConnect\QueryIntegration\UserQueryIntegration;
use TenUp\ContentConnect\QueryIntegration\WPQueryIntegration;
use TenUp\ContentConnect\Relationships\DeletedItems;
use TenUp\ContentConnect\Tables\PostToPost;
use TenUp\ContentConnect\Tables\PostToUser;
use TenUp\ContentConnect\UI\BlockEditor;
use TenUp\ContentConnect\UI\MetaBox;

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
	 * @var BlockEditor
	 */
	public $block_editor;

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

		$this->block_editor = new BlockEditor();
		$this->block_editor->setup();

		$this->deleted_items = new DeletedItems();
		$this->deleted_items->setup();

		$routes = array(
			new API\V1\Search(),
			new API\V2\Post\Field\Relationships(),
			new API\V2\Post\Route\Relationships(),
			new API\V2\Post\Route\RelatedEntities(),
			new API\V2\Post\Route\Search(),
		);

		foreach ( $routes as $route ) {
			$route->setup();
		}

		add_action( 'init', array( $this, 'init' ), 100 );
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
}
