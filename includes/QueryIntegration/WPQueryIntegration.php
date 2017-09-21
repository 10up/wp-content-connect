<?php

namespace TenUp\ContentConnect\QueryIntegration;

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
		global $wpdb;

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

		/*
		 * Since we don't have a UI for anything on user screens, its not possible to order there
		 * Therefore, if we are finding things related by a user ID, we ignore this param, since it would do nothing
		 * and just over complicate the query
		 */
		if ( isset( $query->relationship_query->segments[0]['related_to_user'] ) ) {
			return $orderby;
		}

		/*
		 * We're doing this CASE and FIELD method, in case we switched from a non-sortable relationship to a
		 * sortable relationship. In that case, the meta value would be empty. If we did post__in and order by
		 * post__in, we'd end up with no results, even though we could have a relationship in the relation table
		 *
		 * Using this method, we can order by any values we do have in meta, and THEN for any remaining relationships
		 * in the relation table, we order by the original order by value that was on the WP_Query
		 */
		$segment = $query->relationship_query->segments[0];
		$relationship = $query->relationship_query->get_relationship_for_segment( $segment );

		$ids = $relationship->get_sort_data( $segment['related_to_post'] );

		$query_safe_ids = implode( ', ', array_map( 'intval', $ids ) );

		$orderby = "CASE WHEN {$wpdb->posts}.ID IN ( {$query_safe_ids} ) then 0 ELSE 1 END, FIELD( {$wpdb->posts}.ID, {$query_safe_ids} ) ASC, " . $orderby;

		return $orderby;
	}

}
