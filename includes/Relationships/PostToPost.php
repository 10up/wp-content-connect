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

	public function __construct( $from, $to, $type, $args = array() ) {
		if ( ! post_type_exists( $from ) ) {
			throw new \Exception( "Post Type {$from} does not exist. Post types must exist to create a relationship" );
		}

		if ( ! post_type_exists( $to ) ) {
			throw new \Exception( "Post Type {$to} does not exist. Post types must exist to create a relationship" );
		}

		$this->from = $from;
		$this->to = $to;
		$this->id = strtolower( get_class( $this ) ) . "-{$type}-{$from}-{$to}";
		
		parent::__construct( $type, $args );
	}

	public function setup() {
		if ( $this->enable_from_ui === true ) {
			$this->from_ui = new \TenUp\ContentConnect\UI\PostToPost( $this, $this->from, $this->from_labels, $this->from_sortable );
			$this->from_ui->setup();
		}

		/*
		 * Only register "TO" UI if the following are met:
		 * - CPTs are not the same
		 * - TO UI is enabled
		 * - FROM is not sortable (we can't have multiple UIs when one of them is sortable).
		 *      If from marked as sortable, we also make sure its enabled, or else it doesn't matter
		 */
		if ( ! ( $this->from_sortable === true && $this->enable_from_ui === true ) ) {
			if ( $this->to !== $this->from && $this->enable_to_ui === true ) {
				$this->to_ui = new \TenUp\ContentConnect\UI\PostToPost( $this, $this->to, $this->to_labels, false );
				$this->to_ui->setup();
			}
		}

	}

	/**
	 * Gets the IDs that are related to the supplied post ID in the context of the current relationship
	 *
	 * @param $post_id
	 *
	 * @return array
	 */
	public function get_related_object_ids( $post_id ) {
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
		/** @var \TenUp\ContentConnect\Tables\PostToPost $table */
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
		/** @var \TenUp\ContentConnect\Tables\PostToPost $table */
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

	public function get_sort_meta_key() {
		return "p2p_{$this->from}_{$this->to}_{$this->type}-sort-data";
	}

	public function save_sort_data( $object_id, $ordered_ids ) {
		update_post_meta( $object_id, $this->get_sort_meta_key(), array_map( 'intval', $ordered_ids ) );
	}

	public function get_sort_data( $object_id ) {
		return get_post_meta( $object_id, $this->get_sort_meta_key(), true );
	}

}
