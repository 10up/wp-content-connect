<?php
/**
 * Tests for QueryBlockIntegration class.
 *
 * @package TenUp\ContentConnect\Tests\QueryIntegration
 */

namespace TenUp\ContentConnect\Tests\QueryIntegration;

use TenUp\ContentConnect\QueryIntegration\QueryBlockIntegration;
use TenUp\ContentConnect\Tests\ContentConnectTestCase;

/**
 * Test cases for QueryBlockIntegration.
 *
 * These cover the glue layer that maps Query Loop block attributes and REST
 * request params onto WP_Query arguments. The SQL those arguments generate is
 * exercised separately in RelationshipQueryTest and WP_Query_IntegrationTest.
 */
class QueryBlockIntegrationTest extends ContentConnectTestCase {

	/**
	 * The integration instance under test.
	 *
	 * @var QueryBlockIntegration
	 */
	protected $integration;

	/**
	 * Sets up the test environment.
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		$this->integration = new QueryBlockIntegration();

		// rest_post_query() only applies relationship params for users who can edit posts.
		$admin_id = $this->factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );
	}

	/**
	 * Cleans up the test environment.
	 *
	 * @return void
	 */
	public function tearDown(): void {
		wp_set_current_user( 0 );

		parent::tearDown();
	}

	/**
	 * setup() should register the REST bootstrap action and the query loop filter.
	 *
	 * @return void
	 */
	public function test_setup_registers_hooks(): void {
		$this->integration->setup();

		$this->assertNotFalse(
			has_action( 'rest_api_init', array( $this->integration, 'rest_api_init' ) )
		);
		$this->assertNotFalse(
			has_filter( 'query_loop_block_query_vars', array( $this->integration, 'modify_query_loop_query' ) )
		);
	}

	/**
	 * rest_api_init() should register the query filter for public post types and skip attachments.
	 *
	 * @return void
	 */
	public function test_rest_api_init_registers_supported_post_types_and_skips_attachment(): void {
		$this->integration->rest_api_init();

		$this->assertNotFalse(
			has_filter( 'rest_post_query', array( $this->integration, 'rest_post_query' ) ),
			'Expected the filter to be registered for the "post" post type.'
		);
		$this->assertFalse(
			has_filter( 'rest_attachment_query', array( $this->integration, 'rest_post_query' ) ),
			'Attachments should be excluded.'
		);
	}

	/**
	 * A relationshipQuery param that is an array should populate the relationship_query arg.
	 *
	 * @return void
	 */
	public function test_rest_post_query_applies_relationship_query(): void {
		$relationship_query = array(
			array(
				'name'            => 'post_to_post_basic',
				'related_to_post' => 5,
			),
		);

		$request = $this->get_request( array( 'relationshipQuery' => $relationship_query ) );

		$args = $this->integration->rest_post_query( array(), $request );

		$this->assertSame( $relationship_query, $args['relationship_query'] );
	}

	/**
	 * A non-array relationshipQuery param should be ignored.
	 *
	 * @return void
	 */
	public function test_rest_post_query_ignores_non_array_relationship_query(): void {
		$request = $this->get_request( array( 'relationshipQuery' => 'not-an-array' ) );

		$args = $this->integration->rest_post_query( array(), $request );

		$this->assertArrayNotHasKey( 'relationship_query', $args );
	}

	/**
	 * A truthy orderByRelationship param should set orderby to "relationship".
	 *
	 * @return void
	 */
	public function test_rest_post_query_sets_orderby_when_flag_is_true(): void {
		$request = $this->get_request( array( 'orderByRelationship' => true ) );

		$args = $this->integration->rest_post_query( array(), $request );

		$this->assertSame( 'relationship', $args['orderby'] );
	}

	/**
	 * Without the orderByRelationship param, an existing orderby should be left untouched.
	 *
	 * @return void
	 */
	public function test_rest_post_query_leaves_orderby_when_flag_absent(): void {
		$request = $this->get_request( array() );

		$args = $this->integration->rest_post_query( array( 'orderby' => 'date' ), $request );

		$this->assertSame( 'date', $args['orderby'] );
	}

	/**
	 * Relationship params should be ignored for anonymous visitors.
	 *
	 * @return void
	 */
	public function test_rest_post_query_ignores_relationship_params_for_anonymous_users(): void {
		wp_set_current_user( 0 );

		$request = $this->get_request(
			array(
				'relationshipQuery'   => array(
					array(
						'name'            => 'post_to_post_basic',
						'related_to_post' => 5,
					),
				),
				'orderByRelationship' => true,
			)
		);

		$args = $this->integration->rest_post_query( array( 'post_type' => 'post' ), $request );

		$this->assertArrayNotHasKey( 'relationship_query', $args );
		$this->assertArrayNotHasKey( 'orderby', $args );
	}

	/**
	 * Relationship params should be ignored for users who cannot edit posts of the queried type.
	 *
	 * @return void
	 */
	public function test_rest_post_query_ignores_relationship_params_for_users_who_cannot_edit_posts(): void {
		$subscriber_id = $this->factory()->user->create( array( 'role' => 'subscriber' ) );
		wp_set_current_user( $subscriber_id );

		$request = $this->get_request(
			array(
				'relationshipQuery' => array(
					array(
						'name'            => 'post_to_post_basic',
						'related_to_post' => 5,
					),
				),
			)
		);

		$args = $this->integration->rest_post_query( array( 'post_type' => 'post' ), $request );

		$this->assertArrayNotHasKey( 'relationship_query', $args );
	}

	/**
	 * The tenup_content_connect_rest_relationship_query_allowed filter can allow anonymous visitors.
	 *
	 * @return void
	 */
	public function test_relationship_query_allowed_filter_can_allow_anonymous_users(): void {
		wp_set_current_user( 0 );
		add_filter( 'tenup_content_connect_rest_relationship_query_allowed', '__return_true' );

		$relationship_query = array(
			array(
				'name'            => 'post_to_post_basic',
				'related_to_post' => 5,
			),
		);

		$request = $this->get_request( array( 'relationshipQuery' => $relationship_query ) );

		$args = $this->integration->rest_post_query( array( 'post_type' => 'post' ), $request );

		remove_filter( 'tenup_content_connect_rest_relationship_query_allowed', '__return_true' );

		$this->assertSame( $relationship_query, $args['relationship_query'] );
	}

	/**
	 * The filter should not run when the request carries no relationship params.
	 *
	 * @return void
	 */
	public function test_relationship_query_allowed_filter_skipped_without_relationship_params(): void {
		$filter = new \MockAction();
		add_filter( 'tenup_content_connect_rest_relationship_query_allowed', array( $filter, 'filter' ) );

		$this->integration->rest_post_query( array( 'post_type' => 'post' ), $this->get_request( array() ) );

		remove_filter( 'tenup_content_connect_rest_relationship_query_allowed', array( $filter, 'filter' ) );

		$this->assertSame( 0, $filter->get_call_count() );
	}

	/**
	 * The block's own query context should drive the relationship_query arg.
	 *
	 * @return void
	 */
	public function test_modify_query_loop_query_applies_from_block_context(): void {
		$relationship_query = array(
			array(
				'name'            => 'post_to_post_basic',
				'related_to_post' => 5,
			),
		);

		$block = $this->get_block(
			array(
				'relationshipQuery'   => $relationship_query,
				'orderByRelationship' => true,
			)
		);

		$args = $this->integration->modify_query_loop_query( array(), $block );

		$this->assertSame( $relationship_query, $args['relationship_query'] );
		$this->assertSame( 'relationship', $args['orderby'] );
	}

	/**
	 * A non-array relationshipQuery in block context should be ignored.
	 *
	 * @return void
	 */
	public function test_modify_query_loop_query_ignores_non_array_relationship_query(): void {
		$block = $this->get_block( array( 'relationshipQuery' => 'not-an-array' ) );

		$args = $this->integration->modify_query_loop_query( array(), $block );

		$this->assertArrayNotHasKey( 'relationship_query', $args );
	}

	/**
	 * A block without a query context should return the args unchanged.
	 *
	 * @return void
	 */
	public function test_modify_query_loop_query_without_context_returns_args_unchanged(): void {
		$block = (object) array( 'context' => array() );

		$original = array( 'post_type' => 'post' );
		$args     = $this->integration->modify_query_loop_query( $original, $block );

		$this->assertSame( $original, $args );
	}

	/**
	 * Builds a WP_REST_Request populated with the given params.
	 *
	 * @param array $params The request params.
	 * @return \WP_REST_Request
	 */
	private function get_request( $params ) {
		$request = new \WP_REST_Request();

		foreach ( $params as $key => $value ) {
			$request->set_param( $key, $value );
		}

		return $request;
	}

	/**
	 * Builds a lightweight stand-in for the WP_Block passed to the
	 * query_loop_block_query_vars filter, exposing only the query context the
	 * integration reads.
	 *
	 * @param array $query_context The value stored under context['query'].
	 * @return object
	 */
	private function get_block( $query_context ) {
		return (object) array(
			'context' => array(
				'query' => $query_context,
			),
		);
	}
}
