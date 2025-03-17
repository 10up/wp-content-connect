<?php

namespace TenUp\ContentConnect\QueryIntegration;

use function TenUp\ContentConnect\Helpers\get_post_to_post_relationships_data;

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
		add_action( 'pre_render_block', [ $this, 'modify_query_loop_query' ], 10, 2 );
	}

	/**
	 * Modifies the query loop to include the post picker posts.
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

		add_filter( 'query_loop_block_query_vars', [ $this, 'get_query_by_attributes_once' ] );

		return $block_content;
	}

	/**
	 * Remove the query block filter and parse the custom query.
	 *
	 * @param  array $query_args Array containing parameters for `WP_Query`.
	 * @return array
	 */
	public function get_query_by_attributes_once( $query_args ) {
		remove_filter( 'query_loop_block_query_vars', [ $this, 'get_query_by_attributes_once' ] );
		return $this->get_query_by_attributes( $query_args, $this->parsed_block );
	}

	/**
	 * Returns a custom query based on block attributes.
	 *
	 * @param  array $query_args Array containing parameters for `WP_Query`.
	 * @param  array $block      The block being rendered.
	 * @return array
	 */
	public function get_query_by_attributes( $query_args, $block ) {

		if ( ! $this->is_query_block( $block ) ) {
			return $query_args;
		}

		if ( empty( $block['attrs']['relationshipQuery'] ) ) {
			return $query_args;
		}

		if ( empty( $block['attrs']['relationshipPost'] ) ) {
			return $query_args;
		}

		$post_id = wp_list_pluck( $block['attrs']['relationshipPost'], 'id' );

		if ( empty( $post_id ) ) {
			return $query_args;
		}

		$post_id         = reset( $post_id );
		$other_post_type = $block['attrs']['query']['postType'];

		$post_relationships = get_post_to_post_relationships_data( $post_id, $other_post_type );

		if ( empty( $post_relationships ) ) {
			return $query_args;
		}

		$relationship_key = '';
		if ( ! empty( $block['attrs']['relationshipKey'] ) ) {
			$relationship_key = $block['attrs']['relationshipKey'];
		}

		$relationship_query = array();

		foreach ( $post_relationships as $post_relationship ) {

			if ( ! empty( $relationship_key ) && $relationship_key !== $post_relationship['rel_key'] ) {
				continue;
			}

			$relationship_query[] = array(
				'name'            => $post_relationship['rel_name'],
				'related_to_post' => $post_id,
			);
		}

		if ( ! empty( $relationship_query ) ) {
			$query_args['relationship_query'] = $relationship_query;
		}

		if ( ! isset( $block['attrs']['relationshipOrderBy'] ) || ! empty( $block['attrs']['relationshipOrderBy'] ) ) {
			$query_args['orderby'] = 'relationship';
		}

		return $query_args;
	}

	/**
	 * Check if the block is a Query block.
	 *
	 * @param  array $block The block object.
	 * @return bool
	 */
	public function is_query_block( $block ) {
		return ! empty( $block['blockName'] ) && 'core/query' === $block['blockName'];
	}
}
