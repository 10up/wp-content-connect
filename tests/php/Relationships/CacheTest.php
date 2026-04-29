<?php
/**
 * Tests for Cache relationship module.
 *
 * @package TenUp\ContentConnect\Tests\Relationships
 */

namespace TenUp\ContentConnect\Tests\Relationships;

use TenUp\ContentConnect\Relationships\Cache;
use TenUp\ContentConnect\Relationships\PostToPost;
use TenUp\ContentConnect\Tests\ContentConnectTestCase;

/**
 * Test cases for the Cache class.
 */
class CacheTest extends ContentConnectTestCase {

	/**
	 * Sets up the test environment.
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		global $wpdb;

		$wpdb->query( "delete from {$wpdb->prefix}post_to_post" );

		wp_cache_flush();
	}

	/**
	 * Tests that get_related_ids_key() produces a deterministic key string.
	 *
	 * @return void
	 */
	public function test_get_related_ids_key_format(): void {
		$this->assertSame(
			'related_ids_by_name|123|basic',
			Cache::get_related_ids_key( 123, 'basic' )
		);
	}

	/**
	 * Tests that is_enabled() returns true by default.
	 *
	 * @return void
	 */
	public function test_is_enabled_default(): void {
		$this->assertTrue( Cache::is_enabled() );
	}

	/**
	 * Tests that set() stores values and get() retrieves them.
	 *
	 * @return void
	 */
	public function test_set_and_get_round_trip(): void {
		$key = 'test_key_' . __FUNCTION__;

		Cache::set( $key, array( 1, 2, 3 ) );

		$this->assertSame( array( 1, 2, 3 ), Cache::get( $key ) );
	}

	/**
	 * Tests that get() returns false for missing keys.
	 *
	 * @return void
	 */
	public function test_get_returns_false_on_miss(): void {
		$this->assertFalse( Cache::get( 'nonexistent_key_' . __FUNCTION__ ) );
	}

	/**
	 * Tests that delete() removes a previously stored value.
	 *
	 * @return void
	 */
	public function test_delete_removes_value(): void {
		$key = 'test_key_' . __FUNCTION__;

		Cache::set( $key, 'value' );
		$this->assertSame( 'value', Cache::get( $key ) );

		Cache::delete( $key );
		$this->assertFalse( Cache::get( $key ) );
	}

	/**
	 * Tests that set() respects a custom TTL parameter.
	 *
	 * Validates the TTL is forwarded to wp_cache_set without throwing.
	 *
	 * @return void
	 */
	public function test_set_accepts_custom_ttl(): void {
		$key = 'test_key_' . __FUNCTION__;

		$this->assertNotFalse( Cache::set( $key, 'value', 60 ) );
		$this->assertSame( 'value', Cache::get( $key ) );
	}

	/**
	 * Tests that invalidate_related_ids() removes cached entries for both posts.
	 *
	 * @return void
	 */
	public function test_invalidate_related_ids_clears_both_posts(): void {
		$cache = new Cache();

		$key1 = Cache::get_related_ids_key( 1, 'basic' );
		$key2 = Cache::get_related_ids_key( 2, 'basic' );

		Cache::set( $key1, array( 11 ) );
		Cache::set( $key2, array( 22 ) );

		$cache->invalidate_related_ids( 1, 2, 'basic', 'post-to-post' );

		$this->assertFalse( Cache::get( $key1 ) );
		$this->assertFalse( Cache::get( $key2 ) );
	}

	/**
	 * Tests that invalidate_related_ids() ignores non-post-to-post types.
	 *
	 * @return void
	 */
	public function test_invalidate_related_ids_ignores_other_types(): void {
		$cache = new Cache();

		$key = Cache::get_related_ids_key( 1, 'basic' );
		Cache::set( $key, array( 11 ) );

		$cache->invalidate_related_ids( 1, 2, 'basic', 'post-to-user' );

		$this->assertSame( array( 11 ), Cache::get( $key ) );
	}

	/**
	 * Tests that the add-relationship action triggers cache invalidation.
	 *
	 * @return void
	 */
	public function test_add_relationship_action_invalidates_cache(): void {
		$key1 = Cache::get_related_ids_key( 11, 'basic' );
		$key2 = Cache::get_related_ids_key( 1, 'basic' );

		Cache::set( $key1, array( 1 ) );
		Cache::set( $key2, array( 11 ) );

		$relationship = new PostToPost( 'car', 'post', 'basic' );
		$relationship->add_relationship( 11, 1 );

		$this->assertFalse( Cache::get( $key1 ) );
		$this->assertFalse( Cache::get( $key2 ) );
	}

	/**
	 * Tests that defining CONTENT_CONNECT_DISABLE_CACHE disables the cache.
	 *
	 * Runs in a separate process so the constant definition does not leak into other tests.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 *
	 * @return void
	 */
	public function test_disable_cache_constant_short_circuits_get_and_set(): void {
		define( 'CONTENT_CONNECT_DISABLE_CACHE', true );

		$this->assertFalse( Cache::is_enabled() );

		$key = 'test_key_' . __FUNCTION__;

		$this->assertFalse( Cache::set( $key, 'value' ) );
		$this->assertFalse( Cache::get( $key ) );
	}

	/**
	 * Tests that the delete-relationship action triggers cache invalidation.
	 *
	 * @return void
	 */
	public function test_delete_relationship_action_invalidates_cache(): void {
		$relationship = new PostToPost( 'car', 'post', 'basic' );
		$relationship->add_relationship( 11, 1 );

		$key1 = Cache::get_related_ids_key( 11, 'basic' );
		$key2 = Cache::get_related_ids_key( 1, 'basic' );

		Cache::set( $key1, array( 1 ) );
		Cache::set( $key2, array( 11 ) );

		$relationship->delete_relationship( 11, 1 );

		$this->assertFalse( Cache::get( $key1 ) );
		$this->assertFalse( Cache::get( $key2 ) );
	}
}
