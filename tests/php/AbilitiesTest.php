<?php
/**
 * Tests for the Abilities API registrations.
 *
 * @package TenUp\ContentConnect\Tests
 */

namespace TenUp\ContentConnect\Tests;

use function TenUp\ContentConnect\Helpers\get_registry;

/**
 * Test cases for the Abilities API registrations.
 */
class AbilitiesTest extends ContentConnectTestCase {

	/**
	 * Ability names registered by the plugin.
	 *
	 * @var string[]
	 */
	const ABILITIES = array(
		'content-connect/get-relationships',
		'content-connect/get-post-relationships',
		'content-connect/get-related-items',
	);

	/**
	 * Sets up the test environment.
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		if ( ! function_exists( 'wp_get_ability' ) ) {
			$this->markTestSkipped( 'The Abilities API is not available.' );
		}

		wp_set_current_user( $this->factory()->user->create( array( 'role' => 'administrator' ) ) );
	}

	/**
	 * Tests that every ability is registered as read-only and exposed to clients.
	 *
	 * @return void
	 */
	public function test_abilities_are_registered_read_only() {
		foreach ( self::ABILITIES as $name ) {
			$ability = wp_get_ability( $name );

			$this->assertNotNull( $ability, $name );
			$this->assertSame( 'content-connect', $ability->get_category() );

			$meta = $ability->get_meta();

			$this->assertTrue( $meta['show_in_rest'] );
			$this->assertTrue( $meta['mcp']['public'] );
			$this->assertTrue( $meta['annotations']['readonly'] );
			$this->assertFalse( $meta['annotations']['destructive'] );
			$this->assertTrue( $meta['annotations']['idempotent'] );
		}
	}

	/**
	 * Tests that get-relationships lists registered definitions.
	 *
	 * @return void
	 */
	public function test_get_relationships_lists_definitions() {
		$registry = get_registry();
		$registry->define_post_to_post( 'post', 'car', 'ability-p2p' );
		$rel_key = $registry->get_relationship_key( 'post', 'car', 'ability-p2p' );

		$result = wp_get_ability( 'content-connect/get-relationships' )->execute(
			array(
				'filter_by'    => 'key',
				'filter_value' => $rel_key,
			)
		);

		$this->assertIsArray( $result );
		$this->assertCount( 1, $result );
		$this->assertSame( $rel_key, $result[0]['rel_key'] );
		$this->assertSame( 'post-to-post', $result[0]['rel_type'] );
	}

	/**
	 * Tests that get-relationships runs without input.
	 *
	 * @return void
	 */
	public function test_get_relationships_without_input() {
		$registry = get_registry();
		$registry->define_post_to_post( 'post', 'car', 'ability-no-input' );

		$result = wp_get_ability( 'content-connect/get-relationships' )->execute();

		$this->assertIsArray( $result );
		$this->assertNotEmpty( $result );
	}

	/**
	 * Tests that get-relationships is denied to logged-out users.
	 *
	 * @return void
	 */
	public function test_get_relationships_requires_edit_posts() {
		wp_set_current_user( 0 );

		$result = wp_get_ability( 'content-connect/get-relationships' )->execute();

		$this->assertWPError( $result );
	}

	/**
	 * Tests that get-post-relationships lists the relationships for a post.
	 *
	 * @return void
	 */
	public function test_get_post_relationships_lists_post_relationships() {
		$registry = get_registry();
		$registry->define_post_to_post( 'post', 'car', 'ability-post-rels' );
		$rel_key = $registry->get_relationship_key( 'post', 'car', 'ability-post-rels' );

		$result = wp_get_ability( 'content-connect/get-post-relationships' )->execute(
			array(
				'post_id'  => 1,
				'rel_type' => 'post-to-post',
			)
		);

		$this->assertIsArray( $result );
		$this->assertContains( $rel_key, wp_list_pluck( $result, 'rel_key' ) );
	}

	/**
	 * Tests that get-post-relationships is denied to users who cannot edit the post.
	 *
	 * @return void
	 */
	public function test_get_post_relationships_requires_edit_post() {
		wp_set_current_user( $this->factory()->user->create( array( 'role' => 'subscriber' ) ) );

		$result = wp_get_ability( 'content-connect/get-post-relationships' )->execute( array( 'post_id' => 1 ) );

		$this->assertWPError( $result );
	}

	/**
	 * Tests that get-related-items returns the related posts with totals.
	 *
	 * @return void
	 */
	public function test_get_related_items_returns_items_and_totals() {
		$registry = get_registry();
		$registry->define_post_to_post( 'post', 'post', 'ability-related' );
		$rel_key = $registry->get_relationship_key( 'post', 'post', 'ability-related' );

		$relationship = $registry->get_post_to_post_relationship_by_key( $rel_key );
		$relationship->add_relationship( 1, 2 );
		$relationship->add_relationship( 1, 3 );

		$result = wp_get_ability( 'content-connect/get-related-items' )->execute(
			array(
				'post_id'  => 1,
				'rel_key'  => $rel_key,
				'per_page' => 1,
			)
		);

		$this->assertIsArray( $result );
		$this->assertCount( 1, $result['items'] );
		$this->assertSame( 2, $result['total'] );
		$this->assertSame( 2, $result['total_pages'] );
	}

	/**
	 * Tests that get-related-items surfaces REST errors for unknown relationships.
	 *
	 * @return void
	 */
	public function test_get_related_items_unknown_relationship() {
		$result = wp_get_ability( 'content-connect/get-related-items' )->execute(
			array(
				'post_id' => 1,
				'rel_key' => 'does-not-exist',
			)
		);

		$this->assertWPError( $result );
		$this->assertSame( 'rest_relationship_not_found', $result->get_error_code() );
	}
}
