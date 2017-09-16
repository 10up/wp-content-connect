<?php

namespace TenUp\P2P\Tests\QueryIntegration;

use TenUp\P2P\Plugin;
use TenUp\P2P\QueryIntegration\UserRelationshipQuery;
use TenUp\P2P\Registry;
use TenUp\P2P\Tests\P2PTestCase;

class UserRelationshipQueryTest extends P2PTestCase {

	public function setUp() {
		parent::setUp();

		// Force a clear registry for each test
		$plugin = Plugin::instance();
		$plugin->registry = new Registry();
		$plugin->registry->setup();
	}

	public function test_relation_parsing() {
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
		$query = new UserRelationshipQuery( array( 'relation' => 'aNd' ) );
		$this->assertEquals( 'AND', $query->relation );
		$query = new UserRelationshipQuery( array( 'relation' => 'oR' ) );
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

	public function test_top_level_segments_are_reformatted_into_nested_arrays_correctly() {
		$query = new UserRelationshipQuery( array(
			'related_to_post' => '25',
			'type' => 'owner',
		) );
		$expected = array(
			array(
				'related_to_post' => '25',
				'type' => 'owner',
			)
		);
		$this->assertEquals( $expected, $query->segments );


		// Test top level keys AND segments in arrays
		$query = new UserRelationshipQuery( array(
			'related_to_post' => '25',
			'type' => 'owner',
			array(
				'related_to_post' => '50',
				'type' => 'contrib',
			),
		) );
		$expected = array(
			array(
				'related_to_post' => '25',
				'type' => 'owner',
			),
			array(
				'related_to_post' => '50',
				'type' => 'contrib',
			),
		);
		$this->assertEquals( $expected, $query->segments );
	}

	public function test_invalid_segments_are_recognized_as_invalid() {
		$query = new UserRelationshipQuery( array() );

		$this->assertFalse( $query->is_valid_segment( array() ) );
		$this->assertFalse( $query->is_valid_segment( array( 'type' => 'owner' ) ) );
		$this->assertFalse( $query->is_valid_segment( array( 'related_to_post' ) ) );
	}

	public function test_valid_segments_are_recognized_as_valid() {
		$query = new UserRelationshipQuery( array() );

		$this->assertTrue( $query->is_valid_segment( array(
			'type' => 'owner',
			'related_to_post' => 45,
		) ) );
	}

	public function test_valid_segments_are_tracked() {
		$query = new UserRelationshipQuery( array() );
		$this->assertFalse( $query->has_valid_segments() );

		$query = new UserRelationshipQuery( array(
			'type' => 'owner',
			'related_to_post' => 25,
		));
		$this->assertTrue( $query->has_valid_segments() );

		$query = new UserRelationshipQuery( array(
			array(
				'type' => 'contrib',
				'related_to_post' => 25,
			)
		) );
		$this->assertTrue( $query->has_valid_segments() );
	}

	public function test_generate_where_clause() {
		// Should return nothing, since the relationship isn't defined yet
		$query = new UserRelationshipQuery(array(
			'type' => 'owner',
			'related_to_post' => 1,
		));
		$expected = '';
		$this->assertEquals( $expected, $query->where );


		$registry = Plugin::instance()->get_registry();
		$registry->define_post_to_user( 'post', 'owner' );
		$registry->define_post_to_user( 'post', 'contrib' );


		// If we end up with all invalid segments, we should have no changes to where
		$query = new UserRelationshipQuery( array() );
		$expected = '';
		$this->assertEquals( $expected, $query->where );


		$query = new UserRelationshipQuery( array(
			'type' => 'owner',
			'related_to_post' => 1
		) );
		$expected = " and ((p2u1.post_id = 1 and p2u1.type = 'owner'))";
		$this->assertEquals( $expected, $query->where );


		$query = new UserRelationshipQuery( array(
			array(
				'type' => 'owner',
				'related_to_post' => 2
			),
			array(
				'type' => 'owner',
				'related_to_post' => 3,
			),
			'relation' => 'OR',
		) );
		$expected = " and ((p2u1.post_id = 2 and p2u1.type = 'owner') OR (p2u1.post_id = 3 and p2u1.type = 'owner'))";
		$this->assertEquals( $expected, $query->where );


		$query = new UserRelationshipQuery( array(
			array(
				'type' => 'owner',
				'related_to_post' => 2
			),
			array(
				'type' => 'contrib',
				'related_to_post' => 4,
			),
			'relation' => 'AND',
		) );
		$expected = " and ((p2u1.post_id = 2 and p2u1.type = 'owner') AND (p2u2.post_id = 4 and p2u2.type = 'contrib'))";
		$this->assertEquals( $expected, $query->where );
	}

	public function test_generate_join_clause() {
		global $wpdb;

		// Should return nothing, since the relationship isn't defined yet
		$query = new UserRelationshipQuery(array(
			'type' => 'owner',
			'related_to_post' => 1,
		));
		$expected = '';
		$this->assertEquals( $expected, $query->join );


		$registry = Plugin::instance()->get_registry();
		$registry->define_post_to_user('post', 'owner' );
		$registry->define_post_to_user('post', 'contrib' );


		$query = new UserRelationshipQuery( array(
			'type' => 'owner',
			'related_to_post' => 1
		) );
		$expected = " left join {$wpdb->prefix}post_to_user as p2u1 on {$wpdb->users}.ID = p2u1.user_id";
		$this->assertEquals( $expected, $query->join );


		$query = new UserRelationshipQuery( array(
			array(
				'type' => 'owner',
				'related_to_post' => 2
			),
			array(
				'type' => 'owner',
				'related_to_post' => 3,
			),
			'relation' => 'OR',
		) );
		$expected = " left join {$wpdb->prefix}post_to_user as p2u1 on {$wpdb->users}.ID = p2u1.user_id";
		$this->assertEquals( $expected, $query->join );


		$query = new UserRelationshipQuery( array(
			array(
				'type' => 'owner',
				'related_to_post' => 2
			),
			array(
				'type' => 'contrib',
				'related_to_post' => 4,
			),
			'relation' => 'AND',
		) );
		$expected =  " left join {$wpdb->prefix}post_to_user as p2u1 on {$wpdb->users}.ID = p2u1.user_id";
		$expected .= " left join {$wpdb->prefix}post_to_user as p2u2 on {$wpdb->users}.ID = p2u2.user_id";
		$this->assertEquals( $expected, $query->join );
	}

}
