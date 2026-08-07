<?php

namespace TenUp\ContentConnect\Relationships;

use TenUp\ContentConnect\Plugin;

class DeletedItems {

	public function setup() {
		add_action( 'deleted_post', array( $this, 'deleted_post' ) );
		add_action( 'deleted_user', array( $this, 'deleted_user' ) );
	}

	/**
	 * Fires right after a post was deleted from the database (NOT when it was moved to trash)
	 *
	 * @param $post_id
	 */
	public function deleted_post( $post_id ) {
		/** @var \TenUp\ContentConnect\Tables\PostToPost $p2p_table */
		$p2p_table = Plugin::instance()->get_table( 'p2p' );

		/** @var \TenUp\ContentConnect\Tables\PostToUser $p2p_table */
		$p2u_table = Plugin::instance()->get_table( 'p2u' );

		// Invalidate cached related-id lookups for this post and every post related to it before
		// the rows are removed. These raw deletes bypass delete_relationship(), so the
		// tenup-content-connect-delete-relationship action that busts the cache never fires.
		$this->invalidate_related_ids_cache( $post_id, $p2p_table );

		$p2p_table->delete(
			array( 'id1' => $post_id ),
			array( '%d' )
		);
		$p2p_table->delete(
			array( 'id2' => $post_id ),
			array( '%d' )
		);

		$p2u_table->delete(
			array( 'post_id' => $post_id ),
			array( '%d' )
		);
	}

	/**
	 * Fires immediately after a user is deleted from wp_users (single site) or removed from the site (multisite)
	 *
	 * @param $user_id
	 */
	public function deleted_user( $user_id ) {
		/** @var \TenUp\ContentConnect\Tables\PostToUser $p2p_table */
		$p2u_table = Plugin::instance()->get_table( 'p2u' );

		$p2u_table->delete(
			array( 'user_id' => $user_id ),
			array( '%d' )
		);
	}

	/**
	 * Clears cached related-id lookups affected by removing a post from the post-to-post table.
	 *
	 * Deleting a post makes stale every cache entry keyed on a post that was related to it, so
	 * both endpoints of every row touching the post are invalidated.
	 *
	 * @param int                                     $post_id   The post being deleted.
	 * @param \TenUp\ContentConnect\Tables\PostToPost $p2p_table The post-to-post table.
	 * @return void
	 */
	protected function invalidate_related_ids_cache( $post_id, $p2p_table ) {
		global $wpdb;

		$table_name = esc_sql( $p2p_table->get_table_name() );
		$rows       = $wpdb->get_results(
			$wpdb->prepare( "SELECT id1, id2, name FROM {$table_name} WHERE id1 = %d OR id2 = %d", $post_id, $post_id )
		);

		if ( empty( $rows ) ) {
			return;
		}

		foreach ( $rows as $row ) {
			Cache::delete( Cache::get_related_ids_key( $row->id1, $row->name ) );
			Cache::delete( Cache::get_related_ids_key( $row->id2, $row->name ) );
		}
	}

}
