<?php
/**
 * Tests for the BlockEditor UI class.
 *
 * @package TenUp\ContentConnect\Tests\UI
 */

namespace TenUp\ContentConnect\Tests\UI;

use TenUp\ContentConnect\Tests\ContentConnectTestCase;
use TenUp\ContentConnect\UI\BlockEditor;

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
	 * Tests that block editor assets are enqueued.
	 *
	 * @return void
	 */
	public function test_enqueue_block_editor_assets_enqueues_script_and_style() {
		$block_editor = new BlockEditor();
		$block_editor->enqueue_block_editor_assets();

		$this->assertTrue( wp_script_is( 'wp-content-connect', 'enqueued' ) );
		$this->assertTrue( wp_style_is( 'wp-content-connect', 'enqueued' ) );
	}
}
