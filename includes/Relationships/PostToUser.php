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
	public function get_related_post_ids( $user_id ) {
		/** @var \TenUp\ContentConnect\Tables\PostToUser $table */
		$table = Plugin::instance()->get_table( 'p2u' );
		$db = $table->get_db();

		$table_name = esc_sql( $table->get_table_name() );

		$query = $db->prepare( "SELECT p2u.post_id from {$table_name} as p2u inner join {$db->posts} as p on p.ID = p2u.post_id where p2u.user_id=%d and p2u.name=%s and p.post_type=%s", $user_id, $this->name, $this->post_type );

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

		/** @var \TenUp\ContentConnect\Tables\PostToUser $table */
		$table = Plugin::instance()->get_table( 'p2u' );
		$db = $table->get_db();

		$table_name = esc_sql( $table->get_table_name() );

		$query = $db->prepare( "SELECT p2u.user_id from {$table_name} as p2u where p2u.post_id=%d and p2u.name=%s", $post_id, $this->name );

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
	}

	public function save_sort_data( $object_id, $ordered_ids ) {
		if ( empty( $ordered_ids ) ) {
			return;
		}

		$order = 0;

		$data = array();

		foreach( $ordered_ids as $id ) {
			$order++;

			$data[] = array(
				'post_id' => $object_id,
				'user_id' => $id,
				'name' => $this->name,
				'user_order' => $order
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

}
