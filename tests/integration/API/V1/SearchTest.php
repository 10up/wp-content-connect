<?php
/**
 * Tests for V1 Search REST API endpoint.
 *
 * @package TenUp\ContentConnect\Tests\Integration\API\V1
 */

namespace TenUp\ContentConnect\Tests\Integration\API\V1;

use TenUp\ContentConnect\Tests\Integration\ContentConnectTestCase;
use function TenUp\ContentConnect\Helpers\get_registry;

/**
 * Test cases for the V1 Search REST API endpoint.
 */
class SearchTest extends ContentConnectTestCase {

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
	 * Tests that search endpoint requires authentication.
	 *
	 * @return void
	 */
	public function test_requires_authentication() {
		wp_set_current_user( 0 );

		$request = new \WP_REST_Request( 'POST', '/content-connect/v1/search' );
		$request->set_body_params(
			array(
				'object_type' => 'post',
				'post_type'   => 'post',
				'search'      => 'test',
				'nonce'       => wp_create_nonce( 'content-connect-search' ),
			)
		);

		$response = rest_do_request( $request );

		$this->assertSame( 401, $response->get_status() );
	}

	/**
	 * Tests that search endpoint requires valid nonce.
	 *
	 * @return void
	 */
	public function test_requires_valid_nonce() {
		$request = new \WP_REST_Request( 'POST', '/content-connect/v1/search' );
		$request->set_body_params(
			array(
				'object_type' => 'post',
				'post_type'   => 'post',
				'search'      => 'test',
				'nonce'       => 'invalid-nonce',
			)
		);

		$response = rest_do_request( $request );

		// WordPress REST API returns 403 (Forbidden) when permission_callback returns false
		$this->assertSame( 403, $response->get_status() );
	}

	/**
	 * Tests that search endpoint accepts valid nonce.
	 *
	 * @return void
	 */
	public function test_accepts_valid_nonce() {
		$request = new \WP_REST_Request( 'POST', '/content-connect/v1/search' );
		$request->set_body_params(
			array(
				'object_type' => 'post',
				'post_type'   => 'post',
				'search'      => 'test',
				'nonce'       => wp_create_nonce( 'content-connect-search' ),
			)
		);

		$response = rest_do_request( $request );

		$this->assertNotSame( 401, $response->get_status() );
	}

	/**
	 * Tests that search returns empty array for invalid object_type.
	 *
	 * @return void
	 */
	public function test_returns_empty_for_invalid_object_type() {
		$request = new \WP_REST_Request( 'POST', '/content-connect/v1/search' );
		$request->set_body_params(
			array(
				'object_type' => 'invalid',
				'post_type'   => 'post',
				'search'      => 'test',
				'nonce'       => wp_create_nonce( 'content-connect-search' ),
			)
		);

		$response = rest_do_request( $request );
		$data     = $response->get_data();

		$this->assertSame( 200, $response->get_status() );
		$this->assertIsArray( $data );
		$this->assertEmpty( $data );
	}

	/**
	 * Tests that search returns empty array for invalid post types.
	 *
	 * @return void
	 */
	public function test_returns_empty_for_invalid_post_types() {
		$request = new \WP_REST_Request( 'POST', '/content-connect/v1/search' );
		$request->set_body_params(
			array(
				'object_type' => 'post',
				'post_type'   => 'nonexistent',
				'search'      => 'test',
				'nonce'       => wp_create_nonce( 'content-connect-search' ),
			)
		);

		$response = rest_do_request( $request );
		$data     = $response->get_data();

		$this->assertSame( 200, $response->get_status() );
		$this->assertIsArray( $data );
		$this->assertEmpty( $data );
	}

	/**
	 * Tests that search posts returns results.
	 *
	 * @return void
	 */
	public function test_search_posts_returns_results() {
		$registry = get_registry();
		$registry->define_post_to_post( 'post', 'post', 'test-search' );

		$request = new \WP_REST_Request( 'POST', '/content-connect/v1/search' );
		$request->set_body_params(
			array(
				'object_type'       => 'post',
				'post_type'         => 'post',
				'search'            => 'test',
				'nonce'             => wp_create_nonce( 'content-connect-search' ),
				'relationship_name' => 'test-search',
				'current_post_id'   => 1,
			)
		);

		$response = rest_do_request( $request );
		$data     = $response->get_data();

		$this->assertSame( 200, $response->get_status() );
		$this->assertIsArray( $data );
		$this->assertArrayHasKey( 'data', $data );
		$this->assertArrayHasKey( 'prev_pages', $data );
		$this->assertArrayHasKey( 'more_pages', $data );
		$this->assertIsArray( $data['data'] );
	}

	/**
	 * Tests that search users returns results.
	 *
	 * @return void
	 */
	public function test_search_users_returns_results() {
		$registry = get_registry();
		$registry->define_post_to_user( 'post', 'test-search-user' );

		$request = new \WP_REST_Request( 'POST', '/content-connect/v1/search' );
		$request->set_body_params(
			array(
				'object_type'       => 'user',
				'search'            => 'admin',
				'nonce'             => wp_create_nonce( 'content-connect-search' ),
				'relationship_name' => 'test-search-user',
				'current_post_id'   => 1,
			)
		);

		$response = rest_do_request( $request );
		$data     = $response->get_data();

		$this->assertSame( 200, $response->get_status() );
		$this->assertIsArray( $data );
		$this->assertArrayHasKey( 'data', $data );
		$this->assertArrayHasKey( 'prev_pages', $data );
		$this->assertArrayHasKey( 'more_pages', $data );
		$this->assertIsArray( $data['data'] );
	}

	/**
	 * Tests that search supports pagination.
	 *
	 * @return void
	 */
	public function test_search_supports_pagination() {
		$registry = get_registry();
		$registry->define_post_to_post( 'post', 'post', 'test-pagination' );

		$request = new \WP_REST_Request( 'POST', '/content-connect/v1/search' );
		$request->set_body_params(
			array(
				'object_type'       => 'post',
				'post_type'         => 'post',
				'search'            => 'test',
				'paged'             => 2,
				'nonce'             => wp_create_nonce( 'content-connect-search' ),
				'relationship_name' => 'test-pagination',
				'current_post_id'   => 1,
			)
		);

		$response = rest_do_request( $request );
		$data     = $response->get_data();

		$this->assertSame( 200, $response->get_status() );
		$this->assertIsArray( $data );
		$this->assertArrayHasKey( 'data', $data );
	}

	/**
	 * Tests that search returns empty results when no matches found.
	 *
	 * @return void
	 */
	public function test_search_returns_empty_when_no_matches() {
		$registry = get_registry();
		$registry->define_post_to_post( 'post', 'post', 'test-empty' );

		$request = new \WP_REST_Request( 'POST', '/content-connect/v1/search' );
		$request->set_body_params(
			array(
				'object_type'       => 'post',
				'post_type'         => 'post',
				'search'            => 'nonexistentsearchterm12345',
				'nonce'             => wp_create_nonce( 'content-connect-search' ),
				'relationship_name' => 'test-empty',
				'current_post_id'   => 1,
			)
		);

		$response = rest_do_request( $request );
		$data     = $response->get_data();

		$this->assertSame( 200, $response->get_status() );
		$this->assertIsArray( $data );
		$this->assertArrayHasKey( 'data', $data );
		$this->assertIsArray( $data['data'] );
		$this->assertEmpty( $data['data'] );
	}

	/**
	 * Tests that search supports multiple post types.
	 *
	 * @return void
	 */
	public function test_search_supports_multiple_post_types() {
		$registry = get_registry();
		$registry->define_post_to_post( 'post', array( 'post', 'car' ), 'test-multi' );

		$request = new \WP_REST_Request( 'POST', '/content-connect/v1/search' );
		$request->set_body_params(
			array(
				'object_type'       => 'post',
				'post_type'         => array( 'post', 'car' ),
				'search'            => 'test',
				'nonce'             => wp_create_nonce( 'content-connect-search' ),
				'relationship_name' => 'test-multi',
				'current_post_id'   => 1,
			)
		);

		$response = rest_do_request( $request );
		$data     = $response->get_data();

		$this->assertSame( 200, $response->get_status() );
		$this->assertIsArray( $data );
		$this->assertArrayHasKey( 'data', $data );
	}

	/**
	 * Tests that search filters by relationship name.
	 *
	 * @return void
	 */
	public function test_search_filters_by_relationship_name() {
		$registry = get_registry();
		$registry->define_post_to_post( 'post', 'post', 'test-filter' );

		$request = new \WP_REST_Request( 'POST', '/content-connect/v1/search' );
		$request->set_body_params(
			array(
				'object_type'       => 'post',
				'post_type'         => 'post',
				'search'            => 'test',
				'nonce'             => wp_create_nonce( 'content-connect-search' ),
				'relationship_name' => 'test-filter',
				'current_post_id'   => 1,
			)
		);

		$response = rest_do_request( $request );
		$data     = $response->get_data();

		$this->assertSame( 200, $response->get_status() );
		$this->assertIsArray( $data );
		$this->assertArrayHasKey( 'data', $data );
	}

	/**
	 * Tests that search filters by current post ID.
	 *
	 * @return void
	 */
	public function test_search_filters_by_current_post_id() {
		$registry = get_registry();
		$registry->define_post_to_post( 'post', 'post', 'test-post-id' );

		$request = new \WP_REST_Request( 'POST', '/content-connect/v1/search' );
		$request->set_body_params(
			array(
				'object_type'       => 'post',
				'post_type'         => 'post',
				'search'            => 'test',
				'nonce'             => wp_create_nonce( 'content-connect-search' ),
				'relationship_name' => 'test-post-id',
				'current_post_id'   => 1,
			)
		);

		$response = rest_do_request( $request );
		$data     = $response->get_data();

		$this->assertSame( 200, $response->get_status() );
		$this->assertIsArray( $data );
		$this->assertArrayHasKey( 'data', $data );
	}
}
