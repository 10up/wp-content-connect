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

}
