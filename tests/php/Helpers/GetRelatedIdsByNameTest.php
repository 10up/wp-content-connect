<?php
/**
 * Tests for get_related_ids_by_name() helper function.
 *
 * @package TenUp\ContentConnect\Tests\Helpers
 */

namespace TenUp\ContentConnect\Tests\Helpers;

use TenUp\ContentConnect\Tests\ContentConnectTestCase;
use function TenUp\ContentConnect\Helpers\get_related_ids_by_name;
use function TenUp\ContentConnect\Helpers\get_registry;

/**
 * Test cases for the get_related_ids_by_name() helper function.
 */
class GetRelatedIdsByNameTest extends ContentConnectTestCase {

	/**
	 * Tests that get_related_ids_by_name() returns all related IDs across different post types with the same relationship name.
	 *
	 * @return void
	 */
	public function test_returns_all_post_types() {
		$registry = get_registry();

		$post_car  = $registry->define_post_to_post( 'car', 'post', 'same-name' );
		$post_tire = $registry->define_post_to_post( 'tire', 'post', 'same-name' );

		$post_car->add_relationship( 1, 11 );
		$post_tire->add_relationship( 1, 21 );

		// Sanity check (restrict by specific relationship first)
		$this->assertSame( array( 11 ), $post_car->get_related_object_ids( 1 ) );
		$this->assertSame( array( 21 ), $post_tire->get_related_object_ids( 1 ) );

		$this->assertSame( array( 11, 21 ), get_related_ids_by_name( 1, 'same-name' ) );
	}
}
