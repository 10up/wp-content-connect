<?php

namespace TenUp\ContentConnect\Tests\Tables;

class PostToPostTest extends \PHPUnit_Framework_TestCase {

	public function test_table_is_created() {
		global $wpdb;

		do_action( 'admin_init' );

		$result = $wpdb->query( "SHOW TABLES LIKE '{$wpdb->prefix}post_to_post'" );

		$this->assertEquals( 1, $result );
	}

}
