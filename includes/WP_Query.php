<?php

namespace TenUp\P2P;

class WP_Query {

	public function setup() {
		add_filter( 'posts_where', array( $this, 'posts_where' ), 10, 2 );
		add_filter( 'posts_join', array( $this, 'posts_join' ), 10, 2 );
	}

	public function posts_where( $where, $query ) {
		global $wpdb;

		if ( isset( $query->query['related_to'] ) ) {
			// @todo check that its a valid post type, and get the proper relationship
			$where .= $wpdb->prepare( " and p2p.id2 = %d", $query->query['related_to'] );
		}

		return $where;
	}

	public function posts_join( $join, $query ) {
		global $wpdb;

		if ( isset( $query->query['related_to'] ) ) {
			$join .= " INNER JOIN {$wpdb->prefix}post_to_post as p2p on {$wpdb->posts}.ID = p2p.id1";
		}

		return $join;
	}

}
