<?php

namespace TenUp\P2P\UI;

use TenUp\P2P\Plugin;

class MetaBox {

	public function setup() {
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ), 10, 2 );
	}

	public function add_meta_boxes( $post_type, $post ) {
		// If we have any relationships to show on this page, their data will be injected here by filters
		$relationships = apply_filters( 'tenup_p2p_post_relationship_data', array(), $post );

		$relationship_data = array(
			'nonces' => array(
				'wp_rest' => wp_create_nonce( 'wp_rest' ),
			),
			'endpoints' => array(),
			'relationships' => $relationships,
		);

		if ( empty( $relationship_data['relationships'] ) ) {
			return;
		}

		\add_meta_box( 'tenup-p2p-relationships', __( "Relationships", "tenup-p2p" ), array( $this, 'render' ), $post_type, 'advanced', 'high' );

		wp_enqueue_script( 'tenup-p2p', Plugin::instance()->url . 'assets/js/p2p.js', array(), Plugin::instance()->version, true );
		wp_localize_script( 'tenup-p2p', 'P2PData', apply_filters( 'tenup_p2p_localize_data', $relationship_data ) );
	}

	public function render( $post, $metabox ) {
		?>
		<div id="tenup-p2p-app"></div>
		<?php
	}

}
