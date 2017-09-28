<?php

namespace TenUp\ContentConnect\Tests\Integration\Relationships;

use TenUp\ContentConnect\Relationships\PostToUser;
use TenUp\ContentConnect\Tests\Integration\ContentConnectTestCase;

class PostToUserTest extends ContentConnectTestCase {

	public function setUp() {
		global $wpdb;

		$wpdb->query( "delete from {$wpdb->prefix}post_to_user" );

		parent::setUp();
	}

	public function tearDown() {
		parent::tearDown();
	}

	public function test_invalid_cpt_throws_exception() {
		$this->expectException( \Exception::class );

		new PostToUser( 'fakecpt', 'basic' );
	}

	public function test_valid_cpts_throw_no_exceptions() {
		$p2p = new PostToUser( 'post', 'basic' );

		$this->assertEquals( 'post', $p2p->post_type );
	}

	public function test_add_relationship() {
		global $wpdb;
		$p2u = new PostToUser( 'post', 'basic' );

		// Make sure we don't already have this in the DB
		$this->assertEquals( 0, $wpdb->query( "select * from {$wpdb->prefix}post_to_user where post_id='2' and user_id='1' and name='basic'") );

		$p2u->add_relationship( 2, 1 );
		$this->assertEquals( 1, $wpdb->query( "select * from {$wpdb->prefix}post_to_user where post_id='2' and user_id='1' and name='basic'") );
	}

	public function test_adding_duplicates() {
		global $wpdb;
		$p2u = new PostToUser( 'post', 'basic' );

		// Making sure we don't add duplicates
		$p2u->add_relationship( '2', '1' );
		$p2u->add_relationship( '2', '1' );
		$this->assertEquals( 1, $wpdb->query( "select * from {$wpdb->prefix}post_to_user where post_id='2' and user_id='1' and name='basic'") );
	}

	public function test_delete_relationship() {
		global $wpdb;
		$p2u = new PostToUser( 'post', 'basic' );

		// Make sure we're in a known state of having a relationship in the DB
		$p2u->add_relationship( '2', '1' );
		$this->assertEquals( 1, $wpdb->query( "select * from {$wpdb->prefix}post_to_user where post_id='2' and user_id='1' and name='basic'") );

		$p2u->delete_relationship( 2, 1 );
		$this->assertEquals( 0, $wpdb->query( "select * from {$wpdb->prefix}post_to_user where post_id='2' and user_id='1' and name='basic'") );
	}

	public function test_delete_only_deletes_correct_records() {
		global $wpdb;
		$p2u = new PostToUser( 'post', 'basic' );

		$keep_pairs = array(
			array( 2, 2 ),
			array( 2, 5 ),
			array( 3, 10 ),
			array( 4, 15 ),
		);

		$delete_pairs = array(
			array( 2, 10 ),
		);

		$pairs = array_merge( $keep_pairs, $delete_pairs );

		foreach ( $pairs as $pair ) {
			$p2u->add_relationship( $pair[0], $pair[1] );
		}

		foreach( $pairs as $pair ) {
			$this->assertEquals( 1, $wpdb->query( "select * from {$wpdb->prefix}post_to_user where post_id='{$pair[0]}' and user_id='{$pair[1]}' and name='basic'") );
		}

		foreach( $delete_pairs as $delete_pair ) {
			$p2u->delete_relationship( $pair[0], $pair[1] );
		}

		foreach( $keep_pairs as $pair ) {
			$this->assertEquals( 1, $wpdb->query( "select * from {$wpdb->prefix}post_to_user where post_id='{$pair[0]}' and user_id='{$pair[1]}' and name='basic'") );
		}

		foreach( $delete_pairs as $pair ) {
			$this->assertEquals( 0, $wpdb->query( "select * from {$wpdb->prefix}post_to_user where post_id='{$pair[0]}' and user_id='{$pair[1]}' and name='basic'") );
		}
	}

	public function test_user_ids_from_posts() {
		$this->add_user_relations();

		$postowner = new PostToUser( 'post', 'owner' );
		$postcontrib = new PostToUser( 'post', 'contrib' );
		$carowner = new PostToUser( 'car', 'owner' );
		$carcontrib = new PostToUser( 'car', 'contrib' );

		$this->assertEquals( array( 1 ), $postowner->get_related_user_ids( 1 ) );
		$this->assertEquals( array( 1 ), $postowner->get_related_user_ids( 2 ) );
		$this->assertEquals( array( 1, 2 ), $postowner->get_related_user_ids( 3 ) );
		$this->assertEquals( array( 1, 2 ), $postowner->get_related_user_ids( 4 ) );
		$this->assertEquals( array( 1, 2, 3 ), $postowner->get_related_user_ids( 5 ) );
		$this->assertEquals( array( 3 ), $postowner->get_related_user_ids( 8 ) );
		$this->assertEquals( array(), $postowner->get_related_user_ids( 10 ) );

		// Because 12 is not a 'post' post type, this should return no results, but we aren't restricting on the `FROM` side right now
		$this->assertEquals( array(), $postowner->get_related_user_ids( 12 ) );

		$this->assertEquals( array(), $postcontrib->get_related_user_ids( 1 ) );
		$this->assertEquals( array( 1 ), $postcontrib->get_related_user_ids( 2 ) );
		$this->assertEquals( array( 1 ), $postcontrib->get_related_user_ids( 3 ) );
		$this->assertEquals( array( 1, 2 ), $postcontrib->get_related_user_ids( 4 ) );

		$this->assertEquals( array(), $carowner->get_related_user_ids( 11 ) );
		$this->assertEquals( array( 3 ), $carowner->get_related_user_ids( 12 ) );
		$this->assertEquals( array( 3 ), $carowner->get_related_user_ids( 13 ) );
		$this->assertEquals( array( 2, 3 ), $carowner->get_related_user_ids( 14 ) );

		$this->assertEquals( array( 3 ), $carcontrib->get_related_user_ids( 11 ) );
		$this->assertEquals( array( 3 ), $carcontrib->get_related_user_ids( 12 ) );
		$this->assertEquals( array( 2, 3 ), $carcontrib->get_related_user_ids( 13 ) );
		$this->assertEquals( array(), $carcontrib->get_related_user_ids( 20 ) );

	}

	public function test_post_ids_from_users() {
		$this->add_user_relations();

		$postowner = new PostToUser( 'post', 'owner' );
		$postcontrib = new PostToUser( 'post', 'contrib' );
		$carowner = new PostToUser( 'car', 'owner' );
		$carcontrib = new PostToUser( 'car', 'contrib' );

		$this->assertEquals( array( 1, 2, 3, 4, 5 ), $postowner->get_related_post_ids( 1 ) );
		$this->assertEquals( array( 16, 17, 18, 19, 20 ), $carowner->get_related_post_ids( 1 ) );
		$this->assertEquals( array( 2, 3, 4, 5, 6 ), $postcontrib->get_related_post_ids( 1 ) );
		$this->assertEquals( array( 15, 16, 17, 18, 19 ), $carcontrib->get_related_post_ids( 1 ) );

		$this->assertEquals( array( 3, 4, 5, 6, 7 ), $postowner->get_related_post_ids( 2 ) );
		$this->assertEquals( array( 14, 15, 16, 17, 18 ), $carowner->get_related_post_ids( 2 ) );
		$this->assertEquals( array( 4, 5, 6, 7, 8 ), $postcontrib->get_related_post_ids( 2 ) );
		$this->assertEquals( array( 13, 14, 15, 16, 17 ), $carcontrib->get_related_post_ids( 2 ) );

		$this->assertEquals( array( 5, 6, 7, 8, 9 ), $postowner->get_related_post_ids( 3 ) );
		$this->assertEquals( array( 12, 13, 14, 15, 16 ), $carowner->get_related_post_ids( 3 ) );
		$this->assertEquals( array( 6, 7, 8, 9, 10 ), $postcontrib->get_related_post_ids( 3 ) );
		$this->assertEquals( array( 11, 12, 13, 14, 15 ), $carcontrib->get_related_post_ids( 3 ) );
	}

	public function test_user_order_data_is_saved() {
		global $wpdb;

		$this->add_user_relations();

		$rel = new PostToUser( 'post', 'owner' );
		$rel->save_post_to_user_sort_data( 1, array( 2, 3, 4 ) );

		$this->assertEquals( 1, $wpdb->get_var( "select `user_order` from {$wpdb->prefix}post_to_user where post_id=1 and name='owner' and user_id=2;" ) );
		$this->assertEquals( 2, $wpdb->get_var( "select `user_order` from {$wpdb->prefix}post_to_user where post_id=1 and name='owner' and user_id=3;" ) );
		$this->assertEquals( 3, $wpdb->get_var( "select `user_order` from {$wpdb->prefix}post_to_user where post_id=1 and name='owner' and user_id=4;" ) );

		$rel->save_post_to_user_sort_data( 1, array( 4, 2, 3 ) );

		$this->assertEquals( 1, $wpdb->get_var( "select `user_order` from {$wpdb->prefix}post_to_user where post_id=1 and name='owner' and user_id=4;" ) );
		$this->assertEquals( 2, $wpdb->get_var( "select `user_order` from {$wpdb->prefix}post_to_user where post_id=1 and name='owner' and user_id=2;" ) );
		$this->assertEquals( 3, $wpdb->get_var( "select `user_order` from {$wpdb->prefix}post_to_user where post_id=1 and name='owner' and user_id=3;" ) );
	}

	public function test_user_order_relationship_ids_are_returned_in_order() {
		$this->add_user_relations();

		$rel = new PostToUser( 'post', 'owner' );
		$rel->save_post_to_user_sort_data( 1, array( 1, 2, 3, 4 ) );

		$this->assertEquals( array( 1, 2, 3, 4 ), $rel->get_related_user_ids( 1, false ) );
		$this->assertEquals( array( 1, 2, 3, 4 ), $rel->get_related_user_ids( 1, true ) );

		$rel->save_post_to_user_sort_data( 1, array( 3, 2, 1, 4 ) );

		$this->assertEquals( array( 1, 2, 3, 4 ), $rel->get_related_user_ids( 1, false ) );
		$this->assertEquals( array( 3, 2, 1, 4 ), $rel->get_related_user_ids( 1, true ) );
	}

	public function test_user_order_relationships_added_with_no_order_go_to_end() {
		$this->add_user_relations();

		$rel = new PostToUser( 'post', 'owner' );
		$rel->save_post_to_user_sort_data( 1, array( 2, 3, 4 ) );

		$this->assertEquals( array( 1, 2, 3, 4 ), $rel->get_related_user_ids( 1, false ) );
		$this->assertEquals( array( 2, 3, 4, 1 ), $rel->get_related_user_ids( 1, true ) );

		$rel->save_post_to_user_sort_data( 1, array( 3, 2, 4 ) );

		$this->assertEquals( array( 1, 2, 3, 4 ), $rel->get_related_user_ids( 1, false ) );
		$this->assertEquals( array( 3, 2, 4, 1 ), $rel->get_related_user_ids( 1, true ) );
	}

	public function test_post_order_data_is_saved() {
		global $wpdb;

		$this->add_user_relations();

		$rel = new PostToUser( 'post', 'owner' );
		$rel->save_user_to_post_sort_data( 1, array( 2, 3, 4 ) );

		$this->assertEquals( 1, $wpdb->get_var( "select `post_order` from {$wpdb->prefix}post_to_user where user_id=1 and name='owner' and post_id=2;" ) );
		$this->assertEquals( 2, $wpdb->get_var( "select `post_order` from {$wpdb->prefix}post_to_user where user_id=1 and name='owner' and post_id=3;" ) );
		$this->assertEquals( 3, $wpdb->get_var( "select `post_order` from {$wpdb->prefix}post_to_user where user_id=1 and name='owner' and post_id=4;" ) );

		$rel->save_user_to_post_sort_data( 1, array( 4, 2, 3 ) );

		$this->assertEquals( 1, $wpdb->get_var( "select `post_order` from {$wpdb->prefix}post_to_user where user_id=1 and name='owner' and post_id=4;" ) );
		$this->assertEquals( 2, $wpdb->get_var( "select `post_order` from {$wpdb->prefix}post_to_user where user_id=1 and name='owner' and post_id=2;" ) );
		$this->assertEquals( 3, $wpdb->get_var( "select `post_order` from {$wpdb->prefix}post_to_user where user_id=1 and name='owner' and post_id=3;" ) );
	}

	public function test_post_order_relationship_ids_are_returned_in_order() {
		$this->add_user_relations();

		$rel = new PostToUser( 'post', 'owner' );
		$rel->save_user_to_post_sort_data( 1, array( 1, 2, 3, 4, 5 ) );

		$this->assertEquals( array( 1, 2, 3, 4, 5 ), $rel->get_related_post_ids( 1, false ) );
		$this->assertEquals( array( 1, 2, 3, 4, 5 ), $rel->get_related_post_ids( 1, true ) );

		$rel->save_user_to_post_sort_data( 1, array( 3, 2, 4, 5, 1 ) );

		$this->assertEquals( array( 1, 2, 3, 4, 5 ), $rel->get_related_post_ids( 1, false ) );
		$this->assertEquals( array( 3, 2, 4, 5, 1 ), $rel->get_related_post_ids( 1, true ) );
	}

	public function test_post_order_relationships_added_with_no_order_go_to_end() {
		$this->add_user_relations();

		$rel = new PostToUser( 'post', 'owner' );
		$rel->save_user_to_post_sort_data( 1, array( 3, 4, 5 ) );

		$this->assertEquals( array( 1, 2, 3, 4, 5 ), $rel->get_related_post_ids( 1, false ) );
		$this->assertEquals( array( 3, 4, 5, 1, 2 ), $rel->get_related_post_ids( 1, true ) );
	}

	public function test_user_order_doesnt_overwrite_post_order() {
		global $wpdb;

		$this->add_user_relations();

		$rel = new PostToUser( 'post', 'owner' );
		$rel->save_user_to_post_sort_data( 1, array( 3, 4, 5 ) );

		// Make sure we're in a known state right now
		$this->assertEquals( 1, $wpdb->get_var( "select `post_order` from {$wpdb->prefix}post_to_user where user_id=1 and name='owner' and post_id=3;" ) );
		$this->assertEquals( 2, $wpdb->get_var( "select `post_order` from {$wpdb->prefix}post_to_user where user_id=1 and name='owner' and post_id=4;" ) );
		$this->assertEquals( 3, $wpdb->get_var( "select `post_order` from {$wpdb->prefix}post_to_user where user_id=1 and name='owner' and post_id=5;" ) );
		$this->assertEquals( 0, $wpdb->get_var( "select `user_order` from {$wpdb->prefix}post_to_user where user_id=1 and name='owner' and post_id=3;" ) );
		$this->assertEquals( 0, $wpdb->get_var( "select `user_order` from {$wpdb->prefix}post_to_user where user_id=1 and name='owner' and post_id=4;" ) );
		$this->assertEquals( 0, $wpdb->get_var( "select `user_order` from {$wpdb->prefix}post_to_user where user_id=1 and name='owner' and post_id=5;" ) );

		// The common post here is post 2. Make sure post 2's user_order is updated, and post_order is left alone
		$rel->save_post_to_user_sort_data( 3, array( 2, 1, 3 ) );
		$this->assertEquals( 1, $wpdb->get_var( "select `post_order` from {$wpdb->prefix}post_to_user where user_id=1 and name='owner' and post_id=3;" ) );
		$this->assertEquals( 2, $wpdb->get_var( "select `user_order` from {$wpdb->prefix}post_to_user where user_id=1 and name='owner' and post_id=3;" ) );
	}

	public function test_replace_post_to_user_relationships() {
		global $wpdb;

		$rel = new PostToUser( 'post', 'owner' );

		$this->assertEquals( 0, $wpdb->get_var( "select count(post_id) from {$wpdb->prefix}post_to_user where post_id=1 and `name`='owner';") );

		// Add some known relationships, and make sure they get written to DB
		$rel->add_relationship( 1, 2 );
		$rel->add_relationship( 1, 3 );
		$rel->add_relationship( 1, 4 );
		$rel->add_relationship( 1, 5 );

		$this->assertEquals( 1, $wpdb->get_var( "select count(post_id) from {$wpdb->prefix}post_to_user where post_id=1 and user_id=2 and `name`='owner';") );
		$this->assertEquals( 1, $wpdb->get_var( "select count(post_id) from {$wpdb->prefix}post_to_user where post_id=1 and user_id=3 and `name`='owner';") );
		$this->assertEquals( 1, $wpdb->get_var( "select count(post_id) from {$wpdb->prefix}post_to_user where post_id=1 and user_id=4 and `name`='owner';") );
		$this->assertEquals( 1, $wpdb->get_var( "select count(post_id) from {$wpdb->prefix}post_to_user where post_id=1 and user_id=5 and `name`='owner';") );

		// Should remove 2 and 5 and add 6
		$rel->replace_post_to_user_relationships( 1, array( 3, 4, 6 ) );
		$this->assertEquals( 0, $wpdb->get_var( "select count(post_id) from {$wpdb->prefix}post_to_user where post_id=1 and user_id=2 and `name`='owner';") );
		$this->assertEquals( 1, $wpdb->get_var( "select count(post_id) from {$wpdb->prefix}post_to_user where post_id=1 and user_id=3 and `name`='owner';") );
		$this->assertEquals( 1, $wpdb->get_var( "select count(post_id) from {$wpdb->prefix}post_to_user where post_id=1 and user_id=4 and `name`='owner';") );
		$this->assertEquals( 0, $wpdb->get_var( "select count(post_id) from {$wpdb->prefix}post_to_user where post_id=1 and user_id=5 and `name`='owner';") );
		$this->assertEquals( 1, $wpdb->get_var( "select count(post_id) from {$wpdb->prefix}post_to_user where post_id=1 and user_id=6 and `name`='owner';") );
	}

	public function test_replace_user_to_post_relationships() {
		global $wpdb;

		$rel = new PostToUser( 'post', 'owner' );

		$this->assertEquals( 0, $wpdb->get_var( "select count(user_id) from {$wpdb->prefix}post_to_user where user_id=1 and `name`='owner';") );

		// Add some known relationships, and make sure they get written to DB
		$rel->add_relationship( 2, 1 );
		$rel->add_relationship( 3, 1 );
		$rel->add_relationship( 4, 1 );
		$rel->add_relationship( 5, 1 );

		$this->assertEquals( 1, $wpdb->get_var( "select count(user_id) from {$wpdb->prefix}post_to_user where user_id=1 and post_id=2 and `name`='owner';") );
		$this->assertEquals( 1, $wpdb->get_var( "select count(user_id) from {$wpdb->prefix}post_to_user where user_id=1 and post_id=3 and `name`='owner';") );
		$this->assertEquals( 1, $wpdb->get_var( "select count(user_id) from {$wpdb->prefix}post_to_user where user_id=1 and post_id=4 and `name`='owner';") );
		$this->assertEquals( 1, $wpdb->get_var( "select count(user_id) from {$wpdb->prefix}post_to_user where user_id=1 and post_id=5 and `name`='owner';") );

		// Should remove 2 and 5 and add 6
		$rel->replace_user_to_post_relationships( 1, array( 3, 4, 6 ) );
		$this->assertEquals( 0, $wpdb->get_var( "select count(user_id) from {$wpdb->prefix}post_to_user where user_id=1 and post_id=2 and `name`='owner';") );
		$this->assertEquals( 1, $wpdb->get_var( "select count(user_id) from {$wpdb->prefix}post_to_user where user_id=1 and post_id=3 and `name`='owner';") );
		$this->assertEquals( 1, $wpdb->get_var( "select count(user_id) from {$wpdb->prefix}post_to_user where user_id=1 and post_id=4 and `name`='owner';") );
		$this->assertEquals( 0, $wpdb->get_var( "select count(user_id) from {$wpdb->prefix}post_to_user where user_id=1 and post_id=5 and `name`='owner';") );
		$this->assertEquals( 1, $wpdb->get_var( "select count(user_id) from {$wpdb->prefix}post_to_user where user_id=1 and post_id=6 and `name`='owner';") );
	}

}
