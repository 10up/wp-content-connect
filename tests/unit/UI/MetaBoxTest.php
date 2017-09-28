<?php

namespace TenUp\ContentConnect\Tests\Unit\UI;

use TenUp\ContentConnect\Plugin;
use TenUp\ContentConnect\Tests\Unit\ContentConnectUnitTestCase;
use TenUp\ContentConnect\UI\MetaBox;

class MetaBoxTest extends ContentConnectUnitTestCase {

	public function test_metabox_isnt_added_without_relationships() {
		\WP_Mock::onFilter( 'tenup_content_connect_post_relationship_data' )
		        ->with( array(), new \stdClass() )
		        ->reply(
		        	array()
	            );

		\WP_Mock::userFunction( 'add_meta_box', array( 'times' => 0 ) );
		\WP_Mock::userFunction( 'wp_enqueue_script', array( 'times' => 0 ) );
		\WP_Mock::userFunction( 'wp_localize_script', array( 'times' => 0 ) );

		$metabox = new MetaBox();
		$metabox->add_meta_boxes( 'post', new \stdClass() );
	}

	public function test_metabox_is_added_with_relationships() {
		\WP_Mock::onFilter( 'tenup_content_connect_post_relationship_data' )
		        ->with( array(), new \stdClass() )
		        ->reply(
			        array(
				        array(
					        'reltype' => 'non-empty',
				        ),
			        )
		        );

		\WP_Mock::userFunction( 'add_meta_box', array( 'times' => 1 ) );
		\WP_Mock::userFunction( 'wp_enqueue_script', array( 'times' => 1 ) );
		\WP_Mock::userFunction( 'wp_localize_script', array( 'times' => 1 ) );

		$metabox = new MetaBox();
		$metabox->add_meta_boxes( 'post', new \stdClass() );
	}

}
