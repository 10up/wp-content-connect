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
		'content-connect/get-related-query-block',
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
	 * Tests that get-post-relationships rejects input properties the schema does not declare.
	 *
	 * @return void
	 */
	public function test_get_post_relationships_rejects_unknown_input() {
		$result = wp_get_ability( 'content-connect/get-post-relationships' )->execute(
			array(
				'post_id' => 1,
				'unknown' => 'value',
			)
		);

		$this->assertWPError( $result );
		$this->assertSame( 'ability_invalid_input', $result->get_error_code() );
	}

	/**
	 * Tests that get-post-relationships includes the related items with the embed context.
	 *
	 * @return void
	 */
	public function test_get_post_relationships_embed_includes_related() {
		$registry = get_registry();
		$registry->define_post_to_post( 'post', 'post', 'ability-embed' );
		$rel_key = $registry->get_relationship_key( 'post', 'post', 'ability-embed' );

		$registry->get_post_to_post_relationship_by_key( $rel_key )->add_relationship( 1, 2 );

		$result = wp_get_ability( 'content-connect/get-post-relationships' )->execute(
			array(
				'post_id'  => 1,
				'rel_type' => 'post-to-post',
				'context'  => 'embed',
			)
		);

		$this->assertIsArray( $result );

		$relationships = array_column( $result, null, 'rel_key' );

		$this->assertArrayHasKey( $rel_key, $relationships );
		$this->assertSame(
			array(
				array(
					'id'   => 2,
					'name' => get_the_title( 2 ),
					'type' => 'post',
				),
			),
			$relationships[ $rel_key ]['related']
		);
	}

	/**
	 * Tests that get-related-items returns each item as its ID, name, and type only.
	 *
	 * @return void
	 */
	public function test_get_related_items_maps_item_fields() {
		$registry = get_registry();
		$registry->define_post_to_post( 'post', 'post', 'ability-item-fields' );
		$rel_key = $registry->get_relationship_key( 'post', 'post', 'ability-item-fields' );

		$registry->get_post_to_post_relationship_by_key( $rel_key )->add_relationship( 1, 2 );

		$result = wp_get_ability( 'content-connect/get-related-items' )->execute(
			array(
				'post_id' => 1,
				'rel_key' => $rel_key,
			)
		);

		$this->assertSame(
			array(
				array(
					'id'   => 2,
					'name' => get_the_title( 2 ),
					'type' => 'post',
				),
			),
			$result['items']
		);
	}

	/**
	 * Tests that get-related-items lists published posts only.
	 *
	 * @return void
	 */
	public function test_get_related_items_lists_published_posts_only() {
		$registry = get_registry();
		$registry->define_post_to_post( 'post', 'post', 'ability-published' );
		$rel_key = $registry->get_relationship_key( 'post', 'post', 'ability-published' );

		$draft_id   = $this->factory()->post->create( array( 'post_status' => 'draft' ) );
		$private_id = $this->factory()->post->create( array( 'post_status' => 'private' ) );

		$relationship = $registry->get_post_to_post_relationship_by_key( $rel_key );
		$relationship->add_relationship( 1, 2 );
		$relationship->add_relationship( 1, $draft_id );
		$relationship->add_relationship( 1, $private_id );

		$result = wp_get_ability( 'content-connect/get-related-items' )->execute(
			array(
				'post_id' => 1,
				'rel_key' => $rel_key,
			)
		);

		$this->assertSame( array( 2 ), wp_list_pluck( $result['items'], 'id' ) );
		$this->assertSame( 1, $result['total'] );
	}

	/**
	 * Tests that get-related-items does not accept a post status filter.
	 *
	 * @return void
	 */
	public function test_get_related_items_rejects_post_status_input() {
		$result = wp_get_ability( 'content-connect/get-related-items' )->execute(
			array(
				'post_id'     => 1,
				'rel_key'     => 'any-key',
				'post_status' => array( 'draft' ),
			)
		);

		$this->assertWPError( $result );
		$this->assertSame( 'ability_invalid_input', $result->get_error_code() );
	}

	/**
	 * Tests that get-related-items lists the users related to a post.
	 *
	 * @return void
	 */
	public function test_get_related_items_post_to_user() {
		$registry = get_registry();
		$registry->define_post_to_user( 'post', 'ability-p2u' );
		$rel_key = $registry->get_relationship_key( 'post', 'user', 'ability-p2u' );

		$user_id = $this->factory()->user->create( array( 'display_name' => 'Example User' ) );

		$registry->get_post_to_user_relationship_by_key( $rel_key )->add_relationship( 1, $user_id );

		$result = wp_get_ability( 'content-connect/get-related-items' )->execute(
			array(
				'post_id'  => 1,
				'rel_key'  => $rel_key,
				'rel_type' => 'post-to-user',
				'orderby'  => 'name',
			)
		);

		$this->assertIsArray( $result );
		$this->assertSame(
			array(
				array(
					'id'   => $user_id,
					'name' => 'Example User',
					'type' => 'user',
				),
			),
			$result['items']
		);
		$this->assertSame( 1, $result['total'] );
	}

	/**
	 * Tests that the get-related-items input schema comes from the REST route args.
	 *
	 * @return void
	 */
	public function test_get_related_items_input_schema_matches_route_args() {
		$schema = wp_get_ability( 'content-connect/get-related-items' )->get_input_schema();

		$this->assertSame( array( 'post_id', 'rel_key' ), $schema['required'] );
		$this->assertArrayNotHasKey( 'post_status', $schema['properties'] );
		$this->assertArrayNotHasKey( 'validate_callback', $schema['properties']['orderby'] );
		$this->assertSame( array( 'relationship', 'date', 'id', 'title', 'name' ), $schema['properties']['orderby']['enum'] );
		$this->assertFalse( $schema['additionalProperties'] );
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

	/**
	 * Tests that get-related-query-block returns Query Loop markup that renders the related posts.
	 *
	 * @return void
	 */
	public function test_get_related_query_block_renders_related_posts() {
		$registry = get_registry();
		$registry->define_post_to_post( 'post', 'post', 'ability-query-block' );
		$rel_key = $registry->get_relationship_key( 'post', 'post', 'ability-query-block' );

		$source    = $this->factory()->post->create( array( 'post_title' => 'Query Block Source' ) );
		$related   = $this->factory()->post->create( array( 'post_title' => 'Query Block Related' ) );
		$unrelated = $this->factory()->post->create( array( 'post_title' => 'Query Block Unrelated' ) );

		$registry->get_post_to_post_relationship_by_key( $rel_key )->add_relationship( $source, $related );

		$result = wp_get_ability( 'content-connect/get-related-query-block' )->execute(
			array(
				'post_id' => $source,
				'rel_key' => $rel_key,
			)
		);

		$this->assertIsArray( $result );
		$this->assertTrue( $result['attributes']['showRelated'] );
		$this->assertSame( $rel_key, $result['attributes']['relationshipKey'] );
		$this->assertSame( 'post', $result['attributes']['query']['postType'] );
		$this->assertSame( 3, $result['attributes']['query']['perPage'] );
		$this->assertSame(
			array(
				array(
					'name'            => 'ability-query-block',
					'related_to_post' => $source,
				),
			),
			$result['attributes']['query']['relationshipQuery']
		);

		$blocks = parse_blocks( $result['markup'] );

		$this->assertSame( 'core/query', $blocks[0]['blockName'] );
		$this->assertSame( $result['attributes'], $blocks[0]['attrs'] );

		$html = do_blocks( $result['markup'] );

		$this->assertStringContainsString( 'Query Block Related', $html );
		$this->assertStringNotContainsString( 'Query Block Unrelated', $html );
	}

	/**
	 * Tests that get-related-query-block rejects a relationship that does not apply to the post.
	 *
	 * @return void
	 */
	public function test_get_related_query_block_unknown_relationship() {
		$result = wp_get_ability( 'content-connect/get-related-query-block' )->execute(
			array(
				'post_id' => 1,
				'rel_key' => 'does-not-exist',
			)
		);

		$this->assertWPError( $result );
		$this->assertSame( 'rest_relationship_not_found', $result->get_error_code() );
	}

	/**
	 * Tests that get-related-query-block rejects a post type the relationship does not relate to.
	 *
	 * @return void
	 */
	public function test_get_related_query_block_rejects_unrelated_post_type() {
		$registry = get_registry();
		$registry->define_post_to_post( 'post', 'car', 'ability-query-block-type' );
		$rel_key = $registry->get_relationship_key( 'post', 'car', 'ability-query-block-type' );

		$result = wp_get_ability( 'content-connect/get-related-query-block' )->execute(
			array(
				'post_id'   => 1,
				'rel_key'   => $rel_key,
				'post_type' => 'page',
			)
		);

		$this->assertWPError( $result );
		$this->assertSame( 'rest_invalid_param', $result->get_error_code() );
	}

	/**
	 * Tests that get-related-query-block is denied to users who cannot edit the post.
	 *
	 * @return void
	 */
	public function test_get_related_query_block_requires_edit_post() {
		wp_set_current_user( 0 );

		$result = wp_get_ability( 'content-connect/get-related-query-block' )->execute(
			array(
				'post_id' => 1,
				'rel_key' => 'any',
			)
		);

		$this->assertWPError( $result );
	}
}
