<?php
/**
 * Tests for the admin UI gate.
 *
 * @package TenUp\ContentConnect\Tests\CustomRelationships
 */

namespace TenUp\ContentConnect\Tests\CustomRelationships;

use TenUp\ContentConnect\CustomRelationships\Gate;
use TenUp\ContentConnect\Tests\ContentConnectTestCase;

/**
 * Test cases for TenUp\ContentConnect\CustomRelationships\Gate.
 */
class GateTest extends ContentConnectTestCase {

	/**
	 * Tests that the admin UI is enabled by default.
	 *
	 * @return void
	 */
	public function test_enabled_by_default() {
		$this->assertTrue( Gate::is_admin_ui_enabled() );
	}

	/**
	 * Tests that the filter disables the admin UI.
	 *
	 * @return void
	 */
	public function test_filter_disables_admin_ui() {
		add_filter( 'tenup_content_connect_enable_admin_ui', '__return_false' );

		$this->assertFalse( Gate::is_admin_ui_enabled() );
	}
}
