<?php
/**
 * Relationship cache management.
 *
 * @package TenUp\ContentConnect\Relationships
 */

namespace TenUp\ContentConnect\Relationships;

/**
 * Manages object cache entries for relationship lookups.
 *
 * Listens for relationship mutation actions and invalidates affected cache keys.
 */
class Cache {

	/**
	 * Cache group used for all relationship cache entries.
	 *
	 * @var string
	 */
	const GROUP = 'tenup_content_connect';

	/**
	 * Default cache TTL in seconds.
	 *
	 * @var int
	 */
	const TTL = HOUR_IN_SECONDS;

	/**
	 * Registers hooks.
	 *
	 * @return void
	 */
	public function setup() {
		add_action( 'tenup-content-connect-add-relationship', array( $this, 'invalidate_related_ids' ), 10, 4 );
		add_action( 'tenup-content-connect-delete-relationship', array( $this, 'invalidate_related_ids' ), 10, 4 );
	}

	/**
	 * Determines whether the relationship cache is enabled.
	 *
	 * Cache is disabled when the `CONTENT_CONNECT_DISABLE_CACHE` constant is defined and truthy.
	 *
	 * @return bool True when cache is enabled, false otherwise.
	 */
	public static function is_enabled() {
		return ! ( defined( 'CONTENT_CONNECT_DISABLE_CACHE' ) && CONTENT_CONNECT_DISABLE_CACHE );
	}

	/**
	 * Builds the cache key used by `get_related_ids_by_name()`.
	 *
	 * @param int    $post_id           The post ID.
	 * @param string $relationship_name The relationship name.
	 * @return string
	 */
	public static function get_related_ids_key( $post_id, $relationship_name ) {
		return "related_ids_by_name|{$post_id}|{$relationship_name}";
	}

	/**
	 * Retrieves a value from the relationship cache.
	 *
	 * Returns false when the cache is disabled, mirroring a cache miss.
	 *
	 * @param string $key Cache key.
	 * @return mixed Cached value, or false on miss or when cache is disabled.
	 */
	public static function get( $key ) {
		if ( ! self::is_enabled() ) {
			return false;
		}

		return wp_cache_get( $key, self::GROUP );
	}

	/**
	 * Stores a value in the relationship cache.
	 *
	 * No-op when the cache is disabled.
	 *
	 * @param string $key   Cache key.
	 * @param mixed  $value Value to cache.
	 * @param int    $ttl   Optional. TTL in seconds. Defaults to self::TTL.
	 * @return bool True on success, false on failure or when cache is disabled.
	 */
	public static function set( $key, $value, $ttl = self::TTL ) {
		if ( ! self::is_enabled() ) {
			return false;
		}

		return wp_cache_set( $key, $value, self::GROUP, $ttl );
	}

	/**
	 * Deletes a value from the relationship cache.
	 *
	 * @param string $key Cache key.
	 * @return bool True on success, false on failure.
	 */
	public static function delete( $key ) {
		return wp_cache_delete( $key, self::GROUP );
	}

	/**
	 * Invalidates cached related-id lookups when a post-to-post relationship mutates.
	 *
	 * @param int    $pid1 First post ID.
	 * @param int    $pid2 Second post ID.
	 * @param string $name Relationship name.
	 * @param string $type Relationship type.
	 * @return void
	 */
	public function invalidate_related_ids( $pid1, $pid2, $name, $type ) {
		if ( 'post-to-post' !== $type ) {
			return;
		}

		self::delete( self::get_related_ids_key( $pid1, $name ) );
		self::delete( self::get_related_ids_key( $pid2, $name ) );
	}
}
