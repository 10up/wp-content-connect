<?php

namespace TenUp\P2P;

class WP_Query_Integration {

	public function setup() {
		add_filter( 'posts_where', array( $this, 'posts_where' ), 10, 2 );
		add_filter( 'posts_join', array( $this, 'posts_join' ), 10, 2 );
	}

	public function posts_where( $where, $query ) {
		global $wpdb;

		if ( isset( $query->query['related_to_post'] ) && $this->get_relationship_for_query( $query ) ) {
			$where .= $wpdb->prepare( " and p2p.id2 = %d", $query->query['related_to_post'] );
		}

		return $where;
	}

	public function posts_join( $join, $query ) {
		global $wpdb;

		if ( isset( $query->query['related_to_post'] ) && $this->get_relationship_for_query( $query ) ) {
			$join .= " INNER JOIN {$wpdb->prefix}post_to_post as p2p on {$wpdb->posts}.ID = p2p.id1";
		}

		return $join;
	}

	public function get_relationship_for_query( $query ) {
		if ( ! isset( $query->query['related_to_post'] ) ) {
			return false;
		}

		$related_to_post = get_post( $query->query['related_to_post'] );
		if ( ! $related_to_post ) {
			return false;
		}

		$registry = Plugin::instance()->get_registry();

		$post_type = isset( $query->query['post_type'] ) ? $query->query['post_type'] : 'post';

		$relationship = $registry->get_relationship( $post_type, $related_to_post->post_type );

		return $relationship;
	}

}
