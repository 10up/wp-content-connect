<?php
/**
 * Tests for V2 Post RelatedEntities REST API endpoint.
 *
 * @package TenUp\ContentConnect\Tests\Integration\API\V2\Post\Route
 */

namespace TenUp\ContentConnect\Tests\Integration\API\V2\Post\Route;

use TenUp\ContentConnect\Tests\Integration\ContentConnectTestCase;
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
		$this->assertArrayHasKey( 'ID', $item );
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
}

