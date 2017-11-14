<?php

namespace TenUp\ContentConnect\QueryIntegration;

use TenUp\ContentConnect\Relationships\PostToUser;

class UserQueryIntegration {

	public function setup() {
		// Higher priority, so that we get in on the "Join" later than other things that might be filtering (there isn't a specific join section)
		add_action( 'pre_user_query', array( $this, 'pre_user_query' ), 100 );
	}

	/**
	 * Hooked into the WP_User_Query object once the query is parsed, but before the query is run
	 *
	 * @param \WP_User_Query $query
	 */
	public function pre_user_query( $query ) {
		global $wpdb;

		if ( isset( $query->query_vars['relationship_query'] ) ) {
			$relationship_query = new UserRelationshipQuery( $query->query_vars['relationship_query'] );

			$query->query_where .= $relationship_query->where;
			$query->query_from .= $relationship_query->join;

			$this->sortable_orderby( $query, $relationship_query );

			if ( ! empty( $relationship_query->where ) ) {
				$query->query_orderby = "GROUP BY {$wpdb->users}.ID " . $query->query_orderby;
			}
		}
	}

	public function sortable_orderby( $query, $relationship_query ) {
		/*
		 * If orderby is anything other than relationship (array, etc) we don't allow it.
		 * Trying to allow multiple order by statements would likely end in confusing results
		 */
		if ( ! isset( $query->query_vars['orderby'] ) || $query->query_vars['orderby'] !== 'relationship' ) {
			return;
		}

		/*
		 * Since each component of the relationship query could have its OWN order, and there is not a good way to
		 * reconcile those, we just don't allow this and default to default ordering on WP_Query
		 */
		if ( count( $relationship_query->segments ) > 1 ) {
			return;
		}

		/*
		 * We're doing this CASE and FIELD method, in case we switched from a non-sortable relationship to a
		 * sortable relationship. In that case, the meta value would be empty. If we did post__in and order by
		 * post__in, we'd end up with no results, even though we could have a relationship in the relation table
		 *
		 * Using this method, we can order by any values we do have in meta, and THEN for any remaining relationships
		 * in the relation table, we order by the original order by value that was on the WP_Query
		 */
		$segment = $relationship_query->segments[0];
		$relationship = $relationship_query->get_relationship_for_segment( $segment );

		if ( $relationship instanceof PostToUser ) {
			// Puts 0 last, since these are just defaults
			$query->query_orderby = "ORDER BY p2u1.user_order = 0, p2u1.user_order ASC";
		}
	}

}
