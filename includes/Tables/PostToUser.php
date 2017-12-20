<?php

namespace TenUp\ContentConnect\Tables;

class PostToUser extends BaseTable {

	function get_schema_version() {
		return '0.1.10';
	}

	function get_table_name() {
		return $this->generate_table_name( 'post_to_user');
	}

	/**
	 * Defines the schema for the post to user table
	 *
	 * Indexes:
	 *  post_user_name - Used to ensure no duplicates are created
	 *  user_name - Used on WP_Query "related_to_user" and get_related_post_ids() WITHOUT orderby relationship
	 *  user_name_order - Used on WP_Query "related_to_user" and get_related_post_ids() WITH orderby relationship
	 *  post_name - Used on get_related_user_ids() WITHOUT orderby relationship
	 *  post_name_order - User on get_related_user_ids() WITH orderby relationship
	 *
	 * @return string
	 */
	function get_schema() {
		$table_name = $this->get_table_name();

		$sql = "CREATE TABLE `{$table_name}` (
			post_id bigint(20) unsigned NOT NULL,
			user_id bigint(20) unsigned NOT NULL,
			name varchar(64) NOT NULL,
			user_order int(11) NOT NULL default 0,
			post_order int(11) NOT NULL default 0,
			UNIQUE KEY post_user_name (`post_id`,`user_id`,`name`),
			KEY user_name (`user_id`,`name`),
			KEY user_name_order (`user_id`,`name`,`post_order`),
			KEY post_name (`post_id`,`name`),
			KEY post_name_order (`post_id`,`name`,`user_order`)
		);";

		return $sql;
	}

}
