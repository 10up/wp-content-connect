<?php

namespace TenUp\ContentConnect\QueryIntegration;

class QueryBlockIntegration {

	/**
	 * Setup the Query block integration module.
	 *
	 * @since 1.7.0
	 */
	public function setup() {
		add_action( 'rest_api_init', array( $this, 'rest_api_init' ) );
		add_filter( 'query_loop_block_query_vars', array( $this, 'modify_query_loop_query' ), 10, 2 );
	}

	/**
	 * Registers the necessary REST API modifications for supported post types.
	 *
	 * @since 1.7.0
	 *
	 * @return void
	 */
	public function rest_api_init() {

		$post_types          = get_post_types( array( 'public' => true ) );
		$excluded_post_types = array( 'attachment' );

		foreach ( $post_types as $post_type ) {

			if ( in_array( $post_type, $excluded_post_types, true ) ) {
				continue;
			}

			add_filter( "rest_{$post_type}_query", array( $this, 'rest_post_query' ), 10, 2 );
		}
	}

	/**
	 * Modifies the REST API query to support relationship-based filtering and ordering.
	 *
	 * @since 1.7.0
	 *
	 * @param  array $args    Array of arguments for \WP_Query.
	 * @param  array $request The REST API request.
	 * @return array Modified query arguments.
	 */
	public function rest_post_query( $args, $request ) {

		if ( isset( $request['relationshipQuery'] ) && is_array( $request['relationshipQuery'] ) ) {
			$args['relationship_query'] = $request['relationshipQuery'];
		}

		$order_by_relationship = rest_sanitize_boolean( $request['orderByRelationship'] ?? false );

		if ( ! empty( $order_by_relationship ) ) {
			$args['orderby'] = 'relationship';
		}

		return $args;
	}

	/**
	 * Modifies the query loop arguments when the block is rendered on the front end.
	 *
	 * Reads the relationship attributes from the block's own query context, so each
	 * Query Loop on the page is handled independently.
	 *
	 * @since 1.7.0
	 *
	 * @param  array     $query_args Array containing parameters for `WP_Query`.
	 * @param  \WP_Block $block      The block being rendered.
	 * @return array Modified query arguments.
	 */
	public function modify_query_loop_query( $query_args, $block ) {

		$query_attrs = ( isset( $block->context['query'] ) && is_array( $block->context['query'] ) )
			? $block->context['query']
			: array();

		if ( ! empty( $query_attrs['relationshipQuery'] ) && is_array( $query_attrs['relationshipQuery'] ) ) {
			$query_args['relationship_query'] = $query_attrs['relationshipQuery'];
		}

		if ( ! empty( $query_attrs['orderByRelationship'] ) ) {
			$query_args['orderby'] = 'relationship';
		}

		return $query_args;
	}
}
