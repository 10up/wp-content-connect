<?php

namespace TenUp\P2P\Relationships;

use TenUp\P2P\Plugin;

class PostToUser extends Relationship {

	/**
	 * Post type the user relates to
	 *
	 * @var string
	 */
	public $post_type;

	/**
	 * The UI object for the relationship, if the UI is enabled
	 *
	 * @var \TenUp\P2P\UI\PostToUser
	 */
	public $ui;

	public function __construct( $post_type, $type, $args = array() ) {
		if ( ! post_type_exists( $post_type ) ) {
			throw new \Exception( "Post Type {$post_type} does not exist. Post types must exist to create a relationship" );
		}

		$this->post_type = $post_type;
		$this->id = strtolower( get_class( $this ) ) . "-{$type}-{$post_type}-user";
		
		parent::__construct( $type, $args );
	}

	public function setup() {
		// @todo hook up the metabox and save actions for the default UI
		if ( $this->enable_ui ) {
			$this->ui = new \TenUp\P2P\UI\PostToUser( $this );
			$this->ui->setup();
		}
	}

	/**
	 * Gets the post IDs that are related to the supplied user ID in the context of the current relationship
	 *
	 * @param int $user_id
	 *
	 * @return array
	 */
	public function get_related_post_ids( $user_id ) {
		/** @var \TenUp\P2p\Tables\PostToUser $table */
		$table = Plugin::instance()->get_table( 'p2u' );
		$db = $table->get_db();

		$table_name = esc_sql( $table->get_table_name() );

		$query = $db->prepare( "SELECT p2u.post_id from {$table_name} as p2u inner join {$db->posts} as p on p.ID = p2u.post_id where p2u.user_id=%d and p2u.type=%s and p.post_type=%s", $user_id, $this->type, $this->post_type );

		$objects = $db->get_results( $query );

		return wp_list_pluck( $objects, 'post_id' );
	}

	/**
	 * Gets the user IDs that are related to the supplied post ID in the context of the current relationship
	 *
	 * @param int $post_id
	 *
	 * @return array
	 */
	public function get_related_user_ids( $post_id ) {
		// Ensure the post ID provided is valid for this relationship
		$post = get_post( $post_id );
		if ( $post->post_type !== $this->post_type ) {
			return array();
		}

		/** @var \TenUp\P2p\Tables\PostToUser $table */
		$table = Plugin::instance()->get_table( 'p2u' );
		$db = $table->get_db();

		$table_name = esc_sql( $table->get_table_name() );

		$query = $db->prepare( "SELECT p2u.user_id from {$table_name} as p2u where p2u.post_id=%d and p2u.type=%s", $post_id, $this->type );

		$objects = $db->get_results( $query );

		return wp_list_pluck( $objects, 'user_id' );
	}

	/**
	 * Adds a relationship between a post and a user
	 *
	 * @param int $post_id
	 * @param int $user_id
	 */
	public function add_relationship( $post_id, $user_id ) {
		/** @var \TenUp\P2P\Tables\PostToUser $table */
		$table = Plugin::instance()->get_table( 'p2u' );

		$table->replace(
			array( 'post_id' => $post_id, 'user_id' => $user_id, 'type' => $this->type ),
			array( '%d', '%d', '%s' )
		);
	}

	/**
	 * Deletes a relationship between a post and a user
	 *
	 * @param $post_id
	 * @param $user_id
	 */
	public function delete_relationship( $post_id, $user_id ) {
		/** @var \TenUp\P2P\Tables\PostToUser $table */
		$table = Plugin::instance()->get_table( 'p2u' );

		$table->delete(
			array( 'post_id' => $post_id, 'user_id' => $user_id, 'type' => $this->type ),
			array( '%d', '%d', '%s' )
		);
	}

}
