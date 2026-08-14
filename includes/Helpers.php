<?php

namespace TenUp\ContentConnect\Helpers;

use TenUp\ContentConnect\Plugin;

if ( ! function_exists( __NAMESPACE__ . '\\get_plugin' ) ) :
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
endif;

if ( ! function_exists( __NAMESPACE__ . '\\get_registry' ) ) :
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
endif;

if ( ! function_exists( __NAMESPACE__ . '\\get_related_ids_by_name' ) ) :
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

		$post_id = (int) $post_id;
		$table   = get_plugin()->get_table( 'p2p' );

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

		return array_map( 'intval', wp_list_pluck( $objects, 'ID' ) );
	}
endif;

if ( ! function_exists( __NAMESPACE__ . '\\get_post_to_post_relationships_by' ) ) :
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

		if ( 'key' === $field ) {
			$relationship = get_registry()->get_post_to_post_relationship_by_key( $value );

			if ( $relationship instanceof \TenUp\ContentConnect\Relationships\Relationship ) {
				return array( $value => $relationship );
			}

			return false;
		}

		$relationships = get_registry()->get_post_to_post_relationships();

		if ( empty( $relationships ) ) {
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
				default:
					break;
			}
		}

		return $post_to_post_relationships;
	}
endif;

if ( ! function_exists( __NAMESPACE__ . '\\get_post_to_user_relationships_by' ) ) :
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

		if ( 'key' === $field ) {
			$relationship = get_registry()->get_post_to_user_relationship_by_key( $value );

			if ( $relationship instanceof \TenUp\ContentConnect\Relationships\Relationship ) {
				return array( $value => $relationship );
			}

			return false;
		}

		$relationships = get_registry()->get_post_to_user_relationships();

		if ( empty( $relationships ) ) {
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

		return $post_to_user_relationships;
	}
endif;

if ( ! function_exists( __NAMESPACE__ . '\\get_related_post_item_data' ) ) :
	/**
	 * Prepares the item data for a related post.
	 *
	 * Produces the canonical item shape shared by the editor UIs and the REST API,
	 * and applies the `tenup_content_connect_final_post` and
	 * `tenup_content_connect_post_item_data` filters.
	 *
	 * @since 2.0.0
	 *
	 * @param  int|\WP_Post                                     $post         Post object or ID.
	 * @param  \TenUp\ContentConnect\Relationships\Relationship $relationship The relationship object.
	 * @return array The prepared post item data.
	 */
	function get_related_post_item_data( $post, $relationship ) {

		if ( is_numeric( $post ) ) {
			$post = get_post( $post );
		}

		$item_data = array(
			'ID'   => $post->ID, // Kept for backwards compatibility with filters that expected the legacy `ID` key.
			'id'   => $post->ID,
			'name' => $post->post_title,
			'type' => $post->post_type,
		);

		/**
		 * Filters the final post item data.
		 *
		 * @since 1.3.0
		 *
		 * @param array                                            $item_data    The post item data.
		 * @param \TenUp\ContentConnect\Relationships\Relationship $relationship The relationship object.
		 */
		$item_data = apply_filters( 'tenup_content_connect_final_post', $item_data, $relationship );

		/**
		 * Filters the post item data.
		 *
		 * @since 2.0.0
		 *
		 * @param array                                            $item_data    The post item data.
		 * @param \WP_Post                                         $post         The post object.
		 * @param \TenUp\ContentConnect\Relationships\Relationship $relationship The relationship object.
		 */
		$item_data = apply_filters( 'tenup_content_connect_post_item_data', $item_data, $post, $relationship );

		if ( empty( $item_data['type'] ) ) { // Required for the 10up Content Picker component.
			$item_data['type'] = $post->post_type;
		}

		if ( empty( $item_data['uuid'] ) ) { // Required for the 10up Content Picker component.
			$item_data['uuid'] = wp_generate_uuid4();
		}

		return $item_data;
	}
endif;

if ( ! function_exists( __NAMESPACE__ . '\\get_related_user_item_data' ) ) :
	/**
	 * Prepares the item data for a related user.
	 *
	 * Produces the canonical item shape shared by the editor UIs and the REST API,
	 * and applies the `tenup_content_connect_final_user` and
	 * `tenup_content_connect_user_item_data` filters.
	 *
	 * @since 2.0.0
	 *
	 * @param  int|\WP_User                                     $user         User object or ID.
	 * @param  \TenUp\ContentConnect\Relationships\Relationship $relationship The relationship object.
	 * @return array The prepared user item data.
	 */
	function get_related_user_item_data( $user, $relationship ) {

		if ( is_numeric( $user ) ) {
			$user = get_user_by( 'ID', $user );
		}

		$item_name = $user->display_name;

		if ( empty( $item_name ) ) {
			$item_name = array( $user->first_name, $user->last_name );
			$item_name = array_filter( $item_name );
			$item_name = implode( ' ', $item_name );
		}

		$item_data = array(
			'ID'   => $user->ID, // Kept for backwards compatibility with filters that expected the legacy `ID` key.
			'id'   => $user->ID,
			'name' => $item_name,
			'type' => 'user',
		);

		/**
		 * Filters the final user item data.
		 *
		 * @since 1.3.0
		 *
		 * @param array                                            $item_data    The user item data.
		 * @param \TenUp\ContentConnect\Relationships\Relationship $relationship The relationship object.
		 */
		$item_data = apply_filters( 'tenup_content_connect_final_user', $item_data, $relationship );

		/**
		 * Filters the user item data.
		 *
		 * @since 2.0.0
		 *
		 * @param array                                            $item_data    The user item data.
		 * @param \WP_User                                         $user         The user object.
		 * @param \TenUp\ContentConnect\Relationships\Relationship $relationship The relationship object.
		 */
		$item_data = apply_filters( 'tenup_content_connect_user_item_data', $item_data, $user, $relationship );

		if ( empty( $item_data['type'] ) ) { // Required for the 10up Content Picker component.
			$item_data['type'] = 'user';
		}

		if ( empty( $item_data['uuid'] ) ) { // Required for the 10up Content Picker component.
			$item_data['uuid'] = wp_generate_uuid4();
		}

		return $item_data;
	}
endif;

if ( ! function_exists( __NAMESPACE__ . '\\get_post_relationships_data' ) ) :
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
	 *                                       If 'any', returns both post-to-post and post-to-user relationships, unless
	 *                                       $other_post_type is set (see below).
	 * @param  string|false $other_post_type Optional. The post type to filter post-to-post relationships by.
	 *                                       When set together with $rel_type='any', post-to-user relationships are
	 *                                       excluded from the result, since they cannot be filtered by a related post type.
	 *                                       Default false (returns all relationships).
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
	 *                                          - 'post_type' (string[]): The related post types (only for post-to-post).
	 *                                          - 'labels' (array): UI labels associated with the relationship.
	 *                                          - 'sortable' (bool): Whether the relationship supports sorting.
	 *                                          - 'related' (array): The actual related posts/users (only when context='embed').
	 */
	function get_post_relationships_data( $post, $rel_type = 'any', $other_post_type = false, $context = 'view' ) {

		$post = get_post( $post );

		if ( ! $post ) {
			return array();
		}

		switch ( $rel_type ) {
			case 'post-to-user':
				$relationship_data = get_post_to_user_relationships_data( $post, $context );
				break;
			case 'post-to-post':
				$relationship_data = get_post_to_post_relationships_data( $post, $other_post_type, $context );
				break;
			case 'any':
				if ( ! empty( $other_post_type ) ) {
					$relationship_data = get_post_to_post_relationships_data( $post, $other_post_type, $context );
				} else {
					$relationship_data = array_merge(
						get_post_to_post_relationships_data( $post, $other_post_type, $context ),
						get_post_to_user_relationships_data( $post, $context )
					);
				}
				break;
			default:
				$relationship_data = array();
				break;
		}

		/**
		 * Filters the relationship data assembled for a post.
		 *
		 * @since 1.0.0
		 * @since 2.0.0 $rel_type, $other_post_type, and $context arguments were added.
		 *
		 * @param array        $relationship_data The assembled relationship data, keyed by relationship key.
		 * @param \WP_Post     $post              The post object.
		 * @param string       $rel_type          The relationship type: 'any', 'post-to-post', or 'post-to-user'.
		 * @param string|false $other_post_type   Post type used to filter post-to-post relationships, or false.
		 * @param string       $context           The response context: 'view' or 'embed'.
		 */
		return apply_filters( 'tenup_content_connect_post_relationship_data', $relationship_data, $post, $rel_type, $other_post_type, $context );
	}
endif;

if ( ! function_exists( __NAMESPACE__ . '\\get_post_to_post_relationships_data' ) ) :
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
	 *                                          - 'post_type' (string[]): The related post types (only for post-to-post).
	 *                                          - 'labels' (array): UI labels associated with the relationship.
	 *                                          - 'enable_ui' (bool): Whether the editing UI is enabled for this relationship.
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

			$relationship_to = is_array( $relationship->to ) ? $relationship->to : array( $relationship->to );

			if ( ! empty( $other_post_type ) && ! in_array( $other_post_type, $relationship_to, true ) && $relationship->from !== $other_post_type ) {
				continue;
			}

			$relationship_data = array(
				'rel_key'     => $rel_key,
				'rel_type'    => 'post-to-post',
				'rel_name'    => $relationship->name,
				'object_type' => 'post',
			);

			if ( $post->post_type === $relationship->from ) {
				$relationship_data['labels']    = $relationship->from_labels;
				$relationship_data['enable_ui'] = $relationship->enable_from_ui;
				$relationship_data['sortable']  = $relationship->from_sortable;
				$relationship_data['max_items'] = $relationship->from_max_items;
				$relationship_data['post_type'] = $relationship_to;
			} else {
				$relationship_data['labels']    = $relationship->to_labels;
				$relationship_data['enable_ui'] = $relationship->enable_to_ui;
				$relationship_data['sortable']  = $relationship->to_sortable;
				$relationship_data['max_items'] = $relationship->to_max_items;
				$relationship_data['post_type'] = array( $relationship->from );
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
				$posts_per_page = (int) apply_filters( 'tenup_content_connect_posts_per_page', 100, $rel_key, $post->ID );

				$query_args = array(
					'post_type'              => $relationship_data['post_type'],
					'posts_per_page'         => $posts_per_page,
					'relationship_query'     => array(
						'name'            => $relationship->name,
						'related_to_post' => $post->ID,
					),
					'update_post_meta_cache' => false,
					'update_post_term_cache' => false,
					'no_found_rows'          => true,
				);

				if ( ! empty( $relationship_data['sortable'] ) ) {
					$query_args['orderby'] = 'relationship';
				}

				/**
				 * Filters the Post UI query args.
				 *
				 * @since 1.6.0
				 *
				 * @param array    $query_args The \WP_Query args.
				 * @param \WP_Post $post       The post object.
				 */
				$query_args = apply_filters( 'tenup_content_connect_post_ui_query_args', $query_args, $post );

				$query = new \WP_Query( $query_args );

				$queried_posts = $query->get_posts();

				$related_posts = array();
				foreach ( $queried_posts as $queried_post ) {
					$related_posts[] = get_related_post_item_data( $queried_post, $relationship );
				}

				$relationship_data['related'] = $related_posts;
			}

			$relationships_data[ $rel_key ] = $relationship_data;
		}

		return $relationships_data;
	}
endif;

if ( ! function_exists( __NAMESPACE__ . '\\get_post_to_user_relationships_data' ) ) :
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
	 *                                          - 'post_type' (string[]): The related post types (only for post-to-post).
	 *                                          - 'labels' (array): UI labels associated with the relationship.
	 *                                          - 'enable_ui' (bool): Whether the editing UI is enabled for this relationship.
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

				/**
				 * Filters the default users per page limit for relationship queries.
				 *
				 * @since 2.0.0
				 *
				 * @param int    $users_per_page Default number of users to retrieve. Default 100.
				 * @param string $rel_key        The relationship key.
				 * @param int    $post_id        The post ID being queried.
				 */
				$users_per_page = (int) apply_filters( 'tenup_content_connect_users_per_page', 100, $rel_key, $post->ID );

				$query_args = array(
					'relationship_query' => array(
						'name'            => $relationship->name,
						'related_to_post' => $post->ID,
					),
					'number'             => $users_per_page,
					'count_total'        => false,
				);

				if ( $relationship->from_sortable ) {
					$query_args['orderby'] = 'relationship';
				}

				/**
				 * Filters the Post UI user query args.
				 *
				 * @since 1.6.0
				 *
				 * @param array    $query_args The \WP_User_Query args.
				 * @param \WP_Post $post       The post object.
				 */
				$query_args = apply_filters( 'tenup_content_connect_post_ui_user_query_args', $query_args, $post );

				$query = new \WP_User_Query( $query_args );

				$queried_users = $query->get_results();

				$related_users = array();
				foreach ( $queried_users as $queried_user ) {
					$related_users[] = get_related_user_item_data( $queried_user, $relationship );
				}

				$relationship_data['related'] = $related_users;
			}

			$relationships_data[ $rel_key ] = $relationship_data;
		}

		return $relationships_data;
	}
endif;
