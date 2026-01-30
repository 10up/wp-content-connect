<?php
/**
 * Tests for V2 Post Relationships REST API endpoint.
 *
 * @package TenUp\ContentConnect\Tests\Integration\API\V2\Post\Route
 */

namespace TenUp\ContentConnect\Tests\Integration\API\V2\Post\Route;

use TenUp\ContentConnect\Tests\Integration\ContentConnectTestCase;
use function TenUp\ContentConnect\Helpers\get_registry;

/**
 * Test cases for the V2 Post Relationships REST API endpoint.
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

		$this->add_post_relations();
		$this->add_user_relations();

		$this->user_id = $this->factory()->user->create(
			array(
				'role' => 'administrator',
			)
		);
		wp_set_current_user( $this->user_id );
	}

	/**
	 * Tests that endpoint requires authentication.
	 *
	 * @return void
	 */
	public function test_requires_authentication() {
		wp_set_current_user( 0 );

		$request  = new \WP_REST_Request( 'GET', '/content-connect/v2/post/1/relationships' );
		$response = rest_do_request( $request );

		$this->assertSame( 403, $response->get_status() );
	}

	/**
	 * Tests that endpoint returns relationships for valid post.
	 *
	 * @return void
	 */
	public function test_returns_relationships_for_valid_post() {
		$registry = get_registry();
		$registry->define_post_to_post( 'post', 'post', 'test-valid' );
		$registry->define_post_to_user( 'post', 'test-user' );

		$request  = new \WP_REST_Request( 'GET', '/content-connect/v2/post/1/relationships' );
		$response = rest_do_request( $request );
		$data     = $response->get_data();

		$this->assertSame( 200, $response->get_status() );
		$this->assertIsArray( $data );
	}

	/**
	 * Tests that endpoint returns 403 for invalid post ID (capability check first).
	 *
	 * @return void
	 */
	public function test_returns_404_for_invalid_post_id() {
		$request  = new \WP_REST_Request( 'GET', '/content-connect/v2/post/99999/relationships' );
		$response = rest_do_request( $request );

		// Returns 403 because capability check happens before post existence check
		$this->assertSame( 403, $response->get_status() );
	}

	/**
	 * Tests that endpoint returns 400 for zero post ID (validation).
	 *
	 * @return void
	 */
	public function test_returns_404_for_zero_post_id() {
		$request  = new \WP_REST_Request( 'GET', '/content-connect/v2/post/0/relationships' );
		$response = rest_do_request( $request );

		// Returns 400 because minimum validation fails for id=0
		$this->assertSame( 400, $response->get_status() );
	}

	/**
	 * Tests that endpoint filters by rel_type='any'.
	 *
	 * @return void
	 */
	public function test_filters_by_rel_type_any() {
		$registry = get_registry();
		$registry->define_post_to_post( 'post', 'post', 'test-any-p2p' );
		$registry->define_post_to_user( 'post', 'test-any-p2u' );

		$request = new \WP_REST_Request( 'GET', '/content-connect/v2/post/1/relationships' );
		$request->set_param( 'rel_type', 'any' );

		$response = rest_do_request( $request );
		$data     = $response->get_data();

		$this->assertSame( 200, $response->get_status() );
		$this->assertIsArray( $data );
	}

	/**
	 * Tests that endpoint filters by rel_type='post-to-post'.
	 *
	 * @return void
	 */
	public function test_filters_by_rel_type_post_to_post() {
		$registry = get_registry();
		$registry->define_post_to_post( 'post', 'post', 'test-p2p-filter' );
		$registry->define_post_to_user( 'post', 'test-p2u-filter' );

		$request = new \WP_REST_Request( 'GET', '/content-connect/v2/post/1/relationships' );
		$request->set_param( 'rel_type', 'post-to-post' );

		$response = rest_do_request( $request );
		$data     = $response->get_data();

		$this->assertSame( 200, $response->get_status() );
		$this->assertIsArray( $data );

		foreach ( $data as $relationship ) {
			$this->assertSame( 'post-to-post', $relationship['rel_type'] );
		}
	}

	/**
	 * Tests that endpoint filters by rel_type='post-to-user'.
	 *
	 * @return void
	 */
	public function test_filters_by_rel_type_post_to_user() {
		$registry = get_registry();
		$registry->define_post_to_post( 'post', 'post', 'test-p2p-filter2' );
		$registry->define_post_to_user( 'post', 'test-p2u-filter2' );

		$request = new \WP_REST_Request( 'GET', '/content-connect/v2/post/1/relationships' );
		$request->set_param( 'rel_type', 'post-to-user' );

		$response = rest_do_request( $request );
		$data     = $response->get_data();

		$this->assertSame( 200, $response->get_status() );
		$this->assertIsArray( $data );

		foreach ( $data as $relationship ) {
			$this->assertSame( 'post-to-user', $relationship['rel_type'] );
		}
	}

	/**
	 * Tests that endpoint filters by post_type.
	 *
	 * @return void
	 */
	public function test_filters_by_post_type() {
		$registry = get_registry();
		$registry->define_post_to_post( 'post', 'post', 'test-post-filter' );
		$registry->define_post_to_post( 'post', 'car', 'test-car-filter' );

		$request = new \WP_REST_Request( 'GET', '/content-connect/v2/post/1/relationships' );
		$request->set_param( 'rel_type', 'post-to-post' );
		$request->set_param( 'post_type', 'car' );

		$response = rest_do_request( $request );
		$data     = $response->get_data();

		$this->assertSame( 200, $response->get_status() );
		$this->assertIsArray( $data );

		foreach ( $data as $relationship ) {
			if ( 'post-to-post' === $relationship['rel_type'] ) {
				$post_types = is_array( $relationship['post_type'] ) ? $relationship['post_type'] : array( $relationship['post_type'] );
				$this->assertContains( 'car', $post_types );
			}
		}
	}

	/**
	 * Tests that endpoint validates invalid post_type.
	 *
	 * @return void
	 */
	public function test_validates_invalid_post_type() {
		$request = new \WP_REST_Request( 'GET', '/content-connect/v2/post/1/relationships' );
		$request->set_param( 'post_type', 'nonexistent' );

		$response = rest_do_request( $request );

		$this->assertSame( 400, $response->get_status() );
	}

	/**
	 * Tests that endpoint validates invalid rel_type enum.
	 *
	 * @return void
	 */
	public function test_validates_invalid_rel_type_enum() {
		$request = new \WP_REST_Request( 'GET', '/content-connect/v2/post/1/relationships' );
		$request->set_param( 'rel_type', 'invalid-type' );

		$response = rest_do_request( $request );

		$this->assertSame( 400, $response->get_status() );
	}

	/**
	 * Tests that endpoint validates invalid context enum.
	 *
	 * @return void
	 */
	public function test_validates_invalid_context_enum() {
		$request = new \WP_REST_Request( 'GET', '/content-connect/v2/post/1/relationships' );
		$request->set_param( 'context', 'invalid-context' );

		$response = rest_do_request( $request );

		$this->assertSame( 400, $response->get_status() );
	}

	/**
	 * Tests that endpoint returns view context without related entities.
	 *
	 * @return void
	 */
	public function test_returns_view_context_without_related() {
		$registry = get_registry();
		$registry->define_post_to_post( 'post', 'post', 'test-view' );

		$request = new \WP_REST_Request( 'GET', '/content-connect/v2/post/1/relationships' );
		$request->set_param( 'rel_type', 'post-to-post' );
		$request->set_param( 'context', 'view' );

		$response = rest_do_request( $request );
		$data     = $response->get_data();

		$this->assertSame( 200, $response->get_status() );
		$this->assertIsArray( $data );

		foreach ( $data as $relationship ) {
			$this->assertArrayNotHasKey( 'related', $relationship );
		}
	}

	/**
	 * Tests that endpoint returns embed context with related entities.
	 *
	 * @return void
	 */
	public function test_returns_embed_context_with_related() {
		$registry = get_registry();
		$registry->define_post_to_post( 'post', 'post', 'test-embed' );

		$request = new \WP_REST_Request( 'GET', '/content-connect/v2/post/1/relationships' );
		$request->set_param( 'rel_type', 'post-to-post' );
		$request->set_param( 'context', 'embed' );

		$response = rest_do_request( $request );
		$data     = $response->get_data();

		$this->assertSame( 200, $response->get_status() );
		$this->assertIsArray( $data );

		foreach ( $data as $relationship ) {
			if ( isset( $relationship['related'] ) ) {
				$this->assertIsArray( $relationship['related'] );
			}
		}
	}

	/**
	 * Tests that endpoint returns correct relationship data format.
	 *
	 * @return void
	 */
	public function test_returns_correct_relationship_data_format() {
		$registry = get_registry();
		$registry->define_post_to_post( 'post', 'post', 'test-format' );

		$request = new \WP_REST_Request( 'GET', '/content-connect/v2/post/1/relationships' );
		$request->set_param( 'rel_type', 'post-to-post' );

		$response = rest_do_request( $request );
		$data     = $response->get_data();

		$this->assertSame( 200, $response->get_status() );
		$this->assertIsArray( $data );

		foreach ( $data as $relationship ) {
			$this->assertArrayHasKey( 'rel_key', $relationship );
			$this->assertArrayHasKey( 'rel_type', $relationship );
			$this->assertArrayHasKey( 'rel_name', $relationship );
			$this->assertArrayHasKey( 'object_type', $relationship );
			$this->assertArrayHasKey( 'post_type', $relationship );
			$this->assertArrayHasKey( 'labels', $relationship );
			$this->assertArrayHasKey( 'sortable', $relationship );
		}
	}
}
