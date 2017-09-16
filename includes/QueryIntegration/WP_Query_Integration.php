<?php

namespace TenUp\P2P\QueryIntegration;

class WP_Query_Integration {

	public function setup() {
		// posts_where is first, posts_join is after
		add_filter( 'posts_where', array( $this, 'posts_where' ), 10, 2 );
		add_filter( 'posts_join', array( $this, 'posts_join' ), 10, 2 );
		add_filter( 'posts_groupby', array( $this, 'posts_groupby' ), 10, 2 );
	}

	public function posts_where( $where, $query ) {
		if ( isset( $query->query['relationship_query'] ) ) {
			$post_type = isset( $query->query['post_type'] ) ? $query->query['post_type'] : '';
			$query->relationship_query = new RelationshipQuery( $query->query['relationship_query'], $post_type );

			$where .= $query->relationship_query->where;
		}

		return $where;
	}

	public function posts_join( $join, $query ) {
		if ( isset( $query->relationship_query ) ) {
			$join .= $query->relationship_query->join;
		}

		return $join;
	}

	public function posts_groupby( $groupby, $query ) {
		global $wpdb;

		if ( isset( $query->relationship_query ) && ! empty( $query->relationship_query->where ) ) {
			$groupby = "{$wpdb->posts}.ID";
		}

		return $groupby;
	}

}
