<?php

namespace TenUp\P2P\UI;

use TenUp\P2P\Plugin;

class MetaBox {

	public function setup() {
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ), 10, 2 );
		add_action( 'save_post', array( $this, 'save_post' ) );
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
		wp_nonce_field( 'p2p-save', 'tenup-p2p-save' );
		?>
		<div id="tenup-p2p-app"></div>
		<?php
	}

	public function save_post( $post_id ) {
		if ( ! isset( $_POST['tenup-p2p-save'] ) || ! wp_verify_nonce( $_POST['tenup-p2p-save' ], 'p2p-save' ) ) {
			return false;
		}

		$registry = Plugin::instance()->get_registry();

		$relationships = json_decode( wp_unslash( $_POST['tenup-p2p-relationships'] ), true );

		foreach ( $relationships as $relationship_data ) {
			switch( $relationship_data['reltype'] ) {
				case 'post-to-post':
					$relationship = $registry->get_post_to_post_relationship_by_key( $relationship_data['relid'] );
					break;
				case 'post-to-user':
					$relationship = $registry->get_post_to_user_relationship_by_key( $relationship_data['relid'] );
					break;
			}

			$relationship->ui->handle_save( $relationship_data, $post_id );
		}
	}

}
