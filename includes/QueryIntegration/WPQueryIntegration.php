<?php

namespace TenUp\ContentConnect\QueryIntegration;

use TenUp\ContentConnect\Relationships\PostToPost;
use TenUp\ContentConnect\Relationships\PostToUser;

class WPQueryIntegration {

	public function setup() {
		// posts_where is first, posts_join is after
		add_filter( 'posts_where', array( $this, 'posts_where' ), 10, 2 );
		add_filter( 'posts_join', array( $this, 'posts_join' ), 10, 2 );
		add_filter( 'posts_groupby', array( $this, 'posts_groupby' ), 10, 2 );
		add_filter( 'posts_orderby', array( $this, 'posts_orderby' ), 10, 2 );
	}

	public function posts_where( $where, $query ) {
		if ( isset( $query->query['relationship_query'] ) ) {
			$post_type = isset( $query->query['post_type'] ) ? $query->query['post_type'] : '';

			// Adding to the query, so that we can fetch it from the other filter methods below and be dealing with the same data
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

	public function posts_orderby( $orderby, $query ) {
		if ( ! isset( $query->relationship_query ) || empty( $query->relationship_query->where ) ) {
			return $orderby;
		}

		/*
		 * If orderby is anything other than relationship (array, etc) we don't allow it.
		 * Trying to allow multiple order by statements would likely end in confusing results
		 */
		if ( $query->query['orderby'] !== 'relationship' ) {
			return $orderby;
		}

		/*
		 * Since each component of the relationship query could have its OWN order, and there is not a good way to
		 * reconcile those, we just don't allow this and default to default ordering on WP_Query
		 */
		if ( count( $query->relationship_query->segments ) > 1 ) {
			return $orderby;
		}

		$segment = $query->relationship_query->segments[0];
		$relationship = $query->relationship_query->get_relationship_for_segment( $segment );

		// the order = 0 part puts any zero values (defaults) last to account for cases when they were adding from the
		// other side of the relationship
		if ( $relationship instanceof PostToPost ) {
			$orderby = "p2p1.order = 0, p2p1.order ASC";
		} else if ( $relationship instanceof  PostToUser ) {
			$orderby = "p2u1.post_order = 0, p2u1.post_order ASC";
		}

		return $orderby;
	}

}
