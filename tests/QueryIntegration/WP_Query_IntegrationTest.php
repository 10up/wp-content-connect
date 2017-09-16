<?php

namespace TenUp\P2P\Tests\QueryIntegration;

use TenUp\P2P\Plugin;
use TenUp\P2P\Registry;
use TenUp\P2P\Tests\P2PTestCase;

class WP_Query_IntegrationTest extends P2PTestCase {

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
		$registry->define_many_to_many( 'post', 'post', 'basic' );
		$registry->define_many_to_many( 'post', 'post', 'complex' );
		$registry->define_many_to_many( 'post', 'post', 'page1' );
		$registry->define_many_to_many( 'post', 'post', 'page2' );
	}

	public function tearDown() {
		parent::tearDown();
	}

	public function test_that_nothing_happens_without_relationship_defined() {
		$args = array(
			'post_type' => 'post',
			'fields' => 'ids',
			'orderby' => 'ID',
			'order' => 'ASC',
			'posts_per_page' => 2,
			'paged' => 1,
			'relationship_query' => array(
				array(
					'related_to_post' => '20',
					'type' => 'page1',
				),
			),
		);

		$query = new \WP_Query( $args );
		$this->assertEquals( array( 1, 2 ), $query->posts );

		$args['paged'] = 2;
		$query = new \WP_Query( $args );
		$this->assertEquals( array( 3, 4 ), $query->posts );
	}

	public function test_that_nothing_happens_without_required_params() {
		$args = array(
			'post_type' => 'post',
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

	public function test_that_nothing_happens_without_related_to_post() {
		$args = array(
			'post_type' => 'post',
			'fields' => 'ids',
			'orderby' => 'ID',
			'order' => 'ASC',
			'posts_per_page' => 2,
			'paged' => 1,
			'relationship_query' => array(
				array(
					'type' => 'page1',
				),
			),
		);

		$query = new \WP_Query( $args );
		$this->assertEquals( array( 1, 2 ), $query->posts );

		$args['paged'] = 2;
		$query = new \WP_Query( $args );
		$this->assertEquals( array( 3, 4 ), $query->posts );
	}

	public function test_that_nothing_happens_without_relationship_type() {
		$args = array(
			'post_type' => 'post',
			'fields' => 'ids',
			'orderby' => 'ID',
			'order' => 'ASC',
			'posts_per_page' => 2,
			'paged' => 1,
			'relationship_query' => array(
				array(
					'related_to_post' => '31',
				),
			),
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
			'fields' => 'ids',
			'orderby' => 'ID',
			'order' => 'ASC',
			'posts_per_page' => 2,
			'paged' => 1,
			'relationship_query' => array(
				array(
					'related_to_post' => '31',
					'type' => 'page1',
				),
			),

		);

		$query = new \WP_Query( $args );
		$this->assertEquals( array( 36, 40 ), $query->posts );

		$args['paged'] = 2;
		$query = new \WP_Query( $args );
		$this->assertEquals( array( 44, 48 ), $query->posts );

		$args['relationship_query'][0]['related_to_post'] = 32;
		$args['paged'] = 1;
		$query = new \WP_Query( $args );
		$this->assertEquals( array( 37, 41 ), $query->posts );

		$args['paged'] = 2;
		$query = new \WP_Query( $args );
		$this->assertEquals( array( 45, 49 ), $query->posts );

		// Different type, so should come back empty
		$args['relationship_query'][0]['related_to_post'] = 33;
		$args['paged'] = 1;
		$query = new \WP_Query( $args );
		$this->assertEquals( array(), $query->posts );

		$args['relationship_query'][0]['type'] = 'page2';
		$query = new \WP_Query( $args );
		$this->assertEquals( array( 38, 42 ), $query->posts );

		$args['paged'] = '2';
		$query = new \WP_Query( $args );
		$this->assertEquals( array( 46, 50 ), $query->posts );
	}

	public function test_compound_queries() {
		$this->add_known_relations();
		$this->define_post_to_post_relationship();

		$args = array(
			'post_type' => 'post',
			'fields' => 'ids',
			'orderby' => 'ID',
			'order' => 'ASC',
			'posts_per_page' => 3,
			'paged' => 1,
		);


		$args['relationship_query'] = array(
			'relation' => 'OR',
			array(
				'related_to_post' => 1,
				'type' => 'basic',
			),
			array(
				'related_to_post' => 1,
				'type' => 'complex'
			)
		);
		$query = new \WP_Query( $args );
		$this->assertEquals( array( 2, 3, 4 ), $query->posts );


		$args['relationship_query']['relation'] = 'AND';
		$query = new \WP_Query( $args );
		$this->assertEquals( array( 3 ), $query->posts );
	}

}
