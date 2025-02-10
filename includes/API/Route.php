<?php

namespace TenUp\ContentConnect\API;

abstract class Route {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'content-connect/v2';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = '';

	/**
	 * Setup actions and filters.
	 */
	public function setup() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Registers the REST API routes.
	 *
	 * @return void
	 */
	abstract public function register_routes();

	/**
	 * Get the post, if the ID is valid.
	 *
	 * @param  int $id Supplied ID.
	 * @return \WP_Post|\WP_Error Post object if ID is valid, WP_Error otherwise.
	 */
	protected function get_post( $id ) {

		$error = new \WP_Error(
			'rest_post_invalid_id',
			__( 'Invalid post ID.', 'tenup-content-connect' ),
			array( 'status' => 404 )
		);

		if ( (int) $id <= 0 ) {
			return $error;
		}

		$post = get_post( (int) $id );

		if ( empty( $post ) || empty( $post->ID ) ) {
			return $error;
		}

		return $post;
	}

	/**
	 * Get the user, if the ID is valid.
	 *
	 * @param  int $id Supplied ID.
	 * @return \WP_User|\WP_Error True if ID is valid, WP_Error otherwise.
	 */
	protected function get_user( $id ) {

		$error = new \WP_Error(
			'rest_user_invalid_id',
			__( 'Invalid user ID.', 'tenup-content-connect' ),
			array( 'status' => 404 )
		);

		if ( (int) $id <= 0 ) {
			return $error;
		}

		$user = get_userdata( (int) $id );

		if ( empty( $user ) || ! $user->exists() ) {
			return $error;
		}

		if ( is_multisite() && ! is_user_member_of_blog( $user->ID ) ) {
			return $error;
		}

		return $user;
	}
}
