<?php

namespace TenUp\P2P\Tests\QueryIntegration;

use TenUp\P2P\Plugin;
use TenUp\P2P\Registry;
use TenUp\P2P\Relationships\PostToPost;
use TenUp\P2P\Relationships\PostToUser;
use TenUp\P2P\Tests\P2PTestCase;

class WP_Query_IntegrationTest extends P2PTestCase {

	public function setUp() {
		global $wpdb;

		$wpdb->query( "delete from {$wpdb->prefix}post_to_post" );
		$wpdb->query( "delete from {$wpdb->prefix}post_to_user" );

		$plugin = Plugin::instance();
		$plugin->registry = new Registry();
		$plugin->registry->setup();

		parent::setUp();
	}

	public function define_relationships() {
		$registry = Plugin::instance()->get_registry();
		$registry->define_post_to_post( 'post', 'post', 'basic' );
		$registry->define_post_to_post( 'post', 'post', 'complex' );
		$registry->define_post_to_post( 'post', 'post', 'page1' );
		$registry->define_post_to_post( 'post', 'post', 'page2' );

		$registry->define_post_to_user( 'post', 'owner' );
		$registry->define_post_to_user( 'post', 'contrib' );
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


		$args = array(
			'post_type' => 'post',
			'fields' => 'ids',
			'orderby' => 'ID',
			'order' => 'ASC',
			'posts_per_page' => 2,
			'paged' => 1,
			'relationship_query' => array(
				array(
					'related_to_user' => '2',
					'type' => 'owner',
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
		$this->define_relationships();

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
		$this->define_relationships();

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

	public function test_that_nothing_happens_without_related_to_user() {
		$this->define_relationships();

		$args = array(
			'post_type' => 'post',
			'fields' => 'ids',
			'orderby' => 'ID',
			'order' => 'ASC',
			'posts_per_page' => 2,
			'paged' => 1,
			'relationship_query' => array(
				array(
					'type' => 'owner',
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
		$this->define_relationships();

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


		$args = array(
			'post_type' => 'post',
			'fields' => 'ids',
			'orderby' => 'ID',
			'order' => 'ASC',
			'posts_per_page' => 2,
			'paged' => 1,
			'relationship_query' => array(
				array(
					'related_to_user' => '2',
				),
			),
		);

		$query = new \WP_Query( $args );
		$this->assertEquals( array( 1, 2 ), $query->posts );

		$args['paged'] = 2;
		$query = new \WP_Query( $args );
		$this->assertEquals( array( 3, 4 ), $query->posts );
	}

	public function test_basic_post_to_post_query_integration() {
		$this->add_post_relations();
		$this->define_relationships();

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

	public function test_compound_post_to_post_queries() {
		$this->add_post_relations();
		$this->define_relationships();

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

	public function add_small_relationship_set() {
		$p2p = new PostToPost( 'post', 'post', 'basic' );
		$postowner = new PostToUser( 'post', 'owner' );

		$postowner->add_relationship( 1, 2 );
		$postowner->add_relationship( 2, 2 );
		$postowner->add_relationship( 3, 3 );
		$postowner->add_relationship( 4, 3 );
		$postowner->add_relationship( 5, 2 );
		$postowner->add_relationship( 5, 3 );

		$p2p->add_relationship( 1, 3 );
		$p2p->add_relationship( 1, 4 );
		$p2p->add_relationship( 2, 4 );
		$p2p->add_relationship( 3, 4 );
	}

	public function test_basic_post_to_user_query_integration() {
		$this->define_relationships();
		$this->add_small_relationship_set();

		$args = array(
			'post_type' => 'post',
			'fields' => 'ids',
			'orderby' => 'ID',
			'order' => 'ASC',
			'posts_per_page' => 10,
			'paged' => 1,
			'relationship_query' => array(
				array(
					'related_to_user' => 2,
					'type' => 'owner',
				),
			),
		);

		$query = new \WP_Query( $args );
		$this->assertEquals( array( 1, 2, 5 ), $query->posts );

		$args['relationship_query'][0]['related_to_user'] = 3;
		$query = new \WP_Query( $args );
		$this->assertEquals( array( 3, 4, 5 ), $query->posts );
	}

	public function test_compound_post_to_user_queries() {
		$this->define_relationships();
		$this->add_small_relationship_set();

		$args = array(
			'post_type' => 'post',
			'fields' => 'ids',
			'orderby' => 'ID',
			'order' => 'ASC',
			'posts_per_page' => 10,
			'paged' => 1,
			'relationship_query' => array(
				array(
					'related_to_user' => 2,
					'type' => 'owner',
				),
				array(
					'related_to_user' => 3,
					'type' => 'owner',
				),
				'relation' => 'OR',
			),
		);

		$query = new \WP_Query( $args );
		$this->assertEquals( array( 1, 2, 3, 4, 5 ), $query->posts );

		$args['relationship_query']['relation'] = 'AND';
		$query = new \WP_Query( $args );
		$this->assertEquals( array( 5 ), $query->posts );
	}

	public function test_mixed_post_to_post_and_post_to_user_queries() {
		$this->define_relationships();
		$this->add_small_relationship_set();

		$args = array(
			'post_type' => 'post',
			'fields' => 'ids',
			'orderby' => 'ID',
			'order' => 'ASC',
			'posts_per_page' => 10,
			'paged' => 1,
			'relationship_query' => array(
				array(
					'related_to_user' => 2,
					'type' => 'owner',
				),
				array(
					'related_to_post' => 3,
					'type' => 'basic',
				),
				'relation' => 'AND',
			),
		);

		$query = new \WP_Query( $args );
		$this->assertEquals( array( 1 ), $query->posts );

		$args['relationship_query']['relation'] = 'OR';
		$query = new \WP_Query( $args );
		$this->assertEquals( array( 1, 2, 4, 5 ), $query->posts );
	}

}
