<?php

namespace TenUp\ContentConnect\UI;

use TenUp\ContentConnect\Plugin;

class MetaBox {

	public function setup() {
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ), 10, 2 );
		add_action( 'save_post', array( $this, 'save_post' ) );
	}

	public function add_meta_boxes( $post_type, $post ) {
		// If we have any relationships to show on this page, their data will be injected here by filters
		$relationships = apply_filters( 'tenup_content_connect_post_relationship_data', array(), $post );

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

		\add_meta_box( 'tenup-content-connect-relationships', __( "Relationships", "tenup-content-connect" ), array( $this, 'render' ), $post_type, 'advanced', 'high' );

		wp_enqueue_script( 'tenup-content-connect', Plugin::instance()->url . 'assets/js/content-connect.js', array(), Plugin::instance()->version, true );
		wp_localize_script( 'tenup-content-connect', 'ContentConnectData', apply_filters( 'tenup_content_connect_localize_data', $relationship_data ) );
	}

	public function render( $post, $metabox ) {
		wp_nonce_field( 'content-connect-save', 'tenup-content-connect-save' );
		?>
		<div id="tenup-content-connect-app"></div>
		<?php
	}

	public function save_post( $post_id ) {
		if ( ! isset( $_POST['tenup-content-connect-save'] ) || ! wp_verify_nonce( $_POST['tenup-content-connect-save' ], 'content-connect-save' ) ) {
			return false;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return false;
		}

		$registry = Plugin::instance()->get_registry();

		$relationships = json_decode( wp_unslash( $_POST['tenup-content-connect-relationships'] ), true );

		foreach ( $relationships as $relationship_data ) {
			switch( $relationship_data['reltype'] ) {
				case 'post-to-post':
					$relationship = $registry->get_post_to_post_relationship_by_key( $relationship_data['relid'] );
					break;
				case 'post-to-user':
					$relationship = $registry->get_post_to_user_relationship_by_key( $relationship_data['relid'] );
					break;
			}

			// Deteremine save direction and call proper save function
			$post_type = get_post_type( $post_id );
			if ( $relationship->from_ui->render_post_type === $post_type ) {
				$relationship->from_ui->handle_save( $relationship_data, $post_id );
			} else if ( is_object( $relationship->to_ui ) && $relationship->to_ui->render_post_type === $post_type ) {
				$relationship->to_ui->handle_save( $relationship_data, $post_id );
			}

		}
	}

}
