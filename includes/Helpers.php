<?php

namespace TenUp\ContentConnect\Helpers;

use TenUp\ContentConnect\Plugin;

/**
 * Returns the instance of the plugin.
 *
 * @since 1.7.0
 *
 * @return \TenUp\ContentConnect\Plugin
 */
function get_plugin() {
	return Plugin::instance();
}

/**
 * Returns the instance of the relationship registry.
 *
 * @return \TenUp\ContentConnect\Registry
 */
function get_registry() {
	return get_plugin()->get_registry();
}

/**
 * Returns all related posts for a given post ID and relationship name, without restricting by post type.
 *
 * Useful when you have many relationships between different post types with the same name, and you want to return
 * ALL related posts by relationship name.
 *
 * @param  int    $post_id           The ID of the post to get related posts for.
 * @param  string $relationship_name The name of the relationship to get related posts for.
 * @return array IDs of posts related to the post with the named relationship
 */
function get_related_ids_by_name( $post_id, $relationship_name ) {

	$table = get_plugin()->get_table( 'p2p' );

	if ( empty( $table ) ) {
		return array();
	}

	$db         = $table->get_db();
	$table_name = esc_sql( $table->get_table_name() );
	$query      = $db->prepare( "SELECT p2p.id1 as ID FROM {$table_name} AS p2p WHERE p2p.id2 = %d and p2p.name = %s", $post_id, $relationship_name );

	$objects = $db->get_results( $query );

	if ( empty( $objects ) ) {
		return array();
	}

	if ( ! is_array( $objects ) ) {
		$objects = array( $objects );
	}

	$related_ids = wp_list_pluck( $objects, 'ID' );

	return $related_ids;
}

/**
 * Gets post to post relationships by a given field.
 *
 * @since 1.7.0
 *
 * @param  string $field The field to query against. Accepts 'key', 'post_type', 'from', or 'to'.
 *                       'key' will return a single relationship by key.
 *                       'post_type' will return all relationships for a given post type.
 *                       'from' will return all relationships where the 'from' post type matches the value.
 *                       'to' will return all relationships where the 'to' post type matches the value.
 * @param  string $value The field value.
 * @return array
 */
function get_post_to_post_relationships_by( $field, $value ) {

	$relationships = get_registry()->get_post_to_post_relationships();

	if ( empty( $relationships ) ) {
		return array();
	}

	if ( 'key' === $field ) {
		return get_registry()->get_post_to_post_relationship_by_key( $value );
	}

	$post_to_post_relationships = array();

	foreach ( $relationships as $key => $relationship ) {

		switch ( $field ) {
			case 'post_type':
				if ( $relationship->from === $value || $relationship->to === $value ) {
					$post_to_post_relationships[ $key ] = $relationship;
				}
				break;
			case 'from':
				if ( $relationship->from === $value ) {
					$post_to_post_relationships[ $key ] = $relationship;
				}
				break;
			case 'to':
				if ( in_array( $value, $relationship->to, true ) ) {
					$post_to_post_relationships[ $key ] = $relationship;
				}
				break;
		}
	}

	return $post_to_post_relationships;
}

/**
 * Gets post to users relationships by a given field.
 *
 * @since 1.7.0
 *
 * @param  string $field The field to query against. Accepts 'key', or 'post_type'.
 * 'key' will return a single relationship by key.
 * 'post_type' will return all relationships for a given post type.
 * @param  string $value The field value.
 * @return array
 */
function get_post_to_user_relationships_by( $field, $value ) {

	$relationships = get_registry()->get_post_to_user_relationships();

	if ( empty( $relationships ) ) {
		return array();
	}

	if ( 'key' === $field ) {
		return get_registry()->get_post_to_user_relationship_by_key( $value );
	}

	$post_to_user_relationships = array();

	foreach ( $relationships as $key => $relationship ) {

		switch ( $field ) {
			case 'post_type':
				if ( $relationship->post_type === $value ) {
					$post_to_user_relationships[ $key ] = $relationship;
				}
				break;
		}
	}

	return $post_to_user_relationships;
}

/**
 * Get post relationship data.
 *
 * @since 1.7.0
 *
 * @param  int|\WP_Post $post            Post ID or post object.
 * @param  string       $other_post_type Optional. The post type to get relationships for.
 *                                       If not provided, all relationships for the post will be returned.
 * @return array
 */
function get_post_relationship_data( $post, $other_post_type = false ) {

	$post = get_post( $post );

	if ( ! $post ) {
		return array();
	}

	$relationships = get_post_to_post_relationships_by( 'from', $post->post_type );

	if ( empty( $relationships ) ) {
		return array();
	}

	$registry = get_registry();

	$relationship_data = array();

	foreach ( $relationships as $relationship ) {

		if ( ! empty( $other_post_type ) && ! in_array( $other_post_type, $relationship->to, true ) ) {
			continue;
		}

		$query_args = array(
			'post_type'              => $relationship->to,
			'relationship_query'     => array(
				'name'            => $relationship->name,
				'related_to_post' => $post->ID,
			),
			'posts_per_page'         => 100,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		);

		if ( $relationship->to_sortable ) {
			$query_args['orderby'] = 'relationship';
		}

		/** This filter is documented in includes/UI/MetaBox.php */
		$query_args = apply_filters( 'tenup_content_connect_post_ui_query_args', $query_args, $post );

		$query = new \WP_Query( $query_args );

		if ( ! $query->have_posts() ) {
			continue;
		}

		$other_posts = $query->get_posts();

		$selected = array();
		foreach ( $other_posts as $other_post ) {

			$post_data = array(
				'ID'   => $other_post->ID,
				'name' => $other_post->post_title,
			);

			/** This filter is documented in includes/UI/MetaBox.php */
			$post_data = apply_filters( 'tenup_content_connect_final_post', $post_data, $relationship );

			/**
			 * Filters the Post UI post data.
			 *
			 * @since 1.7.0
			 * @param array        $post_data The post data.
			 * @param \WP_Post     $post      The post object.
			 * @param Relationship $relationship The relationship object.
			 */
			$post_data = apply_filters( 'tenup_content_connect_post_ui_post_data', $post_data, $other_post, $relationship );

			$selected[] = $post_data;
		}

		$relationship_data[] = array(
			'reltype'         => 'post-to-post',
			'object_type'     => 'post',
			'post_type'       => $relationship->to,
			'relid'           => $registry->get_relationship_key( $relationship->from, $relationship->to, $relationship->name ),
			'name'            => $relationship->name,
			'labels'          => $relationship->from_labels,
			'sortable'        => $relationship->from_sortable,
			'selected'        => $selected,
			'current_post_id' => $post->ID,
		);
	}

	return $relationship_data;
}
