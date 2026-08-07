<?php

namespace TenUp\ContentConnect\Tests\Unit\UI;

use TenUp\ContentConnect\Tests\Unit\ContentConnectUnitTestCase;
use TenUp\ContentConnect\UI\MetaBox;

class MetaBoxTest extends ContentConnectUnitTestCase {

	public function test_metabox_isnt_added_without_relationships() {
		$post = $this->mockPost( array( 'post_type' => 'post' ) );

		\WP_Mock::onFilter( 'tenup_content_connect_post_relationship_data' )
			->with( array(), $post )
			->reply( array() );

		\WP_Mock::userFunction( 'add_meta_box', array( 'times' => 0 ) );
		\WP_Mock::userFunction( 'wp_enqueue_script', array( 'times' => 0 ) );
		\WP_Mock::userFunction( 'wp_localize_script', array( 'times' => 0 ) );

		$metabox = new MetaBox();
		$metabox->add_meta_boxes( 'post', $post );

		$this->assertConditionsMet();
	}

	public function test_metabox_is_added_with_relationships() {
		$post          = $this->mockPost( array( 'post_type' => 'post' ) );
		$relationships = array(
			array(
				'reltype' => 'non-empty',
			),
		);

		\WP_Mock::onFilter( 'tenup_content_connect_post_relationship_data' )
			->with( array(), $post )
			->reply( $relationships );

		// The localize filter runs on the assembled data array before it is passed to wp_localize_script.
		\WP_Mock::onFilter( 'tenup_content_connect_localize_data' )
			->with( \WP_Mock\Functions::type( 'array' ) )
			->reply( array( 'relationships' => $relationships ) );

		\WP_Mock::userFunction( '__', array( 'return_arg' => 0 ) );

		\WP_Mock::userFunction( 'add_meta_box', array( 'times' => 1 ) );
		\WP_Mock::userFunction( 'wp_enqueue_script', array( 'times' => 1 ) );
		\WP_Mock::userFunction( 'wp_localize_script', array( 'times' => 1 ) );

		$metabox = new MetaBox();
		$metabox->add_meta_boxes( 'post', $post );

		$this->assertConditionsMet();
	}

}
