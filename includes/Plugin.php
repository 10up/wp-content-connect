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
		$this->define_constants();
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
			new REST(),
			new API\V1\Search(),
			new API\V2\Route\Relationships(),
			new API\V2\Post\Route\Relationships(),
			new API\V2\Post\Route\RelatedEntities(),
		);

		foreach ( $modules as $module ) {

			if ( method_exists( $module, 'setup' ) ) {
				$module->setup();
			}
		}

		add_action( 'init', array( $this, 'init' ), 100 );

		// Source checkouts (e.g. Composer's dev-develop) have no dist/, and the editor UI silently doesn't load without it.
		if ( ! file_exists( CONTENT_CONNECT_PATH . 'dist/js/block-editor.asset.php' ) ) {
			add_action( 'admin_notices', array( $this, 'missing_build_notice' ) );
		}
	}

	/**
	 * Tells admins the editor UI is missing because the plugin's assets haven't been built.
	 *
	 * @return void
	 */
	public function missing_build_notice() {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		wp_admin_notice(
			sprintf(
				/* translators: 1: dist/, 2: npm run build */
				esc_html__( 'WP Content Connect is missing its built assets (%1$s), so the relationship panels won\'t appear in the editor. Install a tagged release, or run %2$s in the plugin directory.', 'wp-content-connect' ),
				'<code>dist/</code>',
				'<code>npm run build</code>'
			),
			array( 'type' => 'error' )
		);
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
			define( 'CONTENT_CONNECT_VERSION', '2.0.0-beta.2' );
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
