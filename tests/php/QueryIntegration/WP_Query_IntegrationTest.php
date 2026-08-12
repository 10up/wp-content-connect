<?php
/**
 * Tests for WP_Query integration with Content Connect relationships.
 *
 * @package TenUp\ContentConnect\Tests\QueryIntegration
 */

namespace TenUp\ContentConnect\Tests\QueryIntegration;

use TenUp\ContentConnect\Plugin;
use TenUp\ContentConnect\QueryIntegration\RelationshipQuery;
use TenUp\ContentConnect\QueryIntegration\WPQueryIntegration;
use TenUp\ContentConnect\Registry;
use TenUp\ContentConnect\Tests\ContentConnectTestCase;

/**
 * Test cases for WP_Query integration.
 */
class WP_Query_IntegrationTest extends ContentConnectTestCase {

	/**
	 * Sets up the test environment.
	 *
	 * @return void
	 */
	public function setUp(): void {
		global $wpdb;

		$wpdb->query( "delete from {$wpdb->prefix}post_to_post" );
		$wpdb->query( "delete from {$wpdb->prefix}post_to_user" );

		parent::setUp();

		// Reset registry after parent setUp to ensure clean state for each test
		$plugin           = Plugin::instance();
		$plugin->registry = new Registry();
		$plugin->registry->setup();
	}

	/**
	 * Tests that nothing happens when no relationship is defined.
	 *
	 * @return void
	 */
	public function test_that_nothing_happens_without_relationship_defined(): void {
		$args = array(
			'post_type'          => 'post',
			'fields'             => 'ids',
			'orderby'            => 'ID',
			'order'              => 'ASC',
			'posts_per_page'     => 2,
			'paged'              => 1,
			'relationship_query' => array(
				array(
					'related_to_post' => '20',
					'name'            => 'page1',
				),
			),
		);

		$query = new \WP_Query( $args );
		$this->assertEquals( array( 1, 2 ), $query->posts );

		$args['paged'] = 2;
		$query         = new \WP_Query( $args );
		$this->assertEquals( array( 3, 4 ), $query->posts );

		$args = array(
			'post_type'          => 'post',
			'fields'             => 'ids',
			'orderby'            => 'ID',
			'order'              => 'ASC',
			'posts_per_page'     => 2,
			'paged'              => 1,
			'relationship_query' => array(
				array(
					'related_to_user' => '2',
					'name'            => 'owner',
				),
			),
		);

		$query = new \WP_Query( $args );
		$this->assertEquals( array( 1, 2 ), $query->posts );

		$args['paged'] = 2;
		$query         = new \WP_Query( $args );
		$this->assertEquals( array( 3, 4 ), $query->posts );
	}

	/**
	 * Tests that nothing happens when required parameters are missing.
	 *
	 * @return void
	 */
	public function test_that_nothing_happens_without_required_params(): void {

		$args = array(
			'post_type'      => 'post',
			'fields'         => 'ids',
			'orderby'        => 'ID',
			'order'          => 'ASC',
			'posts_per_page' => 2,
			'paged'          => 1,
		);

		$query = new \WP_Query( $args );
		$this->assertEquals( array( 1, 2 ), $query->posts );

		$args['paged'] = 2;
		$query         = new \WP_Query( $args );
		$this->assertEquals( array( 3, 4 ), $query->posts );
	}

	/**
	 * Tests that nothing happens when related_to_post parameter is missing.
	 *
	 * @return void
	 */
	public function test_that_nothing_happens_without_related_to_post(): void {

		$args = array(
			'post_type'          => 'post',
			'fields'             => 'ids',
			'orderby'            => 'ID',
			'order'              => 'ASC',
			'posts_per_page'     => 2,
			'paged'              => 1,
			'relationship_query' => array(
				array(
					'name' => 'page1',
				),
			),
		);

		$query = new \WP_Query( $args );
		$this->assertEquals( array( 1, 2 ), $query->posts );

		$args['paged'] = 2;
		$query         = new \WP_Query( $args );
		$this->assertEquals( array( 3, 4 ), $query->posts );
	}

	/**
	 * Tests that nothing happens when related_to_user parameter is missing.
	 *
	 * @return void
	 */
	public function test_that_nothing_happens_without_related_to_user(): void {

		$args = array(
			'post_type'          => 'post',
			'fields'             => 'ids',
			'orderby'            => 'ID',
			'order'              => 'ASC',
			'posts_per_page'     => 2,
			'paged'              => 1,
			'relationship_query' => array(
				array(
					'name' => 'owner',
				),
			),
		);

		$query = new \WP_Query( $args );
		$this->assertEquals( array( 1, 2 ), $query->posts );

		$args['paged'] = 2;
		$query         = new \WP_Query( $args );
		$this->assertEquals( array( 3, 4 ), $query->posts );
	}

	/**
	 * Tests that nothing happens when relationship name parameter is missing.
	 *
	 * @return void
	 */
	public function test_that_nothing_happens_without_relationship_name(): void {

		$args = array(
			'post_type'          => 'post',
			'fields'             => 'ids',
			'orderby'            => 'ID',
			'order'              => 'ASC',
			'posts_per_page'     => 2,
			'paged'              => 1,
			'relationship_query' => array(
				array(
					'related_to_post' => '31',
				),
			),
		);

		$query = new \WP_Query( $args );
		$this->assertEquals( array( 1, 2 ), $query->posts );

		$args['paged'] = 2;
		$query         = new \WP_Query( $args );
		$this->assertEquals( array( 3, 4 ), $query->posts );

		$args = array(
			'post_type'          => 'post',
			'fields'             => 'ids',
			'orderby'            => 'ID',
			'order'              => 'ASC',
			'posts_per_page'     => 2,
			'paged'              => 1,
			'relationship_query' => array(
				array(
					'related_to_user' => '2',
				),
			),
		);

		$query = new \WP_Query( $args );
		$this->assertEquals( array( 1, 2 ), $query->posts );

		$args['paged'] = 2;
		$query         = new \WP_Query( $args );
		$this->assertEquals( array( 3, 4 ), $query->posts );
	}

	/**
	 * Tests basic post-to-post query integration.
	 *
	 * @return void
	 */
	public function test_basic_post_to_post_query_integration(): void {
		$this->add_post_relations();

		$args = array(
			'post_type'          => 'post',
			'fields'             => 'ids',
			'orderby'            => 'ID',
			'order'              => 'ASC',
			'posts_per_page'     => 2,
			'paged'              => 1,
			'relationship_query' => array(
				array(
					'related_to_post' => '31',
					'name'            => 'page1',
				),
			),

		);

		$query = new \WP_Query( $args );
		$this->assertEquals( array( 36, 40 ), $query->posts );

		$args['paged'] = 2;
		$query         = new \WP_Query( $args );
		$this->assertEquals( array( 44, 48 ), $query->posts );

		$args['relationship_query'][0]['related_to_post'] = 32;
		$args['paged']                                    = 1;
		$query = new \WP_Query( $args );
		$this->assertEquals( array( 37, 41 ), $query->posts );

		$args['paged'] = 2;
		$query         = new \WP_Query( $args );
		$this->assertEquals( array( 45, 49 ), $query->posts );

		// Different name, so should come back empty
		$args['relationship_query'][0]['related_to_post'] = 33;
		$args['paged']                                    = 1;
		$query = new \WP_Query( $args );
		$this->assertEquals( array(), $query->posts );

		$args['relationship_query'][0]['name'] = 'page2';
		$query                                 = new \WP_Query( $args );
		$this->assertEquals( array( 38, 42 ), $query->posts );

		$args['paged'] = '2';
		$query         = new \WP_Query( $args );
		$this->assertEquals( array( 46, 50 ), $query->posts );
	}

	/**
	 * Tests compound post-to-post queries with OR and AND relations.
	 *
	 * @return void
	 */
	public function test_compound_post_to_post_queries(): void {
		$this->add_post_relations();

		$args = array(
			'post_type'      => 'post',
			'fields'         => 'ids',
			'orderby'        => 'ID',
			'order'          => 'ASC',
			'posts_per_page' => 3,
			'paged'          => 1,
		);

		$args['relationship_query'] = array(
			'relation' => 'OR',
			array(
				'related_to_post' => 1,
				'name'            => 'basic',
			),
			array(
				'related_to_post' => 1,
				'name'            => 'complex',
			),
		);
		$query                      = new \WP_Query( $args );
		$this->assertEquals( array( 2, 3, 4 ), $query->posts );

		$args['relationship_query']['relation'] = 'AND';
		$query                                  = new \WP_Query( $args );
		$this->assertEquals( array( 3 ), $query->posts );
	}

	/**
	 * Adds a small set of relationships for testing.
	 *
	 * @return void
	 */
	public function add_small_relationship_set(): void {
		$registry = Plugin::instance()->get_registry();

		// Register relationships in the registry
		try {
			$registry->define_post_to_post( 'post', 'post', 'basic' );
		} catch ( \Exception $e ) {
			// Relationship might already exist, that's okay
		}
		try {
			$registry->define_post_to_user( 'post', 'owner' );
		} catch ( \Exception $e ) {
			// Relationship might already exist, that's okay
		}

		// Get relationship objects from registry to add actual relationships
		$p2p       = $registry->get_post_to_post_relationship( 'post', 'post', 'basic' );
		$postowner = $registry->get_post_to_user_relationship( 'post', 'owner' );

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

	/**
	 * Tests basic post-to-user query integration.
	 *
	 * @return void
	 */
	public function test_basic_post_to_user_query_integration(): void {
		$this->add_small_relationship_set();

		$args = array(
			'post_type'          => 'post',
			'fields'             => 'ids',
			'orderby'            => 'ID',
			'order'              => 'ASC',
			'posts_per_page'     => 10,
			'paged'              => 1,
			'relationship_query' => array(
				array(
					'related_to_user' => 2,
					'name'            => 'owner',
				),
			),
		);

		$query = new \WP_Query( $args );
		$this->assertEquals( array( 1, 2, 5 ), $query->posts );

		$args['relationship_query'][0]['related_to_user'] = 3;
		$query = new \WP_Query( $args );
		$this->assertEquals( array( 3, 4, 5 ), $query->posts );
	}

	/**
	 * Tests compound post-to-user queries with OR and AND relations.
	 *
	 * @return void
	 */
	public function test_compound_post_to_user_queries(): void {
		$this->add_small_relationship_set();

		$args = array(
			'post_type'          => 'post',
			'fields'             => 'ids',
			'orderby'            => 'ID',
			'order'              => 'ASC',
			'posts_per_page'     => 10,
			'paged'              => 1,
			'relationship_query' => array(
				array(
					'related_to_user' => 2,
					'name'            => 'owner',
				),
				array(
					'related_to_user' => 3,
					'name'            => 'owner',
				),
				'relation' => 'OR',
			),
		);

		$query = new \WP_Query( $args );
		$this->assertEquals( array( 1, 2, 3, 4, 5 ), $query->posts );

		$args['relationship_query']['relation'] = 'AND';
		$query                                  = new \WP_Query( $args );
		$this->assertEquals( array( 5 ), $query->posts );
	}

	/**
	 * Tests mixed post-to-post and post-to-user queries.
	 *
	 * @return void
	 */
	public function test_mixed_post_to_post_and_post_to_user_queries(): void {
		$this->add_small_relationship_set();

		$args = array(
			'post_type'          => 'post',
			'fields'             => 'ids',
			'orderby'            => 'ID',
			'order'              => 'ASC',
			'posts_per_page'     => 10,
			'paged'              => 1,
			'relationship_query' => array(
				array(
					'related_to_user' => 2,
					'name'            => 'owner',
				),
				array(
					'related_to_post' => 3,
					'name'            => 'basic',
				),
				'relation' => 'AND',
			),
		);

		$query = new \WP_Query( $args );
		$this->assertEquals( array( 1 ), $query->posts );

		$args['relationship_query']['relation'] = 'OR';
		$query                                  = new \WP_Query( $args );
		$this->assertEquals( array( 1, 2, 4, 5 ), $query->posts );
	}

	/**
	 * Tests that orderby only works with one relationship query segment.
	 *
	 * @return void
	 */
	public function test_orderby_only_works_with_one_segment(): void {
		// Register the 'basic' relationship so RelationshipQuery can find it
		$registry = Plugin::instance()->get_registry();
		try {
			$registry->define_post_to_post( 'post', 'post', 'basic' );
		} catch ( \Exception $e ) {
			// Relationship might already exist, that's okay
		}

		$query = new \stdClass();

		$query->query_vars = array(
			'orderby' => 'relationship',
		);

		$query->relationship_query = new RelationshipQuery(
			array(
				array(
					'related_to_post' => 1,
					'name'            => 'basic',
				),
			)
		);

		// The other function does nothing without a where
		$query->relationship_query->where = 'WHERE';

		$orderby = 'default';

		$integration = new WPQueryIntegration();

		$this->assertEquals( 'p2p1.order = 0, p2p1.order ASC', $integration->posts_orderby( $orderby, $query ) );

		$query->relationship_query = new RelationshipQuery(
			array(
				array(
					'related_to_post' => 1,
					'name'            => 'basic',
				),
				array(
					'related_to_post' => 2,
					'name'            => 'basic',
				),
			)
		);

		// The other function does nothing without a where
		$query->relationship_query->where = 'WHERE';

		$this->assertEquals( 'default', $integration->posts_orderby( $orderby, $query ) );
	}

	/**
	 * Tests post-to-post sorting queries.
	 *
	 * @return void
	 */
	public function test_post_to_post_sorting_queries(): void {
		$this->add_post_relations();

		$registry = Plugin::instance()->get_registry();
		try {
			$registry->define_post_to_post( 'post', 'post', 'page1' );
		} catch ( \Exception $e ) {
			// Relationship might already exist, that's okay
		}
		$p2p = $registry->get_post_to_post_relationship( 'post', 'post', 'page1' );
		$p2p->save_sort_data( 31, array( 40, 48, 44, 36 ) );

		$args = array(
			'post_type'          => 'post',
			'fields'             => 'ids',
			'orderby'            => 'relationship',
			'posts_per_page'     => 2,
			'paged'              => 1,
			'relationship_query' => array(
				array(
					'related_to_post' => '31',
					'name'            => 'page1',
				),
			),

		);

		$query = new \WP_Query( $args );
		$this->assertEquals( array( 40, 48 ), $query->posts );

		$args['paged'] = 2;
		$query         = new \WP_Query( $args );
		$this->assertEquals( array( 44, 36 ), $query->posts );
	}

	/**
	 * Tests post-to-user sorting queries.
	 *
	 * @return void
	 */
	public function test_post_to_user_sorting_queries(): void {
		$registry = Plugin::instance()->get_registry();
		try {
			$registry->define_post_to_user( 'post', 'owner' );
		} catch ( \Exception $e ) {
			// Relationship might already exist, that's okay
		}
		$p2u = $registry->get_post_to_user_relationship( 'post', 'owner' );
		$p2u->save_user_to_post_sort_data( 1, array( 2, 4, 1, 3, 5 ) );

		$args = array(
			'post_type'          => 'post',
			'fields'             => 'ids',
			'orderby'            => 'relationship',
			'posts_per_page'     => 2,
			'paged'              => 1,
			'relationship_query' => array(
				array(
					'related_to_user' => '1',
					'name'            => 'owner',
				),
			),

		);

		$query = new \WP_Query( $args );
		$this->assertEquals( array( 2, 4 ), $query->posts );

		$args['paged'] = 2;
		$query         = new \WP_Query( $args );
		$this->assertEquals( array( 1, 3 ), $query->posts );
	}
}
