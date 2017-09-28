<?php

namespace TenUp\ContentConnect\Tests\Unit;

class ContentConnectUnitTestCase extends \PHPUnit_Framework_TestCase {

	public function setUp() {
		\WP_Mock::setUp();

		\WP_Mock::userFunction( 'plugin_dir_url', array( 'return' => 'https://contentconnect.test/wp-content/plugins/content-connect/' ) );
		\WP_Mock::userFunction( 'wp_create_nonce', array( 'return' => '1234567890' ) );

		parent::setUp();
	}

	public function tearDown() {
		// Add assertions from Mockery to the total count
		if ( $container = \Mockery::getContainer() ) {
			$this->addToAssertionCount( $container->mockery_getExpectationCount() );
		}

		\WP_Mock::tearDown();

		parent::tearDown();
	}

}
