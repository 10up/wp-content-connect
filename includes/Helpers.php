<?php

namespace TenUp\ContentConnect\Helpers;

use TenUp\ContentConnect\Plugin;

/**
 * Returns the instance of the plugin.
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
 * Gets all post to post relationships by a given field.
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

	$post_to_post_relationships = get_registry()->get_post_to_post_relationships();

	if ( empty( $post_to_post_relationships ) ) {
		return array();
	}

	if ( 'key' === $field ) {
		return get_registry()->get_post_to_post_relationship_by_key( $value );
	}

	$relationships = array();

	foreach ( $post_to_post_relationships as $key => $relationship ) {

		switch ( $field ) {
			case 'post_type':
				if ( $relationship->from === $value || $relationship->to === $value ) {
					$relationships[ $key ] = $relationship;
				}
				break;
			case 'from':
				if ( $relationship->from === $value ) {
					$relationships[ $key ] = $relationship;
				}
				break;
			case 'to':
				if ( $relationship->to === $value ) {
					$relationships[ $key ] = $relationship;
				}
				break;
		}
	}

	return $relationships;
}

/**
 * Gets all post to users relationships by a given field.
 *
 * @param  string $field The field to query against. Accepts 'key', or 'post_type'.
 * 'key' will return a single relationship by key.
 * 'post_type' will return all relationships for a given post type.
 * @param  string $value The field value.
 * @return array
 */
function get_post_to_user_relationships_by( $field, $value ) {

	$post_to_user_relationships = get_registry()->get_post_to_user_relationships();

	if ( empty( $post_to_user_relationships ) ) {
		return array();
	}

	if ( 'key' === $field ) {
		return get_registry()->get_post_to_user_relationship_by_key( $value );
	}

	$relationships = array();

	foreach ( $post_to_user_relationships as $key => $relationship ) {

		switch ( $field ) {
			case 'post_type':
				if ( $relationship->post_type === $value ) {
					$relationships[ $key ] = $relationship;
				}
				break;
		}
	}

	return $relationships;
}
