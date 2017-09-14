<?php

namespace TenUp\P2P\Tests\Unit\Relationships;

use TenUp\P2P\Plugin;
use TenUp\P2P\Relationships\ManyToMany;

class ManyToManyTest extends \PHPUnit_Framework_TestCase {

	public function setUp() {
		global $wpdb;

		$wpdb->query( "delete from {$wpdb->prefix}post_to_post" );
	}

	public function tearDown() {
		global $wpdb;

		$wpdb->query( "delete from {$wpdb->prefix}post_to_post" );
	}

	/**
	 * @return ManyToMany
	 */
	public function get_post_to_post_object() {
		$registry = Plugin::instance()->get_registry();

		$p2p = $registry->get_relationship( 'post', 'post' );
		if ( $p2p ) {
			return $p2p;
		}

		return $registry->add_many_to_many( 'post', 'post' );
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

	public function test_related_object_ids_returns_correctly() {
		// @todo need actual posts for this to work properly
	}

	public function test_related_object_ids_returns_only_correct_post_types() {
		// @todo need known test data for this
	}

}
