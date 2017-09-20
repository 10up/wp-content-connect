<?php

namespace TenUp\P2P\UI;

class PostToUser {

	/**
	 * @var \TenUp\P2P\Relationships\PostToUser
	 */
	public $relationship;

	/**
	 * @param PostToUser $relationship
	 */
	public function __construct( $relationship ) {
		$this->relationship = $relationship;

	}

	public function setup() {
		add_filter( 'tenup_p2p_post_relationship_data', array( $this, 'filter_data' ), 10, 2 );
	}

	public function filter_data( $data, $post ) {
		// Determine the other post type in the relationship
		$other_post_type = $this->relationship->post_type;

		$final_users = array(

		);

		// @todo if order is supported, we need to respect the order
		$query = new \WP_User_Query( array(
			'relationship_query' => array(
				'type' => $this->relationship->type,
				'related_to_post' => $post->ID,
			)
		) );

		$users = $query->get_results();
		if ( ! empty( $users ) ) {
			foreach( $users as $user ) {
				$final_users[] = array(
					'ID' => $user->ID,
					'name' => $user->display_name,
				);
			}
		}

		// @Todo add pagination

		if ( ! empty( $final_users ) ) {
			$data[] = array(
				'reltype' => 'post-to-user',
				'object_type' => 'user', // The object type we'll be querying for in searches on the front end
				'relid' => "{$this->relationship->post_type}_user_{$this->relationship->type}", // @todo should probably get this from the registry
				'type' => $this->relationship->type,
				'labels' => $this->relationship->labels,
				'selected' => $final_users,
			);
		}

		return $data;
	}

	public function handle_save( $relationship_data, $post_id ) {
		$current_ids = $this->relationship->get_related_user_ids( $post_id );

		$delete_ids = array_diff( $current_ids, $relationship_data['add_items'] );
		$add_ids = array_diff( $relationship_data['add_items'], $current_ids );

		// @todo add bulk methods!
		foreach( $delete_ids as $delete ) {
			$this->relationship->delete_relationship( $post_id, $delete );
		}

		foreach( $add_ids as $add ) {
			$this->relationship->add_relationship( $post_id, $add );
		}
	}

}
