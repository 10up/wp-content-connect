<?php
/**
 * Tests for PostToPost table creation.
 *
 * @package TenUp\ContentConnect\Tests\Tables
 */

namespace TenUp\ContentConnect\Tests\Tables;

use PHPUnit\Framework\TestCase;

/**
 * Test cases for the PostToPost table.
 */
class PostToPostTest extends TestCase {

	/**
	 * Tests that the post_to_post table is created.
	 *
	 * @return void
	 */
	public function test_table_is_created(): void {
		global $wpdb;

		// @ suppresses headers already sent errors
		@do_action( 'admin_init' );

		$result = $wpdb->query( "SHOW TABLES LIKE '{$wpdb->prefix}post_to_post'" );

		$this->assertSame( 1, $result );
	}
}
