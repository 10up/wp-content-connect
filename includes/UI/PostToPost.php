<?php

namespace TenUp\ContentConnect\UI;

class PostToPost extends PostUI {

	public function setup() {
		add_filter( 'tenup_content_connect_post_relationship_data', array( $this, 'filter_data' ), 10, 2 );
	}

	public function filter_data( $data, $post ) {
		// Don't add any data if we aren't on the post type we're supposed to render for
		if ( $post->post_type !== $this->render_post_type ) {
			return $data;
		}

		// Determine the other post type in the relationship
		$other_post_type = $this->relationship->from == $this->render_post_type ? $this->relationship->to : $this->relationship->from;

		$final_posts = array();

		$args = array (
			'post_type' => $other_post_type,
			'relationship_query' => array(
				'type' => $this->relationship->type,
				'related_to_post' => $post->ID,
			),
		);

		if ( $this->sortable ) {
			$args['orderby'] = 'relationship';
		}

		$query = new \WP_Query( $args );

		if ( $query->have_posts() ) {
			while( $query->have_posts() ) {
				$post = $query->next_post();

				$final_posts[] = array(
					'ID' => $post->ID,
					'name' => $post->post_title,
				);
			}
		}

		// @Todo add pagination

		$data[] = array(
			'reltype' => 'post-to-post',
			'object_type' => 'post', // The object type we'll be querying for in searches on the front end
			'post_type' => $other_post_type, // The post type we'll be querying for in searches on the front end (so NOT the current post type, but the matching one in the relationship)
			'relid' => "{$this->relationship->from}_{$this->relationship->to}_{$this->relationship->type}", // @todo should probably get this from the registry
			'type' => $this->relationship->type,
			'labels' => $this->labels,
			'sortable' => $this->sortable,
			'selected' => $final_posts,
		);

		return $data;
	}

	public function handle_save( $relationship_data, $post_id ) {
		$current_ids = $this->relationship->get_related_object_ids( $post_id );

		$delete_ids = array_diff( $current_ids, $relationship_data['add_items'] );
		$add_ids = array_diff( $relationship_data['add_items'], $current_ids );

		// @todo add bulk methods!
		foreach( $delete_ids as $delete ) {
			$this->relationship->delete_relationship( $post_id, $delete );
		}

		foreach( $add_ids as $add ) {
			$this->relationship->add_relationship( $post_id, $add );
		}

		if ( $this->sortable ) {
			$this->relationship->save_sort_data( $post_id, $relationship_data['add_items'] );
		}
	}

}
