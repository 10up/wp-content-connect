<?php

namespace TenUp\P2P\Relationships;

use TenUp\P2P\Plugin;
use TenUp\P2P\Tables\PostToPost;

class ManyToMany extends Relationship {

	public function setup() {
		// @todo hook up the metabox and save actions for the default UI
	}

	/**
	 * Gets the IDs that are related to the supplied post ID in the context of the current relationship
	 *
	 * @param $post_id
	 *
	 * @return array
	 */
	public function get_related_object_ids( $post_id ) {
		/** @var PostToPost $table */
		$table = Plugin::instance()->get_table( 'p2p' );
		$db = $table->get_db();

		$table_name = esc_sql( $table->get_table_name() );

		// Query either to or from, depending on the post type of the ID we're finding relationships for
		$post_type = get_post_type( $post_id );

		if ( $this->from === $post_type ) {
			$select_col = 'to';
			$where_col = 'from';
			$where_post_type = $this->to;
		} else {
			$select_col = 'from';
			$where_col = 'to';
			$where_post_type = $this->from;
		}

		$query = $db->prepare( "SELECT ptp.{$select_col} as ID, p.post_type FROM {$table_name} AS p2p INNER JOIN {$db->posts} as p on p2p.{$select_col} = p.ID WHERE p2p.{$where_col} = %d and p.post_type = %s", $post_id, $where_post_type );

		$objects = $db->get_results( $query );

		return wp_list_pluck( $objects, 'ID' );
	}

}
