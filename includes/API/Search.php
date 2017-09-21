<?php

namespace TenUp\ContentConnect\API;

class Search {

	public function setup() {
		add_action( 'rest_api_init', array( $this, 'register_endpoint' ) );
		add_filter( 'tenup_content_connect_localize_data', array( $this, 'localize_endpoints' ) );
	}

	public function register_endpoint() {
		register_rest_route( 'content-connect/v1', '/search', array(
			'methods' => 'POST',
			'callback' => array( $this, 'process_search' ),
			'permission_callback' => array( $this, 'check_permission' ),
		) );
	}

	public function localize_endpoints( $data ) {
		$data['endpoints']['search'] = get_rest_url( get_current_blog_id(), 'content-connect/v1/search' );
		$data['nonces']['search'] = wp_create_nonce( 'content-connect-search' );

		return $data;
	}

	/**
	 * @param \WP_REST_Request $request
	 *
	 * @return bool
	 */
	public function check_permission( $request ) {
		$user = wp_get_current_user();

		if ( $user->ID === 0 ) {
			return false;
		}

		$nonce = $request->get_param( 'nonce' );

		// If the user got the nonce, they were on the proper edit page
		if ( ! wp_verify_nonce( $nonce, 'content-connect-search' ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Handles calls to the search endpoint
	 *
	 * @param $request \WP_REST_Request
	 *
	 * @return array Array of posts or users that match the query
	 */
	public function process_search( $request ) {
		$object_type = $request->get_param( 'object_type' );

		if ( ! in_array( $object_type, array( 'post', 'user' ) ) ) {
			return array();
		}

		if ( $object_type === 'post' ) {
			$post_type = $request->get_param( 'post_type' );

			if ( ! post_type_exists( $post_type ) ) {
				return array();
			}
		}

		$search_text = sanitize_text_field( $request->get_param( 'search' ) );

		// @todo pagination
		switch( $object_type ) {
			case 'user':
				$results = $this->search_users( $search_text );
				break;
			case 'post':
				$results = $this->search_posts( $search_text, $post_type );
				break;
		}

		return $results;
	}

	public function search_users( $search_text ) {
		$query = new \WP_User_Query( array(
			'search' => $search_text,
		) );

		$results = array();

		// Normalize Formatting
		foreach( $query->get_results() as $user ) {
			$results[] = array(
				'ID' => $user->ID,
				'name' => $user->display_name,
			);
		}

		return $results;
	}

	public function search_posts( $search_text, $post_type ) {
		$query = new \WP_Query( array(
			'post_type' => $post_type,
			's' => $search_text,
		) );

		$results = array();

		// Normalize Formatting
		if ( $query->have_posts() ) {
			while( $query->have_posts() ) {
				$post = $query->next_post();

				$results[] = array(
					'ID' => $post->ID,
					'name' => $post->post_title,
				);
			}
		}

		return $results;
	}

}
