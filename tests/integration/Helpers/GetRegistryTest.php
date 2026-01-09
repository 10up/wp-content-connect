<?php
/**
 * Tests for get_registry() helper function.
 *
 * @package TenUp\ContentConnect\Tests\Integration\Helpers
 */

namespace TenUp\ContentConnect\Tests\Integration\Helpers;

use TenUp\ContentConnect\Tests\Integration\ContentConnectTestCase;
use function TenUp\ContentConnect\Helpers\get_registry;

/**
 * Test cases for the get_registry() helper function.
 */
class GetRegistryTest extends ContentConnectTestCase {

	/**
	 * Tests that get_registry() returns a Registry instance.
	 *
	 * @return void
	 */
	public function test_get_registry_returns_registry() {
		$registry = get_registry();

		$this->assertInstanceOf( '\TenUp\ContentConnect\Registry', $registry );
	}
}
