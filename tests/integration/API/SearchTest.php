<?php

namespace TenUp\ContentConnect\Tests\Integration\API;

use TenUp\ContentConnect\API\Search;
use TenUp\ContentConnect\Plugin;
use TenUp\ContentConnect\Registry;
use TenUp\ContentConnect\Tests\Integration\ContentConnectTestCase;

/**
 * Covers the REST search endpoint: registration, localization, permission checks,
 * request processing/sanitization, and the post/user search + filter surface.
 */
class SearchTest extends ContentConnectTestCase {

	protected $created_posts = array();

	protected $admin_id = 0;

	public function setUp(): void {
		$plugin           = Plugin::instance();
		$plugin->registry = new Registry();
		$plugin->registry->setup();

		parent::setUp();
	}

	public function tearDown(): void {
		foreach ( $this->created_posts as $post_id ) {
			wp_delete_post( $post_id, true );
		}
		$this->created_posts = array();

		if ( $this->admin_id ) {
			wp_delete_user( $this->admin_id );
			$this->admin_id = 0;
		}

		wp_set_current_user( 0 );

		parent::tearDown();
	}

	protected function make_post( $title, $post_type = 'post' ) {
		$post_id               = wp_insert_post(
			array(
				'post_title'  => $title,
				'post_status' => 'publish',
				'post_type'   => $post_type,
			)
		);
		$this->created_posts[] = $post_id;

		return $post_id;
	}

	protected function login_admin() {
		$this->admin_id = wp_insert_user(
			array(
				'user_login' => 'cc_search_admin',
				'user_pass'  => 'password',
				'role'       => 'administrator',
			)
		);
		wp_set_current_user( $this->admin_id );

		return $this->admin_id;
	}

	protected function request( array $params ) {
		$request = new \WP_REST_Request( 'POST', '/content-connect/v1/search' );

		foreach ( $params as $key => $value ) {
			$request->set_param( $key, $value );
		}

		return $request;
	}

	public function test_endpoint_is_registered() {
		$search = new Search();

		// Routes must be registered on rest_api_init; register within that action.
		add_action( 'rest_api_init', array( $search, 'register_endpoint' ) );
		do_action( 'rest_api_init' );

		$routes = rest_get_server()->get_routes();

		remove_action( 'rest_api_init', array( $search, 'register_endpoint' ) );

		$this->assertArrayHasKey( '/content-connect/v1/search', $routes );
	}

	public function test_localize_endpoints_adds_url_and_nonce() {
		$search = new Search();

		$data = $search->localize_endpoints( array( 'endpoints' => array(), 'nonces' => array() ) );

		$this->assertArrayHasKey( 'search', $data['endpoints'] );
		$this->assertStringContainsString( 'content-connect/v1/search', $data['endpoints']['search'] );
		$this->assertArrayHasKey( 'search', $data['nonces'] );
		$this->assertNotEmpty( $data['nonces']['search'] );
	}

	public function test_check_permission_rejects_logged_out_user() {
		wp_set_current_user( 0 );

		$search  = new Search();
		$request = $this->request( array( 'nonce' => wp_create_nonce( 'content-connect-search' ) ) );

		$this->assertFalse( $search->check_permission( $request ) );
	}

	public function test_check_permission_rejects_invalid_nonce() {
		$this->login_admin();

		$search  = new Search();
		$request = $this->request( array( 'nonce' => 'bogus-nonce' ) );

		$this->assertFalse( $search->check_permission( $request ) );
	}

	public function test_check_permission_allows_valid_nonce() {
		$this->login_admin();

		$search  = new Search();
		$request = $this->request( array( 'nonce' => wp_create_nonce( 'content-connect-search' ) ) );

		$this->assertTrue( $search->check_permission( $request ) );
	}

	public function test_process_search_invalid_object_type_returns_empty() {
		$search  = new Search();
		$request = $this->request( array( 'object_type' => 'widget' ) );

		$this->assertSame( array(), $search->process_search( $request ) );
	}

	public function test_process_search_no_valid_post_types_returns_empty() {
		$search  = new Search();
		$request = $this->request(
			array(
				'object_type' => 'post',
				'post_type'   => array( 'does_not_exist' ),
			)
		);

		$this->assertSame( array(), $search->process_search( $request ) );
	}

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
		$request = $this->request(
			array(
				'object_type' => 'post',
				'post_type'   => array( 'post' ),
				'search'      => '  <b>hey</b>  ',
			)
		);
		$search->process_search( $request );

		remove_all_filters( 'tenup_content_connect_search_posts_query_args' );

		$this->assertNotNull( $captured );
		$this->assertSame( 'hey', $captured['s'] );
	}

	public function test_search_posts_normalizes_results_and_applies_filter() {
		$post_id = $this->make_post( 'ContentConnectSearchNeedle' );

		$filter_ran = false;
		add_filter(
			'tenup_content_connect_final_post',
			function ( $final_post ) use ( &$filter_ran ) {
				$filter_ran        = true;
				$final_post['flag'] = 'yes';
				return $final_post;
			}
		);

		$search  = new Search();
		$results = $search->search_posts( 'ContentConnectSearchNeedle', array( 'post' ), array( 'current_post_id' => 1, 'relationship_name' => '' ) );

		remove_all_filters( 'tenup_content_connect_final_post' );

		$ids = wp_list_pluck( $results['data'], 'ID' );
		$this->assertContains( $post_id, $ids );
		$this->assertTrue( $filter_ran );
		$this->assertSame( 'yes', $results['data'][0]['flag'] );
	}

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

		$page1 = $search->search_posts( 'PaginateNeedle', array( 'post' ), array( 'paged' => 1, 'current_post_id' => 1, 'relationship_name' => '' ) );
		$page2 = $search->search_posts( 'PaginateNeedle', array( 'post' ), array( 'paged' => 2, 'current_post_id' => 1, 'relationship_name' => '' ) );

		remove_all_filters( 'tenup_content_connect_search_posts_query_args' );

		$this->assertFalse( $page1['prev_pages'] );
		$this->assertTrue( $page1['more_pages'] );
		$this->assertTrue( $page2['prev_pages'] );
		$this->assertFalse( $page2['more_pages'] );
	}

	public function test_search_users_normalizes_results_and_applies_filter() {
		$filter_ran = false;
		add_filter(
			'tenup_content_connect_final_user',
			function ( $final_user ) use ( &$filter_ran ) {
				$filter_ran = true;
				return $final_user;
			}
		);

		$search  = new Search();
		// Fixture user 1 has display_name "1"; "*1*" matches users 1 and 10.
		$results = $search->search_users( '1', array( 'current_post_id' => 1, 'relationship_name' => '' ) );

		remove_all_filters( 'tenup_content_connect_final_user' );

		$this->assertArrayHasKey( 'data', $results );
		$this->assertNotEmpty( $results['data'] );
		$this->assertArrayHasKey( 'ID', $results['data'][0] );
		$this->assertArrayHasKey( 'name', $results['data'][0] );
		$this->assertTrue( $filter_ran );
	}

}
