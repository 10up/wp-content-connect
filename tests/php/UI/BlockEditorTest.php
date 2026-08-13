<?php
/**
 * Tests for the BlockEditor UI class.
 *
 * @package TenUp\ContentConnect\Tests\UI
 */

namespace TenUp\ContentConnect\Tests\UI;

use TenUp\ContentConnect\Tests\ContentConnectTestCase;
use TenUp\ContentConnect\UI\BlockEditor;
use function TenUp\ContentConnect\Helpers\get_registry;

/**
 * Test cases for TenUp\ContentConnect\UI\BlockEditor.
 */
class BlockEditorTest extends ContentConnectTestCase {

	/**
	 * Tests that setup() hooks the enqueue callback.
	 *
	 * @return void
	 */
	public function test_setup_registers_enqueue_action() {
		$block_editor = new BlockEditor();
		$block_editor->setup();

		$this->assertNotFalse(
			has_action( 'enqueue_block_editor_assets', array( $block_editor, 'enqueue_block_editor_assets' ) )
		);
	}

	/**
	 * Tests that block editor assets are enqueued for an editable post whose
	 * post type has registered relationships.
	 *
	 * @return void
	 */
	public function test_enqueue_block_editor_assets_enqueues_script_and_style() {
		global $post;

		$user_id = $this->factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $user_id );

		get_registry()->define_post_to_post( 'post', 'post', 'test-block-editor-enqueue' );

		$post_id = $this->factory()->post->create( array( 'post_type' => 'post' ) );
		$post    = get_post( $post_id );

		$block_editor = new BlockEditor();
		$block_editor->enqueue_block_editor_assets();

		$this->assertTrue( wp_script_is( 'wp-content-connect-block-editor', 'enqueued' ) );
		$this->assertTrue( wp_style_is( 'wp-content-connect-admin-styles', 'enqueued' ) );
	}

	/**
	 * Tests that assets are not enqueued when the current user cannot edit the post.
	 *
	 * @return void
	 */
	public function test_enqueue_block_editor_assets_skips_without_capability() {
		global $post;

		// Ensure a clean queue: enqueued scripts can leak across tests.
		wp_dequeue_script( 'wp-content-connect-block-editor' );

		$user_id = $this->factory()->user->create( array( 'role' => 'subscriber' ) );
		wp_set_current_user( $user_id );

		get_registry()->define_post_to_post( 'post', 'post', 'test-block-editor-cap' );

		$post_id = $this->factory()->post->create( array( 'post_type' => 'post' ) );
		$post    = get_post( $post_id );

		$block_editor = new BlockEditor();
		$block_editor->enqueue_block_editor_assets();

		$this->assertFalse( wp_script_is( 'wp-content-connect-block-editor', 'enqueued' ) );
	}
}
