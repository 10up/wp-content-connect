<?php

namespace TenUp\P2P\UI;

class PostToPost {

	/**
	 * @var \TenUp\P2P\Relationships\PostToPost
	 */
	public $relationship;

	/**
	 * @param PostToPost $relationship
	 */
	public function __construct( $relationship ) {
		$this->relationship = $relationship;

	}

	public function setup() {
		add_filter( 'tenup_p2p_post_relationship_data', array( $this, 'filter_data' ), 10, 2 );
	}

	public function filter_data( $data, $post ) {
		// Determine the other post type in the relationship
		$other_post_type = $this->relationship->from == $post->post_type ? $this->relationship->to : $this->relationship->from;

		$final_posts = array(

		);

		// @todo if order is supported, we need to respect the order
		$query = new \WP_Query( array (
			'post_type' => $other_post_type,
			'relationship_query' => array(
				'type' => $this->relationship->type,
				'related_to_post' => $post->ID,
			),
		) );

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

		if ( ! empty( $final_posts ) ) {
			$data[] = array(
				'reltype' => 'post-to-post',
				'object_type' => 'post', // The object type we'll be querying for in searches on the front end
				'post_type' => $other_post_type, // The post type we'll be querying for in searches on the front end (so NOT the current post type, but the matching one in the relationship)
				'relid' => "{$this->relationship->from}_{$this->relationship->to}_{$this->relationship->type}", // @todo should probably get this from the registry
				'type' => $this->relationship->type,
				'labels' => $this->relationship->labels,
				'selected' => $final_posts,
			);
		}

		return $data;
	}

}
