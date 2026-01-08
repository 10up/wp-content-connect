<?php
/**
 * Tests for get_plugin() helper function.
 *
 * @package TenUp\ContentConnect\Tests\Integration\Helpers
 */

namespace TenUp\ContentConnect\Tests\Integration\Helpers;

use TenUp\ContentConnect\Tests\Integration\ContentConnectTestCase;
use function TenUp\ContentConnect\Helpers\get_plugin;

/**
 * Test cases for the get_plugin() helper function.
 */
class GetPluginTest extends ContentConnectTestCase {

	/**
	 * Tests that get_plugin() returns a Plugin instance.
	 *
	 * @return void
	 */
	public function test_get_plugin_returns_plugin_instance() {
		$plugin = get_plugin();

		$this->assertInstanceOf( '\TenUp\ContentConnect\Plugin', $plugin );
	}
}
