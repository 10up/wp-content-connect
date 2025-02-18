<?php

namespace TenUp\ContentConnect\API\V2\Post\Field;

use TenUp\ContentConnect\API\V2\AbstractField;

use function TenUp\ContentConnect\Helpers\get_post_relationship_data;

/**
 * Class Relationships
 *
 * REST API field for post relationships.
 *
 * @package TenUp\ContentConnect\API\V2\Post\Field
 */
class Relationships extends AbstractField {

	/**
	 * {@inheritDoc}
	 */
	public function register_fields() {
		$post_types = get_post_types( array( 'public' => true ) );

		foreach ( $post_types as $post_type ) {

			register_rest_field(
				$post_type,
				'relationships',
				array(
					'get_callback'    => array( $this, 'get_relationships' ),
					'update_callback' => null,
					'schema'          => array(
						'description' => __( 'Lists all relationships associated with this post.', 'tenup-content-connect' ),
						'type'        => 'array',
						'context'     => array( 'view', 'edit', 'embed' ),
					),
				)
			);
		}
	}

	/**
	 * Retrieves a collection of relationships for a post.
	 *
	 * @since 1.7.0
	 *
	 * @param array            $object_data Details of current object.
	 * @param string           $field_name  Name of field.
	 * @param \WP_REST_Request $request     Current request.
	 * @return mixed
	 */
	public function get_relationships( $object_data, $field_name, $request ) {

		if ( empty( $object_data['id'] ) ) {
			return array();
		}

		$post = get_post( (int) $object_data['id'] );

		if ( empty( $post ) || empty( $post->ID ) ) {
			return array();
		}

		$context = $request->get_param( 'context' );

		$relationships = get_post_relationship_data( $post, 'any', false, $context );

		return $relationships;
	}
}
