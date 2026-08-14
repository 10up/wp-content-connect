<?php

namespace TenUp\ContentConnect\QueryIntegration;

class QueryBlockIntegration {

	/**
	 * The block with its attributes before it gets rendered.
	 *
	 * @var array
	 */
	public $parsed_block;

	/**
	 * Setup the Query block integration module.
	 *
	 * @since 1.7.0
	 */
	public function setup() {
		add_action( 'rest_api_init', array( $this, 'rest_api_init' ) );
		add_action( 'pre_render_block', array( $this, 'modify_query_loop_query' ), 10, 2 );
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

		if ( isset( $request['relationshipQuery'] ) ) {
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
	 * @since 1.7.0
	 *
	 * @param  string $block_content The block content.
	 * @param  array  $block         The block object.
	 * @return string
	 */
	public function modify_query_loop_query( $block_content, $block ) {

		if ( ! $this->is_query_block( $block ) ) {
			return $block_content;
		}

		$this->parsed_block = $block;

		add_filter( 'query_loop_block_query_vars', array( $this, 'get_query_by_attributes_once' ) );

		return $block_content;
	}

	/**
	 * Applies custom query modifications based on block attributes, then removes itself.
	 *
	 * @since 1.7.0
	 *
	 * @param  array $query_args Array containing parameters for `WP_Query`.
	 * @return array
	 */
	public function get_query_by_attributes_once( $query_args ) {
		if ( has_filter( 'query_loop_block_query_vars', array( $this, 'get_query_by_attributes_once' ) ) ) {
			remove_filter( 'query_loop_block_query_vars', array( $this, 'get_query_by_attributes_once' ) );
		}

		return $this->get_query_by_attributes( $query_args, $this->parsed_block );
	}

	/**
	 * Generates a modified query based on the block attributes.
	 *
	 * @since 1.7.0
	 *
	 * @param  array $query_args Array containing parameters for `WP_Query`.
	 * @param  array $block      The block being rendered.
	 * @return array
	 */
	public function get_query_by_attributes( $query_args, $block ) {

		if ( ! $this->is_query_block( $block ) ) {
			return $query_args;
		}

		$query_attrs = $block['attrs']['query'] ?? [];

		if ( ! empty( $query_attrs['relationshipQuery'] ) ) {
			$query_args['relationship_query'] = $query_attrs['relationshipQuery'];
		}

		if ( ! empty( $query_attrs['orderByRelationship'] ) ) {
			$query_args['orderby'] = 'relationship';
		}

		return $query_args;
	}

	/**
	 * Determines if a given block is a Query Loop block.
	 *
	 * @since 1.7.0
	 *
	 * @param  array $block The block object.
	 * @return bool
	 */
	public function is_query_block( $block ) {
		return ! empty( $block['blockName'] ) && 'core/query' === $block['blockName'];
	}
}
