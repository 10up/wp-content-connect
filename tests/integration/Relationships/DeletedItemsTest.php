<?php
/**
 * Tests for deleted items cleanup in relationships.
 *
 * @package TenUp\ContentConnect\Tests\Integration\Relationships
 */

namespace TenUp\ContentConnect\Tests\Integration\Relationships;

use TenUp\ContentConnect\Relationships\Cache;
use TenUp\ContentConnect\Relationships\PostToPost;
use TenUp\ContentConnect\Relationships\PostToUser;
use TenUp\ContentConnect\Tests\Integration\ContentConnectTestCase;

/**
 * Test cases for deleted items cleanup.
 */
class DeletedItemsTest extends ContentConnectTestCase {

	/**
	 * Sets up the test environment.
	 *
	 * @return void
	 */
	public function setUp(): void {
		global $wpdb;

		// Start out with known empty slate
		$wpdb->query( "delete from {$wpdb->prefix}post_to_post" );
		$wpdb->query( "delete from {$wpdb->prefix}post_to_user" );

		$wpdb->query( "delete from {$wpdb->posts}" );
		self::insert_dummy_data();

		parent::setUp();
	}

	/**
	 * Tests that deleted posts are removed from the post_to_post table.
	 *
	 * Direct DB queries are used because get_related_*_id functions are smart enough
	 * to not return the IDs (because of the join) even if the record still exists
	 * in the join table.
	 *
	 * @return void
	 */
	public function test_deleted_posts_are_removed_from_post_to_post_table(): void {
		global $wpdb;

		$relationship = new PostToPost( 'car', 'tire', 'test' );

		// 11 (car) to 21 (tire)
		$this->assertSame( 0, (int) $wpdb->get_var( "select count(id1) from {$wpdb->prefix}post_to_post where id1=11;" ) );
		$this->assertSame( 0, (int) $wpdb->get_var( "select count(id2) from {$wpdb->prefix}post_to_post where id2=11;" ) );
		$this->assertSame( 0, (int) $wpdb->get_var( "select count(id1) from {$wpdb->prefix}post_to_post where id1=21;" ) );
		$this->assertSame( 0, (int) $wpdb->get_var( "select count(id2) from {$wpdb->prefix}post_to_post where id2=21;" ) );

		$relationship->add_relationship( 11, 21 );
		$this->assertSame( 1, (int) $wpdb->get_var( "select count(id1) from {$wpdb->prefix}post_to_post where id1=11;" ) );
		$this->assertSame( 1, (int) $wpdb->get_var( "select count(id2) from {$wpdb->prefix}post_to_post where id2=11;" ) );
		$this->assertSame( 1, (int) $wpdb->get_var( "select count(id1) from {$wpdb->prefix}post_to_post where id1=21;" ) );
		$this->assertSame( 1, (int) $wpdb->get_var( "select count(id2) from {$wpdb->prefix}post_to_post where id2=21;" ) );

		// Test that relationships persist when trashing posts (in case they are untrashed)
		wp_trash_post( 11 );
		$this->assertSame( 1, (int) $wpdb->get_var( "select count(id1) from {$wpdb->prefix}post_to_post where id1=11;" ) );
		$this->assertSame( 1, (int) $wpdb->get_var( "select count(id2) from {$wpdb->prefix}post_to_post where id2=11;" ) );
		$this->assertSame( 1, (int) $wpdb->get_var( "select count(id1) from {$wpdb->prefix}post_to_post where id1=21;" ) );
		$this->assertSame( 1, (int) $wpdb->get_var( "select count(id2) from {$wpdb->prefix}post_to_post where id2=21;" ) );

		wp_delete_post( 11 );
		$this->assertSame( 0, (int) $wpdb->get_var( "select count(id1) from {$wpdb->prefix}post_to_post where id1=11;" ) );
		$this->assertSame( 0, (int) $wpdb->get_var( "select count(id2) from {$wpdb->prefix}post_to_post where id2=11;" ) );
		$this->assertSame( 0, (int) $wpdb->get_var( "select count(id1) from {$wpdb->prefix}post_to_post where id1=21;" ) );
		$this->assertSame( 0, (int) $wpdb->get_var( "select count(id2) from {$wpdb->prefix}post_to_post where id2=21;" ) );
	}

	/**
	 * Tests that deleted posts are removed from the post_to_user table.
	 *
	 * Direct DB queries are used because get_related_*_id functions are smart enough
	 * to not return the IDs (because of the join) even if the record still exists
	 * in the join table.
	 *
	 * @return void
	 */
	public function test_deleted_posts_are_removed_from_post_to_user_table(): void {
		global $wpdb;

		$relationship = new PostToUser( 'car', 'test' );

		// 11 (car) to 1 (user)
		$this->assertSame( 0, (int) $wpdb->get_var( "select count(post_id) from {$wpdb->prefix}post_to_user where post_id=11;" ) );

		$relationship->add_relationship( 11, 1 );
		$this->assertSame( 1, (int) $wpdb->get_var( "select count(post_id) from {$wpdb->prefix}post_to_user where post_id=11;" ) );

		// Test that relationships persist when trashing posts (in case they are untrashed)
		wp_trash_post( 11 );
		$this->assertSame( 1, (int) $wpdb->get_var( "select count(post_id) from {$wpdb->prefix}post_to_user where post_id=11;" ) );

		wp_delete_post( 11 );
		$this->assertSame( 0, (int) $wpdb->get_var( "select count(post_id) from {$wpdb->prefix}post_to_user where post_id=11;" ) );
	}

	/**
	 * Tests that deleted users are removed from the post_to_user table.
	 *
	 * Direct DB queries are used because get_related_*_id functions are smart enough
	 * to not return the IDs (because of the join) even if the record still exists
	 * in the join table.
	 *
	 * @return void
	 */
	public function test_deleted_users_are_removed_from_post_to_user_table(): void {
		global $wpdb;

		$relationship = new PostToUser( 'car', 'test' );

		// 11 (car) to 1 (user)
		$this->assertSame( 0, (int) $wpdb->get_var( "select count(user_id) from {$wpdb->prefix}post_to_user where user_id=1;" ) );

		$relationship->add_relationship( 11, 1 );
		$this->assertSame( 1, (int) $wpdb->get_var( "select count(user_id) from {$wpdb->prefix}post_to_user where user_id=1;" ) );

		wp_delete_user( 1 );
		$this->assertSame( 0, (int) $wpdb->get_var( "select count(user_id) from {$wpdb->prefix}post_to_user where user_id=1;" ) );
	}

	/**
	 * Tests that deleting a post clears the related-id cache for the post and its related posts.
	 *
	 * The raw deletes in deleted_post() bypass delete_relationship(), so the cache must be
	 * invalidated explicitly; otherwise a related post keeps serving a cached list containing
	 * the deleted ID.
	 *
	 * @return void
	 */
	public function test_deleting_a_post_invalidates_related_ids_cache(): void {
		$relationship = new PostToPost( 'car', 'tire', 'test' );

		// 11 (car) related to 21 (tire).
		$relationship->add_relationship( 11, 21 );

		// Seed the related-id cache for both endpoints, as get_related_ids_by_name() would.
		$key_11 = Cache::get_related_ids_key( 11, 'test' );
		$key_21 = Cache::get_related_ids_key( 21, 'test' );
		Cache::set( $key_11, array( 21 ) );
		Cache::set( $key_21, array( 11 ) );

		$this->assertSame( array( 21 ), Cache::get( $key_11 ) );
		$this->assertSame( array( 11 ), Cache::get( $key_21 ) );

		// Hard-deleting the post must clear the cached lookups for it and its related post.
		wp_delete_post( 11 );

		$this->assertFalse( Cache::get( $key_11 ) );
		$this->assertFalse( Cache::get( $key_21 ) );
	}

}
