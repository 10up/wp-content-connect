<?php
/**
 * Tests for the ClassicEditor UI class.
 *
 * @package TenUp\ContentConnect\Tests\UI
 */

namespace TenUp\ContentConnect\Tests\UI;

use TenUp\ContentConnect\Tests\ContentConnectTestCase;
use TenUp\ContentConnect\UI\ClassicEditor;
use function TenUp\ContentConnect\Helpers\get_registry;
use function TenUp\ContentConnect\Helpers\get_post_relationships_data;

/**
 * Test cases for TenUp\ContentConnect\UI\ClassicEditor.
 */
class ClassicEditorTest extends ContentConnectTestCase {

	/**
	 * Sets up an admin user and two post-to-post relationships (one with the
	 * editing UI enabled, one disabled) so meta-box behaviour can be asserted.
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );

		$registry = get_registry();

		try {
			$registry->define_post_to_post( 'post', 'post', 'ce_enabled' );
		} catch ( \Exception $e ) {
			// Already defined in a previous test; that's fine.
		}

		try {
			$registry->define_post_to_post( 'post', 'post', 'ce_disabled', array( 'from' => array( 'enable_ui' => false ) ) );
		} catch ( \Exception $e ) {
			// Already defined in a previous test; that's fine.
		}
	}

	/**
	 * Collects every registered meta-box ID for the given screen.
	 *
	 * @param string $screen The screen (post type) key.
	 * @return array<int, string> The registered meta-box IDs.
	 */
	private function get_registered_meta_box_ids( $screen ) {
		$ids = array();

		if ( empty( $GLOBALS['wp_meta_boxes'][ $screen ] ) ) {
			return $ids;
		}

		foreach ( $GLOBALS['wp_meta_boxes'][ $screen ] as $contexts ) {
			foreach ( $contexts as $priorities ) {
				foreach ( $priorities as $id => $box ) {
					$ids[] = $id;
				}
			}
		}

		return $ids;
	}

	/**
	 * Tests that setup() hooks the enqueue and meta-box callbacks.
	 *
	 * @return void
	 */
	public function test_setup_registers_hooks() {
		$classic_editor = new ClassicEditor();
		$classic_editor->setup();

		$this->assertNotFalse(
			has_action( 'admin_enqueue_scripts', array( $classic_editor, 'enqueue_classic_editor_assets' ) )
		);
		$this->assertNotFalse(
			has_action( 'add_meta_boxes', array( $classic_editor, 'add_relationships_meta_boxes' ) )
		);
	}

	/**
	 * Tests that meta boxes are added for UI-enabled relationships and skipped
	 * for relationships whose UI is disabled.
	 *
	 * @return void
	 */
	public function test_add_relationships_meta_boxes_registers_only_enabled_relationships() {
		$GLOBALS['wp_meta_boxes'] = array();

		$post = get_post( 1 );

		// add_relationships_meta_boxes only runs in the classic editor.
		add_filter( 'use_block_editor_for_post', '__return_false' );

		$classic_editor = new ClassicEditor();
		$classic_editor->add_relationships_meta_boxes( 'post', $post );

		$ids           = $this->get_registered_meta_box_ids( 'post' );
		$relationships = get_post_relationships_data( $post );

		remove_filter( 'use_block_editor_for_post', '__return_false' );

		$enabled_box_ids  = array();
		$disabled_box_ids = array();

		foreach ( $relationships as $rel_key => $relationship ) {
			$box_id = 'wp-content-connect-relationship-' . $rel_key;

			if ( empty( $relationship['enable_ui'] ) ) {
				$disabled_box_ids[] = $box_id;
			} else {
				$enabled_box_ids[] = $box_id;
			}
		}

		// Guard against a vacuous pass: both branches must have been exercised.
		$this->assertNotEmpty( $enabled_box_ids, 'Expected at least one UI-enabled relationship.' );
		$this->assertNotEmpty( $disabled_box_ids, 'Expected at least one UI-disabled relationship.' );

		foreach ( $enabled_box_ids as $box_id ) {
			$this->assertTrue( in_array( $box_id, $ids, true ) );
		}

		foreach ( $disabled_box_ids as $box_id ) {
			$this->assertFalse( in_array( $box_id, $ids, true ) );
		}
	}

	/**
	 * Tests that no meta boxes are added when the post is not a WP_Post.
	 *
	 * @return void
	 */
	public function test_add_relationships_meta_boxes_bails_without_wp_post() {
		$GLOBALS['wp_meta_boxes'] = array();

		$classic_editor = new ClassicEditor();
		$classic_editor->add_relationships_meta_boxes( 'post', null );

		$this->assertSame( array(), $this->get_registered_meta_box_ids( 'post' ) );
	}

	/**
	 * Tests that the meta box renders the mount point with post ID and
	 * relationship payload data attributes.
	 *
	 * @return void
	 */
	public function test_render_relationship_meta_box_outputs_data_attributes() {
		$post         = get_post( 1 );
		$relationship = array(
			'rel_key'  => 'ce_enabled',
			'rel_type' => 'post-to-post',
			'labels'   => array( 'name' => 'Enabled' ),
		);

		$classic_editor = new ClassicEditor();

		ob_start();
		$classic_editor->render_relationship_meta_box( $post, array( 'args' => array( 'relationship' => $relationship ) ) );
		$output = ob_get_clean();

		$this->assertStringContainsString( 'data-content-connect', $output );
		$this->assertStringContainsString( 'data-post-id="1"', $output );
		$this->assertStringContainsString( esc_attr( wp_json_encode( $relationship ) ), $output );
	}

	/**
	 * Tests that the meta box renders nothing when no relationship is passed.
	 *
	 * @return void
	 */
	public function test_render_relationship_meta_box_bails_without_relationship() {
		$post = get_post( 1 );

		$classic_editor = new ClassicEditor();

		ob_start();
		$classic_editor->render_relationship_meta_box( $post, array( 'args' => array() ) );
		$output = ob_get_clean();

		$this->assertSame( '', trim( $output ) );
	}

	/**
	 * Tests that assets are not enqueued outside the post edit screens.
	 *
	 * @return void
	 */
	public function test_enqueue_bails_on_non_edit_screen() {
		global $post;
		$post = get_post( 1 );

		$classic_editor = new ClassicEditor();
		$classic_editor->enqueue_classic_editor_assets( 'edit.php' );

		$this->assertFalse( wp_script_is( 'wp-content-connect-classic-editor', 'enqueued' ) );
	}

	/**
	 * Tests that assets are enqueued on the classic post edit screen.
	 *
	 * @return void
	 */
	public function test_enqueue_on_classic_edit_screen() {
		global $post;
		$post = get_post( 1 );

		// Force the classic editor path.
		add_filter( 'use_block_editor_for_post', '__return_false' );

		$classic_editor = new ClassicEditor();
		$classic_editor->enqueue_classic_editor_assets( 'post.php' );

		remove_filter( 'use_block_editor_for_post', '__return_false' );

		$this->assertTrue( wp_script_is( 'wp-content-connect-classic-editor', 'enqueued' ) );
	}
}
