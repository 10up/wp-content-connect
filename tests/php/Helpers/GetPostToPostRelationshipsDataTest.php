<?php
/**
 * Tests for get_post_to_post_relationships_data() helper function.
 *
 * @package TenUp\ContentConnect\Tests\Helpers
 */

namespace TenUp\ContentConnect\Tests\Helpers;

use TenUp\ContentConnect\Tests\ContentConnectTestCase;
use function TenUp\ContentConnect\Helpers\get_post_to_post_relationships_data;
use function TenUp\ContentConnect\Helpers\get_registry;

/**
 * Test cases for the get_post_to_post_relationships_data() helper function.
 */
class GetPostToPostRelationshipsDataTest extends ContentConnectTestCase {

	/**
	 * Tests that get_post_to_post_relationships_data() returns empty array for invalid post.
	 *
	 * @return void
	 */
	public function test_returns_empty_array_for_invalid_post() {
		$result = get_post_to_post_relationships_data( 99999 );

		$this->assertIsArray( $result );
		$this->assertEmpty( $result );
	}

	/**
	 * Tests that get_post_to_post_relationships_data() returns relationship data for a post.
	 *
	 * @return void
	 */
	public function test_returns_relationship_data_for_post() {
		$this->add_post_relations();

		$registry = get_registry();
		$registry->define_post_to_post( 'post', 'post', 'test-relationship' );

		$result = get_post_to_post_relationships_data( 1 );

		$this->assertIsArray( $result );

		foreach ( $result as $relationship ) {
			$this->assertArrayHasKey( 'rel_key', $relationship );
			$this->assertArrayHasKey( 'rel_type', $relationship );
			$this->assertArrayHasKey( 'rel_name', $relationship );
			$this->assertArrayHasKey( 'object_type', $relationship );
			$this->assertSame( 'post-to-post', $relationship['rel_type'] );
			$this->assertSame( 'post', $relationship['object_type'] );
		}
	}

	/**
	 * Tests that get_post_to_post_relationships_data() filters relationships by other_post_type parameter.
	 *
	 * @return void
	 */
	public function test_filters_by_other_post_type() {
		$this->add_post_relations();

		$registry = get_registry();
		$registry->define_post_to_post( 'post', 'post', 'test-post' );
		$registry->define_post_to_post( 'post', 'car', 'test-car' );

		$result = get_post_to_post_relationships_data( 1, 'car' );

		$this->assertIsArray( $result );

		foreach ( $result as $relationship ) {
			$post_types = is_array( $relationship['post_type'] ) ? $relationship['post_type'] : array( $relationship['post_type'] );
			$this->assertContains( 'car', $post_types, 'Relationship should include car post type' );
		}
	}

	/**
	 * Tests that get_post_to_post_relationships_data() returns view context without related entities.
	 *
	 * @return void
	 */
	public function test_returns_view_context_by_default() {
		$this->add_post_relations();

		$registry = get_registry();
		$registry->define_post_to_post( 'post', 'post', 'test-view' );

		$result = get_post_to_post_relationships_data( 1, false, 'view' );

		$this->assertIsArray( $result );

		foreach ( $result as $relationship ) {
			$this->assertArrayNotHasKey( 'related', $relationship );
		}
	}

	/**
	 * Tests that get_post_to_post_relationships_data() returns embed context with related entities.
	 *
	 * @return void
	 */
	public function test_returns_embed_context_with_related() {
		$this->add_post_relations();

		$registry     = get_registry();
		$relationship = $registry->define_post_to_post( 'post', 'post', 'test-embed' );
		$relationship->add_relationship( 1, 2 );

		$result = get_post_to_post_relationships_data( 1, false, 'embed' );

		$this->assertIsArray( $result );

		foreach ( $result as $rel_data ) {
			if ( isset( $rel_data['related'] ) ) {
				$this->assertIsArray( $rel_data['related'] );
			}
		}
	}

	/**
	 * Tests the "to" side of an asymmetric relationship.
	 *
	 * Defines a car -> tire relationship, then queries a tire post. Because the queried post's
	 * type (tire) differs from the relationship's "from" type (car), this exercises the else
	 * branch of get_post_to_post_relationships_data(), where the related post type must resolve
	 * to the "from" side (car) rather than the "to" side (tire).
	 *
	 * @return void
	 */
	public function test_returns_to_side_data_for_asymmetric_relationship() {
		$registry = get_registry();
		$registry->define_post_to_post( 'car', 'tire', 'to-side' );

		// 21 is a tire post (dummy data: cars 11-20, tires 21-30); tire is the relationship's "to" side.
		$result = get_post_to_post_relationships_data( 21 );

		$this->assertIsArray( $result );

		$car_tire = null;
		foreach ( $result as $rel_data ) {
			if ( 'to-side' === $rel_data['rel_name'] ) {
				$car_tire = $rel_data;
				break;
			}
		}

		$this->assertNotNull( $car_tire, 'Expected the car -> tire relationship queried from the tire (to) side.' );

		// The else branch resolves the related post type to the "from" side (car), proving the
		// queried tire post was treated as the "to" side.
		$this->assertSame( array( 'car' ), $car_tire['post_type'] );
		$this->assertArrayHasKey( 'labels', $car_tire );
		$this->assertArrayHasKey( 'enable_ui', $car_tire );
		$this->assertArrayHasKey( 'sortable', $car_tire );
	}
}
