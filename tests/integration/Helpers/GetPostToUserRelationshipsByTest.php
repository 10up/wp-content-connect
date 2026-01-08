<?php

namespace TenUp\ContentConnect\Tests\Integration\Helpers;

use function TenUp\ContentConnect\Helpers\get_registry;
use function TenUp\ContentConnect\Helpers\get_post_to_user_relationships_by;
use TenUp\ContentConnect\Tests\Integration\ContentConnectTestCase;

class GetPostToUserRelationshipsByTest extends ContentConnectTestCase {

	public function test_returns_all_relationships_with_any() {
		$registry = get_registry();

		$registry->define_post_to_user( 'post', 'test-owner' );
		$registry->define_post_to_user( 'post', 'test-contrib' );
		$registry->define_post_to_user( 'car', 'test-owner' );

		$relationships = get_post_to_user_relationships_by( 'any' );

		$this->assertIsArray( $relationships );
		$this->assertGreaterThanOrEqual( 3, count( $relationships ) );
	}

	public function test_returns_relationship_by_key() {
		$registry = get_registry();

		$relationship = $registry->define_post_to_user( 'post', 'test-key' );
		$key = $registry->get_relationship_key( 'post', 'user', 'test-key' );

		$result = get_post_to_user_relationships_by( 'key', $key );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( $key, $result );
		$this->assertSame( $relationship, $result[ $key ] );
	}

	public function test_returns_false_for_invalid_key() {
		$result = get_post_to_user_relationships_by( 'key', 'invalid-key' );

		$this->assertFalse( $result );
	}

	public function test_filters_by_post_type() {
		$registry = get_registry();

		$registry->define_post_to_user( 'post', 'test-post-owner' );
		$registry->define_post_to_user( 'post', 'test-post-contrib' );
		$registry->define_post_to_user( 'car', 'test-car-owner' );

		$relationships = get_post_to_user_relationships_by( 'post_type', 'post' );

		$this->assertIsArray( $relationships );
		foreach ( $relationships as $relationship ) {
			$this->assertEquals( 'post', $relationship->post_type );
		}
	}

}

