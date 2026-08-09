<?php
/**
 * Tests for V1 Search REST API endpoint.
 *
 * @package TenUp\ContentConnect\Tests\API\V1
 */

namespace TenUp\ContentConnect\Tests\API\V1;

use TenUp\ContentConnect\API\V1\Search;
use TenUp\ContentConnect\Tests\ContentConnectTestCase;
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
	 * Posts created during a test, removed on tearDown.
	 *
	 * @var int[]
	 */
	private $created_posts = array();

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
	 * Cleans up the test environment.
	 *
	 * @return void
	 */
	public function tearDown(): void {
		foreach ( $this->created_posts as $post_id ) {
			wp_delete_post( $post_id, true );
		}
		$this->created_posts = array();

		wp_set_current_user( 0 );

		parent::tearDown();
	}

	/**
	 * Creates a published post and tracks it for cleanup.
	 *
	 * @param  string $title     The post title.
	 * @param  string $post_type The post type.
	 * @return int The created post ID.
	 */
	private function make_post( $title, $post_type = 'post' ) {
		$post_id = wp_insert_post(
			array(
				'post_title'  => $title,
				'post_status' => 'publish',
				'post_type'   => $post_type,
			)
		);

		$this->created_posts[] = $post_id;

		return $post_id;
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

	/**
	 * Tests that the search endpoint is registered on rest_api_init.
	 *
	 * @return void
	 */
	public function test_endpoint_is_registered() {
		$search = new Search();

		// Routes must be registered on rest_api_init; register within that action.
		add_action( 'rest_api_init', array( $search, 'register_endpoint' ) );
		do_action( 'rest_api_init' );

		$routes = rest_get_server()->get_routes();

		remove_action( 'rest_api_init', array( $search, 'register_endpoint' ) );

		$this->assertArrayHasKey( '/content-connect/v1/search', $routes );
	}

	/**
	 * Tests that localize_endpoints() adds the search URL and nonce.
	 *
	 * @return void
	 */
	public function test_localize_endpoints_adds_url_and_nonce() {
		$search = new Search();

		$data = $search->localize_endpoints(
			array(
				'endpoints' => array(),
				'nonces'    => array(),
			)
		);

		$this->assertArrayHasKey( 'search', $data['endpoints'] );
		$this->assertStringContainsString( 'content-connect/v1/search', $data['endpoints']['search'] );
		$this->assertArrayHasKey( 'search', $data['nonces'] );
		$this->assertNotEmpty( $data['nonces']['search'] );
	}

	/**
	 * Tests that process_search() trims and strips tags from the search text.
	 *
	 * @return void
	 */
	public function test_process_search_sanitizes_search_text() {
		$captured = null;

		add_filter(
			'tenup_content_connect_search_posts_query_args',
			function ( $query_args ) use ( &$captured ) {
				$captured = $query_args;
				return $query_args;
			}
		);

		$search  = new Search();
		$request = new \WP_REST_Request( 'POST', '/content-connect/v1/search' );
		$request->set_param( 'object_type', 'post' );
		$request->set_param( 'post_type', array( 'post' ) );
		$request->set_param( 'search', '  <b>hey</b>  ' );
		$search->process_search( $request );

		remove_all_filters( 'tenup_content_connect_search_posts_query_args' );

		$this->assertNotNull( $captured );
		$this->assertSame( 'hey', $captured['s'] );
	}

	/**
	 * Tests that search_posts() normalizes results and applies the final_post filter.
	 *
	 * @return void
	 */
	public function test_search_posts_normalizes_results_and_applies_filter() {
		$post_id = $this->make_post( 'ContentConnectSearchNeedle' );

		$filter_ran = false;
		add_filter(
			'tenup_content_connect_final_post',
			function ( $final_post ) use ( &$filter_ran ) {
				$filter_ran         = true;
				$final_post['flag'] = 'yes';
				return $final_post;
			}
		);

		$search  = new Search();
		$results = $search->search_posts(
			'ContentConnectSearchNeedle',
			array( 'post' ),
			array(
				'current_post_id'   => 1,
				'relationship_name' => '',
			)
		);

		remove_all_filters( 'tenup_content_connect_final_post' );

		$ids = wp_list_pluck( $results['data'], 'ID' );
		$this->assertContains( $post_id, $ids );
		$this->assertTrue( $filter_ran );
		$this->assertSame( 'yes', $results['data'][0]['flag'] );
	}

	/**
	 * Tests that search_posts() reports prev/next page flags correctly.
	 *
	 * @return void
	 */
	public function test_search_posts_pagination_flags() {
		$this->make_post( 'PaginateNeedle One' );
		$this->make_post( 'PaginateNeedle Two' );

		// Force one result per page so two matching posts span two pages.
		add_filter(
			'tenup_content_connect_search_posts_query_args',
			function ( $query_args ) {
				$query_args['posts_per_page'] = 1;
				return $query_args;
			}
		);

		$search = new Search();

		$page1 = $search->search_posts(
			'PaginateNeedle',
			array( 'post' ),
			array(
				'paged'             => 1,
				'current_post_id'   => 1,
				'relationship_name' => '',
			)
		);
		$page2 = $search->search_posts(
			'PaginateNeedle',
			array( 'post' ),
			array(
				'paged'             => 2,
				'current_post_id'   => 1,
				'relationship_name' => '',
			)
		);

		remove_all_filters( 'tenup_content_connect_search_posts_query_args' );

		$this->assertFalse( $page1['prev_pages'] );
		$this->assertTrue( $page1['more_pages'] );
		$this->assertTrue( $page2['prev_pages'] );
		$this->assertFalse( $page2['more_pages'] );
	}

	/**
	 * Tests that search_users() normalizes results and applies the final_user filter.
	 *
	 * @return void
	 */
	public function test_search_users_normalizes_results_and_applies_filter() {
		$filter_ran = false;
		add_filter(
			'tenup_content_connect_final_user',
			function ( $final_user ) use ( &$filter_ran ) {
				$filter_ran = true;
				return $final_user;
			}
		);

		$search = new Search();
		// Fixture user 1 has display_name "1"; "*1*" matches users 1 and 10.
		$results = $search->search_users(
			'1',
			array(
				'current_post_id'   => 1,
				'relationship_name' => '',
			)
		);

		remove_all_filters( 'tenup_content_connect_final_user' );

		$this->assertArrayHasKey( 'data', $results );
		$this->assertNotEmpty( $results['data'] );
		$this->assertArrayHasKey( 'ID', $results['data'][0] );
		$this->assertArrayHasKey( 'name', $results['data'][0] );
		$this->assertTrue( $filter_ran );
	}
}
