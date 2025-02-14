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
						'context'     => array( 'view', 'edit' ),
					),
				)
			);
		}
	}

	/**
	 * Retrieves a collection of relationships for a post.
	 *
	 * @param  array $post_data Raw post data from the REST API request.
	 * @return array
	 */
	public function get_relationships( $post_data ) {

		if ( empty( $post_data['id'] ) ) {
			return array();
		}

		$post = get_post( (int) $post_data['id'] );

		if ( empty( $post ) || empty( $post->ID ) ) {
			return array();
		}

		$relationships = get_post_relationship_data( $post );

		return $relationships;
	}
}
