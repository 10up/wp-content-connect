<?php

namespace TenUp\P2P\Tables;

class PostToPost extends BaseTable {

	function get_schema_version() {
		return '0.1.0';
	}

	function get_table_name() {
		return $this->generate_table_name( 'post_to_post');
	}

	function get_schema() {
		$table_name = $this->get_table_name();

		$sql = "CREATE TABLE `{$table_name}` ( `from` bigint(20) unsigned NOT NULL, `to` bigint(20) unsigned NOT NULL, PRIMARY KEY (`from`,`to`) );";

		return $sql;
	}

}
