<?php

namespace TenUp\ContentConnect\Tests\Unit;

use WP_Mock\Tools\TestCase;

class ContentConnectUnitTestCase extends TestCase {

	public function setUp(): void {
		parent::setUp();

		\WP_Mock::userFunction( 'plugin_dir_url', array( 'return' => 'https://contentconnect.test/wp-content/plugins/content-connect/' ) );
		\WP_Mock::userFunction( 'wp_create_nonce', array( 'return' => '1234567890' ) );
	}

}
