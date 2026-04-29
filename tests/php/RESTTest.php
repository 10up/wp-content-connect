<?php
/**
 * Tests for REST API link additions.
 *
 * @package TenUp\ContentConnect\Tests
 */

namespace TenUp\ContentConnect\Tests;

use TenUp\ContentConnect\Tests\ContentConnectTestCase;
use function TenUp\ContentConnect\Helpers\get_registry;

/**
 * Test cases for REST API link additions.
 */
class RESTTest extends ContentConnectTestCase {

	/**
	 * Sets up the test environment.
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		\TenUp\ContentConnect\Plugin::instance();

		$this->add_post_relations();
		$this->add_user_relations();

		$rest = new \TenUp\ContentConnect\REST();
		$rest->add_links();
	}

	/**
	 * Tests that links are added to posts with show_in_rest=true.
	 *
	 * @return void
	 */
	public function test_links_added_to_posts_with_show_in_rest() {
		$request  = new \WP_REST_Request( 'GET', '/wp/v2/posts/1' );
		$response = rest_do_request( $request );
		$data     = $response->get_data();
		$links    = $response->get_links();

		$this->assertSame( 200, $response->get_status() );
		$this->assertArrayHasKey( 'content-connect:relationships', $links );
		$this->assertArrayHasKey( 'content-connect:related', $links );
	}

	/**
	 * Tests that links include content-connect:relationships link.
	 *
	 * @return void
	 */
	public function test_links_include_relationships_link() {
		$request  = new \WP_REST_Request( 'GET', '/wp/v2/posts/1' );
		$response = rest_do_request( $request );
		$links    = $response->get_links();

		$this->assertSame( 200, $response->get_status() );
		$this->assertArrayHasKey( 'content-connect:relationships', $links );

		$rel_link = $links['content-connect:relationships'];
		$this->assertIsArray( $rel_link );
		$this->assertNotEmpty( $rel_link );

		$first_link = $rel_link[0];
		$this->assertArrayHasKey( 'href', $first_link );
		$this->assertStringContainsString( '/content-connect/v2/post/1/relationships', $first_link['href'] );
	}

	/**
	 * Tests that links include content-connect:related links for each relationship.
	 *
	 * @return void
	 */
	public function test_links_include_related_links_for_each_relationship() {
		$request  = new \WP_REST_Request( 'GET', '/wp/v2/posts/1' );
		$response = rest_do_request( $request );
		$links    = $response->get_links();

		$this->assertSame( 200, $response->get_status() );
		$this->assertArrayHasKey( 'content-connect:related', $links );

		$related_links = $links['content-connect:related'];
		$this->assertIsArray( $related_links );
		$this->assertGreaterThanOrEqual( 2, count( $related_links ) );

		foreach ( $related_links as $link ) {
			$this->assertArrayHasKey( 'href', $link );
			$this->assertArrayHasKey( 'attributes', $link );
			$this->assertArrayHasKey( 'relationship', $link['attributes'] );
			$this->assertStringContainsString( '/content-connect/v2/post/1/related', $link['href'] );
			$this->assertStringContainsString( 'rel_key=', $link['href'] );
			$this->assertStringContainsString( 'rel_type=', $link['href'] );
		}
	}

	/**
	 * Tests that links have correct href format for relationships endpoint.
	 *
	 * @return void
	 */
	public function test_links_have_correct_href_format_for_relationships() {
		$request  = new \WP_REST_Request( 'GET', '/wp/v2/posts/1' );
		$response = rest_do_request( $request );
		$links    = $response->get_links();

		$this->assertSame( 200, $response->get_status() );
		$this->assertArrayHasKey( 'content-connect:relationships', $links );

		$rel_link = $links['content-connect:relationships'];
		$this->assertIsArray( $rel_link );
		$this->assertNotEmpty( $rel_link );

		$first_link = $rel_link[0];
		$href       = $first_link['href'];
		$this->assertStringContainsString( rest_url( '/content-connect/v2/post/1/relationships' ), $href );
	}

	/**
	 * Tests that links have correct href format for related endpoint.
	 *
	 * @return void
	 */
	public function test_links_have_correct_href_format_for_related() {
		$registry = get_registry();
		$rel_key  = $registry->get_relationship_key( 'post', 'post', 'basic' );

		$request  = new \WP_REST_Request( 'GET', '/wp/v2/posts/1' );
		$response = rest_do_request( $request );
		$links    = $response->get_links();

		$this->assertSame( 200, $response->get_status() );
		$this->assertArrayHasKey( 'content-connect:related', $links );

		$related_links = $links['content-connect:related'];
		$this->assertIsArray( $related_links );
		$this->assertNotEmpty( $related_links );

		// Find the link for the 'basic' relationship
		$basic_link = null;
		foreach ( $related_links as $link ) {
			if ( isset( $link['href'] ) && strpos( $link['href'], rawurlencode( $rel_key ) ) !== false ) {
				$basic_link = $link;
				break;
			}
		}

		$this->assertNotNull( $basic_link, 'Should find link for basic relationship' );
		$this->assertArrayHasKey( 'href', $basic_link );
		$this->assertArrayHasKey( 'attributes', $basic_link );
		$href = $basic_link['href'];
		$this->assertStringContainsString( rest_url( '/content-connect/v2/post/1/related' ), $href );
		$this->assertStringContainsString( 'rel_key=' . rawurlencode( $rel_key ), $href );
		$this->assertStringContainsString( 'rel_type=post-to-post', $href );
	}

	/**
	 * Tests that no links are added when post type has no registered relationships.
	 *
	 * @return void
	 */
	public function test_no_links_added_when_post_has_no_relationships() {
		// Register a post type without any relationships
		register_post_type(
			'no-relationships',
			array(
				'public'       => true,
				'show_in_rest' => true,
			)
		);

		// Ensure REST routes are registered for the new post type
		$server = rest_get_server();
		do_action( 'rest_api_init', $server );

		$post_id = $this->factory()->post->create(
			array(
				'post_type' => 'no-relationships',
			)
		);

		$request  = new \WP_REST_Request( 'GET', '/wp/v2/no-relationships/' . $post_id );
		$response = rest_do_request( $request );

		$this->assertSame( 200, $response->get_status() );
		$links = $response->get_links();

		if ( isset( $links['content-connect:relationships'] ) ) {
			$this->fail( 'Links should not be added when post type has no registered relationships' );
		}
	}

	/**
	 * Tests that no links are added to posts with show_in_rest=false.
	 *
	 * @return void
	 */
	public function test_no_links_added_to_posts_without_show_in_rest() {
		register_post_type(
			'no-rest',
			array(
				'public'       => true,
				'show_in_rest' => false,
			)
		);

		$server = rest_get_server();
		do_action( 'rest_api_init', $server );

		$post_id = $this->factory()->post->create(
			array(
				'post_type' => 'no-rest',
			)
		);

		$registry = get_registry();
		$registry->define_post_to_post( 'no-rest', 'post', 'test-no-rest' );

		$request  = new \WP_REST_Request( 'GET', '/wp/v2/no-rest/' . $post_id );
		$response = rest_do_request( $request );

		$links = $response->get_links();
		$this->assertArrayNotHasKey( 'content-connect:relationships', $links );
	}

	/**
	 * Tests that multiple relationships result in multiple related links.
	 *
	 * @return void
	 */
	public function test_multiple_relationships_result_in_multiple_related_links() {
		$request  = new \WP_REST_Request( 'GET', '/wp/v2/posts/1' );
		$response = rest_do_request( $request );
		$links    = $response->get_links();

		$this->assertSame( 200, $response->get_status() );
		$this->assertArrayHasKey( 'content-connect:related', $links );

		$related_links = $links['content-connect:related'];
		$this->assertIsArray( $related_links );
		// Post 1 has relationships: 'basic' (post-to-post), 'complex' (post-to-post), 'owner' (post-to-user), 'contrib' (post-to-user)
		$this->assertGreaterThanOrEqual( 4, count( $related_links ) );
	}

	/**
	 * Tests that both post-to-post and post-to-user relationships are included.
	 *
	 * @return void
	 */
	public function test_both_post_to_post_and_post_to_user_relationships_included() {
		$request  = new \WP_REST_Request( 'GET', '/wp/v2/posts/1' );
		$response = rest_do_request( $request );
		$links    = $response->get_links();

		$this->assertSame( 200, $response->get_status() );
		$this->assertArrayHasKey( 'content-connect:related', $links );

		$related_links = $links['content-connect:related'];
		$this->assertIsArray( $related_links );
		$this->assertGreaterThanOrEqual( 2, count( $related_links ) );

		$rel_types = array();
		foreach ( $related_links as $link ) {
			if ( isset( $link['href'] ) ) {
				parse_str( wp_parse_url( $link['href'], PHP_URL_QUERY ), $query_params );
				if ( isset( $query_params['rel_type'] ) ) {
					$rel_types[] = $query_params['rel_type'];
				}
			}
		}

		$this->assertContains( 'post-to-post', $rel_types );
		$this->assertContains( 'post-to-user', $rel_types );
	}
}
