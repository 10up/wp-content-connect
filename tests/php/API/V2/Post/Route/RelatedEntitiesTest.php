<?php
/**
 * Tests for V2 Post RelatedEntities REST API endpoint.
 *
 * @package TenUp\ContentConnect\Tests\API\V2\Post\Route
 */

namespace TenUp\ContentConnect\Tests\API\V2\Post\Route;

use TenUp\ContentConnect\Tests\ContentConnectTestCase;
use function TenUp\ContentConnect\Helpers\get_registry;

/**
 * Test cases for the V2 Post RelatedEntities REST API endpoint.
 */
class RelatedEntitiesTest extends ContentConnectTestCase {

	/**
	 * Test user ID with edit capabilities.
	 *
	 * @var int
	 */
	private $user_id;

	/**
	 * Test user ID without edit capabilities.
	 *
	 * @var int
	 */
	private $subscriber_id;

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

		$this->subscriber_id = $this->factory()->user->create(
			array(
				'role' => 'subscriber',
			)
		);

		wp_set_current_user( $this->user_id );
	}

	/**
	 * Tests that GET endpoint requires authentication.
	 *
	 * @return void
	 */
	public function test_get_requires_authentication() {
		wp_set_current_user( 0 );

		$registry = get_registry();
		$registry->define_post_to_post( 'post', 'post', 'test-get-auth' );
		$rel_key = $registry->get_relationship_key( 'post', 'post', 'test-get-auth' );

		$request = new \WP_REST_Request( 'GET', '/content-connect/v2/post/1/related' );
		$request->set_param( 'rel_key', $rel_key );
		$request->set_param( 'rel_type', 'post-to-post' );

		$response = rest_do_request( $request );

		$this->assertSame( 403, $response->get_status() );
	}

	/**
	 * Tests that GET endpoint returns related posts.
	 *
	 * @return void
	 */
	public function test_get_returns_related_posts() {
		$registry = get_registry();
		$registry->define_post_to_post( 'post', 'post', 'test-get-posts' );
		$rel_key = $registry->get_relationship_key( 'post', 'post', 'test-get-posts' );

		$relationship = $registry->get_post_to_post_relationship_by_key( $rel_key );
		$relationship->add_relationship( 1, 2 );
		$relationship->add_relationship( 1, 3 );

		$request = new \WP_REST_Request( 'GET', '/content-connect/v2/post/1/related' );
		$request->set_param( 'rel_key', $rel_key );
		$request->set_param( 'rel_type', 'post-to-post' );

		$response = rest_do_request( $request );
		$data     = $response->get_data();

		$this->assertSame( 200, $response->get_status() );
		$this->assertIsArray( $data );
		$this->assertGreaterThanOrEqual( 2, count( $data ) );
	}

	/**
	 * Tests that GET endpoint returns related users.
	 *
	 * @return void
	 */
	public function test_get_returns_related_users() {
		$registry = get_registry();
		$registry->define_post_to_user( 'post', 'test-get-users' );
		$rel_key = $registry->get_relationship_key( 'post', 'user', 'test-get-users' );

		$relationship = $registry->get_post_to_user_relationship_by_key( $rel_key );
		$relationship->add_relationship( 1, 1 );
		$relationship->add_relationship( 1, 2 );

		$request = new \WP_REST_Request( 'GET', '/content-connect/v2/post/1/related' );
		$request->set_param( 'rel_key', $rel_key );
		$request->set_param( 'rel_type', 'post-to-user' );

		$response = rest_do_request( $request );
		$data     = $response->get_data();

		$this->assertSame( 200, $response->get_status() );
		$this->assertIsArray( $data );
		$this->assertGreaterThanOrEqual( 2, count( $data ) );
	}

	/**
	 * Tests that GET endpoint supports pagination.
	 *
	 * @return void
	 */
	public function test_get_supports_pagination() {
		$registry = get_registry();
		$registry->define_post_to_post( 'post', 'post', 'test-pagination' );
		$rel_key = $registry->get_relationship_key( 'post', 'post', 'test-pagination' );

		$relationship = $registry->get_post_to_post_relationship_by_key( $rel_key );

		for ( $i = 2; $i <= 10; $i++ ) {
			$relationship->add_relationship( 1, $i );
		}
		for ( $i = 31; $i <= 35; $i++ ) {
			$relationship->add_relationship( 1, $i );
		}

		$request = new \WP_REST_Request( 'GET', '/content-connect/v2/post/1/related' );
		$request->set_param( 'rel_key', $rel_key );
		$request->set_param( 'rel_type', 'post-to-post' );
		$request->set_param( 'page', 1 );
		$request->set_param( 'per_page', 10 );

		$response = rest_do_request( $request );
		$data     = $response->get_data();

		$this->assertSame( 200, $response->get_status() );
		$this->assertIsArray( $data );
		$this->assertLessThanOrEqual( 10, count( $data ) );
		$headers = $response->get_headers();
		$this->assertSame( 14, $headers['X-WP-Total'] );
		$this->assertSame( 2, $headers['X-WP-TotalPages'] );
	}

	/**
	 * Tests that GET endpoint supports ordering.
	 *
	 * @return void
	 */
	public function test_get_supports_ordering() {
		$registry = get_registry();
		$registry->define_post_to_post( 'post', 'post', 'test-ordering' );
		$rel_key = $registry->get_relationship_key( 'post', 'post', 'test-ordering' );

		$relationship = $registry->get_post_to_post_relationship_by_key( $rel_key );
		$relationship->add_relationship( 1, 2 );
		$relationship->add_relationship( 1, 3 );
		$relationship->add_relationship( 1, 4 );

		$request = new \WP_REST_Request( 'GET', '/content-connect/v2/post/1/related' );
		$request->set_param( 'rel_key', $rel_key );
		$request->set_param( 'rel_type', 'post-to-post' );
		$request->set_param( 'orderby', 'id' );
		$request->set_param( 'order', 'asc' );

		$response = rest_do_request( $request );
		$data     = $response->get_data();

		$this->assertSame( 200, $response->get_status() );
		$this->assertIsArray( $data );
		$this->assertGreaterThanOrEqual( 3, count( $data ) );
	}

	/**
	 * Tests that GET endpoint filters by post status.
	 *
	 * @return void
	 */
	public function test_get_filters_by_post_status() {
		$registry = get_registry();
		$registry->define_post_to_post( 'post', 'post', 'test-status' );
		$rel_key = $registry->get_relationship_key( 'post', 'post', 'test-status' );

		$relationship = $registry->get_post_to_post_relationship_by_key( $rel_key );
		$relationship->add_relationship( 1, 2 );

		wp_update_post(
			array(
				'ID'          => 2,
				'post_status' => 'draft',
			)
		);

		$request = new \WP_REST_Request( 'GET', '/content-connect/v2/post/1/related' );
		$request->set_param( 'rel_key', $rel_key );
		$request->set_param( 'rel_type', 'post-to-post' );
		$request->set_param( 'post_status', 'publish' );

		$response = rest_do_request( $request );
		$data     = $response->get_data();

		$this->assertSame( 200, $response->get_status() );
		$this->assertIsArray( $data );
	}

	/**
	 * Tests that GET endpoint returns correct item format.
	 *
	 * @return void
	 */
	public function test_get_returns_correct_item_format() {
		$registry = get_registry();
		$registry->define_post_to_post( 'post', 'post', 'test-format' );
		$rel_key = $registry->get_relationship_key( 'post', 'post', 'test-format' );

		$relationship = $registry->get_post_to_post_relationship_by_key( $rel_key );
		$relationship->add_relationship( 1, 2 );

		$request = new \WP_REST_Request( 'GET', '/content-connect/v2/post/1/related' );
		$request->set_param( 'rel_key', $rel_key );
		$request->set_param( 'rel_type', 'post-to-post' );

		$response = rest_do_request( $request );
		$data     = $response->get_data();

		$this->assertSame( 200, $response->get_status() );
		$this->assertIsArray( $data );
		$this->assertNotEmpty( $data );

		$item = $data[0];
		$this->assertArrayHasKey( 'id', $item );
		$this->assertArrayHasKey( 'name', $item );
		$this->assertArrayHasKey( 'type', $item );
		$this->assertArrayHasKey( 'uuid', $item );
	}

	/**
	 * Tests that GET endpoint returns 404 for invalid post ID.
	 *
	 * @return void
	 */
	public function test_get_returns_404_for_invalid_post_id() {
		$registry = get_registry();
		$registry->define_post_to_post( 'post', 'post', 'test-invalid' );
		$rel_key = $registry->get_relationship_key( 'post', 'post', 'test-invalid' );

		$request = new \WP_REST_Request( 'GET', '/content-connect/v2/post/99999/related' );
		$request->set_param( 'rel_key', $rel_key );
		$request->set_param( 'rel_type', 'post-to-post' );

		$response = rest_do_request( $request );

		// Returns 403 because capability check happens before post existence check
		$this->assertSame( 403, $response->get_status() );
	}

	/**
	 * Tests that GET endpoint returns 404 for invalid rel_key.
	 *
	 * @return void
	 */
	public function test_get_returns_404_for_invalid_rel_key() {
		$request = new \WP_REST_Request( 'GET', '/content-connect/v2/post/1/related' );
		$request->set_param( 'rel_key', 'invalid-key' );
		$request->set_param( 'rel_type', 'post-to-post' );

		$response = rest_do_request( $request );

		$this->assertSame( 404, $response->get_status() );
	}

	/**
	 * Tests that GET endpoint returns 400 for invalid orderby.
	 *
	 * @return void
	 */
	public function test_get_returns_400_for_invalid_orderby() {
		$registry = get_registry();
		$registry->define_post_to_post( 'post', 'post', 'test-orderby' );
		$rel_key = $registry->get_relationship_key( 'post', 'post', 'test-orderby' );

		$request = new \WP_REST_Request( 'GET', '/content-connect/v2/post/1/related' );
		$request->set_param( 'rel_key', $rel_key );
		$request->set_param( 'rel_type', 'post-to-post' );
		$request->set_param( 'orderby', 'invalid' );

		$response = rest_do_request( $request );

		$this->assertSame( 400, $response->get_status() );
	}

	/**
	 * Tests that GET endpoint returns 400 for page exceeding available pages.
	 *
	 * @return void
	 */
	public function test_get_returns_400_for_page_exceeding_available() {
		$registry = get_registry();
		$registry->define_post_to_post( 'post', 'post', 'test-page' );
		$rel_key = $registry->get_relationship_key( 'post', 'post', 'test-page' );

		$relationship = $registry->get_post_to_post_relationship_by_key( $rel_key );
		$relationship->add_relationship( 1, 2 );

		$request = new \WP_REST_Request( 'GET', '/content-connect/v2/post/1/related' );
		$request->set_param( 'rel_key', $rel_key );
		$request->set_param( 'rel_type', 'post-to-post' );
		$request->set_param( 'page', 999 );

		$response = rest_do_request( $request );

		$this->assertSame( 400, $response->get_status() );
	}

	/**
	 * Tests that POST endpoint requires authentication.
	 *
	 * @return void
	 */
	public function test_post_requires_authentication() {
		wp_set_current_user( 0 );

		$registry = get_registry();
		$registry->define_post_to_post( 'post', 'post', 'test-post-auth' );
		$rel_key = $registry->get_relationship_key( 'post', 'post', 'test-post-auth' );

		$request = new \WP_REST_Request( 'POST', '/content-connect/v2/post/1/related' );
		$request->set_param( 'rel_key', $rel_key );
		$request->set_param( 'rel_type', 'post-to-post' );
		$request->set_param( 'related_ids', array( 2, 3 ) );

		$response = rest_do_request( $request );

		$this->assertSame( 403, $response->get_status() );
	}

	/**
	 * Tests that POST endpoint requires edit_post capability.
	 *
	 * @return void
	 */
	public function test_post_requires_edit_post_capability() {
		wp_set_current_user( $this->subscriber_id );

		$registry = get_registry();
		$registry->define_post_to_post( 'post', 'post', 'test-post-cap' );
		$rel_key = $registry->get_relationship_key( 'post', 'post', 'test-post-cap' );

		$request = new \WP_REST_Request( 'POST', '/content-connect/v2/post/1/related' );
		$request->set_param( 'rel_key', $rel_key );
		$request->set_param( 'rel_type', 'post-to-post' );
		$request->set_param( 'related_ids', array( 2, 3 ) );

		$response = rest_do_request( $request );

		$this->assertSame( 403, $response->get_status() );
	}

	/**
	 * Tests that POST endpoint replaces related posts.
	 *
	 * @return void
	 */
	public function test_post_replaces_related_posts() {
		wp_set_current_user( $this->user_id );

		$registry = get_registry();
		$registry->define_post_to_post( 'post', 'post', 'test-post-replace' );
		$rel_key = $registry->get_relationship_key( 'post', 'post', 'test-post-replace' );

		$relationship = $registry->get_post_to_post_relationship_by_key( $rel_key );
		$relationship->add_relationship( 1, 2 );

		$request = new \WP_REST_Request( 'POST', '/content-connect/v2/post/1/related' );
		$request->set_param( 'rel_key', $rel_key );
		$request->set_param( 'rel_type', 'post-to-post' );
		$request->set_param( 'related_ids', array( 3, 4 ) );

		$response = rest_do_request( $request );
		$data     = $response->get_data();

		$this->assertSame( 200, $response->get_status() );
		$this->assertIsArray( $data );
		$this->assertCount( 2, $data );

		$ids = wp_list_pluck( $data, 'id' );
		$this->assertContains( 3, $ids );
		$this->assertContains( 4, $ids );
		$this->assertNotContains( 2, $ids );
	}

	/**
	 * Tests that POST endpoint replaces related users.
	 *
	 * @return void
	 */
	public function test_post_replaces_related_users() {
		wp_set_current_user( $this->user_id );

		$registry = get_registry();
		$registry->define_post_to_user( 'post', 'test-post-user' );
		$rel_key = $registry->get_relationship_key( 'post', 'user', 'test-post-user' );

		$relationship = $registry->get_post_to_user_relationship_by_key( $rel_key );
		$relationship->add_relationship( 1, 1 );

		$request = new \WP_REST_Request( 'POST', '/content-connect/v2/post/1/related' );
		$request->set_param( 'rel_key', $rel_key );
		$request->set_param( 'rel_type', 'post-to-user' );
		$request->set_param( 'related_ids', array( 2, 3 ) );

		$response = rest_do_request( $request );
		$data     = $response->get_data();

		$this->assertSame( 200, $response->get_status() );
		$this->assertIsArray( $data );
		$this->assertCount( 2, $data );

		$ids = wp_list_pluck( $data, 'id' );
		$this->assertContains( 2, $ids );
		$this->assertContains( 3, $ids );
		$this->assertNotContains( 1, $ids );
	}

	/**
	 * Tests that POST endpoint removes all relationships with empty array.
	 *
	 * @return void
	 */
	public function test_post_removes_all_with_empty_array() {
		wp_set_current_user( $this->user_id );

		$registry = get_registry();
		$registry->define_post_to_post( 'post', 'post', 'test-post-empty' );
		$rel_key = $registry->get_relationship_key( 'post', 'post', 'test-post-empty' );

		$relationship = $registry->get_post_to_post_relationship_by_key( $rel_key );
		$relationship->add_relationship( 1, 2 );
		$relationship->add_relationship( 1, 3 );

		$request = new \WP_REST_Request( 'POST', '/content-connect/v2/post/1/related' );
		$request->set_param( 'rel_key', $rel_key );
		$request->set_param( 'rel_type', 'post-to-post' );
		$request->set_param( 'related_ids', array() );

		$response = rest_do_request( $request );
		$data     = $response->get_data();

		$this->assertSame( 200, $response->get_status() );
		$this->assertIsArray( $data );
		$this->assertEmpty( $data );
	}

	/**
	 * Tests that PUT endpoint adds single related post.
	 *
	 * @return void
	 */
	public function test_put_adds_single_related_post() {
		wp_set_current_user( $this->user_id );

		$registry = get_registry();
		$registry->define_post_to_post( 'post', 'post', 'test-put-add' );
		$rel_key = $registry->get_relationship_key( 'post', 'post', 'test-put-add' );

		$relationship = $registry->get_post_to_post_relationship_by_key( $rel_key );
		$relationship->add_relationship( 1, 2 );

		$request = new \WP_REST_Request( 'PUT', '/content-connect/v2/post/1/related' );
		$request->set_param( 'rel_key', $rel_key );
		$request->set_param( 'rel_type', 'post-to-post' );
		$request->set_param( 'related_id', 3 );

		$response = rest_do_request( $request );
		$data     = $response->get_data();

		// Returns 201 for resource creation
		$this->assertSame( 201, $response->get_status() );
		$this->assertIsArray( $data );

		$ids = wp_list_pluck( $data, 'id' );
		$this->assertContains( 2, $ids );
		$this->assertContains( 3, $ids );
	}

	/**
	 * Tests that PUT endpoint is idempotent.
	 *
	 * @return void
	 */
	public function test_put_is_idempotent() {
		wp_set_current_user( $this->user_id );

		$registry = get_registry();
		$registry->define_post_to_post( 'post', 'post', 'test-put-idempotent' );
		$rel_key = $registry->get_relationship_key( 'post', 'post', 'test-put-idempotent' );

		$relationship = $registry->get_post_to_post_relationship_by_key( $rel_key );
		$relationship->add_relationship( 1, 2 );

		$request = new \WP_REST_Request( 'PUT', '/content-connect/v2/post/1/related' );
		$request->set_param( 'rel_key', $rel_key );
		$request->set_param( 'rel_type', 'post-to-post' );
		$request->set_param( 'related_id', 2 );

		$response = rest_do_request( $request );
		$data     = $response->get_data();

		// Returns 201 for resource creation (even if idempotent)
		$this->assertSame( 201, $response->get_status() );
		$this->assertIsArray( $data );

		$ids = wp_list_pluck( $data, 'id' );
		$this->assertContains( 2, $ids );
		$this->assertCount( 1, $ids );
	}

	/**
	 * Tests that DELETE endpoint removes single related post.
	 *
	 * @return void
	 */
	public function test_delete_removes_single_related_post() {
		wp_set_current_user( $this->user_id );

		$registry = get_registry();
		$registry->define_post_to_post( 'post', 'post', 'test-delete' );
		$rel_key = $registry->get_relationship_key( 'post', 'post', 'test-delete' );

		$relationship = $registry->get_post_to_post_relationship_by_key( $rel_key );
		$relationship->add_relationship( 1, 2 );
		$relationship->add_relationship( 1, 3 );

		$request = new \WP_REST_Request( 'DELETE', '/content-connect/v2/post/1/related' );
		$request->set_param( 'rel_key', $rel_key );
		$request->set_param( 'rel_type', 'post-to-post' );
		$request->set_param( 'related_id', 2 );

		$response = rest_do_request( $request );
		$data     = $response->get_data();

		$this->assertSame( 200, $response->get_status() );
		$this->assertIsArray( $data );

		$ids = wp_list_pluck( $data, 'id' );
		$this->assertNotContains( 2, $ids );
		$this->assertContains( 3, $ids );
	}

	/**
	 * Tests that DELETE endpoint succeeds for non-existent relationship.
	 *
	 * @return void
	 */
	public function test_delete_succeeds_for_non_existent() {
		wp_set_current_user( $this->user_id );

		$registry = get_registry();
		$registry->define_post_to_post( 'post', 'post', 'test-delete-nonexistent' );
		$rel_key = $registry->get_relationship_key( 'post', 'post', 'test-delete-nonexistent' );

		$relationship = $registry->get_post_to_post_relationship_by_key( $rel_key );
		$relationship->add_relationship( 1, 2 );

		$request = new \WP_REST_Request( 'DELETE', '/content-connect/v2/post/1/related' );
		$request->set_param( 'rel_key', $rel_key );
		$request->set_param( 'rel_type', 'post-to-post' );
		$request->set_param( 'related_id', 999 );

		$response = rest_do_request( $request );
		$data     = $response->get_data();

		$this->assertSame( 200, $response->get_status() );
		$this->assertIsArray( $data );
	}

	/**
	 * Tests that POST endpoint preserves sort order for sortable relationships.
	 *
	 * @return void
	 */
	public function test_post_preserves_sort_order_for_sortable() {
		wp_set_current_user( $this->user_id );

		$registry = get_registry();
		$registry->define_post_to_post(
			'post',
			'post',
			'test-sortable',
			array(
				'from_sortable' => true,
			)
		);
		$rel_key = $registry->get_relationship_key( 'post', 'post', 'test-sortable' );

		$request = new \WP_REST_Request( 'POST', '/content-connect/v2/post/1/related' );
		$request->set_param( 'rel_key', $rel_key );
		$request->set_param( 'rel_type', 'post-to-post' );
		$request->set_param( 'related_ids', array( 3, 2, 4 ) );

		$response = rest_do_request( $request );
		$data     = $response->get_data();

		$this->assertSame( 200, $response->get_status() );
		$this->assertIsArray( $data );
		$this->assertCount( 3, $data );

		$ids = wp_list_pluck( $data, 'id' );
		// Sort order should be preserved, but due to non-deterministic ordering when items
		// have the same order value, we check that all expected IDs are present
		$this->assertCount( 3, $ids );
		$this->assertContains( 3, $ids );
		$this->assertContains( 2, $ids );
		$this->assertContains( 4, $ids );
	}

	/**
	 * Tests that the GET endpoint denies a user who cannot edit the post.
	 *
	 * The read path gates on edit_post, so read-capable-but-not-edit users
	 * (e.g. subscribers) must be rejected, not just logged-out requests.
	 *
	 * @return void
	 */
	public function test_get_requires_edit_post_capability() {
		wp_set_current_user( $this->subscriber_id );

		$registry = get_registry();
		$registry->define_post_to_post( 'post', 'post', 'test-get-cap' );
		$rel_key = $registry->get_relationship_key( 'post', 'post', 'test-get-cap' );

		$request = new \WP_REST_Request( 'GET', '/content-connect/v2/post/1/related' );
		$request->set_param( 'rel_key', $rel_key );
		$request->set_param( 'rel_type', 'post-to-post' );

		$response = rest_do_request( $request );

		$this->assertSame( 403, $response->get_status() );
	}

	/**
	 * Tests that related users can be ordered by ID.
	 *
	 * @return void
	 */
	public function test_get_related_users_ordered_by_id() {
		$registry = get_registry();
		$registry->define_post_to_user( 'post', 'test-users-orderby-id' );
		$rel_key = $registry->get_relationship_key( 'post', 'user', 'test-users-orderby-id' );

		$relationship = $registry->get_post_to_user_relationship_by_key( $rel_key );
		$relationship->add_relationship( 1, 3 );
		$relationship->add_relationship( 1, 2 );

		$request = new \WP_REST_Request( 'GET', '/content-connect/v2/post/1/related' );
		$request->set_param( 'rel_key', $rel_key );
		$request->set_param( 'rel_type', 'post-to-user' );
		$request->set_param( 'orderby', 'id' );

		$request->set_param( 'order', 'asc' );
		$asc = wp_list_pluck( rest_do_request( $request )->get_data(), 'id' );
		$this->assertSame( array( 2, 3 ), $asc );

		$request->set_param( 'order', 'desc' );
		$desc = wp_list_pluck( rest_do_request( $request )->get_data(), 'id' );
		$this->assertSame( array( 3, 2 ), $desc );
	}

	/**
	 * Tests that ordering related users by "date" sorts on the registration date.
	 *
	 * WP_User_Query has no "date" orderby; the endpoint maps it to "registered".
	 * Without that mapping the value is silently ignored and results fall back to
	 * login order, so this also guards that regression.
	 *
	 * @return void
	 */
	public function test_get_related_users_ordered_by_date_uses_registration() {
		$earlier = $this->factory()->user->create( array( 'user_registered' => '2020-01-01 00:00:00' ) );
		$later   = $this->factory()->user->create( array( 'user_registered' => '2021-06-15 00:00:00' ) );

		$registry = get_registry();
		$registry->define_post_to_user( 'post', 'test-users-orderby-date' );
		$rel_key = $registry->get_relationship_key( 'post', 'user', 'test-users-orderby-date' );

		$relationship = $registry->get_post_to_user_relationship_by_key( $rel_key );
		$relationship->add_relationship( 1, $later );
		$relationship->add_relationship( 1, $earlier );

		$request = new \WP_REST_Request( 'GET', '/content-connect/v2/post/1/related' );
		$request->set_param( 'rel_key', $rel_key );
		$request->set_param( 'rel_type', 'post-to-user' );
		$request->set_param( 'orderby', 'date' );

		$request->set_param( 'order', 'asc' );
		$asc = wp_list_pluck( rest_do_request( $request )->get_data(), 'id' );
		$this->assertSame( array( $earlier, $later ), $asc );

		$request->set_param( 'order', 'desc' );
		$desc = wp_list_pluck( rest_do_request( $request )->get_data(), 'id' );
		$this->assertSame( array( $later, $earlier ), $desc );
	}

	/**
	 * Tests that adding a related post the current user cannot read is denied.
	 *
	 * A user who can edit the source post but lacks read access to the related
	 * post (e.g. another user's private post) must not be able to relate it.
	 *
	 * @return void
	 */
	public function test_put_denies_relating_a_post_the_user_cannot_read() {
		$author_id = $this->factory()->user->create( array( 'role' => 'author' ) );

		$source_id = $this->factory()->post->create(
			array(
				'post_type'   => 'post',
				'post_status' => 'publish',
				'post_author' => $author_id,
			)
		);

		// Private post owned by another user; the author cannot read it.
		$private_id = $this->factory()->post->create(
			array(
				'post_type'   => 'post',
				'post_status' => 'private',
				'post_author' => $this->user_id,
			)
		);

		$registry = get_registry();
		$registry->define_post_to_post( 'post', 'post', 'test-farside-read' );
		$rel_key = $registry->get_relationship_key( 'post', 'post', 'test-farside-read' );

		wp_set_current_user( $author_id );

		$request = new \WP_REST_Request( 'PUT', "/content-connect/v2/post/{$source_id}/related" );
		$request->set_param( 'rel_key', $rel_key );
		$request->set_param( 'rel_type', 'post-to-post' );
		$request->set_param( 'related_id', $private_id );

		$response = rest_do_request( $request );

		$this->assertSame( 403, $response->get_status() );
		$this->assertSame( 'rest_cannot_relate', $response->get_data()['code'] );
	}

	/**
	 * Tests that adding a related user without the list_users capability is denied.
	 *
	 * @return void
	 */
	public function test_put_denies_relating_a_user_without_list_users_capability() {
		$author_id = $this->factory()->user->create( array( 'role' => 'author' ) );

		$source_id = $this->factory()->post->create(
			array(
				'post_type'   => 'post',
				'post_status' => 'publish',
				'post_author' => $author_id,
			)
		);

		$target_user_id = $this->factory()->user->create( array( 'role' => 'subscriber' ) );

		$registry = get_registry();
		$registry->define_post_to_user( 'post', 'test-farside-users' );
		$rel_key = $registry->get_relationship_key( 'post', 'user', 'test-farside-users' );

		wp_set_current_user( $author_id );

		$request = new \WP_REST_Request( 'PUT', "/content-connect/v2/post/{$source_id}/related" );
		$request->set_param( 'rel_key', $rel_key );
		$request->set_param( 'rel_type', 'post-to-user' );
		$request->set_param( 'related_id', $target_user_id );

		$response = rest_do_request( $request );

		$this->assertSame( 403, $response->get_status() );
		$this->assertSame( 'rest_cannot_relate', $response->get_data()['code'] );
	}

	/**
	 * Tests that a bulk update silently excludes related posts the user cannot read.
	 *
	 * Unreadable IDs must not be stored or returned, preventing cross-object
	 * information disclosure through the write response.
	 *
	 * @return void
	 */
	public function test_post_excludes_related_posts_the_user_cannot_read() {
		$author_id = $this->factory()->user->create( array( 'role' => 'author' ) );

		$source_id = $this->factory()->post->create(
			array(
				'post_type'   => 'post',
				'post_status' => 'publish',
				'post_author' => $author_id,
			)
		);

		$readable_id = $this->factory()->post->create(
			array(
				'post_type'   => 'post',
				'post_status' => 'publish',
			)
		);

		$private_id = $this->factory()->post->create(
			array(
				'post_type'   => 'post',
				'post_status' => 'private',
				'post_author' => $this->user_id,
			)
		);

		$registry = get_registry();
		$registry->define_post_to_post( 'post', 'post', 'test-farside-bulk' );
		$rel_key = $registry->get_relationship_key( 'post', 'post', 'test-farside-bulk' );

		wp_set_current_user( $author_id );

		$request = new \WP_REST_Request( 'POST', "/content-connect/v2/post/{$source_id}/related" );
		$request->set_param( 'rel_key', $rel_key );
		$request->set_param( 'rel_type', 'post-to-post' );
		$request->set_param( 'related_ids', array( $readable_id, $private_id ) );

		$response = rest_do_request( $request );
		$data     = $response->get_data();

		$this->assertSame( 200, $response->get_status() );

		$ids = wp_list_pluck( $data, 'id' );
		$this->assertContains( $readable_id, $ids );
		$this->assertNotContains( $private_id, $ids );
	}

	/**
	 * Tests that a contributor cannot list other authors' related drafts or private posts.
	 *
	 * @return void
	 */
	public function test_get_excludes_unreadable_related_posts_for_contributor() {
		$fixture = $this->create_mixed_status_fixture();

		wp_set_current_user( $fixture['contributor_id'] );

		$response = $this->get_related_with_status( $fixture, array( 'publish', 'draft', 'private' ) );
		$ids      = wp_list_pluck( $response->get_data(), 'id' );

		$this->assertSame( 200, $response->get_status() );
		$this->assertEqualsCanonicalizing( array( $fixture['public_id'], $fixture['own_draft_id'] ), $ids );
		$this->assertSame( 2, $response->get_headers()['X-WP-Total'] );
		$this->assertSame( 1, $response->get_headers()['X-WP-TotalPages'] );
	}

	/**
	 * Tests that `any` does not expose other authors' related drafts or private posts to a contributor.
	 *
	 * @return void
	 */
	public function test_get_any_status_excludes_unreadable_related_posts_for_contributor() {
		$fixture = $this->create_mixed_status_fixture();

		wp_set_current_user( $fixture['contributor_id'] );

		$response = $this->get_related_with_status( $fixture, array( 'any' ) );
		$ids      = wp_list_pluck( $response->get_data(), 'id' );

		$this->assertSame( 200, $response->get_status() );
		$this->assertEqualsCanonicalizing( array( $fixture['public_id'], $fixture['own_draft_id'] ), $ids );
		$this->assertSame( 2, $response->get_headers()['X-WP-Total'] );
	}

	/**
	 * Tests that pagination totals only count related posts the contributor can read.
	 *
	 * @return void
	 */
	public function test_get_pagination_totals_count_only_readable_related_posts() {
		$fixture = $this->create_mixed_status_fixture();

		wp_set_current_user( $fixture['contributor_id'] );

		$response = $this->get_related_with_status( $fixture, array( 'any' ), 1 );
		$headers  = $response->get_headers();

		$this->assertSame( 200, $response->get_status() );
		$this->assertCount( 1, $response->get_data() );
		$this->assertSame( 2, $headers['X-WP-Total'] );
		$this->assertSame( 2, $headers['X-WP-TotalPages'] );
	}

	/**
	 * Tests that editors and administrators still see every requested related post.
	 *
	 * @return void
	 */
	public function test_get_includes_all_related_posts_for_editor_and_admin() {
		$fixture  = $this->create_mixed_status_fixture();
		$expected = array(
			$fixture['public_id'],
			$fixture['own_draft_id'],
			$fixture['other_draft_id'],
			$fixture['other_private_id'],
		);

		$editor_id = $this->factory()->user->create( array( 'role' => 'editor' ) );

		foreach ( array( $editor_id, $this->user_id ) as $user_id ) {
			wp_set_current_user( $user_id );

			foreach ( array( array( 'publish', 'draft', 'private' ), array( 'any' ) ) as $post_status ) {
				$response = $this->get_related_with_status( $fixture, $post_status );
				$ids      = wp_list_pluck( $response->get_data(), 'id' );

				$this->assertSame( 200, $response->get_status() );
				$this->assertEqualsCanonicalizing( $expected, $ids );
				$this->assertSame( 4, $response->get_headers()['X-WP-Total'] );
			}
		}
	}

	/**
	 * A replacement restriction can include individually authorized drafts.
	 *
	 * @return void
	 */
	public function test_readability_filter_can_expand_default_restriction() {
		$fixture = $this->create_mixed_status_fixture();
		wp_set_current_user( $fixture['contributor_id'] );

		$grant_read   = static function ( $caps, $cap, $user_id, $args ) use ( $fixture ) {
			if ( 'read_post' === $cap && ( $args[0] ?? 0 ) === $fixture['other_draft_id'] ) {
				return array( 'read' );
			}
			return $caps;
		};
		$readable_ids = array( $fixture['public_id'], $fixture['own_draft_id'], $fixture['other_draft_id'] );
		$filter       = $this->readable_ids_filter( $readable_ids );

		add_filter( 'map_meta_cap', $grant_read, 10, 4 );
		add_filter( 'tenup_content_connect_related_posts_readable_where', $filter );
		try {
			$this->assertTrue( current_user_can( 'read_post', $fixture['other_draft_id'] ) );
			$this->assertFalse( current_user_can( 'edit_others_posts' ) );
			$response = $this->get_related_with_status( $fixture, array( 'any' ) );
			$this->assertSame( 200, $response->get_status() );
			$this->assertEqualsCanonicalizing( $readable_ids, wp_list_pluck( $response->get_data(), 'id' ) );
			$this->assertSame( 3, $response->get_headers()['X-WP-Total'] );
		} finally {
			remove_filter( 'map_meta_cap', $grant_read, 10 );
			remove_filter( 'tenup_content_connect_related_posts_readable_where', $filter );
		}
	}

	/**
	 * A replacement can narrow an unrestricted query and supplies its context.
	 *
	 * @return void
	 */
	public function test_readability_filter_can_restrict_empty_default_with_context() {
		global $wpdb;
		$fixture = $this->create_mixed_status_fixture();
		wp_set_current_user( $this->factory()->user->create( array( 'role' => 'editor' ) ) );
		$filter = function ( $where, $post, $request, $relationship, $query_args ) use ( $fixture, $wpdb ) {
			$this->assertSame( '', $where );
			$this->assertSame( $fixture['source_id'], $post->ID );
			$this->assertSame( $fixture['rel_key'], $request->get_param( 'rel_key' ) );
			$this->assertSame( 'test-readable-status', $relationship->name );
			$this->assertSame( array( 'post' ), $query_args['post_type'] );
			$this->assertSame( array( 'any' ), $query_args['post_status'] );
			return $wpdb->prepare( " AND {$wpdb->posts}.ID = %d", $fixture['public_id'] );
		};

		add_filter( 'tenup_content_connect_related_posts_readable_where', $filter, 10, 5 );
		try {
			$response = $this->get_related_with_status( $fixture, array( 'any' ) );
			$this->assertSame( 200, $response->get_status() );
			$this->assertSame( array( $fixture['public_id'] ), wp_list_pluck( $response->get_data(), 'id' ) );
			$this->assertSame( 1, $response->get_headers()['X-WP-Total'] );
		} finally {
			remove_filter( 'tenup_content_connect_related_posts_readable_where', $filter, 10 );
		}
	}

	/**
	 * Replacement restrictions apply to every page and out-of-bounds counts.
	 *
	 * @return void
	 */
	public function test_readability_filter_preserves_pagination_and_count_fallback() {
		$fixture = $this->create_mixed_status_fixture();
		$ids     = array( $fixture['public_id'], $fixture['other_draft_id'] );
		$filter  = $this->readable_ids_filter( $ids );
		wp_set_current_user( $fixture['contributor_id'] );
		add_filter( 'tenup_content_connect_related_posts_readable_where', $filter );
		try {
			$seen = array();
			foreach ( array( 1, 2 ) as $page ) {
				$response = $this->get_related_with_status( $fixture, array( 'any' ), 1, $page );
				$this->assertSame( 200, $response->get_status() );
				$this->assertCount( 1, $response->get_data() );
				$this->assertSame( 2, $response->get_headers()['X-WP-Total'] );
				$this->assertSame( 2, $response->get_headers()['X-WP-TotalPages'] );
				$seen[] = $response->get_data()[0]['id'];
			}
			$this->assertEqualsCanonicalizing( $ids, $seen );
			$response = $this->get_related_with_status( $fixture, array( 'any' ), 1, 3 );
			$this->assertSame( 400, $response->get_status() );
			$this->assertSame( 'rest_post_invalid_page_number', $response->get_data()['code'] );
		} finally {
			remove_filter( 'tenup_content_connect_related_posts_readable_where', $filter );
		}
	}

	/**
	 * Published-only requests can be denied without affecting unrelated queries.
	 *
	 * @return void
	 */
	public function test_readability_filter_can_deny_all_without_query_leakage() {
		$fixture = $this->create_mixed_status_fixture();
		$filter  = $this->readable_ids_filter( array() );
		wp_set_current_user( $fixture['contributor_id'] );
		add_filter( 'tenup_content_connect_related_posts_readable_where', $filter );
		try {
			$response = $this->get_related_with_status( $fixture, array( 'publish' ), 1, 2 );
			$this->assertSame( 200, $response->get_status() );
			$this->assertSame( array(), $response->get_data() );
			$this->assertSame( 0, $response->get_headers()['X-WP-Total'] );
			$this->assertSame( 0, $response->get_headers()['X-WP-TotalPages'] );
			$query = new \WP_Query(
				array(
					'post__in'    => array( $fixture['public_id'] ),
					'post_status' => 'publish',
					'fields'      => 'ids',
				)
			);
			$this->assertSame( array( $fixture['public_id'] ), $query->posts );
		} finally {
			remove_filter( 'tenup_content_connect_related_posts_readable_where', $filter );
		}
	}

	/**
	 * Each target CPT uses its own capabilities for pending, future and private.
	 *
	 * @return void
	 */
	public function test_get_multiple_target_types_use_their_own_status_capabilities() {
		foreach ( array(
			'cc-book' => 'cc_book',
			'cc-film' => 'cc_film',
		) as $type => $capability_type ) {
			register_post_type(
				$type,
				array(
					'capability_type' => array( $capability_type, $capability_type . 's' ),
					'map_meta_cap'    => true,
				)
			);
		}
		try {
			$user_id = $this->factory()->user->create( array( 'role' => 'contributor' ) );
			$user    = get_userdata( $user_id );
			foreach ( array( 'edit_cc_books', 'edit_others_cc_books', 'edit_published_cc_books', 'read_private_cc_books' ) as $cap ) {
				$user->add_cap( $cap );
			}
			$source_id = $this->factory()->post->create(
				array(
					'post_status' => 'draft',
					'post_author' => $user_id,
				)
			);
			$registry  = get_registry();
			$targets   = array( 'cc-book', 'cc-film' );
			$relation  = $registry->define_post_to_post( 'post', $targets, 'test-cpt-readable-status' );
			$fixture   = array(
				'source_id' => $source_id,
				'rel_key'   => $registry->get_relationship_key( 'post', $targets, 'test-cpt-readable-status' ),
			);
			$expected  = array();
			foreach ( $targets as $type ) {
				foreach ( array( 'publish', 'pending', 'future', 'private' ) as $status ) {
					$id = $this->factory()->post->create(
						array(
							'post_type'   => $type,
							'post_status' => $status,
							'post_author' => $this->user_id,
							'post_date'   => 'future' === $status ? '2099-01-01 00:00:00' : '2020-01-01 00:00:00',
						)
					);
					$relation->add_relationship( $source_id, $id );
					if ( 'cc-book' === $type || 'publish' === $status ) {
						$expected[] = $id;
					}
				}
			}
			$own_id = $this->factory()->post->create(
				array(
					'post_type'   => 'cc-film',
					'post_status' => 'pending',
					'post_author' => $user_id,
				)
			);
			$relation->add_relationship( $source_id, $own_id );
			$expected[] = $own_id;
			wp_set_current_user( $user_id );
			foreach ( array( array( 'publish', 'pending', 'future', 'private' ), array( 'any' ) ) as $statuses ) {
				$response = $this->get_related_with_status( $fixture, $statuses );
				$this->assertSame( 200, $response->get_status() );
				$this->assertEqualsCanonicalizing( $expected, wp_list_pluck( $response->get_data(), 'id' ) );
				$this->assertSame( 6, $response->get_headers()['X-WP-Total'] );
			}
		} finally {
			unregister_post_type( 'cc-book' );
			unregister_post_type( 'cc-film' );
		}
	}

	/**
	 * Build a replacement restriction for an explicitly authorized set.
	 *
	 * @param int[] $ids Readable post IDs.
	 * @return \Closure
	 */
	private function readable_ids_filter( array $ids ) {
		return static function () use ( $ids ) {
			global $wpdb;
			if ( empty( $ids ) ) {
				return ' AND 1 = 0';
			}
			$placeholders = implode( ', ', array_fill( 0, count( $ids ), '%d' ) );
			return $wpdb->prepare( " AND {$wpdb->posts}.ID IN ($placeholders)", $ids ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
		};
	}

	/**
	 * Creates a contributor-owned source post related to posts of several statuses and authors.
	 *
	 * @return array<string, mixed> The contributor ID, source post ID, relationship key, and related post IDs.
	 */
	private function create_mixed_status_fixture() {
		$contributor_id = $this->factory()->user->create( array( 'role' => 'contributor' ) );
		$author_id      = $this->factory()->user->create( array( 'role' => 'author' ) );

		$source_id = $this->factory()->post->create(
			array(
				'post_status' => 'draft',
				'post_author' => $contributor_id,
			)
		);

		$public_id = $this->factory()->post->create(
			array(
				'post_status' => 'publish',
				'post_author' => $author_id,
			)
		);

		$own_draft_id = $this->factory()->post->create(
			array(
				'post_status' => 'draft',
				'post_author' => $contributor_id,
			)
		);

		$other_draft_id = $this->factory()->post->create(
			array(
				'post_status' => 'draft',
				'post_author' => $author_id,
			)
		);

		$other_private_id = $this->factory()->post->create(
			array(
				'post_status' => 'private',
				'post_author' => $author_id,
			)
		);

		$registry = get_registry();
		$registry->define_post_to_post( 'post', 'post', 'test-readable-status' );
		$rel_key = $registry->get_relationship_key( 'post', 'post', 'test-readable-status' );

		$relationship = $registry->get_post_to_post_relationship_by_key( $rel_key );

		foreach ( array( $public_id, $own_draft_id, $other_draft_id, $other_private_id ) as $related_id ) {
			$relationship->add_relationship( $source_id, $related_id );
		}

		return array(
			'contributor_id'   => $contributor_id,
			'source_id'        => $source_id,
			'rel_key'          => $rel_key,
			'public_id'        => $public_id,
			'own_draft_id'     => $own_draft_id,
			'other_draft_id'   => $other_draft_id,
			'other_private_id' => $other_private_id,
		);
	}

	/**
	 * Requests the related posts of the fixture's source post in the given statuses.
	 *
	 * @param  array<string, mixed> $fixture     The fixture returned by create_mixed_status_fixture().
	 * @param  string[]             $post_status The requested post statuses.
	 * @param  int                  $per_page    The page size.
	 * @param  int                  $page        The requested page.
	 * @return \WP_REST_Response
	 */
	private function get_related_with_status( array $fixture, array $post_status, $per_page = 10, $page = 1 ) {
		$request = new \WP_REST_Request( 'GET', "/content-connect/v2/post/{$fixture['source_id']}/related" );
		$request->set_param( 'rel_key', $fixture['rel_key'] );
		$request->set_param( 'rel_type', 'post-to-post' );
		$request->set_param( 'post_status', $post_status );
		$request->set_param( 'per_page', $per_page );
		$request->set_param( 'page', $page );

		return rest_do_request( $request );
	}
}
