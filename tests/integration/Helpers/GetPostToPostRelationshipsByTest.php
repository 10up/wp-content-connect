<?php

namespace TenUp\ContentConnect\Tests\Integration\Helpers;

use function TenUp\ContentConnect\Helpers\get_registry;
use function TenUp\ContentConnect\Helpers\get_post_to_post_relationships_by;
use TenUp\ContentConnect\Tests\Integration\ContentConnectTestCase;

class GetPostToPostRelationshipsByTest extends ContentConnectTestCase {

	public function test_returns_all_relationships_with_any() {
		$registry = get_registry();

		$registry->define_post_to_post( 'post', 'post', 'test-basic' );
		$registry->define_post_to_post( 'post', 'car', 'test-car' );
		$registry->define_post_to_post( 'car', 'tire', 'test-tire' );

		$relationships = get_post_to_post_relationships_by( 'any' );

		$this->assertIsArray( $relationships );
		$this->assertGreaterThanOrEqual( 3, count( $relationships ) );
	}

	public function test_returns_relationship_by_key() {
		$registry = get_registry();

		$relationship = $registry->define_post_to_post( 'post', 'car', 'test-key' );
		$key = $registry->get_relationship_key( 'post', 'car', 'test-key' );

		$result = get_post_to_post_relationships_by( 'key', $key );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( $key, $result );
		$this->assertSame( $relationship, $result[ $key ] );
	}

	public function test_returns_false_for_invalid_key() {
		$result = get_post_to_post_relationships_by( 'key', 'invalid-key' );

		$this->assertFalse( $result );
	}

	public function test_filters_by_post_type() {
		$registry = get_registry();

		$registry->define_post_to_post( 'post', 'post', 'test-post-post' );
		$registry->define_post_to_post( 'post', 'car', 'test-post-car' );
		$registry->define_post_to_post( 'car', 'tire', 'test-car-tire' );

		$relationships = get_post_to_post_relationships_by( 'post_type', 'post' );

		$this->assertIsArray( $relationships );
		foreach ( $relationships as $relationship ) {
			$relationship_to = is_array( $relationship->to ) ? $relationship->to : array( $relationship->to );
			$this->assertTrue(
				$relationship->from === 'post' || in_array( 'post', $relationship_to, true ),
				'Relationship should involve post type "post"'
			);
		}
	}

	public function test_filters_by_from() {
		$registry = get_registry();

		$registry->define_post_to_post( 'post', 'post', 'test-from-post' );
		$registry->define_post_to_post( 'post', 'car', 'test-from-post-car' );
		$registry->define_post_to_post( 'car', 'tire', 'test-from-car-tire' );

		$relationships = get_post_to_post_relationships_by( 'from', 'post' );

		$this->assertIsArray( $relationships );
		foreach ( $relationships as $relationship ) {
			$this->assertEquals( 'post', $relationship->from );
		}
	}

	public function test_filters_by_to() {
		$registry = get_registry();

		$registry->define_post_to_post( 'post', 'car', 'test-to-car' );
		$registry->define_post_to_post( 'car', 'tire', 'test-to-tire' );
		$registry->define_post_to_post( 'post', 'tire', 'test-to-tire-2' );

		$relationships = get_post_to_post_relationships_by( 'to', 'tire' );

		$this->assertIsArray( $relationships );
		foreach ( $relationships as $relationship ) {
			$relationship_to = is_array( $relationship->to ) ? $relationship->to : array( $relationship->to );
			$this->assertTrue( in_array( 'tire', $relationship_to, true ) );
		}
	}

}

