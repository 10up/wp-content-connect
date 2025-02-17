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
 * Retrieves all related post IDs for a given post and relationship name.
 *
 * Unlike other functions, this does not restrict results by post type, making it useful
 * for cases where multiple post types share the same relationship name.
 *
 * @since 1.7.0
 *
 * @param  int    $post_id           The ID of the post to retrieve relationships for.
 * @param  string $relationship_name The name of the relationship to filter by.
 * @return int[]                      An array of related post IDs.
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
 * Retrieves post-to-post relationships based on a specified field.
 *
 * @since 1.7.0
 *
 * @param  string $field The field to query against. Accepts 'key', 'post_type', 'from', or 'to'.
 *                       - 'key': Returns a single relationship by its unique key.
 *                       - 'post_type': Returns all relationships involving the specified post type.
 *                       - 'from': Returns all relationships originating from the specified post type.
 *                       - 'to': Returns all relationships targeting the specified post type.
 * @param  string $value The value to match against the specified field.
 * @return \TenUp\ContentConnect\Relationships\Relationship|array<int, \TenUp\ContentConnect\Relationships\Relationship>
 *         A single Relationship object if 'key' is specified and found,
 *         otherwise an associative array of Relationship objects indexed by relationship key.
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
 * Retrieves post-to-user relationships based on a specified field.
 *
 * @since 1.7.0
 *
 * @param  string $field The field to query against. Accepts 'key' or 'post_type'.
 *                       - 'key': Returns a single relationship by its unique key.
 *                       - 'post_type': Returns all relationships involving the specified post type.
 * @param  string $value The value to match against the specified field.
 * @return \TenUp\ContentConnect\Relationships\Relationship|array<int, \TenUp\ContentConnect\Relationships\Relationship>
 *         A single Relationship object if 'key' is specified and found,
 *         otherwise an associative array of Relationship objects indexed by relationship key.
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
 * Retrieves relationships (post-to-post and post-to-user) for a given post.
 *
 * Retrieves relationship data for a specific post, optionally filtered by relationship type
 * ('post-to-post' or 'post-to-user') and, for post-to-post relationships, by post type.
 *
 * @since 1.7.0
 *
 * @param  int|\WP_Post $post            Post ID or post object.
 * @param  string       $rel_type        Optional. The relationship type. Accepts 'post-to-post', 'post-to-user', or 'any' (default).
 *                                       If 'any', the function retrieves both post-to-post and post-to-user relationships.
 * @param  string|false $other_post_type Optional. The post type to filter post-to-post relationships by.
 *                                       Ignored for post-to-user relationships. Default false (returns all relationships).
 * @param  string       $context         Optional. Defines the level of detail in the response.
 *                                       - 'view': Returns basic relationship metadata without fetching related entities.
 *                                       - 'embed': Includes the full list of related posts or users in the response.
 *                                       Defaults to 'view' for performance reasons.
 * @return array<int, array<string, mixed>> Associative array containing relationship data.
 *                                          Each relationship entry includes:
 *                                          - 'rel_key' (string): The unique key of the relationship.
 *                                          - 'rel_type' (string): Either 'post-to-post' or 'post-to-user'.
 *                                          - 'rel_name' (string): The relationship name.
 *                                          - 'object_type' (string): 'post' or 'user'.
 *                                          - 'post_type' (string|null): The related post type (only for post-to-post).
 *                                          - 'labels' (array): UI labels associated with the relationship.
 *                                          - 'sortable' (bool): Whether the relationship supports sorting.
 *                                          - 'related' (array): The actual related posts/users (only when context='embed').
 */
function get_post_relationship_data( $post, $rel_type = 'any', $other_post_type = false, $context = 'view' ) {

	$post = get_post( $post );

	if ( ! $post ) {
		return array();
	}

	if ( 'post-to-user' === $rel_type ) {
		return get_post_to_user_relationships_data( $post, $context );
	}

	if ( 'post-to-post' === $rel_type ) {
		return get_post_to_post_relationships_data( $post, $other_post_type, $context );
	}

	if ( 'any' !== $rel_type ) {
		return array();
	}

	if ( ! empty( $other_post_type ) ) {
		return get_post_to_post_relationships_data( $post, $other_post_type, $context );
	}

	$relationship_data = array_merge(
		get_post_to_post_relationships_data( $post, $other_post_type, $context ),
		get_post_to_user_relationships_data( $post, $context )
	);

	return $relationship_data;
}

/**
 * Retrieves post-to-post relationship data for a given post.
 *
 * Fetches related posts based on post-to-post relationships configured in Content Connect.
 * Optionally filters results by a specific post type.
 *
 * @since 1.7.0
 *
 * @param  int|\WP_Post $post            Post ID or post object.
 * @param  string|false $other_post_type Optional. A post type to filter relationships by.
 *                                       Only relationships to this post type will be returned.
 *                                       Defaults to false (returns all post-to-post relationships).
 * @param  string       $context         Optional. Defines the level of detail in the response.
 *                                       - 'view': Returns basic relationship metadata without fetching related entities.
 *                                       - 'embed': Includes the full list of related posts or users in the response.
 *                                       Defaults to 'view' for performance reasons.
 * @return array<int, array<string, mixed>> Associative array containing relationship data.
 *                                          Each relationship entry includes:
 *                                          - 'rel_key' (string): The unique key of the relationship.
 *                                          - 'rel_type' (string): Either 'post-to-post' or 'post-to-user'.
 *                                          - 'rel_name' (string): The relationship name.
 *                                          - 'object_type' (string): 'post' or 'user'.
 *                                          - 'post_type' (string|null): The related post type (only for post-to-post).
 *                                          - 'labels' (array): UI labels associated with the relationship.
 *                                          - 'sortable' (bool): Whether the relationship supports sorting.
 *                                          - 'related' (array): The actual related posts/users (only when context='embed').
 */
function get_post_to_post_relationships_data( $post, $other_post_type = false, $context = 'view' ) {

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

		$rel_key = $registry->get_relationship_key( $relationship->from, $relationship->to, $relationship->name );

		$relationship_data[ $rel_key ] = array(
			'rel_key'         => $rel_key,
			'rel_type'        => 'post-to-post',
			'rel_name'        => $relationship->name,
			'object_type'     => 'post',
			'post_type'       => $relationship->to,
			'labels'          => $relationship->from_labels,
			'sortable'        => $relationship->from_sortable,
			'current_post_id' => $post->ID,
		);

		if ( 'embed' === $context ) {

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

			if ( $relationship->from_sortable ) {
				$query_args['orderby'] = 'relationship';
			}

			/** This filter is documented in includes/UI/MetaBox.php */
			$query_args = apply_filters( 'tenup_content_connect_post_ui_query_args', $query_args, $post );

			$query = new \WP_Query( $query_args );

			$queried_posts = $query->get_posts();

			$related_posts = array();
			foreach ( $queried_posts as $queried_post ) {

				$item_data = array(
					'ID'   => $queried_post->ID,
					'name' => $queried_post->post_title,
				);

				/** This filter is documented in includes/UI/MetaBox.php */
				$item_data = apply_filters( 'tenup_content_connect_final_post', $item_data, $relationship );

				/**
				 * Filters the Post UI item data.
				 *
				 * @since 1.7.0
				 * @param array    $item_data The item data.
				 * @param \WP_Post $post      The post object.
				 * @param Relationship $relationship The relationship object.
				 */
				$item_data = apply_filters( 'tenup_content_connect_post_ui_item_data', $item_data, $queried_post, $relationship );

				$related_posts[] = $item_data;
			}

			$relationship_data[ $rel_key ]['related'] = $related_posts;
		}
	}

	return $relationship_data;
}

/**
 * Retrieves post-to-user relationship data for a given post.
 *
 * Fetches related users based on post-to-user relationships configured in Content Connect.
 *
 * @since 1.7.0
 *
 * @param  int|\WP_Post $post    Post ID or post object.
 * @param  string       $context         Optional. Defines the level of detail in the response.
 *                                       - 'view': Returns basic relationship metadata without fetching related entities.
 *                                       - 'embed': Includes the full list of related posts or users in the response.
 *                                       Defaults to 'view' for performance reasons.
 * @return array<int, array<string, mixed>> Associative array containing relationship data.
 *                                          Each relationship entry includes:
 *                                          - 'rel_key' (string): The unique key of the relationship.
 *                                          - 'rel_type' (string): Either 'post-to-post' or 'post-to-user'.
 *                                          - 'rel_name' (string): The relationship name.
 *                                          - 'object_type' (string): 'post' or 'user'.
 *                                          - 'post_type' (string|null): The related post type (only for post-to-post).
 *                                          - 'labels' (array): UI labels associated with the relationship.
 *                                          - 'sortable' (bool): Whether the relationship supports sorting.
 *                                          - 'related' (array): The actual related posts/users (only when context='embed').
 */
function get_post_to_user_relationships_data( $post, $context = 'view' ) {

	$post = get_post( $post );

	if ( ! $post ) {
		return array();
	}

	$relationships = get_post_to_user_relationships_by( 'post_type', $post->post_type );

	if ( empty( $relationships ) ) {
		return array();
	}

	$registry = get_registry();

	$relationship_data = array();

	foreach ( $relationships as $relationship ) {

		$rel_key = $registry->get_relationship_key( $relationship->post_type, 'user', $relationship->name );

		$relationship_data[ $rel_key ] = array(
			'rel_key'         => $rel_key,
			'rel_type'        => 'post-to-user',
			'rel_name'        => $relationship->name,
			'object_type'     => 'user',
			'labels'          => $relationship->from_labels,
			'sortable'        => $relationship->from_sortable,
			'current_post_id' => $post->ID,
		);

		if ( 'embed' === $context ) {

			$query_args = array(
				'relationship_query' => array(
					'name'            => $relationship->name,
					'related_to_post' => $post->ID,
				),
			);

			if ( $relationship->from_sortable ) {
				$query_args['orderby'] = 'relationship';
			}

			/** This filter is documented in includes/UI/MetaBox.php */
			$query_args = apply_filters( 'tenup_content_connect_post_ui_user_query_args', $query_args, $post );

			$query = new \WP_User_Query( $query_args );

			$queried_users = $query->get_results();

			$related_users = array();
			foreach ( $queried_users as $queried_user ) {

				$item_data = array(
					'ID'   => $queried_user->ID,
					'name' => $queried_user->display_name,
				);

				/** This filter is documented in includes/UI/MetaBox.php */
				$item_data = apply_filters( 'tenup_content_connect_final_user', $item_data, $relationship );

				/**
				 * Filters the Post UI item data.
				 *
				 * @since 1.7.0
				 * @param array        $item_data The item data.
				 * @param \WP_Post     $user      The user object.
				 * @param Relationship $relationship The relationship object.
				 */
				$item_data = apply_filters( 'tenup_content_connect_post_ui_item_data', $item_data, $queried_user, $relationship );

				$related_users[] = $item_data;
			}

			$relationship_data[ $rel_key ]['related'] = $related_users;
		}
	}

	return $relationship_data;
}
