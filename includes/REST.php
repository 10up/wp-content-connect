<?php

namespace TenUp\ContentConnect;

use function TenUp\ContentConnect\Helpers\get_post_relationships_data;

class REST {

	/**
	 * Per-request cache of relationship data, keyed by post type.
	 *
	 * In the `view` context the relationship set depends only on the post type,
	 * so it can be reused across every post of that type in a collection response.
	 *
	 * @since 2.0.0
	 *
	 * @var array<string, array>
	 */
	private $relationships_by_type = array();

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
			add_filter( "rest_prepare_{$post_type}", array( $this, 'prepare_links' ), 10, 3 );
		}
	}

	/**
	 * Prepares the links for the REST API response.
	 *
	 * @since 2.0.0
	 *
	 * @param  \WP_REST_Response     $response The response object.
	 * @param  \WP_Post              $post     The post object.
	 * @param  \WP_REST_Request|null $request  The request object.
	 * @return \WP_REST_Response
	 */
	public function prepare_links( $response, $post, $request = null ) {

		$can_edit_post = current_user_can( 'edit_post', $post->ID );

		/**
		 * Filters whether Content Connect links are added to a post's REST response.
		 *
		 * The links point to endpoints that require the `edit_post` capability, so by
		 * default they are only added for users who can edit the post.
		 *
		 * @since 2.0.0
		 *
		 * @param bool                  $add_links Whether to add the links.
		 * @param \WP_Post              $post      The post object.
		 * @param \WP_REST_Request|null $request   The request object.
		 */
		$add_links = (bool) apply_filters( 'tenup_content_connect_add_rest_links', $can_edit_post, $post, $request );

		if ( ! $add_links ) {
			return $response;
		}

		// The relationship set is the same for every post of a given type in the
		// view context, so compute it once per type per request.
		if ( ! isset( $this->relationships_by_type[ $post->post_type ] ) ) {
			$this->relationships_by_type[ $post->post_type ] = get_post_relationships_data( $post->ID );
		}

		$relationships_data = $this->relationships_by_type[ $post->post_type ];

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
