<?php
/**
 * Tests for the Plugin singleton lifecycle.
 *
 * @package TenUp\ContentConnect\Tests
 */

namespace TenUp\ContentConnect\Tests;

use TenUp\ContentConnect\Plugin;
use TenUp\ContentConnect\Tables\PostToPost as PostToPostTable;
use TenUp\ContentConnect\Tables\PostToUser as PostToUserTable;

/**
 * Tests for Plugin singleton lifecycle and side effects.
 */
class PluginTest extends ContentConnectTestCase {

	/**
	 * `Plugin::instance()` returns the same singleton across calls.
	 *
	 * @return void
	 */
	public function test_instance_returns_singleton(): void {
		$first  = Plugin::instance();
		$second = Plugin::instance();

		$this->assertSame( $first, $second );
	}

	/**
	 * `define_constants()` defines the expected constants without overwriting prior definitions.
	 *
	 * @return void
	 */
	public function test_define_constants(): void {
		$this->assertTrue( defined( 'CONTENT_CONNECT_VERSION' ) );
		$this->assertTrue( defined( 'CONTENT_CONNECT_URL' ) );
		$this->assertTrue( defined( 'CONTENT_CONNECT_PATH' ) );

		$this->assertSame( '2.0.0', CONTENT_CONNECT_VERSION );

		$this->assertNotEmpty( CONTENT_CONNECT_PATH );
		$this->assertDirectoryExists( CONTENT_CONNECT_PATH );
	}

	/**
	 * `register_tables()` registers both relationship tables on the Plugin instance.
	 *
	 * @return void
	 */
	public function test_register_tables(): void {
		$plugin = Plugin::instance();

		$this->assertArrayHasKey( 'p2p', $plugin->tables );
		$this->assertArrayHasKey( 'p2u', $plugin->tables );

		$this->assertInstanceOf( PostToPostTable::class, $plugin->tables['p2p'] );
		$this->assertInstanceOf( PostToUserTable::class, $plugin->tables['p2u'] );
	}

	/**
	 * `get_table()` returns the registered table or false for unknown keys.
	 *
	 * @return void
	 */
	public function test_get_table(): void {
		$plugin = Plugin::instance();

		$this->assertInstanceOf( PostToPostTable::class, $plugin->get_table( 'p2p' ) );
		$this->assertInstanceOf( PostToUserTable::class, $plugin->get_table( 'p2u' ) );
		$this->assertFalse( $plugin->get_table( 'unknown_table_key' ) );
	}

	/**
	 * `get_registry()` returns the Registry instance held by Plugin.
	 *
	 * @return void
	 */
	public function test_get_registry(): void {
		$plugin = Plugin::instance();

		$this->assertSame( $plugin->registry, $plugin->get_registry() );
	}

	/**
	 * `init()` fires the `tenup-content-connect-init` action with the registry.
	 *
	 * @return void
	 */
	public function test_init_fires_action(): void {
		$captured = null;

		$callback = function ( $registry ) use ( &$captured ) {
			$captured = $registry;
		};

		add_action( 'tenup-content-connect-init', $callback );

		Plugin::instance()->init();

		remove_action( 'tenup-content-connect-init', $callback );

		$this->assertSame( Plugin::instance()->get_registry(), $captured );
	}
}
