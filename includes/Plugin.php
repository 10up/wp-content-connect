<?php

namespace TenUp\ContentConnect;

use TenUp\ContentConnect\API;
use TenUp\ContentConnect\QueryIntegration\QueryBlockIntegration;
use TenUp\ContentConnect\QueryIntegration\UserQueryIntegration;
use TenUp\ContentConnect\QueryIntegration\WPQueryIntegration;
use TenUp\ContentConnect\Relationships\DeletedItems;
use TenUp\ContentConnect\Tables\PostToPost;
use TenUp\ContentConnect\Tables\PostToUser;
use TenUp\ContentConnect\UI\BlockEditor;
use TenUp\ContentConnect\UI\ClassicEditor;

/**
 * Class Plugin
 *
 * @package TenUp\ContentConnect
 */
class Plugin {

	/**
	 * The tables for the plugin.
	 *
	 * @var array
	 */
	public $tables = array();

	/**
	 * The registry instance.
	 *
	 * @var Registry
	 */
	public $registry;

	/**
	 * The single instance of the class.
	 *
	 * @var Plugin
	 */
	protected static $instance;

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

	/**
	 * Retrieves the registry instance.
	 *
	 * @return Registry
	 */
	public function get_registry() {
		return $this->registry;
	}

	/**
	 * Retrieves a table.
	 *
	 * @param string $table The table to retrieve.
	 * @return PostToPost|PostToUser|bool
	 */
	public function get_table( $table ) {

		if ( isset( $this->tables[ $table ] ) ) {
			return $this->tables[ $table ];
		}

		return false;
	}

	/**
	 * Sets up the plugin.
	 *
	 * @return void
	 */
	public function setup() {
		$this->register_tables();

		$this->registry = new Registry();
		$this->registry->setup();

		$modules = array(
			new WPQueryIntegration(),
			new UserQueryIntegration(),
			new QueryBlockIntegration(),
			new ClassicEditor(),
			new BlockEditor(),
			new DeletedItems(),
			new API\V1\Search(), // @deprecated remove in 1.7.0
			new API\V2\Post\Field\Relationships(),
			new API\V2\Post\Route\Relationships(),
			new API\V2\Post\Route\RelatedEntities(),
			new API\V2\Post\Route\Search(),
		);

		foreach ( $modules as $module ) {

			if ( method_exists( $module, 'setup' ) ) {
				$module->setup();
			}
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
