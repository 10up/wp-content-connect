<?php
/**
 * Tests for the ClassicEditor UI module.
 *
 * @package TenUp\ContentConnect\Tests\UI
 */

namespace TenUp\ContentConnect\Tests\UI;

use TenUp\ContentConnect\Tests\ContentConnectTestCase;
use TenUp\ContentConnect\UI\ClassicEditor;
use function TenUp\ContentConnect\Helpers\get_registry;

/**
 * Tests for the ClassicEditor UI module.
 */
class ClassicEditorTest extends ContentConnectTestCase {

	/**
	 * Sets up the test environment.
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		global $wp_meta_boxes;
		$wp_meta_boxes = array();
	}

	/**
	 * setup() registers the expected hooks.
	 *
	 * @return void
	 */
	public function test_setup_registers_hooks(): void {
		$module = new ClassicEditor();
		$module->setup();

		$this->assertNotFalse( has_action( 'admin_enqueue_scripts', array( $module, 'enqueue_classic_editor_assets' ) ) );
		$this->assertNotFalse( has_action( 'add_meta_boxes', array( $module, 'add_relationships_meta_boxes' ) ) );
	}

	/**
	 * add_relationships_meta_boxes() bails when $post is not a WP_Post.
	 *
	 * @return void
	 */
	public function test_add_relationships_meta_boxes_skips_when_post_invalid(): void {
		$module = new ClassicEditor();

		$module->add_relationships_meta_boxes( 'post', null );

		global $wp_meta_boxes;
		$this->assertEmpty( $wp_meta_boxes );
	}

	/**
	 * add_relationships_meta_boxes() bails when current user lacks edit_post.
	 *
	 * @return void
	 */
	public function test_add_relationships_meta_boxes_skips_when_user_cannot_edit(): void {
		$registry = get_registry();
		$registry->define_post_to_post( 'post', 'post', 'classic-perm' );

		// Anonymous user → cannot edit_post for any post.
		wp_set_current_user( 0 );

		$post = get_post( 1 );
		$this->assertInstanceOf( \WP_Post::class, $post );

		$module = new ClassicEditor();
		$module->add_relationships_meta_boxes( 'post', $post );

		global $wp_meta_boxes;
		$this->assertEmpty( $wp_meta_boxes );
	}

	/**
	 * add_relationships_meta_boxes() bails when the post uses the block editor.
	 *
	 * @return void
	 */
	public function test_add_relationships_meta_boxes_skips_when_block_editor_used(): void {
		$registry = get_registry();
		$registry->define_post_to_post( 'post', 'post', 'classic-block' );

		$user_id = $this->factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $user_id );

		add_filter( 'use_block_editor_for_post', '__return_true' );

		$post = get_post( 1 );

		$module = new ClassicEditor();
		$module->add_relationships_meta_boxes( 'post', $post );

		remove_filter( 'use_block_editor_for_post', '__return_true' );

		global $wp_meta_boxes;
		$this->assertEmpty( $wp_meta_boxes );
	}

	/**
	 * add_relationships_meta_boxes() registers meta boxes for each relationship.
	 *
	 * @return void
	 */
	public function test_add_relationships_meta_boxes_registers_meta_boxes(): void {
		$registry = get_registry();
		$registry->define_post_to_post( 'post', 'post', 'classic-register' );

		$user_id = $this->factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $user_id );

		add_filter( 'use_block_editor_for_post', '__return_false' );

		$post = get_post( 1 );

		$module = new ClassicEditor();
		$module->add_relationships_meta_boxes( 'post', $post );

		remove_filter( 'use_block_editor_for_post', '__return_false' );

		global $wp_meta_boxes;
		$this->assertNotEmpty( $wp_meta_boxes );
		$this->assertArrayHasKey( 'post', $wp_meta_boxes );
		$this->assertArrayHasKey( 'advanced', $wp_meta_boxes['post'] );
		$this->assertArrayHasKey( 'high', $wp_meta_boxes['post']['advanced'] );

		$ids = array_keys( $wp_meta_boxes['post']['advanced']['high'] );
		$this->assertNotEmpty( $ids );

		// Box id should be sanitized — no characters outside [a-z0-9_-].
		foreach ( $ids as $id ) {
			$this->assertMatchesRegularExpression(
				'/^[a-z0-9_-]+$/i',
				$id,
				"Meta box id `{$id}` contains characters that break DOM/CSS expectations"
			);
		}
	}

	/**
	 * render_relationship_meta_box() outputs a properly attributed container div.
	 *
	 * @return void
	 */
	public function test_render_relationship_meta_box_outputs_data_attributes(): void {
		$user_id = $this->factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $user_id );

		$post = get_post( 1 );

		$relationship = array(
			'rel_key'   => 'post_post_test',
			'rel_type'  => 'post-to-post',
			'rel_name'  => 'test',
			'labels'    => array( 'name' => 'Test' ),
			'sortable'  => false,
			'enable_ui' => true,
		);

		$module = new ClassicEditor();

		ob_start();
		$module->render_relationship_meta_box( $post, array( 'args' => array( 'relationship' => $relationship ) ) );
		$output = ob_get_clean();

		$this->assertStringContainsString( 'data-content-connect', $output );
		$this->assertStringContainsString( 'data-post-id="' . $post->ID . '"', $output );
		$this->assertStringContainsString( 'data-relationship=', $output );
	}

	/**
	 * render_relationship_meta_box() bails when the user cannot edit the post.
	 *
	 * @return void
	 */
	public function test_render_relationship_meta_box_skips_when_user_cannot_edit(): void {
		wp_set_current_user( 0 );

		$post = get_post( 1 );

		$module = new ClassicEditor();

		ob_start();
		$module->render_relationship_meta_box( $post, array( 'args' => array( 'relationship' => array( 'rel_key' => 'k' ) ) ) );
		$output = ob_get_clean();

		$this->assertSame( '', trim( $output ) );
	}
}
