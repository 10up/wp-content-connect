<?php

namespace TenUp\ContentConnect\Tests\QueryIntegration;

use TenUp\ContentConnect\Plugin;
use TenUp\ContentConnect\Registry;
use TenUp\ContentConnect\Relationships\PostToPost;
use TenUp\ContentConnect\Relationships\PostToUser;
use TenUp\ContentConnect\Tests\ContentConnectTestCase;

class User_Query_IntegrationTest extends ContentConnectTestCase {

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

		$registry->define_post_to_user( 'post', 'owner' );
		$registry->define_post_to_user( 'post', 'contrib' );
	}

	public function tearDown() {
		parent::tearDown();
	}

	public function test_that_nothing_happens_without_relationship_defined() {
		$args = array(
			'fields' => 'ids',
			'orderby' => 'ID',
			'order' => 'ASC',
			'number' => 2,
			'paged' => 1,
			'relationship_query' => array(
				array(
					'related_to_post' => '20',
					'type' => 'owner',
				),
			),
		);

		$query = new \WP_User_Query( $args );
		$this->assertEquals( array( 1, 2 ), $query->get_results() );

		$args['paged'] = 2;
		$query = new \WP_User_Query( $args );
		$this->assertEquals( array( 3, 4 ), $query->get_results() );
	}

	public function test_that_nothing_happens_without_required_params() {
		$this->define_relationships();

		$args = array(
			'fields' => 'ids',
			'orderby' => 'ID',
			'order' => 'ASC',
			'number' => 2,
			'paged' => 1,
		);

		$query = new \WP_User_Query( $args );
		$this->assertEquals( array( 1, 2 ), $query->get_results() );

		$args['paged'] = 2;
		$query = new \WP_User_Query( $args );
		$this->assertEquals( array( 3, 4 ), $query->get_results() );
	}

	public function test_that_nothing_happens_without_related_to_post() {
		$this->define_relationships();

		$args = array(
			'fields' => 'ids',
			'orderby' => 'ID',
			'order' => 'ASC',
			'number' => 2,
			'paged' => 1,
			'relationship_query' => array(
				array(
					'type' => 'owner',
				),
			),
		);

		$query = new \WP_User_Query( $args );
		$this->assertEquals( array( 1, 2 ), $query->get_results() );

		$args['paged'] = 2;
		$query = new \WP_User_Query( $args );
		$this->assertEquals( array( 3, 4 ), $query->get_results() );
	}

	public function test_that_nothing_happens_without_relationship_type() {
		$this->define_relationships();

		$args = array(
			'fields' => 'ids',
			'orderby' => 'ID',
			'order' => 'ASC',
			'number' => 2,
			'paged' => 1,
			'relationship_query' => array(
				array(
					'related_to_post' => '31',
				),
			),
		);

		$query = new \WP_User_Query( $args );
		$this->assertEquals( array( 1, 2 ), $query->get_results() );

		$args['paged'] = 2;
		$query = new \WP_User_Query( $args );
		$this->assertEquals( array( 3, 4 ), $query->get_results() );
	}

	public function add_small_relationship_set() {
		$postowner = new PostToUser( 'post', 'owner' );

		$postowner->add_relationship( 1, 2 );
		$postowner->add_relationship( 2, 2 );
		$postowner->add_relationship( 3, 3 );
		$postowner->add_relationship( 4, 3 );
		$postowner->add_relationship( 5, 2 );
		$postowner->add_relationship( 5, 3 );
	}

	public function test_basic_post_to_user_query_integration() {
		$this->define_relationships();
		$this->add_small_relationship_set();

		$args = array(
			'fields' => 'ids',
			'orderby' => 'ID',
			'order' => 'ASC',
			'number' => 10,
			'paged' => 1,
			'relationship_query' => array(
				array(
					'related_to_post' => 1,
					'type' => 'owner',
				),
			),
		);

		$query = new \WP_User_Query( $args );
		$this->assertEquals( array( 2 ), $query->get_results() );

		$args['relationship_query'][0]['related_to_post'] = 3;
		$query = new \WP_User_Query( $args );
		$this->assertEquals( array( 3 ), $query->get_results() );

		$args['relationship_query'][0]['related_to_post'] = 5;
		$query = new \WP_User_Query( $args );
		$this->assertEquals( array( 2, 3 ), $query->get_results() );
	}

	public function test_compound_post_to_user_queries() {
		$this->define_relationships();
		$this->add_small_relationship_set();

		$args = array(
			'fields' => 'ids',
			'orderby' => 'ID',
			'order' => 'ASC',
			'number' => 10,
			'paged' => 1,
			'relationship_query' => array(
				array(
					'related_to_post' => 2,
					'type' => 'owner',
				),
				array(
					'related_to_post' => 5,
					'type' => 'owner',
				),
				'relation' => 'OR',
			),
		);

		$query = new \WP_User_Query( $args );
		$this->assertEquals( array( 2, 3 ), $query->get_results() );

		$args['relationship_query']['relation'] = 'AND';
		$query = new \WP_User_Query( $args );
		$this->assertEquals( array( 2 ), $query->get_results() );
	}

}
