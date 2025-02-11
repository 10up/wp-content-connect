<?php

namespace TenUp\ContentConnect\QueryIntegration;

use TenUp\ContentConnect\Plugin;
use TenUp\ContentConnect\Relationships\PostToPost;
use TenUp\ContentConnect\Relationships\PostToUser;

class RelationshipQuery {

	/**
	 * The raw args from the relationship query passed to WP_Query
	 *
	 * @var array
	 */
	public $relationship_query = array();

	public $post_type = '';

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
	 * Have we already joined the p2p table?
	 *
	 * on an "OR" relation, we don't need a join for each clause, so this enables us to track that
	 *
	 * @var bool
	 */
	protected $p2p_join = false;

	/**
	 * Have we already joined the p2u table?
	 *
	 * on an "OR" relation, we don't need a join for each clause, so this enables us to track that
	 *
	 * @var bool
	 */
	protected $p2u_join = false;

	public function __construct( $relationship_query, $post_type = '' ) {
		$this->relationship_query = $relationship_query;
		$this->post_type = ! empty( $post_type ) ? $post_type : 'post';

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
	 */
	public function format_segments() {
		// Check for any top level keys that should be moved into a nested segment
		$valid_keys = array(
			'related_to_post',
			'related_to_user',
			'name',
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
	 * A valid segment requires both a 'name' property AND one of the following additional properties:
	 *  - related_to_post
	 *  - related_to_user
	 *
	 * @param $segment
	 *
	 * @return bool
	 */
	public function is_valid_segment( $segment ) {
		// Not allowed to have user AND post on the same segment
		if ( isset( $segment['related_to_post'] ) && isset( $segment['related_to_user'] ) ) {
			return false;
		}

		if ( ( isset( $segment['related_to_post'] ) || isset( $segment['related_to_user'] ) ) && isset( $segment['name'] ) ) {
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

		$wherecount = 1;

		$where_parts = array();

		foreach( $this->segments as $segment ) {
			// Only generate the clause if this is a valid relationship
			if ( $relationship = $this->get_relationship_for_segment( $segment ) ) {
				if ( $relationship instanceof PostToPost ) {
					$where_parts[] = $wpdb->prepare( "(p2p{$wherecount}.id2 = %d and p2p{$wherecount}.name = %s)", $segment['related_to_post'], $segment['name'] );
				} else if ( $relationship instanceof PostToUser ) {
					$where_parts[] = $wpdb->prepare( "(p2u{$wherecount}.user_id = %d and p2u{$wherecount}.name = %s)", $segment['related_to_user'], $segment['name'] );
				}

				// Only increment counter no "AND" relations, when we are joining a table for each segment
				if ( $this->relation === 'AND' ) {
					$wherecount++;
				}
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

		foreach( $this->segments as $segment ) {
			// Only generate the clause if this is a valid relationship
			if ( $relationship = $this->get_relationship_for_segment( $segment ) ) {
				if ( $relationship instanceof PostToPost ) {
					if ( $this->relation === 'AND' || $this->p2p_join === false ) {
						$join_parts[] = " left join {$wpdb->prefix}post_to_post as p2p{$joincount} on {$wpdb->posts}.ID = p2p{$joincount}.id1";

						// Track that we've joined the p2p table
						$this->p2p_join = true;
					}
				} else if ( $relationship instanceof PostToUser ) {
					if ( $this->relation === 'AND' || $this->p2u_join === false ) {
						$join_parts[] = " left join {$wpdb->prefix}post_to_user as p2u{$joincount} on {$wpdb->posts}.ID = p2u{$joincount}.post_id";

						// Track that we've joined the p2u table
						$this->p2u_join = true;
					}
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

		if ( isset( $segment['related_to_post'] ) ) {
			$related_to_post = get_post( $segment['related_to_post'] );

			if ( ! $related_to_post ) {
				return false;
			}

			$relationship = $registry->get_post_to_post_relationship( $this->post_type, $related_to_post->post_type, $segment['name'] );
		} else {
			$related_to_user = get_user_by( 'id', $segment['related_to_user'] );

			if ( ! $related_to_user ) {
				return false;
			}

			$relationship = $registry->get_post_to_user_relationship( $this->post_type, $segment['name'] );
		}

		return $relationship;
	}

}
