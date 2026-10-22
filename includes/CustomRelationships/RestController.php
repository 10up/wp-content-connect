<?php

namespace TenUp\ContentConnect\CustomRelationships;

/**
 * Class RestController
 *
 * REST controller for custom relationships. Reading them requires
 * the same capability as editing them.
 *
 * @package TenUp\ContentConnect\CustomRelationships
 */
class RestController extends \WP_REST_Posts_Controller {

	/**
	 * Checks if a given request has access to read custom relationships.
	 *
	 * @since 2.1.0
	 *
	 * @param  \WP_REST_Request $request Full details about the request.
	 * @return true|\WP_Error
	 */
	public function get_items_permissions_check( $request ) {

		if ( ! current_user_can( 'manage_options' ) ) {
			return $this->forbidden();
		}

		return parent::get_items_permissions_check( $request );
	}

	/**
	 * Checks if a given request has access to read a custom relationship.
	 *
	 * @since 2.1.0
	 *
	 * @param  \WP_REST_Request $request Full details about the request.
	 * @return true|\WP_Error
	 */
	public function get_item_permissions_check( $request ) {

		if ( ! current_user_can( 'manage_options' ) ) {
			return $this->forbidden();
		}

		return parent::get_item_permissions_check( $request );
	}

	/**
	 * Builds the error returned to users who cannot manage custom relationships.
	 *
	 * @since 2.1.0
	 *
	 * @return \WP_Error
	 */
	protected function forbidden() {
		return new \WP_Error(
			'rest_forbidden',
			__( 'Sorry, you are not allowed to view custom relationships.', 'wp-content-connect' ),
			array( 'status' => rest_authorization_required_code() )
		);
	}
}
