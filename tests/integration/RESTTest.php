<?php
/**
 * Tests for REST API link additions.
 *
 * @package TenUp\ContentConnect\Tests\Integration
 */

namespace TenUp\ContentConnect\Tests\Integration;

use TenUp\ContentConnect\Tests\Integration\ContentConnectTestCase;
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

		$this->add_post_relations();
		$this->add_user_relations();
	}

	/**
	 * Tests that links are added to posts with show_in_rest=true.
	 *
	 * @return void
	 */
	public function test_links_added_to_posts_with_show_in_rest() {
		$registry = get_registry();
		$registry->define_post_to_post( 'post', 'post', 'test-links' );
		$registry->define_post_to_user( 'post', 'test-user-links' );

		$post = get_post( 1 );

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
		$registry = get_registry();
		$registry->define_post_to_post( 'post', 'post', 'test-rel-link' );

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
		$registry = get_registry();
		$registry->define_post_to_post( 'post', 'post', 'test-related-link-1' );
		$registry->define_post_to_post( 'post', 'post', 'test-related-link-2' );

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
		$registry = get_registry();
		$registry->define_post_to_post( 'post', 'post', 'test-href-format' );

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
		$registry->define_post_to_post( 'post', 'post', 'test-related-href' );
		$rel_key = $registry->get_relationship_key( 'post', 'post', 'test-related-href' );

		$request  = new \WP_REST_Request( 'GET', '/wp/v2/posts/1' );
		$response = rest_do_request( $request );
		$links    = $response->get_links();

		$this->assertSame( 200, $response->get_status() );
		$this->assertArrayHasKey( 'content-connect:related', $links );

		$related_links = $links['content-connect:related'];
		$this->assertIsArray( $related_links );
		$this->assertNotEmpty( $related_links );

		$link = $related_links[0];
		$this->assertArrayHasKey( 'href', $link );
		$this->assertArrayHasKey( 'attributes', $link );
		$href = $link['href'];
		$this->assertStringContainsString( rest_url( '/content-connect/v2/post/1/related' ), $href );
		$this->assertStringContainsString( 'rel_key=' . urlencode( $rel_key ), $href );
		$this->assertStringContainsString( 'rel_type=post-to-post', $href );
	}

	/**
	 * Tests that no links are added when post has no relationships.
	 *
	 * @return void
	 */
	public function test_no_links_added_when_post_has_no_relationships() {
		$request  = new \WP_REST_Request( 'GET', '/wp/v2/posts/1' );
		$response = rest_do_request( $request );
		$links    = $response->get_links();

		$this->assertSame( 200, $response->get_status() );

		if ( isset( $links['content-connect:relationships'] ) ) {
			$this->fail( 'Links should not be added when post has no relationships' );
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

		$post_id = $this->factory()->post->create(
			array(
				'post_type' => 'no-rest',
			)
		);

		$registry = get_registry();
		$registry->define_post_to_post( 'no-rest', 'post', 'test-no-rest' );

		$request  = new \WP_REST_Request( 'GET', '/wp/v2/no-rest/' . $post_id );
		$response = rest_do_request( $request );

		// The endpoint itself might not exist (404) because show_in_rest=false,
		// but if it does exist, links shouldn't be added.
		if ( 200 === $response->get_status() ) {
			$links = $response->get_links();
			$this->assertArrayNotHasKey( 'content-connect:relationships', $links );
		} else {
			// If the endpoint doesn't exist, that's also valid - posts without show_in_rest
			// typically don't have REST endpoints, so links can't be added.
			$this->assertNotSame( 200, $response->get_status() );
		}
	}

	/**
	 * Tests that multiple relationships result in multiple related links.
	 *
	 * @return void
	 */
	public function test_multiple_relationships_result_in_multiple_related_links() {
		$registry = get_registry();
		$registry->define_post_to_post( 'post', 'post', 'test-multi-1' );
		$registry->define_post_to_post( 'post', 'post', 'test-multi-2' );
		$registry->define_post_to_post( 'post', 'post', 'test-multi-3' );

		$request  = new \WP_REST_Request( 'GET', '/wp/v2/posts/1' );
		$response = rest_do_request( $request );
		$links    = $response->get_links();

		$this->assertSame( 200, $response->get_status() );
		$this->assertArrayHasKey( 'content-connect:related', $links );

		$related_links = $links['content-connect:related'];
		$this->assertIsArray( $related_links );
		$this->assertGreaterThanOrEqual( 3, count( $related_links ) );
	}

	/**
	 * Tests that both post-to-post and post-to-user relationships are included.
	 *
	 * @return void
	 */
	public function test_both_post_to_post_and_post_to_user_relationships_included() {
		$registry = get_registry();
		$registry->define_post_to_post( 'post', 'post', 'test-both-p2p' );
		$registry->define_post_to_user( 'post', 'test-both-p2u' );

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
