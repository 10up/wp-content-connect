<?php
/**
 * Tests for the custom relationships Loader.
 *
 * @package TenUp\ContentConnect\Tests\CustomRelationships
 */

namespace TenUp\ContentConnect\Tests\CustomRelationships;

use TenUp\ContentConnect\CustomRelationships\Loader;
use TenUp\ContentConnect\Registry;
use TenUp\ContentConnect\Relationships\PostToPost;
use TenUp\ContentConnect\Relationships\PostToUser;
use TenUp\ContentConnect\Tests\ContentConnectTestCase;

/**
 * Test cases for TenUp\ContentConnect\CustomRelationships\Loader.
 */
class LoaderTest extends ContentConnectTestCase {

	use CustomRelationshipTestTrait;

	/**
	 * Sets up the test environment.
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		$this->register_custom_relationship_post_type();
	}

	/**
	 * Tests that a published post-to-post custom relationship is registered with its settings.
	 *
	 * @return void
	 */
	public function test_register_defines_post_to_post_relationship() {
		$post_id = $this->create_custom_relationship();
		$registry      = new Registry();
		$loader        = new Loader();

		$loader->register( $registry );

		$relationship = $registry->get_post_to_post_relationship_by_key( 'car_tire_stored' );

		$this->assertInstanceOf( PostToPost::class, $relationship );
		$this->assertTrue( $relationship->from_sortable );
		$this->assertSame( 5, $relationship->from_max_items );
		$this->assertSame( array( 'name' => 'Tires' ), $relationship->from_labels );
		$this->assertTrue( $relationship->enable_to_ui );
		$this->assertSame( array( 'name' => 'Cars' ), $relationship->to_labels );
		$this->assertSame( $post_id, $loader->get_post_id( 'car_tire_stored' ) );
		$this->assertSame( array(), $loader->get_errors() );
	}

	/**
	 * Tests that a post-to-user custom relationship is registered.
	 *
	 * @return void
	 */
	public function test_register_defines_post_to_user_relationship() {
		$this->create_custom_relationship(
			array(
				'rel_type' => 'post-to-user',
				'rel_to'   => array(),
				'rel_name' => 'owners',
			)
		);
		$registry = new Registry();

		( new Loader() )->register( $registry );

		$this->assertInstanceOf( PostToUser::class, $registry->get_post_to_user_relationship_by_key( 'car_user_owners' ) );
	}

	/**
	 * Tests that an empty panel title falls back to the relationship name.
	 *
	 * @return void
	 */
	public function test_empty_panel_title_falls_back_to_name() {
		$this->create_custom_relationship(
			array(
				'from_args' => array(
					'enable_ui' => true,
					'sortable'  => false,
					'max_items' => 100,
					'labels'    => array( 'name' => '' ),
				),
			)
		);
		$registry = new Registry();

		( new Loader() )->register( $registry );

		$relationship = $registry->get_post_to_post_relationship_by_key( 'car_tire_stored' );

		$this->assertSame( array( 'name' => 'stored' ), $relationship->from_labels );
	}

	/**
	 * Tests that draft custom relationships are not registered.
	 *
	 * @return void
	 */
	public function test_draft_custom_relationships_are_skipped() {
		$this->create_custom_relationship( array(), array( 'post_status' => 'draft' ) );
		$registry = new Registry();

		( new Loader() )->register( $registry );

		$this->assertFalse( $registry->get_post_to_post_relationship_by_key( 'car_tire_stored' ) );
	}

	/**
	 * Tests that a custom relationship whose post type is gone records an error instead of failing.
	 *
	 * @return void
	 */
	public function test_missing_post_type_records_error() {
		$post_id = $this->create_custom_relationship( array( 'rel_from' => 'not_registered' ) );
		$loader        = new Loader();

		$loader->register( new Registry() );

		$errors = $loader->get_errors();

		$this->assertArrayHasKey( $post_id, $errors );
		$this->assertStringContainsString( 'not_registered', $errors[ $post_id ] );
	}

	/**
	 * Tests that a custom relationship whose key is already taken records an error.
	 *
	 * @return void
	 */
	public function test_duplicate_key_records_error() {
		$post_id = $this->create_custom_relationship();
		$registry      = new Registry();
		$loader        = new Loader();

		$registry->define_post_to_post( 'car', 'tire', 'stored' );
		$loader->register( $registry );

		$this->assertArrayHasKey( $post_id, $loader->get_errors() );
		$this->assertNull( $loader->get_post_id( 'car_tire_stored' ) );
	}

	/**
	 * Tests that custom relationships are compiled into the option and read back from it.
	 *
	 * @return void
	 */
	public function test_custom_relationships_are_compiled_into_option() {
		$post_id = $this->create_custom_relationship();
		delete_option( Loader::OPTION );

		$relationships = ( new Loader() )->get_all();
		$stored      = get_option( Loader::OPTION );

		$this->assertCount( 1, $relationships );
		$this->assertSame( $post_id, $relationships[0]['id'] );
		$this->assertSame( $relationships, $stored );
	}

	/**
	 * Tests that saving a custom relationship clears the compiled option.
	 *
	 * @return void
	 */
	public function test_saving_custom_relationship_invalidates_option() {
		$post_id = $this->create_custom_relationship();

		( new Loader() )->compile();
		wp_update_post(
			array(
				'ID'         => $post_id,
				'post_title' => 'Renamed',
			)
		);

		$this->assertFalse( get_option( Loader::OPTION ) );
	}

	/**
	 * Tests that deleting a custom relationship clears the compiled option.
	 *
	 * @return void
	 */
	public function test_deleting_custom_relationship_invalidates_option() {
		$post_id = $this->create_custom_relationship();

		( new Loader() )->compile();
		wp_delete_post( $post_id, true );

		$this->assertFalse( get_option( Loader::OPTION ) );
	}

	/**
	 * Tests that saving other post types keeps the compiled option.
	 *
	 * @return void
	 */
	public function test_saving_other_post_types_keeps_option() {
		$this->create_custom_relationship();

		( new Loader() )->compile();
		$this->factory()->post->create();

		$this->assertIsArray( get_option( Loader::OPTION ) );
	}

	/**
	 * Tests that changing a custom relationship's meta alone clears the compiled option.
	 *
	 * @return void
	 */
	public function test_updating_meta_invalidates_option() {
		$post_id = $this->create_custom_relationship();

		( new Loader() )->compile();
		update_post_meta( $post_id, 'rel_name', 'renamed' );

		$this->assertFalse( get_option( Loader::OPTION ) );
	}

	/**
	 * Tests that meta changes on other post types keep the compiled option.
	 *
	 * @return void
	 */
	public function test_updating_other_meta_keeps_option() {
		$this->create_custom_relationship();
		$other_id = $this->factory()->post->create();

		( new Loader() )->compile();
		update_post_meta( $other_id, 'rel_name', 'unrelated' );

		$this->assertIsArray( get_option( Loader::OPTION ) );
	}
}
