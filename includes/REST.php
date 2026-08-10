<?php

namespace TenUp\ContentConnect;

use function TenUp\ContentConnect\Helpers\get_post_relationships_data;

class REST {

	/**
	 * Setup the REST module.
	 *
	 * @since 2.0.0
	 */
	public function setup() {
		add_action( 'rest_api_init', array( $this, 'add_links' ) );
	}

	/**
	 * Adds links to the REST API responses for post types that support REST.
	 *
	 * @since 2.0.0
	 */
	public function add_links() {

		$post_types = get_post_types( array( 'show_in_rest' => true ), 'names' );
		foreach ( $post_types as $post_type ) {
			add_action( "rest_prepare_{$post_type}", array( $this, 'prepare_links' ), 10, 2 );
		}
	}

	/**
	 * Prepares the links for the REST API response.
	 *
	 * @since 2.0.0
	 *
	 * @param  \WP_REST_Response $response The response object.
	 * @param  \WP_Post          $post     The post object.
	 * @return \WP_REST_Response
	 */
	public function prepare_links( $response, $post ) {

		if ( ! current_user_can( 'read_post', $post->ID ) ) {
			return $response;
		}

		$relationships_data = get_post_relationships_data( $post->ID );

		if ( empty( $relationships_data ) ) {
			return $response;
		}

		$response->add_link(
			'content-connect:relationships',
			rest_url( sprintf( '/content-connect/v2/post/%d/relationships', $post->ID ) )
		);

		foreach ( $relationships_data as $relationship ) {
			$response->add_link(
				'content-connect:related',
				rest_url(
					sprintf(
						'/content-connect/v2/post/%1$d/related/?rel_key=%2$s&rel_type=%3$s',
						$post->ID,
						rawurlencode( $relationship['rel_key'] ),
						$relationship['rel_type']
					)
				),
				array( 'relationship' => $relationship['rel_name'] )
			);
		}

		return $response;
	}
}
