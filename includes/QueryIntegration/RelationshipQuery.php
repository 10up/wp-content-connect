<?php

namespace TenUp\P2P\QueryIntegration;

use TenUp\P2P\Plugin;

class RelationshipQuery{

	/**
	 * The raw args from the relationship query passed to WP_Query
	 *
	 * @var array
	 */
	public $relationship_query = array();

	public $post_type = '';

	public $segments = array();

	public $relation = 'AND';

	public $where = '';

	public $join = '';

	public function __construct( $relationship_query, $post_type = '' ) {
		$this->relationship_query = $relationship_query;
		$this->post_type = ! empty( $post_type ) ? $post_type : 'post';

		$this->parse_query();
	}

	public function parse_query() {
		$this->format_segments();

		if ( $this->has_valid_segments() ) {
			$this->where = $this->generate_where_clause();
			$this->join = $this->generate_join_clause();
		}
	}

	/**
	 * Formats the provided raw query to valid segments.
	 */
	public function format_segments() {
		// Check for any top level keys that should be moved into a nested segment
		$valid_keys = array(
			'related_to_post',
			'type',
		);
		$new_segment = array();
		foreach( $valid_keys as $key ) {
			if ( isset( $this->relationship_query[ $key ] ) ) {
				$new_segment[ $key ] = $this->relationship_query[ $key ];
				unset( $this->relationship_query[ $key ] );
			}
		}
		if ( $this->is_valid_segment( $new_segment ) ) {
			$this->segments[] = $new_segment;
		}

		foreach( $this->relationship_query as $key => $segment ) {
			if ( is_array( $segment ) && $this->is_valid_segment( $segment ) ) {
				$this->segments[] = $segment;
			} else if ( strtolower( $key ) == 'relation' ) {
				$this->relation = in_array( strtolower( $segment ), array( 'and', 'or' ) ) ? strtoupper( $segment ) : 'AND';
			}
		}

		// @todo What about AND / OR ?
	}

	/**
	 * Determines if the segment is valid or not.
	 *
	 * A valid segment requires both a 'type' property AND one of the following additional properties:
	 *  - relates_to_post
	 *
	 * @param $segment
	 *
	 * @return bool
	 */
	public function is_valid_segment( $segment ) {

		if ( isset( $segment['related_to_post'] ) && isset( $segment['type'] ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Checks if we currently have any valid segments
	 */
	public function has_valid_segments() {
		if ( empty( $this->segments ) ) {
			return false;
		}

		foreach( $this->segments as $segment ) {
			if ( $this->is_valid_segment( $segment ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Generates the where clause for the relationship query
	 */
	public function generate_where_clause() {
		global $wpdb;
		$where = '';

		$wherecount = 0;

		$where_parts = array();

		foreach( $this->segments as $segment ) {
			// Only generate the clause if this is a valid relationship
			if ( $this->get_relationship_for_segment( $segment ) ) {
				$wherecount++;
				$where_parts[] = $wpdb->prepare( "(p2p{$wherecount}.id2 = %d and p2p{$wherecount}.type = %s)", $segment['related_to_post'], $segment['type'] );
			}
		}

		if ( ! empty( $where_parts ) ) {
			$where = " and (" . implode( " {$this->relation} ", $where_parts ) . ")";
		}

		return $where;
	}

	/**
	 * Generates the join clause for the relationship query
	 */
	public function generate_join_clause() {
		global $wpdb;
		$join = '';

		$joincount = 0;

		$join_parts = array();

		foreach( $this->segments as $segment ) {
			// Only generate the clause if this is a valid relationship
			if ( $this->get_relationship_for_segment( $segment ) ) {
				$joincount++;
				$join_parts[] = " inner join {$wpdb->prefix}post_to_post as p2p{$joincount} on {$wpdb->posts}.ID = p2p{$joincount}.id1";
			}
		}

		if ( ! empty( $join_parts ) ) {
			$join = implode( '', $join_parts );
		}

		return $join;
	}

	public function get_relationship_for_segment( $segment ) {
		if ( ! isset( $segment['related_to_post'] ) ) {
			return false;
		}

		if ( ! isset( $segment['type'] ) ) {
			return false;
		}

		$related_to_post = get_post( $segment['related_to_post'] );
		if ( ! $related_to_post ) {
			return false;
		}

		$registry = Plugin::instance()->get_registry();

		$relationship = $registry->get_post_relationship( $this->post_type, $related_to_post->post_type, $segment['type'] );

		return $relationship;
	}

}
