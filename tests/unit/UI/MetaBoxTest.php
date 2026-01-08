<?php

namespace TenUp\ContentConnect\Tests\Unit\UI;

use TenUp\ContentConnect\Plugin;
use TenUp\ContentConnect\Tests\Unit\ContentConnectUnitTestCase;
use TenUp\ContentConnect\UI\MetaBox;

class MetaBoxTest extends ContentConnectUnitTestCase {

	/**
	 * Creates a mock WP_Post object for testing using WP_Mock's mockPost method.
	 *
	 * @return \WP_Post
	 */
	private function get_mock_post() {
		// Use WP_Mock's mockPost implementation
		// This mirrors the implementation from WP_Mock\Tools\TestCase::mockPost()
		$post = \Mockery::mock( 'WP_Post' );
		$data = array_merge( array(
			'ID'                => 0,
			'post_author'       => 0,
			'post_type'         => '',
			'post_title'        => '',
			'post_date'         => '',
			'post_date_gmt'     => '',
			'post_content'      => '',
			'post_excerpt'      => '',
			'post_status'       => '',
			'comment_status'    => '',
			'ping_status'       => '',
			'post_password'     => '',
			'post_parent'       => 0,
			'post_modified'     => '',
			'post_modified_gmt' => '',
			'comment_count'     => 0,
			'menu_order'        => 0,
		), array(
			'ID' => 1,
			'post_type' => 'post',
			'post_title' => 'Test Post',
			'post_content' => '',
			'post_status' => 'publish',
		) );

		array_walk( $data, function ( $value, $prop ) use ( $post ) {
			$post->$prop = $value;
		} );

		return $post;
	}

	public function test_metabox_isnt_added_without_relationships() {
		$post = $this->get_mock_post();

		\WP_Mock::onFilter( 'tenup_content_connect_post_relationship_data' )
		        ->with( array(), $post )
		        ->reply(
		        	array()
	            );

		\WP_Mock::userFunction( 'add_meta_box', array( 'times' => 0 ) );
		\WP_Mock::userFunction( 'wp_enqueue_script', array( 'times' => 0 ) );
		\WP_Mock::userFunction( 'wp_localize_script', array( 'times' => 0 ) );

		$metabox = new MetaBox();
		$metabox->add_meta_boxes( 'post', $post );
	}

	public function test_metabox_is_added_with_relationships() {
		$post = $this->get_mock_post();

		\WP_Mock::onFilter( 'tenup_content_connect_post_relationship_data' )
		        ->with( array(), $post )
		        ->reply(
			        array(
				        array(
					        'reltype' => 'non-empty',
				        ),
			        )
		        );

		\WP_Mock::userFunction( '__', array(
			'args' => array( 'Relationships', 'tenup-content-connect' ),
			'return' => 'Relationships',
			'times' => 1,
		) );
		\WP_Mock::userFunction( 'add_meta_box', array( 'times' => 1 ) );
		\WP_Mock::userFunction( 'wp_enqueue_script', array( 'times' => 1 ) );
		\WP_Mock::userFunction( 'wp_localize_script', array( 'times' => 1 ) );
		\WP_Mock::onFilter( 'tenup_content_connect_localize_data' )
		        ->with( \Mockery::type( 'array' ) )
		        ->reply( new \WP_Mock\InvokedFilterValue( function( $data ) {
			        return $data;
		        } ) );

		$metabox = new MetaBox();
		$metabox->add_meta_boxes( 'post', $post );
	}

}
