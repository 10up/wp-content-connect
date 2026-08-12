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
		$this->define_constants();
		$this->register_tables();

		$this->registry = new Registry();
		$this->registry->setup();

		$modules = array(
			new WPQueryIntegration(),
			new UserQueryIntegration(),
			new MetaBox(), // @deprecated remove in 2.0.0
			new BlockEditor(),
			new DeletedItems(),
			new REST(),
			new API\V1\Search(),
			new API\V2\Route\Relationships(),
			new API\V2\Post\Route\Relationships(),
			new API\V2\Post\Route\RelatedEntities(),
		);

		foreach ( $modules as $module ) {
			$module->setup();
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
	 * Define plugin constants.
	 *
	 * @return void
	 */
	public function define_constants() {

		if ( ! defined( 'CONTENT_CONNECT_VERSION' ) ) {
			define( 'CONTENT_CONNECT_VERSION', '2.0.0' );
		}

		if ( ! defined( 'CONTENT_CONNECT_URL' ) ) {
			define( 'CONTENT_CONNECT_URL', plugin_dir_url( __DIR__ ) );
		}

		if ( ! defined( 'CONTENT_CONNECT_PATH' ) ) {
			define( 'CONTENT_CONNECT_PATH', plugin_dir_path( __DIR__ ) );
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
}
