<?php

namespace TenUp\P2P\QueryIntegration;

use TenUp\P2P\Plugin;
use TenUp\P2P\Tables\PostToUser;

class UserRelationshipQuery {

	/**
	 * The raw args from the relationship query passed to WP_User_Query
	 *
	 * @var array
	 */
	public $relationship_query = array();

	/**
	 * Final relationship query segments used to generate where and join clauses
	 *
	 * @var array
	 */
	public $segments = array();

	/**
	 * The relation of the segments. Can be "AND" or "OR"
	 *
	 * @var string
	 */
	public $relation = 'AND';

	/**
	 * The where clause for the provided relationship query segments.
	 *
	 * @var string
	 */
	public $where = '';

	/**
	 * The join clause for the provided relationship query segments.
	 *
	 * @var string
	 */
	public $join = '';

	/**
	 * Have we already joined the p2u table?
	 *
	 * on an "OR" relation, we don't need a join for each clause, so this enables us to track that
	 *
	 * @var bool
	 */
	protected $p2u_join = false;

	public function __construct( $relationship_query ) {
		$this->relationship_query = $relationship_query;

		$this->parse_query();
	}

	/**
	 * Parses the provided raw relationship query into valid segments and generates the where and join clauses.
	 */
	public function parse_query() {
		$this->format_segments();

		if ( $this->has_valid_segments() ) {
			$this->where = $this->generate_where_clause();
			$this->join = $this->generate_join_clause();
		}
	}

	/**
	 * Formats the provided raw query to valid segments.
	 *
	 * @todo if we ever support user to user mapping, we should make a parent class or trait for validating segments
	 *       that is shared with RelationshipQuery, since the accepted keys would be identical for both
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
	}

	/**
	 * Determines if the segment is valid or not.
	 *
	 * A valid segment requires both a 'type' property AND one of the following additional properties:
	 *  - related_to_post
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
		$where= '';

		$wherecount = 1;

		$where_parts = array();

		foreach( $this->segments as $segment ) {
			// Only generate the clause if this is a valid relationship
			if ( $relationship = $this->get_relationship_for_segment( $segment ) ) {
				$where_parts[] = $wpdb->prepare( "(p2u{$wherecount}.post_id = %d and p2u{$wherecount}.type = %s)", $segment['related_to_post'], $segment['type'] );
			}

			// Only increment counter no "AND" relations, when we are joining a table for each segment
			if ( $this->relation === 'AND' ) {
				$wherecount++;
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

		$joincount = 1;

		$join_parts = array();

		foreach ( $this->segments as $segment ) {
			// Only generate the clause if this is a valid relationship
			if ( $relationship = $this->get_relationship_for_segment( $segment ) ) {
				if ( $this->relation === 'AND' || $this->p2u_join === false ) {
					$join_parts[] = " left join {$wpdb->prefix}post_to_user as p2u{$joincount} on {$wpdb->users}.ID = p2u{$joincount}.user_id";

					// Track that we've joined the
					$this->p2u_join = true;
				}

				// Only increment counter no "AND" relations, when we are joining a table for each segment
				if ( $this->relation === 'AND' ) {
					$joincount++;
				}
			}
		}

		if ( ! empty( $join_parts ) ) {
			$join = implode( '', $join_parts );
		}

		return $join;
	}

	public function get_relationship_for_segment( $segment ) {
		if ( ! $this->is_valid_segment( $segment ) ) {
			return false;
		}

		$registry = Plugin::instance()->get_registry();

		$related_to_post = get_post( $segment['related_to_post'] );

		if ( ! $related_to_post ) {
			return false;
		}
		
		$relationship = $registry->get_post_to_user_relationship( $related_to_post->post_type, $segment['type'] );

		return $relationship;
	}

}
