<?php

namespace TenUp\ContentConnect\Relationships;

use TenUp\ContentConnect\Plugin;

class PostToUser extends Relationship {

	/**
	 * Post type the user relates to
	 *
	 * @var string
	 */
	public $post_type;

	/**
	 * The UI object for the "from" (post) relationship, if the UI is enabled
	 *
	 * @var \TenUp\ContentConnect\UI\PostToUser
	 */
	public $from_ui;

	public function __construct( $post_type, $name, $args = array() ) {
		if ( ! post_type_exists( $post_type ) ) {
			throw new \Exception( "Post Type {$post_type} does not exist. Post types must exist to create a relationship" );
		}

		$this->post_type = $post_type;
		$this->id = strtolower( get_class( $this ) ) . "-{$name}-{$post_type}-user";

		parent::__construct( $name, $args );
	}

	public function setup() {
		if ( $this->enable_from_ui ) {
			$this->from_ui = new \TenUp\ContentConnect\UI\PostToUser( $this, $this->post_type, $this->from_labels, $this->from_sortable );
			$this->from_ui->setup();
		}
	}

	/**
	 * Gets the post IDs that are related to the supplied user ID in the context of the current relationship
	 *
	 * @param int $user_id
	 *
	 * @return array
	 */
	public function get_related_post_ids( $user_id, $order_by_relationship = false ) {
		/** @var \TenUp\ContentConnect\Tables\PostToUser $table */
		$table = Plugin::instance()->get_table( 'p2u' );
		$db = $table->get_db();

		$table_name = esc_sql( $table->get_table_name() );

		$query = $db->prepare( "SELECT p2u.post_id from {$table_name} as p2u inner join {$db->posts} as p on p.ID = p2u.post_id where p2u.user_id=%d and p2u.name=%s and p.post_type=%s", $user_id, $this->name, $this->post_type );
		if ( $order_by_relationship ) {
			$query .= "order by p2u.post_order = 0, p2u.post_order ASC";
		}

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
	public function get_related_user_ids( $post_id, $order_by_relationship = false ) {
		// Ensure the post ID provided is valid for this relationship
		$post = get_post( $post_id );
		if ( $post->post_type !== $this->post_type ) {
			return array();
		}

		/** @var \TenUp\ContentConnect\Tables\PostToUser $table */
		$table = Plugin::instance()->get_table( 'p2u' );
		$db = $table->get_db();

		$table_name = esc_sql( $table->get_table_name() );

		$query = $db->prepare( "SELECT p2u.user_id from {$table_name} as p2u where p2u.post_id=%d and p2u.name=%s", $post_id, $this->name );
		if ( $order_by_relationship ) {
			$query .= "order by p2u.user_order = 0, p2u.user_order ASC";
		}

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
		/** @var \TenUp\ContentConnect\Tables\PostToUser $table */
		$table = Plugin::instance()->get_table( 'p2u' );

		$table->replace(
			array( 'post_id' => $post_id, 'user_id' => $user_id, 'name' => $this->name ),
			array( '%d', '%d', '%s' )
		);

		/**
		 * This action is documented in PostToPost.php
		 */
		do_action( 'tenup-content-connect-add-relationship', $post_id, $user_id, $this->name, 'post-to-user' );
	}

	/**
	 * Deletes a relationship between a post and a user
	 *
	 * @param $post_id
	 * @param $user_id
	 */
	public function delete_relationship( $post_id, $user_id ) {
		/** @var \TenUp\ContentConnect\Tables\PostToUser $table */
		$table = Plugin::instance()->get_table( 'p2u' );

		$table->delete(
			array( 'post_id' => $post_id, 'user_id' => $user_id, 'name' => $this->name ),
			array( '%d', '%d', '%s' )
		);

		/**
		 * This action is documented in PostToPost.php
		 */
		do_action( 'tenup-content-connect-delete-relationship', $post_id, $user_id, $this->name, 'post-to-user' );
	}

	/**
	 * Replaces users related to a post with the provided set of user ids.
	 *
	 * Any users related to the post that are not provided in $user_ids will no longer be related
	 *
	 * @param $post_id
	 * @param $user_ids
	 */
	public function replace_post_to_user_relationships( $post_id, $user_ids ) {
		$current_ids = $this->get_related_user_ids( $post_id );

		$delete_user_ids = array_diff( $current_ids, $user_ids );
		$add_user_ids = array_diff( $user_ids, $current_ids );

		foreach( $delete_user_ids as $delete_user_id ) {
			$this->delete_relationship( $post_id, $delete_user_id );
		}

		foreach( $add_user_ids as $add_user_id ) {
			$this->add_relationship( $post_id, $add_user_id );
		}

		/**
		 * This action is documented in PostToPost.php
		 */
		do_action( 'tenup-content-connect-replace-relationships', $post_id, $user_ids, 'post-to-user' );
	}

	/**
	 * Replaces posts relasted to a user with the provided post ids.
	 *
	 * Any posts related to the user that are not provided in $post_ids will no longer be related
	 *
	 * @param $user_id
	 * @param $post_ids
	 */
	public function replace_user_to_post_relationships( $user_id, $post_ids ) {
		$current_ids = $this->get_related_post_ids( $user_id );

		$delete_post_ids = array_diff( $current_ids, $post_ids );
		$add_post_ids = array_diff( $post_ids, $current_ids );

		foreach ( $delete_post_ids as $delete_post_id ) {
			$this->delete_relationship( $delete_post_id, $user_id );
		}

		foreach( $add_post_ids as $add_post_id ) {
			$this->add_relationship( $add_post_id, $user_id );
		}

		/**
		 * This action is documented in PostToPost.php
		 */
		do_action( 'tenup-content-connect-replace-relationships', $user_id, $post_ids, 'user-to-post' );
	}

	/**
	 * Saves the order of users for a particular post
	 *
	 * @param $object_id
	 * @param $ordered_user_ids
	 */
	public function save_post_to_user_sort_data( $object_id, $ordered_user_ids ) {
		if ( empty( $ordered_user_ids ) ) {
			return;
		}

		$order = 0;

		$data = array();

		foreach ( $ordered_user_ids as $id ) {
			$order++;

			$data[] = array(
				'post_id' => $object_id,
				'user_id' => $id,
				'name' => $this->name,
				'user_order' => $order,
			);
		}

		$fields = array(
			'post_id' => '%d',
			'user_id' => '%d',
			'name' => '%s',
			'user_order' => '%d',
		);

		/** @var \TenUp\ContentConnect\Tables\PostToUser $table */
		$table = Plugin::instance()->get_table( 'p2u' );
		$table->replace_bulk( $fields, $data );
	}

	/**
	 * Saves the order of posts for a particular user.
	 *
	 * Currently no UI for this, but supporting this on the API level for consistency on all sides of the relationship
	 *
	 * @param $user_id
	 * @param $ordered_post_ids
	 */
	public function save_user_to_post_sort_data( $user_id, $ordered_post_ids ) {
		if ( empty( $ordered_post_ids ) ) {
			return;
		}

		$order = 0;

		$data = array();

		foreach ( $ordered_post_ids as $id ) {
			$order++;

			$data[] = array(
				'post_id' => $id,
				'user_id' => $user_id,
				'name' => $this->name,
				'post_order' => $order,
			);
		}

		$fields = array(
			'post_id' => '%d',
			'user_id' => '%d',
			'name' => '%s',
			'post_order' => '%d',
		);

		/** @var \TenUp\ContentConnect\Tables\PostToUser $table */
		$table = Plugin::instance()->get_table( 'p2u' );
		$table->replace_bulk( $fields, $data );
	}

}
