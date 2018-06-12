<?php

namespace TenUp\ContentConnect\Tests\Integration\Tables;

class PostToUserTest extends \PHPUnit_Framework_TestCase {

	public function test_table_is_created() {
		global $wpdb;

		// @ suppresses headers already sent errors
		@do_action( 'admin_init' );

		$result = $wpdb->query( "SHOW TABLES LIKE '{$wpdb->prefix}post_to_user'" );

		$this->assertEquals( 1, $result );
	}

}
