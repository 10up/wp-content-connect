<?php

namespace TenUp\ContentConnect\Tables;

class PostToUser extends BaseTable {

	function get_schema_version() {
		return '0.1.7';
	}

	function get_table_name() {
		return $this->generate_table_name( 'post_to_user');
	}

	function get_schema() {
		$table_name = $this->get_table_name();

		$sql = "CREATE TABLE `{$table_name}` ( 
			post_id bigint(20) unsigned NOT NULL, 
			user_id bigint(20) unsigned NOT NULL, 
			name varchar(20) NOT NULL,
			user_order int(11) NOT NULL default 0,
			post_order int(11) NOT NULL default 0,
			UNIQUE KEY post_user_name (`post_id`,`user_id`,`name`)
		);";

		return $sql;
	}

}
