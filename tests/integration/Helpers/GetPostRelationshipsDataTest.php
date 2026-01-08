<?php

namespace TenUp\ContentConnect\Tests\Integration\Helpers;

use function TenUp\ContentConnect\Helpers\get_registry;
use function TenUp\ContentConnect\Helpers\get_post_relationships_data;
use TenUp\ContentConnect\Tests\Integration\ContentConnectTestCase;

class GetPostRelationshipsDataTest extends ContentConnectTestCase {

	public function test_returns_empty_array_for_invalid_post() {
		$result = get_post_relationships_data( 99999 );

		$this->assertIsArray( $result );
		$this->assertEmpty( $result );
	}

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

	public function test_returns_only_post_to_post_with_rel_type() {
		$this->add_post_relations();
		$this->add_user_relations();

		$registry = get_registry();
		$registry->define_post_to_post( 'post', 'post', 'test-p2p' );
		$registry->define_post_to_user( 'post', 'test-p2u' );

		$result = get_post_relationships_data( 1, 'post-to-post' );

		$this->assertIsArray( $result );
		foreach ( $result as $relationship ) {
			$this->assertEquals( 'post-to-post', $relationship['rel_type'] );
		}
	}

	public function test_returns_only_post_to_user_with_rel_type() {
		$this->add_post_relations();
		$this->add_user_relations();

		$registry = get_registry();
		$registry->define_post_to_post( 'post', 'post', 'test-p2p' );
		$registry->define_post_to_user( 'post', 'test-p2u' );

		$result = get_post_relationships_data( 1, 'post-to-user' );

		$this->assertIsArray( $result );
		foreach ( $result as $relationship ) {
			$this->assertEquals( 'post-to-user', $relationship['rel_type'] );
		}
	}

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
				$this->assertTrue( in_array( 'car', $post_types, true ) );
			}
		}
	}

}

