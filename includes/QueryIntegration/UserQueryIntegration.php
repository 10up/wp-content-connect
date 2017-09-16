<?php

namespace TenUp\P2P\QueryIntegration;

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

			if ( ! empty( $relationship_query->where ) ) {
				$query->query_orderby = "GROUP BY {$wpdb->users}.ID " . $query->query_orderby;
			}
		}
	}

}
