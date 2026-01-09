<?php
/**
 * Tests for get_post_relationships_data() helper function.
 *
 * @package TenUp\ContentConnect\Tests\Integration\Helpers
 */

namespace TenUp\ContentConnect\Tests\Integration\Helpers;

use TenUp\ContentConnect\Tests\Integration\ContentConnectTestCase;
use function TenUp\ContentConnect\Helpers\get_post_relationships_data;
use function TenUp\ContentConnect\Helpers\get_registry;

/**
 * Test cases for the get_post_relationships_data() helper function.
 */
class GetPostRelationshipsDataTest extends ContentConnectTestCase {

	/**
	 * Tests that get_post_relationships_data() returns empty array for invalid post.
	 *
	 * @return void
	 */
	public function test_returns_empty_array_for_invalid_post() {
		$result = get_post_relationships_data( 99999 );

		$this->assertIsArray( $result );
		$this->assertEmpty( $result );
	}

	/**
	 * Tests that get_post_relationships_data() returns both post-to-post and post-to-user relationships when rel_type is 'any'.
	 *
	 * @return void
	 */
	public function test_returns_both_types_with_any() {
		$this->add_post_relations();
		$this->add_user_relations();

		$registry = get_registry();
		$registry->define_post_to_post( 'post', 'post', 'test-p2p' );
		$registry->define_post_to_user( 'post', 'test-p2u' );

		$result = get_post_relationships_data( 1, 'any' );

		$this->assertIsArray( $result );
		$this->assertGreaterThanOrEqual( 1, count( $result ) );
	}

	/**
	 * Tests that get_post_relationships_data() returns only post-to-post relationships when rel_type is 'post-to-post'.
	 *
	 * @return void
	 */
	public function test_returns_only_post_to_post_with_rel_type() {
		$this->add_post_relations();
		$this->add_user_relations();

		$registry = get_registry();
		$registry->define_post_to_post( 'post', 'post', 'test-p2p' );
		$registry->define_post_to_user( 'post', 'test-p2u' );

		$result = get_post_relationships_data( 1, 'post-to-post' );

		$this->assertIsArray( $result );

		foreach ( $result as $relationship ) {
			$this->assertSame( 'post-to-post', $relationship['rel_type'] );
		}
	}

	/**
	 * Tests that get_post_relationships_data() returns only post-to-user relationships when rel_type is 'post-to-user'.
	 *
	 * @return void
	 */
	public function test_returns_only_post_to_user_with_rel_type() {
		$this->add_post_relations();
		$this->add_user_relations();

		$registry = get_registry();
		$registry->define_post_to_post( 'post', 'post', 'test-p2p' );
		$registry->define_post_to_user( 'post', 'test-p2u' );

		$result = get_post_relationships_data( 1, 'post-to-user' );

		$this->assertIsArray( $result );

		foreach ( $result as $relationship ) {
			$this->assertSame( 'post-to-user', $relationship['rel_type'] );
		}
	}

	/**
	 * Tests that get_post_relationships_data() returns view context without related entities.
	 *
	 * @return void
	 */
	public function test_returns_view_context_by_default() {
		$this->add_post_relations();

		$registry = get_registry();
		$registry->define_post_to_post( 'post', 'post', 'test-view' );

		$result = get_post_relationships_data( 1, 'post-to-post', false, 'view' );

		$this->assertIsArray( $result );

		foreach ( $result as $relationship ) {
			$this->assertArrayNotHasKey( 'related', $relationship );
		}
	}

	/**
	 * Tests that get_post_relationships_data() returns embed context with related entities.
	 *
	 * @return void
	 */
	public function test_returns_embed_context_with_related() {
		$this->add_post_relations();

		$registry = get_registry();
		$registry->define_post_to_post( 'post', 'post', 'test-embed' );

		$result = get_post_relationships_data( 1, 'post-to-post', false, 'embed' );

		$this->assertIsArray( $result );

		foreach ( $result as $relationship ) {
			if ( isset( $relationship['related'] ) ) {
				$this->assertIsArray( $relationship['related'] );
			}
		}
	}

	/**
	 * Tests that get_post_relationships_data() filters relationships by other_post_type parameter.
	 *
	 * @return void
	 */
	public function test_filters_by_other_post_type() {
		$this->add_post_relations();

		$registry = get_registry();
		$registry->define_post_to_post( 'post', 'post', 'test-post' );
		$registry->define_post_to_post( 'post', 'car', 'test-car' );

		$result = get_post_relationships_data( 1, 'any', 'car' );

		$this->assertIsArray( $result );

		foreach ( $result as $relationship ) {
			if ( 'post-to-post' === $relationship['rel_type'] ) {
				$post_types = is_array( $relationship['post_type'] ) ? $relationship['post_type'] : array( $relationship['post_type'] );
				$this->assertContains( 'car', $post_types, 'Post-to-post relationship should include car post type' );
			}
		}
	}
}
