<?php

namespace TenUp\P2P\Tests;

use TenUp\P2P\Plugin;
use TenUp\P2P\Registry;

class WP_QueryTest extends P2PTestCase {

	public function setUp() {
		global $wpdb;

		$wpdb->query( "delete from {$wpdb->prefix}post_to_post" );

		$plugin = Plugin::instance();
		$plugin->registry = new Registry();
		$plugin->registry->setup();

		parent::setUp();
	}

	public function define_post_to_post_relationship() {
		$registry = Plugin::instance()->get_registry();
		$registry->define_many_to_many( 'post', 'post' );
	}

	public function tearDown() {
		parent::tearDown();
	}

	public function test_that_nothing_happens_without_relationship_defined() {
		$args = array(
			'post_type' => 'post',
			'related_to_post' => '20',
			'fields' => 'ids',
			'orderby' => 'ID',
			'order' => 'ASC',
			'posts_per_page' => 2,
			'paged' => 1,
		);

		$query = new \WP_Query( $args );
		$this->assertEquals( array( 1, 2 ), $query->posts );

		$args['paged'] = 2;
		$query = new \WP_Query( $args );
		$this->assertEquals( array( 3, 4 ), $query->posts );
	}

	public function test_basic_query_integration() {
		$this->add_known_relations();
		$this->define_post_to_post_relationship();

		$args = array(
			'post_type' => 'post',
			'related_to_post' => '20',
			'fields' => 'ids',
			'orderby' => 'ID',
			'order' => 'ASC',
			'posts_per_page' => 2,
			'paged' => 1,
		);

		$query = new \WP_Query( $args );
		$this->assertEquals( array( 22, 24 ), $query->posts );

		$args['paged'] = 2;
		$query = new \WP_Query( $args );
		$this->assertEquals( array( 26, 28 ), $query->posts );

		$args['related_to_post'] = 21;
		$args['paged'] = 1;
		$query = new \WP_Query( $args );
		$this->assertEquals( array( 23, 25 ), $query->posts );

		$args['paged'] = 2;
		$query = new \WP_Query( $args );
		$this->assertEquals( array( 27, 29 ), $query->posts );
	}

}
