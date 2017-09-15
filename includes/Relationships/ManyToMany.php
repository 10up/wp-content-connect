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
		if ( $post_type != $this->from && $post_type != $this->to ) {
			return array();
		}
		$where_post_type = $post_type === $this->from ? $this->to : $this->from;

		$query = $db->prepare( "SELECT p2p.id2 as ID, p.post_type FROM {$table_name} AS p2p INNER JOIN {$db->posts} as p on p2p.id2 = p.ID WHERE p2p.id1 = %d and p2p.type = %s and p.post_type = %s", $post_id, $this->type, $where_post_type );

		$objects = $db->get_results( $query );

		return wp_list_pluck( $objects, 'ID' );
	}

	/**
	 * Since we are joining on the same tables, its rather difficult to always know which order the relationship will be
	 * ESPECIALLY when joining the same post type to itself. To work around this, we just store both combinations of
	 * the relationship. Adds a tiny bit of data to the DB, but greatly simplifies queries to find related posts
	 *
	 * @param $pid1
	 * @param $pid2
	 */
	public function add_relationship( $pid1, $pid2 ) {
		/** @var PostToPost $table */
		$table = Plugin::instance()->get_table( 'p2p' );

		$table->replace(
			array( 'id1' => $pid1, 'id2' => $pid2, 'type' => $this->type ),
			array( '%d', '%d', '%s' )
		);
		$table->replace(
			array( 'id1' => $pid2, 'id2' => $pid1, 'type' => $this->type ),
			array( '%d', '%d', '%s' )
		);
	}

	public function delete_relationship( $pid1, $pid2 ) {
		/** @var PostToPost $table */
		$table = Plugin::instance()->get_table( 'p2p' );

		$table->delete(
			array( 'id1' => $pid1, 'id2' => $pid2, 'type' => $this->type ),
			array( '%d', '%d', '%s' )
		);
		$table->delete(
			array( 'id1' => $pid2, 'id2' => $pid1, 'type' => $this->type ),
			array( '%d', '%d', '%s' )
		);
	}

}
