<?php

namespace TenUp\ContentConnect\Helpers;

use TenUp\ContentConnect\Plugin;

/**
 * Returns the instance of the plugin.
 *
 * @since 2.0.0
 *
 * @return \TenUp\ContentConnect\Plugin
 */
function get_plugin() {
	return Plugin::instance();
}

/**
 * Returns the instance of the relationship registry.
 *
 * @since 2.0.0
 *
 * @return \TenUp\ContentConnect\Registry
 */
function get_registry() {
	return get_plugin()->get_registry();
}

/**
 * Checks if the code is running in a test environment.
 *
 * @since 2.0.0
 *
 * @return bool True if running tests, false otherwise.
 */
function is_doing_tests() {
	return defined( 'CONTENT_CONNECT_DOING_TESTS' ) && CONTENT_CONNECT_DOING_TESTS;
}

/**
 * Retrieves all related post IDs for a given post and relationship name.
 *
 * Unlike other functions, this does not restrict results by post type, making it useful
 * for cases where multiple post types share the same relationship name.
 *
 * @since 2.0.0
 *
 * @param  int    $post_id           The ID of the post to retrieve relationships for.
 * @param  string $relationship_name The name of the relationship to filter by.
 * @return int[]                      An array of related post IDs.
 */
function get_related_ids_by_name( $post_id, $relationship_name ) {

	if ( ! is_numeric( $post_id ) || $post_id <= 0 ) {
		return array();
	}

	if ( empty( $relationship_name ) || ! is_string( $relationship_name ) ) {
		return array();
	}

	$table = get_plugin()->get_table( 'p2p' );

	if ( empty( $table ) ) {
		return array();
	}

	$db         = $table->get_db();
	$table_name = esc_sql( $table->get_table_name() );
	$query      = $db->prepare( "SELECT p2p.id1 as ID FROM {$table_name} AS p2p WHERE p2p.id2 = %d and p2p.name = %s", $post_id, $relationship_name );

	$objects = $db->get_results( $query );

	// Check for database errors.
	if ( ! empty( $db->last_error ) ) {
		return array();
	}

	if ( empty( $objects ) ) {
		return array();
	}

	if ( ! is_array( $objects ) ) {
		$objects = array( $objects );
	}

	$related_ids = wp_list_pluck( $objects, 'ID' );

	return array_map( 'intval', $related_ids );
}

/**
 * Retrieves post-to-post relationships based on a specified field.
 *
 * @since 2.0.0
 *
 * @param  string $field The field to query against. Accepts 'any', 'key', 'post_type', 'from', or 'to'.
 *                       - 'key': Returns a single relationship by its unique key.
 *                       - 'post_type': Returns all relationships involving the specified post type.
 *                       - 'from': Returns all relationships originating from the specified post type.
 *                       - 'to': Returns all relationships targeting the specified post type.
 *                       - 'any': Returns all relationships.
 * @param  string $value The value to match against the specified field.
 * @return false|array<string, \TenUp\ContentConnect\Relationships\PostToPost> Associative array of Relationship objects indexed by relationship key, otherwise false.
 */
function get_post_to_post_relationships_by( $field = 'any', $value = '' ) {

	// Use static cache for repeated calls within the same request.
	static $cache = array();

	$cache_key = $field . '_' . $value;

	if ( ! is_doing_tests() && isset( $cache[ $cache_key ] ) ) {
		return $cache[ $cache_key ];
	}

	if ( 'key' === $field ) {
		$relationship = get_registry()->get_post_to_post_relationship_by_key( $value );

		if ( $relationship instanceof \TenUp\ContentConnect\Relationships\Relationship ) {
			$result = array( $value => $relationship );
			if ( ! is_doing_tests() ) {
				$cache[ $cache_key ] = $result;
			}
			return $result;
		}

		if ( ! is_doing_tests() ) {
			$cache[ $cache_key ] = false;
		}

		return false;
	}

	$relationships = get_registry()->get_post_to_post_relationships();

	if ( empty( $relationships ) ) {

		if ( ! is_doing_tests() ) {
			$cache[ $cache_key ] = array();
		}

		return array();
	}

	$post_to_post_relationships = array();

	foreach ( $relationships as $key => $relationship ) {
		$relationship_to = is_array( $relationship->to ) ? $relationship->to : array( $relationship->to );

		switch ( $field ) {
			case 'post_type':
				if ( ! empty( $value ) && ( $relationship->from === $value || in_array( $value, $relationship_to, true ) ) ) {
					$post_to_post_relationships[ $key ] = $relationship;
				}
				break;
			case 'from':
				if ( ! empty( $value ) && $relationship->from === $value ) {
					$post_to_post_relationships[ $key ] = $relationship;
				}
				break;
			case 'to':
				if ( ! empty( $value ) && in_array( $value, $relationship_to, true ) ) {
					$post_to_post_relationships[ $key ] = $relationship;
				}
				break;
			case 'any':
				$post_to_post_relationships[ $key ] = $relationship;
				break;
		}
	}

	if ( ! is_doing_tests() ) {
		$cache[ $cache_key ] = $post_to_post_relationships;
	}

	return $post_to_post_relationships;
}

/**
 * Retrieves post-to-user relationships based on a specified field.
 *
 * @since 2.0.0
 *
 * @param  string $field The field to query against. Accepts 'any', 'key' or 'post_type'.
 *                       - 'key': Returns a single relationship by its unique key.
 *                       - 'post_type': Returns all relationships involving the specified post type.
 *                       - 'any': Returns all relationships.
 * @param  string $value The value to match against the specified field.
 * @return false|array<string, \TenUp\ContentConnect\Relationships\PostToUser> Associative array of Relationship objects indexed by relationship key, otherwise false.
 */
function get_post_to_user_relationships_by( $field = 'any', $value = '' ) {

	// Use static cache for repeated calls within the same request.
	static $cache = array();

	$cache_key = $field . '_' . $value;

	if ( ! is_doing_tests() && isset( $cache[ $cache_key ] ) ) {
		return $cache[ $cache_key ];
	}

	if ( 'key' === $field ) {
		$relationship = get_registry()->get_post_to_user_relationship_by_key( $value );

		if ( $relationship instanceof \TenUp\ContentConnect\Relationships\Relationship ) {
			$result = array( $value => $relationship );

			if ( ! is_doing_tests() ) {
				$cache[ $cache_key ] = $result;
			}

			return $result;
		}

		if ( ! is_doing_tests() ) {
			$cache[ $cache_key ] = false;
		}

		return false;
	}

	$relationships = get_registry()->get_post_to_user_relationships();

	if ( empty( $relationships ) ) {

		if ( ! is_doing_tests() ) {
			$cache[ $cache_key ] = array();
		}

		return array();
	}

	$post_to_user_relationships = array();

	foreach ( $relationships as $key => $relationship ) {

		switch ( $field ) {
			case 'post_type':
				if ( $relationship->post_type === $value ) {
					$post_to_user_relationships[ $key ] = $relationship;
				}
				break;
			case 'any':
				$post_to_user_relationships[ $key ] = $relationship;
				break;
		}
	}

	if ( ! is_doing_tests() ) {
		$cache[ $cache_key ] = $post_to_user_relationships;
	}

	return $post_to_user_relationships;
}

/**
 * Retrieves relationships (post-to-post and post-to-user) for a given post.
 *
 * Retrieves relationship data for a specific post, optionally filtered by relationship type
 * ('post-to-post' or 'post-to-user') and, for post-to-post relationships, by post type.
 *
 * @since 2.0.0
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
function get_post_relationships_data( $post, $rel_type = 'any', $other_post_type = false, $context = 'view' ) {

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
 * @since 2.0.0
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

	$relationships = get_post_to_post_relationships_by( 'post_type', $post->post_type );

	if ( empty( $relationships ) ) {
		return array();
	}

	$relationships_data = array();

	foreach ( $relationships as $rel_key => $relationship ) {

		$relationship_data = array(
			'rel_key'     => $rel_key,
			'rel_type'    => 'post-to-post',
			'rel_name'    => $relationship->name,
			'object_type' => 'post',
		);

		$relationship_to = is_array( $relationship->to ) ? $relationship->to : array( $relationship->to );

		if ( $post->post_type === $relationship->from ) {
			$relationship_data['labels']    = $relationship->from_labels;
			$relationship_data['enable_ui'] = $relationship->enable_from_ui;
			$relationship_data['sortable']  = $relationship->from_sortable;
			$relationship_data['post_type'] = $relationship_to;
		} else {
			$relationship_data['labels']    = $relationship->to_labels;
			$relationship_data['enable_ui'] = $relationship->enable_to_ui;
			$relationship_data['sortable']  = $relationship->to_sortable;
			$relationship_data['post_type'] = array( $relationship->from );
		}

		if ( ! empty( $other_post_type ) && ! in_array( $other_post_type, $relationship_to, true ) && $relationship->from !== $other_post_type ) {
			continue;
		}

		if ( 'embed' === $context ) {

			/**
			 * Filters the default posts per page limit for relationship queries.
			 *
			 * @since 2.0.0
			 *
			 * @param int    $posts_per_page Default number of posts to retrieve. Default 100.
			 * @param string $rel_key        The relationship key.
			 * @param int    $post_id        The post ID being queried.
			 */
			$posts_per_page = apply_filters( 'tenup_content_connect_posts_per_page', 100, $rel_key, $post->ID );

			$query_args = array(
				'post_type'              => $relationship_data['post_type'],
				'posts_per_page'         => $posts_per_page,
				'relationship_query'     => array(
					'name'            => $relationship->name,
					'related_to_post' => $post->ID,
				),
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			);

			if ( ! empty( $relationship_data['sortable'] ) ) {
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
				 * Filters the post item data.
				 *
				 * @since 2.0.0
				 * @param array    $item_data The item data.
				 * @param \WP_Post $post      The post object.
				 * @param Relationship $relationship The relationship object.
				 */
				$item_data = apply_filters( 'tenup_content_connect_post_item_data', $item_data, $queried_post, $relationship );

				$related_posts[] = $item_data;
			}

			$relationship_data['related'] = $related_posts;
		}

		$relationships_data[ $rel_key ] = $relationship_data;
	}

	return $relationships_data;
}

/**
 * Retrieves post-to-user relationship data for a given post.
 *
 * Fetches related users based on post-to-user relationships configured in Content Connect.
 *
 * @since 2.0.0
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

	$relationships_data = array();

	foreach ( $relationships as $rel_key => $relationship ) {

		$relationship_data = array(
			'rel_key'     => $rel_key,
			'rel_type'    => 'post-to-user',
			'rel_name'    => $relationship->name,
			'object_type' => 'user',
			'labels'      => $relationship->from_labels,
			'sortable'    => $relationship->from_sortable,
			'enable_ui'   => $relationship->enable_from_ui,
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
				 * Filters the user item data.
				 *
				 * @since 2.0.0
				 * @param array        $item_data The item data.
				 * @param \WP_User     $user      The user object.
				 * @param Relationship $relationship The relationship object.
				 */
				$item_data = apply_filters( 'tenup_content_connect_user_item_data', $item_data, $queried_user, $relationship );

				$related_users[] = $item_data;
			}

			$relationship_data['related'] = $related_users;
		}

		$relationships_data[ $rel_key ] = $relationship_data;
	}

	return $relationships_data;
}
