<?php

namespace TenUp\ContentConnect\API\V2\Route;

use TenUp\ContentConnect\API\V2\AbstractRoute;

use function TenUp\ContentConnect\Helpers\get_post_to_post_relationships_by;
use function TenUp\ContentConnect\Helpers\get_post_to_user_relationships_by;

/**
 * Class Relationships
 *
 * @package TenUp\ContentConnect\API\V2\Route
 */
class Relationships extends AbstractRoute {

	/**
	 * {@inheritDoc}
	 */
	public function register_routes() {

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/relationships',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
					'args'                => array(
						'rel_type'     => array(
							'description'       => __( 'The relationship type to filter relationships by.', 'tenup-content-connect' ),
							'type'              => 'string',
							'default'           => 'post-to-post',
							'sanitize_callback' => 'sanitize_text_field',
							'validate_callback' => 'rest_validate_request_arg',
							'enum'              => array( 'post-to-post', 'post-to-user' ),
						),
						'filter_by'    => array(
							'description'       => __( 'The criteria to filter relationships by.', 'tenup-content-connect' ),
							'type'              => 'string',
							'default'           => 'any',
							'sanitize_callback' => 'sanitize_text_field',
							'validate_callback' => array( $this, 'validate_filter_by' ),
							'enum'              => array( 'key', 'post_type', 'from', 'to', 'any' ),
						),
						'filter_value' => array(
							'description'       => __( 'The value to use with the selected filter.', 'tenup-content-connect' ),
							'type'              => 'string',
							'default'           => '',
							'sanitize_callback' => 'sanitize_text_field',
							'validate_callback' => 'rest_validate_request_arg',
						),
					),
				),
			),
		);
	}

	/**
	 * Retrieves a collection of relationships by type.
	 *
	 * @since 1.7.0
	 *
	 * @param  \WP_REST_Request $request Full details about the request.
	 * @return \WP_REST_Response
	 */
	public function get_items( \WP_REST_Request $request ) {

		$rel_type     = $request->get_param( 'rel_type' );
		$filter_by    = $request->get_param( 'filter_by' );
		$filter_value = $request->get_param( 'filter_value' );

		$relationships = array();

		switch ( $rel_type ) {
			case 'post-to-post':
				$relationships = get_post_to_post_relationships_by( $filter_by, $filter_value );
				break;
			case 'post-to-user':
				$relationships = get_post_to_user_relationships_by( $filter_by, $filter_value );
				break;
		}

		$prepared_relationships = array();

		foreach ( $relationships as $rel_key => $relationship ) {

			$prepared_relationships[ $rel_key ] = array(
				'rel_key'  => $rel_key,
				'rel_type' => $rel_type,
				'rel_name' => $relationship->name,
			);

			switch ( $rel_type ) {
				case 'post-to-user':
					$prepared_relationships[ $rel_key ]['object_type'] = 'user';
					$prepared_relationships[ $rel_key ]['labels']      = $relationship->from_labels;
					$prepared_relationships[ $rel_key ]['sortable']    = $relationship->from_sortable;
					$prepared_relationships[ $rel_key ]['enable_ui']   = $relationship->enable_from_ui;
					break;

				case 'post-to-post':
					$prepared_relationships[ $rel_key ]['object_type'] = 'post';
					$prepared_relationships[ $rel_key ]['from']        = array(
						'object_type' => $relationship->from,
						'labels'      => $relationship->from_labels,
						'sortable'    => $relationship->from_sortable,
						'enable_ui'   => $relationship->enable_from_ui,
					);
					$prepared_relationships[ $rel_key ]['to']          = array(
						'object_type' => $relationship->to,
						'labels'      => $relationship->to_labels,
						'sortable'    => $relationship->to_sortable,
						'enable_ui'   => $relationship->enable_to_ui,
					);
					break;
			}
		}

		$response = rest_ensure_response( $prepared_relationships );

		return $response;
	}

	/**
	 * Checks if a given request has access to retrieve relationships by type.
	 *
	 * @since 1.7.0
	 *
	 * @param  \WP_REST_Request $request Full details about the request.
	 * @return true|\WP_Error True if the request has access, WP_Error object otherwise.
	 */
	public function get_items_permissions_check( \WP_REST_Request $request ) {

		if ( ! is_user_logged_in() ) {
			return new \WP_Error(
				'rest_forbidden',
				__( 'Sorry, you are not allowed to view relationships for this type.', 'tenup-content-connect' ),
				array( 'status' => 401 )
			);
		}

		return true;
	}

	/**
	 * Validates the `filter_by` parameter.
	 *
	 * @since 1.7.0
	 *
	 * @param  mixed            $value   The value to validate.
	 * @param  \WP_REST_Request $request Full details about the request.
	 * @param  string           $param   The name of the parameter.
	 * @return true|\WP_Error True if the value is valid, WP_Error object otherwise.
	 */
	public function validate_filter_by( $value, $request, $param ) {

		$result = rest_validate_request_arg( $value, $request, $param );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$rel_type = $request->get_param( 'rel_type' );

		if ( 'post-to-user' === $rel_type && in_array( $value, array( 'post_type', 'to' ), true ) ) {
			return new \WP_Error(
				'rest_invalid_param',
				/* translators: %s: filter value */
				sprintf( __( '%s is not valid for post-to-user relationships', 'tenup-content-connect' ), $value ),
				array( 'status' => 400 )
			);
		}

		return true;
	}
}
