<?php
/**
 * Tests for the BlockEditor UI module.
 *
 * @package TenUp\ContentConnect\Tests\UI
 */

namespace TenUp\ContentConnect\Tests\UI;

use TenUp\ContentConnect\Tests\ContentConnectTestCase;
use TenUp\ContentConnect\UI\BlockEditor;
use function TenUp\ContentConnect\Helpers\get_registry;

/**
 * Tests for the BlockEditor UI module.
 */
class BlockEditorTest extends ContentConnectTestCase {

	/**
	 * Sets up the test environment.
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		// Ensure each test starts with a clean current screen.
		set_current_screen( 'front' );
	}

	/**
	 * Tears down the test environment.
	 *
	 * @return void
	 */
	public function tearDown(): void {
		set_current_screen( 'front' );
		parent::tearDown();
	}

	/**
	 * setup() registers the expected hooks.
	 *
	 * @return void
	 */
	public function test_setup_registers_hooks(): void {
		$module = new BlockEditor();
		$module->setup();

		$this->assertNotFalse( has_action( 'init', array( $module, 'register_edit_lock_meta' ) ) );
		$this->assertNotFalse( has_action( 'enqueue_block_editor_assets', array( $module, 'enqueue_block_editor_assets' ) ) );
	}

	/**
	 * register_edit_lock_meta() registers the meta only for post types with relationships.
	 *
	 * @return void
	 */
	public function test_register_edit_lock_meta_only_registers_for_relationship_post_types(): void {
		$registry = get_registry();
		$registry->define_post_to_post( 'post', 'post', 'edit-lock-test' );

		$module = new BlockEditor();
		$module->register_edit_lock_meta();

		$post_meta = get_registered_meta_keys( 'post', 'post' );
		$this->assertArrayHasKey( BlockEditor::EDIT_LOCK_META_KEY, $post_meta );

		// `page` has no relationships defined → meta should NOT be registered.
		$page_meta = get_registered_meta_keys( 'post', 'page' );
		$this->assertArrayNotHasKey( BlockEditor::EDIT_LOCK_META_KEY, $page_meta );
	}

	/**
	 * Edit-lock meta auth_callback grants writes only to users with edit_post.
	 *
	 * @return void
	 */
	public function test_edit_lock_meta_auth_callback_requires_edit_post(): void {
		$registry = get_registry();
		$registry->define_post_to_post( 'post', 'post', 'edit-lock-cap' );

		$module = new BlockEditor();
		$module->register_edit_lock_meta();

		$registered_meta = get_registered_meta_keys( 'post', 'post' );
		$this->assertArrayHasKey( BlockEditor::EDIT_LOCK_META_KEY, $registered_meta );
		$this->assertTrue( $registered_meta[ BlockEditor::EDIT_LOCK_META_KEY ]['show_in_rest'] );
		$this->assertSame( 'integer', $registered_meta[ BlockEditor::EDIT_LOCK_META_KEY ]['type'] );
		$this->assertTrue( $registered_meta[ BlockEditor::EDIT_LOCK_META_KEY ]['single'] );
	}

	/**
	 * enqueue_block_editor_assets() returns silently when there are no relationships for the current post type.
	 *
	 * @return void
	 */
	public function test_enqueue_block_editor_assets_skips_post_type_without_relationships(): void {
		// page has no relationships defined.
		set_current_screen( 'edit-page' );

		$module = new BlockEditor();
		$module->enqueue_block_editor_assets();

		$this->assertFalse( wp_script_is( 'wp-content-connect-block-editor', 'enqueued' ) );
		$this->assertFalse( wp_style_is( 'wp-content-connect-admin-styles', 'enqueued' ) );
	}

	/**
	 * enqueue_block_editor_assets() returns silently when there is no current screen.
	 *
	 * @return void
	 */
	public function test_enqueue_block_editor_assets_skips_when_no_current_screen(): void {
		// Force a screen with no post_type (e.g. front-end requests).
		set_current_screen( 'front' );

		$module = new BlockEditor();
		$module->enqueue_block_editor_assets();

		$this->assertFalse( wp_script_is( 'wp-content-connect-block-editor', 'enqueued' ) );
	}
}
