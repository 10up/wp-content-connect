<?php

namespace TenUp\ContentConnect\Tables;

class PostToUser extends BaseTable {

	function get_schema_version() {
		return '0.1.0';
	}

	function get_table_name() {
		return $this->generate_table_name( 'post_to_user');
	}

	function get_schema() {
		$table_name = $this->get_table_name();

		$sql = "CREATE TABLE `{$table_name}` ( 
			`post_id` bigint(20) unsigned NOT NULL, 
			`user_id` bigint(20) unsigned NOT NULL, 
			`type` varchar(20) NOT NULL, 
			PRIMARY KEY (`post_id`,`user_id`,`type`) 
		);";

		return $sql;
	}

}
