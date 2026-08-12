<?php
/**
 * Tests for PostToUser table creation.
 *
 * @package TenUp\ContentConnect\Tests\Tables
 */

namespace TenUp\ContentConnect\Tests\Tables;

use PHPUnit\Framework\TestCase;

/**
 * Test cases for the PostToUser table.
 */
class PostToUserTest extends TestCase {

	/**
	 * Tests that the post_to_user table is created.
	 *
	 * @return void
	 */
	public function test_table_is_created(): void {
		global $wpdb;

		// @ suppresses headers already sent errors
		@do_action( 'admin_init' );

		$result = $wpdb->query( "SHOW TABLES LIKE '{$wpdb->prefix}post_to_user'" );

		$this->assertSame( 1, $result );
	}
}
