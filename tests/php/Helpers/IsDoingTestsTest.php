<?php
/**
 * Tests for is_doing_tests() helper function.
 *
 * @package TenUp\ContentConnect\Tests\Helpers
 */

namespace TenUp\ContentConnect\Tests\Helpers;

use TenUp\ContentConnect\Tests\ContentConnectTestCase;
use function TenUp\ContentConnect\Helpers\is_doing_tests;

/**
 * Test cases for is_doing_tests().
 */
class IsDoingTestsTest extends ContentConnectTestCase {

	/**
	 * Returns true when CONTENT_CONNECT_DOING_TESTS is defined and truthy.
	 *
	 * The bootstrap defines this constant, so the helper should report true under PHPUnit.
	 *
	 * @return void
	 */
	public function test_returns_true_when_constant_defined(): void {
		$this->assertTrue( defined( 'CONTENT_CONNECT_DOING_TESTS' ) );
		$this->assertTrue( is_doing_tests() );
	}
}
