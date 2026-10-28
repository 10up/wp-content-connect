<?php

namespace TenUp\ContentConnect\QueryIntegration;

class QueryBlockIntegration {

	/**
	 * Setup the Query block integration module.
	 *
	 * @since 2.0.0
	 */
	public function setup() {
		add_action( 'rest_api_init', array( $this, 'rest_api_init' ) );
		add_filter( 'query_loop_block_query_vars', array( $this, 'modify_query_loop_query' ), 10, 2 );
	}

	/**
	 * Registers the necessary REST API modifications for supported post types.
	 *
	 * @since 2.0.0
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
	 * @since 2.0.0
	 *
	 * @param  array            $args    Array of arguments for \WP_Query.
	 * @param  \WP_REST_Request $request The REST API request.
	 * @return array Modified query arguments.
	 */
	public function rest_post_query( $args, $request ) {

		if ( ! isset( $request['relationshipQuery'] ) && ! isset( $request['orderByRelationship'] ) ) {
			return $args;
		}

		$post_type_object = get_post_type_object( $args['post_type'] ?? 'post' );
		$can_edit_posts   = $post_type_object && current_user_can( $post_type_object->cap->edit_posts );

		/**
		 * Filters whether a REST request may filter or order posts by relationship.
		 *
		 * The `relationshipQuery` and `orderByRelationship` params reveal which posts are
		 * related to a given post or user, so by default only users who can edit posts of
		 * the queried type may use them. The Query Loop block's front-end rendering does
		 * not go through REST and is not affected.
		 *
		 * @since 2.0.0
		 *
		 * @param bool             $allowed Whether the relationship params are applied.
		 * @param \WP_REST_Request $request The REST API request.
		 * @param array            $args    Array of arguments for \WP_Query.
		 */
		$allowed = (bool) apply_filters( 'tenup_content_connect_rest_relationship_query_allowed', $can_edit_posts, $request, $args );

		if ( ! $allowed ) {
			return $args;
		}

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
	 * @since 2.0.0
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
