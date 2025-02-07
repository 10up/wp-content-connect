<?php

namespace TenUp\ContentConnect\Helpers;

use TenUp\ContentConnect\Plugin;

/**
 * Returns the instance of the relationship registry
 *
 * @return \TenUp\ContentConnect\Registry
 */
function get_registry() {
	return Plugin::instance()->registry;
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

	$table = Plugin::instance()->get_table( 'p2p' );

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
