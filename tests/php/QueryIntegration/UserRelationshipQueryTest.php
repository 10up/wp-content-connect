<?php
/**
 * Tests for UserRelationshipQuery class.
 *
 * @package TenUp\ContentConnect\Tests\QueryIntegration
 */

namespace TenUp\ContentConnect\Tests\QueryIntegration;

use TenUp\ContentConnect\Plugin;
use TenUp\ContentConnect\QueryIntegration\UserRelationshipQuery;
use TenUp\ContentConnect\Registry;
use TenUp\ContentConnect\Tests\ContentConnectTestCase;

/**
 * Test cases for UserRelationshipQuery.
 */
class UserRelationshipQueryTest extends ContentConnectTestCase {

	/**
	 * Sets up the test environment.
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		// Force a clear registry for each test
		$plugin           = Plugin::instance();
		$plugin->registry = new Registry();
		$plugin->registry->setup();
	}

	/**
	 * Tests relation parsing (AND/OR).
	 *
	 * @return void
	 */
	public function test_relation_parsing(): void {
		// With nothing, relation should default to and
		$query = new UserRelationshipQuery( array() );
		$this->assertEquals( 'AND', $query->relation );

		// Test with valid AND
		$query = new UserRelationshipQuery( array( 'relation' => 'AND' ) );
		$this->assertEquals( 'AND', $query->relation );

		// Test with valid OR
		$query = new UserRelationshipQuery( array( 'relation' => 'OR' ) );
		$this->assertEquals( 'OR', $query->relation );

		// Test with weird capitalization
		$query = new UserRelationshipQuery( array( 'relation' => 'aNd' ) ); // @spellchecker:disable-line
		$this->assertEquals( 'AND', $query->relation );
		$query = new UserRelationshipQuery( array( 'relation' => 'oR' ) ); // @spellchecker:disable-line
		$this->assertEquals( 'OR', $query->relation );

		// Test completely invalid defaults to AND
		$query = new UserRelationshipQuery( array( 'relationship' => 'any' ) );
		$this->assertEquals( 'AND', $query->relation );

		// Test empty defaults to AND
		$query = new UserRelationshipQuery( array( 'relationship' => '' ) );
		$this->assertEquals( 'AND', $query->relation );

		// test incorrect capitalization of the key
		// testing or, since that is not default, we'll know it worked
		$query = new UserRelationshipQuery( array( 'RELATION' => 'OR' ) );
		$this->assertEquals( 'OR', $query->relation );
	}

	/**
	 * Tests that top-level segments are reformatted into nested arrays correctly.
	 *
	 * @return void
	 */
	public function test_top_level_segments_are_reformatted_into_nested_arrays_correctly(): void {
		$query    = new UserRelationshipQuery(
			array(
				'related_to_post' => '25',
				'name'            => 'owner',
			)
		);
		$expected = array(
			array(
				'related_to_post' => '25',
				'name'            => 'owner',
			),
		);
		$this->assertEquals( $expected, $query->segments );

		// Test top level keys AND segments in arrays
		$query    = new UserRelationshipQuery(
			array(
				'related_to_post' => '25',
				'name'            => 'owner',
				array(
					'related_to_post' => '50',
					'name'            => 'contrib',
				),
			)
		);
		$expected = array(
			array(
				'related_to_post' => '25',
				'name'            => 'owner',
			),
			array(
				'related_to_post' => '50',
				'name'            => 'contrib',
			),
		);
		$this->assertEquals( $expected, $query->segments );
	}

	/**
	 * Tests that invalid segments are recognized as invalid.
	 *
	 * @return void
	 */
	public function test_invalid_segments_are_recognized_as_invalid(): void {
		$query = new UserRelationshipQuery( array() );

		$this->assertFalse( $query->is_valid_segment( array() ) );
		$this->assertFalse( $query->is_valid_segment( array( 'name' => 'owner' ) ) );
		$this->assertFalse( $query->is_valid_segment( array( 'related_to_post' ) ) );
	}

	/**
	 * Tests that valid segments are recognized as valid.
	 *
	 * @return void
	 */
	public function test_valid_segments_are_recognized_as_valid(): void {
		$query = new UserRelationshipQuery( array() );

		$this->assertTrue(
			$query->is_valid_segment(
				array(
					'name'            => 'owner',
					'related_to_post' => 45,
				)
			)
		);
	}

	/**
	 * Tests that valid segments are tracked correctly.
	 *
	 * @return void
	 */
	public function test_valid_segments_are_tracked(): void {
		$query = new UserRelationshipQuery( array() );
		$this->assertFalse( $query->has_valid_segments() );

		$query = new UserRelationshipQuery(
			array(
				'name'            => 'owner',
				'related_to_post' => 25,
			)
		);
		$this->assertTrue( $query->has_valid_segments() );

		$query = new UserRelationshipQuery(
			array(
				array(
					'name'            => 'contrib',
					'related_to_post' => 25,
				),
			)
		);
		$this->assertTrue( $query->has_valid_segments() );
	}

	/**
	 * Tests WHERE clause generation.
	 *
	 * @return void
	 */
	public function test_generate_where_clause(): void {
		// Should return nothing, since the relationship isn't defined yet
		$query    = new UserRelationshipQuery(
			array(
				'name'            => 'owner',
				'related_to_post' => 1,
			)
		);
		$expected = '';
		$this->assertEquals( $expected, $query->where );

		$registry = Plugin::instance()->get_registry();
		$registry->define_post_to_user( 'post', 'owner' );
		$registry->define_post_to_user( 'post', 'contrib' );

		// If we end up with all invalid segments, we should have no changes to where
		$query    = new UserRelationshipQuery( array() );
		$expected = '';
		$this->assertEquals( $expected, $query->where );

		$query    = new UserRelationshipQuery(
			array(
				'name'            => 'owner',
				'related_to_post' => 1,
			)
		);
		$expected = " and ((p2u1.post_id = 1 and p2u1.name = 'owner'))";
		$this->assertEquals( $expected, $query->where );

		$query    = new UserRelationshipQuery(
			array(
				array(
					'name'            => 'owner',
					'related_to_post' => 2,
				),
				array(
					'name'            => 'owner',
					'related_to_post' => 3,
				),
				'relation' => 'OR',
			)
		);
		$expected = " and ((p2u1.post_id = 2 and p2u1.name = 'owner') OR (p2u1.post_id = 3 and p2u1.name = 'owner'))";
		$this->assertEquals( $expected, $query->where );

		$query    = new UserRelationshipQuery(
			array(
				array(
					'name'            => 'owner',
					'related_to_post' => 2,
				),
				array(
					'name'            => 'contrib',
					'related_to_post' => 4,
				),
				'relation' => 'AND',
			)
		);
		$expected = " and ((p2u1.post_id = 2 and p2u1.name = 'owner') AND (p2u2.post_id = 4 and p2u2.name = 'contrib'))";
		$this->assertEquals( $expected, $query->where );
	}

	/**
	 * Tests JOIN clause generation.
	 *
	 * @return void
	 */
	public function test_generate_join_clause(): void {
		global $wpdb;

		// Should return nothing, since the relationship isn't defined yet
		$query    = new UserRelationshipQuery(
			array(
				'name'            => 'owner',
				'related_to_post' => 1,
			)
		);
		$expected = '';
		$this->assertEquals( $expected, $query->join );

		$registry = Plugin::instance()->get_registry();
		$registry->define_post_to_user( 'post', 'owner' );
		$registry->define_post_to_user( 'post', 'contrib' );

		$query    = new UserRelationshipQuery(
			array(
				'name'            => 'owner',
				'related_to_post' => 1,
			)
		);
		$expected = " left join {$wpdb->prefix}post_to_user as p2u1 on {$wpdb->users}.ID = p2u1.user_id";
		$this->assertEquals( $expected, $query->join );

		$query    = new UserRelationshipQuery(
			array(
				array(
					'name'            => 'owner',
					'related_to_post' => 2,
				),
				array(
					'name'            => 'owner',
					'related_to_post' => 3,
				),
				'relation' => 'OR',
			)
		);
		$expected = " left join {$wpdb->prefix}post_to_user as p2u1 on {$wpdb->users}.ID = p2u1.user_id";
		$this->assertEquals( $expected, $query->join );

		$query     = new UserRelationshipQuery(
			array(
				array(
					'name'            => 'owner',
					'related_to_post' => 2,
				),
				array(
					'name'            => 'contrib',
					'related_to_post' => 4,
				),
				'relation' => 'AND',
			)
		);
		$expected  = " left join {$wpdb->prefix}post_to_user as p2u1 on {$wpdb->users}.ID = p2u1.user_id";
		$expected .= " left join {$wpdb->prefix}post_to_user as p2u2 on {$wpdb->users}.ID = p2u2.user_id";
		$this->assertEquals( $expected, $query->join );
	}
}
