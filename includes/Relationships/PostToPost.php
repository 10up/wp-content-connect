<?php

namespace TenUp\ContentConnect\Relationships;

use TenUp\ContentConnect\Plugin;

class PostToPost extends Relationship {

	/**
	 * CPT Name of the first post type in the relationship
	 *
	 * @var string
	 */
	public $from;

	/**
	 * CPT Name of the second post type in the relationship
	 *
	 * @var string
	 */
	public $to;

	/**
	 * The UI Object for the "from" portion of the relationship, if the from UI is enabled
	 *
	 * @var \TenUp\ContentConnect\UI\PostToPost
	 */
	public $from_ui;

	/**
	 * The UI Object for the "to" portion of the relationship, if the to UI is enabled
	 *
	 * @var \TenUp\ContentConnect\UI\PostToPost
	 */
	public $to_ui;

	public function __construct( $from, $to, $name, $args = array() ) {
		if ( ! post_type_exists( $from ) ) {
			throw new \Exception( "Post Type {$from} does not exist. Post types must exist to create a relationship" );
		}

		if ( ! post_type_exists( $to ) ) {
			throw new \Exception( "Post Type {$to} does not exist. Post types must exist to create a relationship" );
		}

		$this->from = $from;
		$this->to = $to;
		$this->id = strtolower( get_class( $this ) ) . "-{$name}-{$from}-{$to}";
		
		parent::__construct( $name, $args );
	}

	public function setup() {
		if ( $this->enable_from_ui === true ) {
			$this->from_ui = new \TenUp\ContentConnect\UI\PostToPost( $this, $this->from, $this->from_labels, $this->from_sortable );
			$this->from_ui->setup();
		}

		// Make sure CPT is not the same as "from" so we don't get a duplicate, then register if enabled
		if ( $this->to !== $this->from && $this->enable_to_ui === true ) {
			$this->to_ui = new \TenUp\ContentConnect\UI\PostToPost( $this, $this->to, $this->to_labels, $this->to_sortable );
			$this->to_ui->setup();
		}

	}

	/**
	 * Gets the IDs that are related to the supplied post ID in the context of the current relationship
	 *
	 * @param $post_id
	 *
	 * @return array
	 */
	public function get_related_object_ids( $post_id, $order_by_relationship = false ) {
		/** @var \TenUp\ContentConnect\Tables\PostToPost $table */
		$table = Plugin::instance()->get_table( 'p2p' );
		$db = $table->get_db();

		$table_name = esc_sql( $table->get_table_name() );

		// Query either to or from, depending on the post type of the ID we're finding relationships for
		$post_type = get_post_type( $post_id );
		if ( $post_type != $this->from && $post_type != $this->to ) {
			return array();
		}
		$where_post_type = $post_type === $this->from ? $this->to : $this->from;

		$query = $db->prepare( "SELECT p2p.id1 as ID, p.post_type FROM {$table_name} AS p2p INNER JOIN {$db->posts} as p on p2p.id1 = p.ID WHERE p2p.id2 = %d and p2p.name = %s and p.post_type = %s", $post_id, $this->name, $where_post_type );
		if ( $order_by_relationship ) {
			$query .= " ORDER BY p2p.order = 0, p2p.order ASC";
		}

		$objects = $db->get_results( $query );

		return wp_list_pluck( $objects, 'ID' );
	}

	/**
	 * Since we are joining on the same tables, its rather difficult to always know which order the relationship will be
	 * ESPECIALLY when joining the same post type to itself. To work around this, we just store both combinations of
	 * the relationship. Adds a tiny bit of data to the DB, but greatly simplifies queries to find related posts
	 *
	 * Coincidentally, this also allows us to store directional sort order information
	 *
	 * `order` corresponds to the order of id2, when viewed from id1
	 *
	 * @param $pid1
	 * @param $pid2
	 */
	public function add_relationship( $pid1, $pid2 ) {
		/** @var \TenUp\ContentConnect\Tables\PostToPost $table */
		$table = Plugin::instance()->get_table( 'p2p' );

		$table->replace(
			array( 'id1' => $pid1, 'id2' => $pid2, 'name' => $this->name ),
			array( '%d', '%d', '%s' )
		);
		$table->replace(
			array( 'id1' => $pid2, 'id2' => $pid1, 'name' => $this->name ),
			array( '%d', '%d', '%s' )
		);
	}

	public function delete_relationship( $pid1, $pid2 ) {
		/** @var \TenUp\ContentConnect\Tables\PostToPost $table */
		$table = Plugin::instance()->get_table( 'p2p' );

		$table->delete(
			array( 'id1' => $pid1, 'id2' => $pid2, 'name' => $this->name ),
			array( '%d', '%d', '%s' )
		);
		$table->delete(
			array( 'id1' => $pid2, 'id2' => $pid1, 'name' => $this->name ),
			array( '%d', '%d', '%s' )
		);
	}

	/**
	 * Replaces existing relationships for the post with this set.
	 *
	 * Any relationship that is present in the database but not in $related_ids will no longer be related
	 *
	 * @param $post_id
	 * @param $related_ids
	 */
	public function replace_relationships( $post_id, $related_ids ) {
		$current_ids = $this->get_related_object_ids( $post_id );

		$delete_ids = array_diff( $current_ids, $related_ids );
		$add_ids = array_diff( $related_ids, $current_ids );

		// @todo add bulk methods!
		foreach( $delete_ids as $delete ) {
			$this->delete_relationship( $post_id, $delete );
		}

		foreach( $add_ids as $add ) {
			$this->add_relationship( $post_id, $add );
		}
	}

	/**
	 * Updates all the rows with order information.
	 *
	 * This function ONLY modifies ONE direction of the query:
	 *      - id2 is the post we're ordering on (we're on this edit screen)
	 *      - id1 is the post being ordered
	 * The inverse is managed from the other end of the relationship
	 *
	 * @param $object_id
	 * @param $ordered_ids
	 */
	public function save_sort_data( $object_id, $ordered_ids ) {
		if ( empty( $ordered_ids ) ) {
			return;
		}

		$order = 0;

		$data = array();

		foreach( $ordered_ids as $id ) {
			$order++;

			$data[] = array(
				'id1' => $id,
				'id2' => $object_id,
				'name' => $this->name,
				'order' => $order
			);
		}
		
		$fields = array(
			'id1' => '%d',
			'id2' => '%d',
			'name' => '%s',
			'order' => '%d',
		);

		/** @var \TenUp\ContentConnect\Tables\PostToPost $table */
		$table = Plugin::instance()->get_table( 'p2p' );
		$table->replace_bulk( $fields, $data );
	}

}
