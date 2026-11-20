<?php
/**
 * Tests for get_post_to_user_relationships_by() helper function.
 *
 * @package TenUp\ContentConnect\Tests\Helpers
 */

namespace TenUp\ContentConnect\Tests\Helpers;

use TenUp\ContentConnect\Tests\ContentConnectTestCase;
use function TenUp\ContentConnect\Helpers\get_post_to_user_relationships_by;
use function TenUp\ContentConnect\Helpers\get_registry;

/**
 * Test cases for the get_post_to_user_relationships_by() helper function.
 */
class GetPostToUserRelationshipsByTest extends ContentConnectTestCase {

	/**
	 * Tests that get_post_to_user_relationships_by() returns all relationships when field is 'any'.
	 *
	 * @return void
	 */
	public function test_returns_all_relationships_with_any() {
		$registry = get_registry();

		$registry->define_post_to_user( 'post', 'test-owner' );
		$registry->define_post_to_user( 'post', 'test-contrib' );
		$registry->define_post_to_user( 'car', 'test-owner' );

		$relationships = get_post_to_user_relationships_by( 'any' );

		$this->assertIsArray( $relationships );
		$this->assertGreaterThanOrEqual( 3, count( $relationships ) );
	}

	/**
	 * Tests that get_post_to_user_relationships_by() returns a relationship by key.
	 *
	 * @return void
	 */
	public function test_returns_relationship_by_key() {
		$registry = get_registry();

		$relationship = $registry->define_post_to_user( 'post', 'test-key' );
		$key          = $registry->get_relationship_key( 'post', 'user', 'test-key' );

		$result = get_post_to_user_relationships_by( 'key', $key );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( $key, $result );
		$this->assertSame( $relationship, $result[ $key ] );
	}

	/**
	 * Tests that get_post_to_user_relationships_by() returns false for invalid key.
	 *
	 * @return void
	 */
	public function test_returns_false_for_invalid_key() {
		$result = get_post_to_user_relationships_by( 'key', 'invalid-key' );

		$this->assertFalse( $result );
	}

	/**
	 * Tests that get_post_to_user_relationships_by() filters relationships by post type.
	 *
	 * @return void
	 */
	public function test_filters_by_post_type() {
		$registry = get_registry();

		$registry->define_post_to_user( 'post', 'test-post-owner' );
		$registry->define_post_to_user( 'post', 'test-post-contrib' );
		$registry->define_post_to_user( 'car', 'test-car-owner' );

		$relationships = get_post_to_user_relationships_by( 'post_type', 'post' );

		$this->assertIsArray( $relationships );

		foreach ( $relationships as $relationship ) {
			$this->assertSame( 'post', $relationship->post_type );
		}
	}
}
