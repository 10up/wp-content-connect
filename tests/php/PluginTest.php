<?php
/**
 * Tests for the plugin bootstrap: constants, tables, module registration and the init action.
 *
 * @package TenUp\ContentConnect\Tests
 */

namespace TenUp\ContentConnect\Tests;

use TenUp\ContentConnect\Plugin;
use TenUp\ContentConnect\Registry;
use TenUp\ContentConnect\Tables\BaseTable;

/**
 * Test cases for Plugin bootstrap.
 */
class PluginTest extends ContentConnectTestCase {

	/**
	 * Sets up the test environment.
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		Plugin::instance();
	}

	/**
	 * Tests that setup() defines the plugin constants.
	 *
	 * @return void
	 */
	public function test_setup_defines_constants() {
		$this->assertTrue( defined( 'CONTENT_CONNECT_VERSION' ) );
		$this->assertTrue( defined( 'CONTENT_CONNECT_URL' ) );
		$this->assertTrue( defined( 'CONTENT_CONNECT_PATH' ) );

		$this->assertSame( '2.0.0', CONTENT_CONNECT_VERSION );
		$this->assertStringEndsWith( '/', CONTENT_CONNECT_URL );
		$this->assertStringEndsWith( '/', CONTENT_CONNECT_PATH );
	}

	/**
	 * Tests that the registry is set up and returned.
	 *
	 * @return void
	 */
	public function test_registry_is_available() {
		$this->assertInstanceOf( Registry::class, Plugin::instance()->get_registry() );
	}

	/**
	 * Tests that the relationship tables are registered.
	 *
	 * @return void
	 */
	public function test_tables_are_registered() {
		$this->assertInstanceOf( BaseTable::class, Plugin::instance()->get_table( 'p2p' ) );
		$this->assertInstanceOf( BaseTable::class, Plugin::instance()->get_table( 'p2u' ) );
		$this->assertFalse( Plugin::instance()->get_table( 'does-not-exist' ) );
	}

	/**
	 * Tests that init() fires the public init action with the registry.
	 *
	 * @return void
	 */
	public function test_init_fires_action_with_registry() {
		$fired    = false;
		$argument = null;

		add_action(
			'tenup-content-connect-init',
			function ( $registry ) use ( &$fired, &$argument ) {
				$fired    = true;
				$argument = $registry;
			}
		);

		Plugin::instance()->init();

		remove_all_actions( 'tenup-content-connect-init' );

		$this->assertTrue( $fired, 'The tenup-content-connect-init action should fire.' );
		$this->assertInstanceOf( Registry::class, $argument );
	}

	/**
	 * Tests that the REST modules register their routes on rest_api_init.
	 *
	 * @return void
	 */
	public function test_modules_register_rest_routes() {
		do_action( 'rest_api_init', rest_get_server() );

		$routes = rest_get_server()->get_routes();

		$this->assertArrayHasKey( '/content-connect/v1/search', $routes );
		$this->assertArrayHasKey( '/content-connect/v2/relationships', $routes );
		$this->assertArrayHasKey( '/content-connect/v2/post/(?P<id>[\d]+)/relationships', $routes );
		$this->assertArrayHasKey( '/content-connect/v2/post/(?P<id>[\d]+)/related', $routes );
	}
}
