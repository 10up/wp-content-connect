<?php

namespace TenUp\ContentConnect\Tests\Integration\Helpers;

use function TenUp\ContentConnect\Helpers\get_registry;
use function TenUp\ContentConnect\Helpers\get_post_to_user_relationships_data;
use TenUp\ContentConnect\Tests\Integration\ContentConnectTestCase;

class GetPostToUserRelationshipsDataTest extends ContentConnectTestCase {

	public function test_returns_empty_array_for_invalid_post() {
		$result = get_post_to_user_relationships_data( 99999 );

		$this->assertIsArray( $result );
		$this->assertEmpty( $result );
	}

	public function test_returns_relationship_data_for_post() {
		$this->add_user_relations();

		$registry = get_registry();
		$registry->define_post_to_user( 'post', 'test-relationship' );

		$result = get_post_to_user_relationships_data( 1 );

		$this->assertIsArray( $result );
		foreach ( $result as $relationship ) {
			$this->assertArrayHasKey( 'rel_key', $relationship );
			$this->assertArrayHasKey( 'rel_type', $relationship );
			$this->assertArrayHasKey( 'rel_name', $relationship );
			$this->assertArrayHasKey( 'object_type', $relationship );
			$this->assertEquals( 'post-to-user', $relationship['rel_type'] );
			$this->assertEquals( 'user', $relationship['object_type'] );
		}
	}

	public function test_returns_view_context_by_default() {
		$this->add_user_relations();

		$registry = get_registry();
		$registry->define_post_to_user( 'post', 'test-view' );

		$result = get_post_to_user_relationships_data( 1, 'view' );

		$this->assertIsArray( $result );
		foreach ( $result as $relationship ) {
			$this->assertArrayNotHasKey( 'related', $relationship );
		}
	}

	public function test_returns_embed_context_with_related() {
		$this->add_user_relations();

		$registry = get_registry();
		$relationship = $registry->define_post_to_user( 'post', 'test-embed' );
		$relationship->add_relationship( 1, 1 );

		$result = get_post_to_user_relationships_data( 1, 'embed' );

		$this->assertIsArray( $result );
		foreach ( $result as $rel_data ) {
			if ( isset( $rel_data['related'] ) ) {
				$this->assertIsArray( $rel_data['related'] );
			}
		}
	}

}

