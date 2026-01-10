<?php

namespace TenUp\ContentConnect\Tests\Unit;

class ContentConnectUnitTestCase extends \PHPUnit\Framework\TestCase {

	public function setUp(): void {
		\WP_Mock::setUp();

		// Define constants that are normally set by Plugin::instance()
		if ( ! defined( 'CONTENT_CONNECT_VERSION' ) ) {
			define( 'CONTENT_CONNECT_VERSION', '1.7.0' );
		}

		if ( ! defined( 'CONTENT_CONNECT_URL' ) ) {
			define( 'CONTENT_CONNECT_URL', 'https://contentconnect.test/wp-content/plugins/content-connect/' );
		}

		if ( ! defined( 'CONTENT_CONNECT_PATH' ) ) {
			define( 'CONTENT_CONNECT_PATH', '/path/to/content-connect/' );
		}

		\WP_Mock::userFunction( 'plugin_dir_url', array( 'return' => 'https://contentconnect.test/wp-content/plugins/content-connect/' ) );
		\WP_Mock::userFunction( 'wp_create_nonce', array( 'return' => '1234567890' ) );

		parent::setUp();
	}

	public function tearDown(): void {
		// Add assertions from Mockery to the total count
		if ( $container = \Mockery::getContainer() ) {
			$this->addToAssertionCount( $container->mockery_getExpectationCount() );
		}

		\WP_Mock::tearDown();

		parent::tearDown();
	}

}
