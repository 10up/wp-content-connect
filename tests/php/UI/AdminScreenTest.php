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
}
