<?php

namespace TenUp\P2P\Tests\Relationships;

use TenUp\P2P\Relationships\ManyToMany;
use TenUp\P2P\Tests\P2PTestCase;

class ManyToManyTest extends P2PTestCase {

	public function setUp() {
		global $wpdb;

		$wpdb->query( "delete from {$wpdb->prefix}post_to_post" );

		parent::setUp();
	}

	public function tearDown() {
		parent::tearDown();
	}

	public function test_add_relationship() {
		global $wpdb;
		$p2p = $this->get_post_to_post_object();

		// Make sure we don't already have this in the DB
		$this->assertEquals( 0, $wpdb->query( "select * from {$wpdb->prefix}post_to_post where id1='1' and id2='2'") );
		$this->assertEquals( 0, $wpdb->query( "select * from {$wpdb->prefix}post_to_post where id1='2' and id2='1'") );

		$p2p->add_relationship( '1', '2' );
		$this->assertEquals( 1, $wpdb->query( "select * from {$wpdb->prefix}post_to_post where id1='1' and id2='2'") );
		$this->assertEquals( 1, $wpdb->query( "select * from {$wpdb->prefix}post_to_post where id1='2' and id2='1'") );
	}

	public function test_adding_duplicates() {
		global $wpdb;
		$p2p = $this->get_post_to_post_object();

		// Making sure we don't add duplicates
		$p2p->add_relationship( '1', '2' );
		$p2p->add_relationship( '1', '2' );
		$this->assertEquals( 1, $wpdb->query( "select * from {$wpdb->prefix}post_to_post where id1='1' and id2='2'") );
		$this->assertEquals( 1, $wpdb->query( "select * from {$wpdb->prefix}post_to_post where id1='2' and id2='1'") );

		// Making sure that order doesn't matter / duplicates
		$p2p->add_relationship( 2, 1 );

		$this->assertEquals( 1, $wpdb->query( "select * from {$wpdb->prefix}post_to_post where id1='1' and id2='2'") );
		$this->assertEquals( 1, $wpdb->query( "select * from {$wpdb->prefix}post_to_post where id1='2' and id2='1'") );
	}

	public function test_delete_relationship() {
		global $wpdb;
		$p2p = $this->get_post_to_post_object();

		// Make sure we're in a known state of having a relationship in the DB
		$p2p->add_relationship( '1', '2' );
		$this->assertEquals( 1, $wpdb->query( "select * from {$wpdb->prefix}post_to_post where id1='1' and id2='2'") );
		$this->assertEquals( 1, $wpdb->query( "select * from {$wpdb->prefix}post_to_post where id1='2' and id2='1'") );

		$p2p->delete_relationship( 1, 2 );
		$this->assertEquals( 0, $wpdb->query( "select * from {$wpdb->prefix}post_to_post where id1='1' and id2='2'") );
		$this->assertEquals( 0, $wpdb->query( "select * from {$wpdb->prefix}post_to_post where id1='2' and id2='1'") );
	}

	public function test_delete_flipped_order() {
		global $wpdb;
		$p2p = $this->get_post_to_post_object();

		// Make sure we're in a known state of having a relationship in the DB
		$p2p->add_relationship( '1', '2' );
		$this->assertEquals( 1, $wpdb->query( "select * from {$wpdb->prefix}post_to_post where id1='1' and id2='2'") );
		$this->assertEquals( 1, $wpdb->query( "select * from {$wpdb->prefix}post_to_post where id1='2' and id2='1'") );

		$p2p->delete_relationship( 2, 1 );
		$this->assertEquals( 0, $wpdb->query( "select * from {$wpdb->prefix}post_to_post where id1='1' and id2='2'") );
		$this->assertEquals( 0, $wpdb->query( "select * from {$wpdb->prefix}post_to_post where id1='2' and id2='1'") );
	}

	public function test_delete_only_deletes_correct_records() {
		global $wpdb;
		$p2p = $this->get_post_to_post_object();

		$keep_pairs = array(
			array( 1, 2 ),
			array( 1, 5 ),
			array( 2, 10 ),
			array( 2, 15 ),
		);

		$delete_pairs = array(
			array( 1, 10 ),
		);

		$pairs = array_merge( $keep_pairs, $delete_pairs );

		foreach ( $pairs as $pair ) {
			$p2p->add_relationship( $pair[0], $pair[1] );
		}

		foreach( $pairs as $pair ) {
			$this->assertEquals( 1, $wpdb->query( "select * from {$wpdb->prefix}post_to_post where id1='{$pair[0]}' and id2='{$pair[1]}'") );
			$this->assertEquals( 1, $wpdb->query( "select * from {$wpdb->prefix}post_to_post where id1='{$pair[1]}' and id2='{$pair[0]}'") );
		}

		$p2p->delete_relationship( 1, 10 );

		foreach( $keep_pairs as $pair ) {
			$this->assertEquals( 1, $wpdb->query( "select * from {$wpdb->prefix}post_to_post where id1='{$pair[0]}' and id2='{$pair[1]}'") );
			$this->assertEquals( 1, $wpdb->query( "select * from {$wpdb->prefix}post_to_post where id1='{$pair[1]}' and id2='{$pair[0]}'") );
		}

		foreach( $delete_pairs as $pair ) {
			$this->assertEquals( 0, $wpdb->query( "select * from {$wpdb->prefix}post_to_post where id1='{$pair[0]}' and id2='{$pair[1]}'") );
			$this->assertEquals( 0, $wpdb->query( "select * from {$wpdb->prefix}post_to_post where id1='{$pair[1]}' and id2='{$pair[0]}'") );
		}
	}

	public function test_that_posts_relate_to_posts() {
		$this->add_known_relations();

		/** @var ManyToMany $pp */
		$pp = $this->get_relationship_object( 'post', 'post' );

		$related = $pp->get_related_object_ids( 1 );

		// Only post type 'post'
		$this->assertEquals( array( 2, 3 ), $related );

		// This would be true if it was ignoring post type
		$this->assertNotEquals( array( 2, 3, 5 ), $related );

		// Make sure that we can query starting from the inverse of the way we added the relationship
		$this->assertEquals( array( 1 ), $pp->get_related_object_ids( 3 ) );
	}

	public function test_that_posts_relate_to_cars() {
		$this->add_known_relations();

		/** @var ManyToMany $pp */
		$pp = $this->get_relationship_object( 'post', 'car' );

		$this->assertEquals( array( 5 ), $pp->get_related_object_ids( 1 ) );

		// Should return nothing, because wrong CPTs
		$this->assertEquals( array(), $pp->get_related_object_ids( 12 ) );
	}

	public function test_that_posts_relate_to_tires() {
		$this->add_known_relations();

		/** @var ManyToMany $pp */
		$pp = $this->get_relationship_object( 'post', 'tire' );

		$this->assertEquals( array( 11 ), $pp->get_related_object_ids( 3 ) );
		$this->assertEquals( array( 3 ), $pp->get_related_object_ids( 11 ) );
	}

	public function test_that_cars_relate_to_tires() {
		$this->add_known_relations();

		/** @var ManyToMany $pp */
		$pp = $this->get_relationship_object( 'car', 'tire' );

		$this->assertEquals( array( 9, 10 ), $pp->get_related_object_ids( 5 ) );
		$this->assertEquals( array( 5 ), $pp->get_related_object_ids( 9 ) );
		$this->assertEquals( array( 5 ), $pp->get_related_object_ids( 10 ) );
	}

}
