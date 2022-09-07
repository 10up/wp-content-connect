<?php

namespace TenUp\ContentConnect\UI;

use TenUp\ContentConnect\Plugin;

class PostToUser extends PostUI {

	public function setup() {
		add_filter( 'tenup_content_connect_post_relationship_data', array( $this, 'filter_data' ), 10, 2 );
	}

	public function filter_data( $data, $post ) {
		// Don't add any data if we aren't on the post type we're supposed to render for
		if ( $post->post_type !== $this->render_post_type ) {
			return $data;
		}

		$final_users = array();

		$args = array(
			'relationship_query' => array(
				'name'            => $this->relationship->name,
				'related_to_post' => $post->ID,
			),
		);

		if ( $this->sortable ) {
			$args['orderby'] = 'relationship';
		}

		/**
		 * Filters the Post UI user query args.
		 *
		 * @since  1.6.0
		 * @param  array    $args The \WP_User_Query args.
		 * @param  \WP_Post $post The post object.
		 * @return array
		 */
		$args = apply_filters( 'tenup_content_connect_post_ui_user_query_args', $args, $post );

		$query = new \WP_User_Query( $args );

		$users = $query->get_results();
		if ( ! empty( $users ) ) {
			foreach ( $users as $user ) {

				$final_user = array(
					'ID'   => $user->ID,
					'name' => $user->display_name,
				);

				$final_users[] = apply_filters( 'tenup_content_connect_final_user', $final_user, $this->relationship );
			}
		}

		// @Todo add pagination

		$registry = Plugin::instance()->get_registry();

		$data[] = array(
			'reltype'         => 'post-to-user',
			'object_type'     => 'user', // The object type we'll be querying for in searches on the front end.
			'relid'           => $registry->get_relationship_key( $this->relationship->post_type, 'user', $this->relationship->name ),
			'name'            => $this->relationship->name,
			'labels'          => $this->labels,
			'sortable'        => $this->sortable,
			'selected'        => $final_users,
			'current_post_id' => $post->ID,
		);

		return $data;
	}

	public function handle_save( $relationship_data, $post_id ) {
		$this->relationship->replace_post_to_user_relationships( $post_id, $relationship_data['add_items'] );

		if ( $this->sortable ) {
			$this->relationship->save_post_to_user_sort_data( $post_id, $relationship_data['add_items'] );
		}
	}

}
