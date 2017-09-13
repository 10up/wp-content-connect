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

		$sql = "CREATE TABLE `{$table_name}` ( `id1` bigint(20) unsigned NOT NULL, `id2` bigint(20) unsigned NOT NULL, PRIMARY KEY (`id1`,`id2`) );";

		return $sql;
	}

}
