<?php
/**
 * Tests for the AdminScreen UI class.
 *
 * @package TenUp\ContentConnect\Tests\UI
 */

namespace TenUp\ContentConnect\Tests\UI;

use TenUp\ContentConnect\Tests\ContentConnectTestCase;
use TenUp\ContentConnect\UI\AdminScreen;

/**
 * Test cases for TenUp\ContentConnect\UI\AdminScreen.
 */
class AdminScreenTest extends ContentConnectTestCase {

	/**
	 * Loads the admin menu API.
	 *
	 * @return void
	 */
	public static function setUpBeforeClass(): void {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
		parent::setUpBeforeClass();
	}

	/**
	 * Starts each test with no scripts or styles enqueued.
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		$GLOBALS['wp_scripts'] = null; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$GLOBALS['wp_styles']  = null; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		wp_set_current_user( $this->factory()->user->create( array( 'role' => 'administrator' ) ) );
	}

	/**
	 * Tests that the page is added under Settings for administrators.
	 *
	 * @return void
	 */
	public function test_adds_settings_page() {
		global $submenu;

		wp_set_current_user( $this->factory()->user->create( array( 'role' => 'administrator' ) ) );

		( new AdminScreen() )->add_page();

		$slugs = wp_list_pluck( $submenu['options-general.php'] ?? array(), 2 );

		$this->assertContains( AdminScreen::PAGE_SLUG, $slugs );
	}

	/**
	 * Tests that the page is not added when the admin UI is disabled.
	 *
	 * @return void
	 */
	public function test_skips_page_when_disabled() {
		global $submenu;

		wp_set_current_user( $this->factory()->user->create( array( 'role' => 'administrator' ) ) );
		add_filter( 'tenup_content_connect_enable_admin_ui', '__return_false' );
		$submenu = array(); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		( new AdminScreen() )->add_page();

		$slugs = wp_list_pluck( $submenu['options-general.php'] ?? array(), 2 );

		$this->assertNotContains( AdminScreen::PAGE_SLUG, $slugs );
	}

	/**
	 * Tests that the boot page is enqueued when core bundles boot.
	 *
	 * @return void
	 */
	public function test_enqueues_boot_page_when_boot_is_available() {
		$screen = new AdminScreen();

		if ( ! $screen->is_boot_available() ) {
			$this->markTestSkipped( 'This WordPress version does not bundle @wordpress/boot.' );
		}

		$screen->add_page();
		$screen->enqueue_scripts( get_plugin_page_hookname( AdminScreen::PAGE_SLUG, 'options-general.php' ) );

		$carrier = AdminScreen::BOOT_HANDLE . '-prerequisites';
		$inline  = implode( '', (array) wp_scripts()->get_data( $carrier, 'after' ) );

		$this->assertTrue( wp_script_is( $carrier, 'enqueued' ) );
		$this->assertTrue( wp_style_is( $carrier, 'enqueued' ) );
		$this->assertStringContainsString( 'initSinglePage', $inline );
		$this->assertStringContainsString( AdminScreen::BOOT_MOUNT_ID, $inline );
		$this->assertStringContainsString( AdminScreen::BOOT_HANDLE . '-content', $inline );
		$this->assertFalse( wp_script_is( 'wp-content-connect-admin-relationships', 'enqueued' ) );
	}

	/**
	 * Tests that the classic page is enqueued when boot is not available.
	 *
	 * @return void
	 */
	public function test_enqueues_classic_page_without_boot() {
		$screen = $this->get_screen_without_boot();

		$screen->add_page();
		$screen->enqueue_scripts( get_plugin_page_hookname( AdminScreen::PAGE_SLUG, 'options-general.php' ) );

		$this->assertTrue( wp_script_is( 'wp-content-connect-admin-relationships', 'enqueued' ) );
		$this->assertTrue( wp_style_is( 'wp-content-connect-admin-relationships', 'enqueued' ) );
		$this->assertFalse( wp_script_is( AdminScreen::BOOT_HANDLE . '-prerequisites', 'enqueued' ) );
	}

	/**
	 * Tests that nothing is enqueued on other admin screens.
	 *
	 * @return void
	 */
	public function test_skips_other_admin_screens() {
		$screen = new AdminScreen();

		$screen->add_page();
		$screen->enqueue_scripts( 'index.php' );

		$this->assertFalse( wp_script_is( AdminScreen::BOOT_HANDLE . '-prerequisites', 'enqueued' ) );
		$this->assertFalse( wp_script_is( 'wp-content-connect-admin-relationships', 'enqueued' ) );
	}

	/**
	 * Tests that the boot page renders its mount point.
	 *
	 * @return void
	 */
	public function test_renders_boot_mount_point() {
		$screen = new AdminScreen();

		if ( ! $screen->is_boot_available() ) {
			$this->markTestSkipped( 'This WordPress version does not bundle @wordpress/boot.' );
		}

		ob_start();
		$screen->render_page();
		$output = ob_get_clean();

		$this->assertStringContainsString( 'id="' . AdminScreen::BOOT_MOUNT_ID . '"', $output );
		$this->assertStringContainsString( 'boot-layout-container', $output );
	}

	/**
	 * Tests that the classic page renders its mount point.
	 *
	 * @return void
	 */
	public function test_renders_classic_mount_point() {
		ob_start();
		$this->get_screen_without_boot()->render_page();
		$output = ob_get_clean();

		$this->assertStringContainsString( 'id="content-connect-admin"', $output );
		$this->assertStringNotContainsString( 'boot-layout-container', $output );
	}

	/**
	 * Returns an admin screen that behaves as on WordPress versions without boot.
	 *
	 * @return AdminScreen
	 */
	private function get_screen_without_boot() {
		return new class() extends AdminScreen {
			/**
			 * {@inheritDoc}
			 */
			public function is_boot_available() {
				return false;
			}
		};
	}
}
