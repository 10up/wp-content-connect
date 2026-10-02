<?php

namespace TenUp\ContentConnect\UI;

use TenUp\ContentConnect\CustomRelationships\Gate;

/**
 * Class AdminScreen
 *
 * Adds the Settings → Content Connect screen where administrators manage
 * relationships.
 *
 * @package TenUp\ContentConnect\UI
 */
class AdminScreen {

	/**
	 * Admin page slug.
	 *
	 * @since 2.1.0
	 *
	 * @var string
	 */
	const PAGE_SLUG = 'content-connect';

	/**
	 * Handle shared by the boot page's script modules and prerequisites.
	 *
	 * @since 2.1.0
	 *
	 * @var string
	 */
	const BOOT_HANDLE = 'wp-content-connect-relationships';

	/**
	 * ID of the element the boot page mounts into.
	 *
	 * @since 2.1.0
	 *
	 * @var string
	 */
	const BOOT_MOUNT_ID = 'content-connect-app';

	/**
	 * Build directory of the boot page, relative to the plugin root.
	 *
	 * @since 2.1.0
	 *
	 * @var string
	 */
	const BOOT_MODULES_DIR = 'dist/modules/relationships/';

	/**
	 * Hook suffix of the admin page, set once the page is added.
	 *
	 * @since 2.1.0
	 *
	 * @var string|false
	 */
	protected $hook_suffix = false;

	/**
	 * Setup the admin screen module.
	 *
	 * @since 2.1.0
	 */
	public function setup() {
		add_action( 'admin_menu', array( $this, 'add_page' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

	/**
	 * Adds the admin page when the admin UI is enabled.
	 *
	 * @since 2.1.0
	 *
	 * @return void
	 */
	public function add_page() {

		if ( ! Gate::is_admin_ui_enabled() ) {
			return;
		}

		$this->hook_suffix = add_options_page(
			__( 'Content Connect', 'wp-content-connect' ),
			__( 'Content Connect', 'wp-content-connect' ),
			'manage_options',
			self::PAGE_SLUG,
			array( $this, 'render_page' )
		);
	}

	/**
	 * Renders the element the admin app mounts into.
	 *
	 * @since 2.1.0
	 *
	 * @return void
	 */
	public function render_page() {

		if ( $this->is_boot_available() ) {
			$this->render_boot_mount();
			return;
		}

		?>
		<div class="wrap">
			<div id="content-connect-admin"></div>
		</div>
		<?php
	}

	/**
	 * Enqueues the admin app on the Content Connect screen.
	 *
	 * @since 2.1.0
	 *
	 * @param  string $hook_suffix The current admin page.
	 * @return void
	 */
	public function enqueue_scripts( $hook_suffix ) {

		if ( ! $this->hook_suffix || $hook_suffix !== $this->hook_suffix ) {
			return;
		}

		if ( $this->is_boot_available() ) {
			$this->enqueue_boot_page();
			return;
		}

		$this->enqueue_classic_page();
	}

	/**
	 * Whether the page can use the boot layout: core bundles `@wordpress/boot`
	 * (WordPress 7.0+) and the boot page was built.
	 *
	 * @since 2.1.0
	 *
	 * @return bool
	 */
	public function is_boot_available() {
		return function_exists( 'wp_register_script_module' )
			&& file_exists( ABSPATH . WPINC . '/js/dist/script-modules/boot/index.min.asset.php' )
			&& file_exists( CONTENT_CONNECT_PATH . self::BOOT_MODULES_DIR . 'content.asset.php' );
	}

	/**
	 * Renders the boot mount point, with the styles that hand the screen over to
	 * the boot layout before it loads.
	 *
	 * @since 2.1.0
	 *
	 * @return void
	 */
	protected function render_boot_mount() {
		?>
		<style>
			#wpwrap { background: var(--wpds-color-foreground-content-neutral, var(--wpds-color-fg-content-neutral, #1e1e1e)); overflow-y: auto; }
			body { background: #fff; }
			#wpcontent { padding-left: 0; }
			#wpbody-content { padding-bottom: 0; }
			#wpbody-content > div:not(.boot-layout-container):not(#screen-meta) { display: none; }
			#wpfooter { display: none; }
			.a11y-speak-region { left: -1px; top: -1px; }
			ul#adminmenu a.wp-has-current-submenu::after,
			ul#adminmenu > li.current > a.current::after { border-right-color: #fff; }
			@media (min-width: 782px) { #wpwrap { overflow-y: initial; } }
		</style>
		<div id="<?php echo esc_attr( self::BOOT_MOUNT_ID ); ?>" class="boot-layout-container"></div>
		<?php
	}

	/**
	 * Enqueues the boot page: a prerequisites carrier for the classic scripts
	 * and styles the modules read, the page's script modules, and the call that
	 * starts boot.
	 *
	 * @since 2.1.0
	 *
	 * @return void
	 */
	protected function enqueue_boot_page() {

		$modules_path = CONTENT_CONNECT_PATH . self::BOOT_MODULES_DIR;
		$modules_url  = CONTENT_CONNECT_URL . self::BOOT_MODULES_DIR;

		$boot_asset    = require ABSPATH . WPINC . '/js/dist/script-modules/boot/index.min.asset.php';
		$content_asset = require $modules_path . 'content.asset.php';
		$route_asset   = file_exists( $modules_path . 'route.asset.php' ) ? require $modules_path . 'route.asset.php' : array();
		$version       = $content_asset['version'];

		$this->preload_rest_data();

		$script_deps = array_unique(
			array_merge(
				$boot_asset['dependencies'],
				$content_asset['dependencies'],
				isset( $route_asset['dependencies'] ) ? $route_asset['dependencies'] : array()
			)
		);

		// A false src registers a script with no file of its own.
		wp_register_script( self::BOOT_HANDLE . '-prerequisites', false, $script_deps, $version, true );

		$routes = array(
			array(
				'path'           => '/',
				'content_module' => self::BOOT_HANDLE . '-content',
				'route_module'   => self::BOOT_HANDLE . '-route',
			),
		);

		// Boot waits for DOMContentLoaded, because a preloaded boot module can otherwise run
		// before the classic scripts it depends on have defined their globals.
		wp_add_inline_script(
			self::BOOT_HANDLE . '-prerequisites',
			sprintf(
				'(function(){var i=function(){import("@wordpress/boot").then(function(m){m.initSinglePage({mountId:"%s",routes:%s});});};if(document.readyState==="loading"){document.addEventListener("DOMContentLoaded",i);}else{i();}})();',
				esc_js( self::BOOT_MOUNT_ID ),
				wp_json_encode( $routes, JSON_HEX_TAG | JSON_UNESCAPED_SLASHES )
			)
		);

		wp_register_script_module( self::BOOT_HANDLE . '-route', $modules_url . 'route.js', array(), $version );
		wp_register_script_module( self::BOOT_HANDLE . '-content', $modules_url . 'content.js', array(), $version );

		$graph   = isset( $content_asset['module_dependencies'] ) ? $content_asset['module_dependencies'] : array();
		$graph[] = array(
			'id'     => '@wordpress/boot',
			'import' => 'static',
		);
		$graph[] = array(
			'id'     => self::BOOT_HANDLE . '-route',
			'import' => 'static',
		);
		$graph[] = array(
			'id'     => self::BOOT_HANDLE . '-content',
			'import' => 'dynamic',
		);

		wp_register_script_module( self::BOOT_HANDLE, $modules_url . 'loader.js', $graph, $version );

		$style_deps = array_filter(
			$boot_asset['dependencies'],
			function ( $handle ) {
				return wp_style_is( $handle, 'registered' );
			}
		);

		$style_deps[] = 'wp-components';

		// WordPress 7.1+ registers the design system tokens as a style of their own.
		if ( wp_style_is( 'wp-theme', 'registered' ) ) {
			$style_deps[] = 'wp-theme';
		}

		wp_register_style( self::BOOT_HANDLE . '-prerequisites', false, array_unique( $style_deps ), $version );

		wp_enqueue_script( self::BOOT_HANDLE . '-prerequisites' );
		wp_enqueue_script_module( self::BOOT_HANDLE );
		wp_enqueue_style( self::BOOT_HANDLE . '-prerequisites' );

		if ( file_exists( $modules_path . 'content.css' ) ) {
			wp_enqueue_style(
				self::BOOT_HANDLE,
				$modules_url . 'content.css',
				array( self::BOOT_HANDLE . '-prerequisites' ),
				$version
			);
		}
	}

	/**
	 * Enqueues the page for WordPress versions without the boot layout.
	 *
	 * @since 2.1.0
	 *
	 * @return void
	 */
	protected function enqueue_classic_page() {

		if ( file_exists( CONTENT_CONNECT_PATH . 'dist/js/admin-relationships.asset.php' ) ) {
			$asset_info = require CONTENT_CONNECT_PATH . 'dist/js/admin-relationships.asset.php';

			wp_enqueue_script(
				'wp-content-connect-admin-relationships',
				CONTENT_CONNECT_URL . 'dist/js/admin-relationships.js',
				$asset_info['dependencies'],
				$asset_info['version'],
				true
			);

			wp_set_script_translations( 'wp-content-connect-admin-relationships', 'wp-content-connect' );
		}

		wp_enqueue_style( 'wp-components' );

		if ( file_exists( CONTENT_CONNECT_PATH . 'dist/css/admin-relationships.asset.php' ) ) {
			$asset_info = require CONTENT_CONNECT_PATH . 'dist/css/admin-relationships.asset.php';

			wp_enqueue_style(
				'wp-content-connect-admin-relationships',
				CONTENT_CONNECT_URL . 'dist/css/admin-relationships.css',
				array( 'wp-components' ),
				$asset_info['version']
			);
		}
	}

	/**
	 * Preloads the REST requests the page makes on load.
	 *
	 * @since 2.1.0
	 *
	 * @return void
	 */
	protected function preload_rest_data() {

		$paths = array(
			'/content-connect/v2/relationships?rel_type=post-to-post',
			'/content-connect/v2/relationships?rel_type=post-to-user',
		);

		$preload = array_reduce( $paths, 'rest_preload_api_request', array() );

		wp_add_inline_script(
			'wp-api-fetch',
			sprintf(
				'wp.apiFetch.use( wp.apiFetch.createPreloadingMiddleware( %s ) );',
				wp_json_encode( $preload, JSON_HEX_TAG | JSON_UNESCAPED_SLASHES )
			),
			'after'
		);
	}
}
