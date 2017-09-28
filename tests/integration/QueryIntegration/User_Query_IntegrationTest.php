<?php

namespace TenUp\ContentConnect\Tests\Integration\QueryIntegration;

use TenUp\ContentConnect\Plugin;
use TenUp\ContentConnect\QueryIntegration\UserQueryIntegration;
use TenUp\ContentConnect\QueryIntegration\UserRelationshipQuery;
use TenUp\ContentConnect\Registry;
use TenUp\ContentConnect\Relationships\PostToUser;
use TenUp\ContentConnect\Tests\Integration\ContentConnectTestCase;

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
					'name' => 'owner',
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
					'name' => 'owner',
				),
			),
		);

		$query = new \WP_User_Query( $args );
		$this->assertEquals( array( 1, 2 ), $query->get_results() );

		$args['paged'] = 2;
		$query = new \WP_User_Query( $args );
		$this->assertEquals( array( 3, 4 ), $query->get_results() );
	}

	public function test_that_nothing_happens_without_relationship_name() {
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
					'name' => 'owner',
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
					'name' => 'owner',
				),
				array(
					'related_to_post' => 5,
					'name' => 'owner',
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

	public function test_orderby_only_works_with_one_segment() {
		$this->define_relationships();

		$query = new \stdClass();

		$query->query_vars = array(
			'orderby' => 'relationship',
		);

		$relationship_query = new UserRelationshipQuery( array(
			array(
				'related_to_post' => 1,
				'name' => 'owner',
			),
		) );

		$query->query_orderby = 'default';

		$integration = new UserQueryIntegration();
		$integration->sortable_orderby( $query, $relationship_query );

		$this->assertEquals( 'ORDER BY p2u1.user_order = 0, p2u1.user_order ASC', $query->query_orderby );


		$relationship_query = new UserRelationshipQuery( array(
			array(
				'related_to_post' => 1,
				'name' => 'owner',
			),
			array(
				'related_to_post' => 2,
				'name' => 'owner',
			)
		) );

		$query->query_orderby = 'default';

		$integration->sortable_orderby( $query, $relationship_query );
		$this->assertEquals( 'default', $query->query_orderby );
	}

	public function test_post_to_user_sorting_queries() {
		$this->define_relationships();
		$this->add_small_relationship_set();

		$rel = new PostToUser( 'post', 'owner' );
		$rel->save_post_to_user_sort_data( 5, array( 2, 3 ) );

		$args = array(
			'fields' => 'ids',
			'orderby' => 'relationship',
			'number' => 10,
			'paged' => 1,
			'relationship_query' => array(
				array(
					'related_to_post' => 5,
					'name' => 'owner',
				),
			),
		);

		$query = new \WP_User_Query( $args );
		$this->assertEquals( array( 2, 3 ), $query->get_results() );

		$rel->save_post_to_user_sort_data( 5, array( 3, 2 ) );
		$query = new \WP_User_Query( $args );
		$this->assertEquals( array( 3, 2 ), $query->get_results() );
	}

}
