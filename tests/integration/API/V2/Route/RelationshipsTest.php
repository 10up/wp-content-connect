<?php
/**
 * Tests for V2 Relationships REST API endpoint.
 *
 * @package TenUp\ContentConnect\Tests\Integration\API\V2\Route
 */

namespace TenUp\ContentConnect\Tests\Integration\API\V2\Route;

use TenUp\ContentConnect\Tests\Integration\ContentConnectTestCase;
use function TenUp\ContentConnect\Helpers\get_registry;

/**
 * Test cases for the V2 Relationships REST API endpoint.
 */
class RelationshipsTest extends ContentConnectTestCase {

	/**
	 * Test user ID.
	 *
	 * @var int
	 */
	private $user_id;

	/**
	 * Sets up the test environment.
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		$this->user_id = $this->factory()->user->create(
			array(
				'role' => 'administrator',
			)
		);
		wp_set_current_user( $this->user_id );
	}

	/**
	 * Tests that relationships endpoint requires authentication.
	 *
	 * @return void
	 */
	public function test_requires_authentication() {
		wp_set_current_user( 0 );

		$request  = new \WP_REST_Request( 'GET', '/content-connect/v2/relationships' );
		$response = rest_do_request( $request );

		$this->assertSame( 403, $response->get_status() );
	}

	/**
	 * Tests that authenticated requests succeed.
	 *
	 * @return void
	 */
	public function test_authenticated_requests_succeed() {
		$registry = get_registry();
		$registry->define_post_to_post( 'post', 'post', 'test-p2p' );

		$request  = new \WP_REST_Request( 'GET', '/content-connect/v2/relationships' );
		$response = rest_do_request( $request );

		$this->assertSame( 200, $response->get_status() );
	}

	/**
	 * Tests that endpoint returns post-to-post relationships.
	 *
	 * @return void
	 */
	public function test_returns_post_to_post_relationships() {
		$registry = get_registry();
		$registry->define_post_to_post( 'post', 'post', 'test-p2p' );
		$registry->define_post_to_post( 'post', 'car', 'test-car' );

		$request = new \WP_REST_Request( 'GET', '/content-connect/v2/relationships' );
		$request->set_param( 'rel_type', 'post-to-post' );

		$response = rest_do_request( $request );
		$data     = $response->get_data();

		$this->assertSame( 200, $response->get_status() );
		$this->assertIsArray( $data );

		foreach ( $data as $relationship ) {
			$this->assertSame( 'post-to-post', $relationship['rel_type'] );
			$this->assertArrayHasKey( 'rel_key', $relationship );
			$this->assertArrayHasKey( 'rel_name', $relationship );
			$this->assertArrayHasKey( 'object_type', $relationship );
			$this->assertSame( 'post', $relationship['object_type'] );
			$this->assertArrayHasKey( 'from', $relationship );
			$this->assertArrayHasKey( 'to', $relationship );
		}
	}

	/**
	 * Tests that endpoint returns post-to-user relationships.
	 *
	 * @return void
	 */
	public function test_returns_post_to_user_relationships() {
		$registry = get_registry();
		$registry->define_post_to_user( 'post', 'test-p2u' );

		$request = new \WP_REST_Request( 'GET', '/content-connect/v2/relationships' );
		$request->set_param( 'rel_type', 'post-to-user' );

		$response = rest_do_request( $request );
		$data     = $response->get_data();

		$this->assertSame( 200, $response->get_status() );
		$this->assertIsArray( $data );

		foreach ( $data as $relationship ) {
			$this->assertSame( 'post-to-user', $relationship['rel_type'] );
			$this->assertArrayHasKey( 'rel_key', $relationship );
			$this->assertArrayHasKey( 'rel_name', $relationship );
			$this->assertArrayHasKey( 'object_type', $relationship );
			$this->assertSame( 'user', $relationship['object_type'] );
			$this->assertArrayHasKey( 'labels', $relationship );
			$this->assertArrayHasKey( 'sortable', $relationship );
			$this->assertArrayHasKey( 'enable_ui', $relationship );
		}
	}

	/**
	 * Tests that endpoint filters by relationship key.
	 *
	 * @return void
	 */
	public function test_filters_by_relationship_key() {
		$registry = get_registry();
		$registry->define_post_to_post( 'post', 'post', 'test-key' );

		$rel_key = $registry->get_relationship_key( 'post', 'post', 'test-key' );

		$request = new \WP_REST_Request( 'GET', '/content-connect/v2/relationships' );
		$request->set_param( 'rel_type', 'post-to-post' );
		$request->set_param( 'filter_by', 'key' );
		$request->set_param( 'filter_value', $rel_key );

		$response = rest_do_request( $request );
		$data     = $response->get_data();

		$this->assertSame( 200, $response->get_status() );
		$this->assertIsArray( $data );
		$this->assertArrayHasKey( $rel_key, $data );
	}

	/**
	 * Tests that endpoint filters by post_type.
	 *
	 * @return void
	 */
	public function test_filters_by_post_type() {
		$registry = get_registry();
		$registry->define_post_to_post( 'post', 'post', 'test-post' );
		$registry->define_post_to_post( 'post', 'car', 'test-car' );

		$request = new \WP_REST_Request( 'GET', '/content-connect/v2/relationships' );
		$request->set_param( 'rel_type', 'post-to-post' );
		$request->set_param( 'filter_by', 'post_type' );
		$request->set_param( 'filter_value', 'car' );

		$response = rest_do_request( $request );
		$data     = $response->get_data();

		$this->assertSame( 200, $response->get_status() );
		$this->assertIsArray( $data );

		foreach ( $data as $relationship ) {
			$this->assertContains( 'car', $relationship['to']['object_types'] );
		}
	}

	/**
	 * Tests that endpoint filters by 'from' post type.
	 *
	 * @return void
	 */
	public function test_filters_by_from_post_type() {
		$registry = get_registry();
		$registry->define_post_to_post( 'post', 'post', 'test-from' );
		$registry->define_post_to_post( 'car', 'tire', 'test-car-tire' );

		$request = new \WP_REST_Request( 'GET', '/content-connect/v2/relationships' );
		$request->set_param( 'rel_type', 'post-to-post' );
		$request->set_param( 'filter_by', 'from' );
		$request->set_param( 'filter_value', 'post' );

		$response = rest_do_request( $request );
		$data     = $response->get_data();

		$this->assertSame( 200, $response->get_status() );
		$this->assertIsArray( $data );

		foreach ( $data as $relationship ) {
			$this->assertSame( 'post', $relationship['from']['object_type'] );
		}
	}

	/**
	 * Tests that endpoint filters by 'to' post type.
	 *
	 * @return void
	 */
	public function test_filters_by_to_post_type() {
		$registry = get_registry();
		$registry->define_post_to_post( 'post', 'post', 'test-to' );
		$registry->define_post_to_post( 'post', 'car', 'test-to-car' );

		$request = new \WP_REST_Request( 'GET', '/content-connect/v2/relationships' );
		$request->set_param( 'rel_type', 'post-to-post' );
		$request->set_param( 'filter_by', 'to' );
		$request->set_param( 'filter_value', 'car' );

		$response = rest_do_request( $request );
		$data     = $response->get_data();

		$this->assertSame( 200, $response->get_status() );
		$this->assertIsArray( $data );

		foreach ( $data as $relationship ) {
			$this->assertContains( 'car', $relationship['to']['object_types'] );
		}
	}

	/**
	 * Tests that endpoint returns all relationships when filter_by is 'any'.
	 *
	 * @return void
	 */
	public function test_returns_all_when_filter_by_is_any() {
		$registry = get_registry();
		$registry->define_post_to_post( 'post', 'post', 'test-any-1' );
		$registry->define_post_to_post( 'post', 'car', 'test-any-2' );

		$request = new \WP_REST_Request( 'GET', '/content-connect/v2/relationships' );
		$request->set_param( 'rel_type', 'post-to-post' );
		$request->set_param( 'filter_by', 'any' );

		$response = rest_do_request( $request );
		$data     = $response->get_data();

		$this->assertSame( 200, $response->get_status() );
		$this->assertIsArray( $data );
		$this->assertGreaterThanOrEqual( 2, count( $data ) );
	}

	/**
	 * Tests that endpoint validates rel_type enum.
	 *
	 * @return void
	 */
	public function test_validates_rel_type_enum() {
		$request = new \WP_REST_Request( 'GET', '/content-connect/v2/relationships' );
		$request->set_param( 'rel_type', 'invalid-type' );

		$response = rest_do_request( $request );

		$this->assertSame( 400, $response->get_status() );
	}

	/**
	 * Tests that endpoint validates filter_by enum.
	 *
	 * @return void
	 */
	public function test_validates_filter_by_enum() {
		$request = new \WP_REST_Request( 'GET', '/content-connect/v2/relationships' );
		$request->set_param( 'rel_type', 'post-to-post' );
		$request->set_param( 'filter_by', 'invalid-filter' );

		$response = rest_do_request( $request );

		$this->assertSame( 400, $response->get_status() );
	}

	/**
	 * Tests that endpoint rejects invalid filter_by for post-to-user.
	 *
	 * @return void
	 */
	public function test_rejects_invalid_filter_by_for_post_to_user() {
		$request = new \WP_REST_Request( 'GET', '/content-connect/v2/relationships' );
		$request->set_param( 'rel_type', 'post-to-user' );
		$request->set_param( 'filter_by', 'from' );

		$response = rest_do_request( $request );

		$this->assertSame( 400, $response->get_status() );
	}

	/**
	 * Tests that endpoint rejects 'to' filter_by for post-to-user.
	 *
	 * @return void
	 */
	public function test_rejects_to_filter_by_for_post_to_user() {
		$request = new \WP_REST_Request( 'GET', '/content-connect/v2/relationships' );
		$request->set_param( 'rel_type', 'post-to-user' );
		$request->set_param( 'filter_by', 'to' );

		$response = rest_do_request( $request );

		$this->assertSame( 400, $response->get_status() );
	}

	/**
	 * Tests that post-to-post relationships include labels and sortable flags.
	 *
	 * @return void
	 */
	public function test_post_to_post_includes_labels_and_sortable() {
		$registry = get_registry();
		$registry->define_post_to_post(
			'post',
			'post',
			'test-labels',
			array(
				'from_labels' => array( 'singular' => 'Test From' ),
				'to_labels'   => array( 'singular' => 'Test To' ),
			)
		);

		$request = new \WP_REST_Request( 'GET', '/content-connect/v2/relationships' );
		$request->set_param( 'rel_type', 'post-to-post' );
		$request->set_param( 'filter_by', 'key' );
		$rel_key = $registry->get_relationship_key( 'post', 'post', 'test-labels' );
		$request->set_param( 'filter_value', $rel_key );

		$response = rest_do_request( $request );
		$data     = $response->get_data();

		$this->assertSame( 200, $response->get_status() );
		$this->assertIsArray( $data );
		$this->assertArrayHasKey( $rel_key, $data );

		$relationship = $data[ $rel_key ];
		$this->assertArrayHasKey( 'from', $relationship );
		$this->assertArrayHasKey( 'labels', $relationship['from'] );
		$this->assertArrayHasKey( 'sortable', $relationship['from'] );
		$this->assertArrayHasKey( 'enable_ui', $relationship['from'] );
		$this->assertArrayHasKey( 'to', $relationship );
		$this->assertArrayHasKey( 'labels', $relationship['to'] );
		$this->assertArrayHasKey( 'sortable', $relationship['to'] );
		$this->assertArrayHasKey( 'enable_ui', $relationship['to'] );
	}
}
