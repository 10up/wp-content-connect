<?php

namespace TenUp\P2P\Tables;

abstract class BaseTable {

	public $columns          = array();
	public $keys             = array();
	public $primary_key_name = null;
	public $unique_key_name  = null;
	public $did_schema       = false;
	public $bulk_updater     = null;

	public $inserted = 0;
	public $updated  = 0;
	public $deleted  = 0;

	public function setup() {
		add_action( 'admin_init', [ $this, 'upgrade' ] );
	}

	/**
	 * @return string Version string for table x.x.x
	 */
	abstract function get_schema_version();

	/**
	 * @return string SQL statement to create the table
	 */
	abstract function get_schema();

	/**
	 * @return string table name of the table we're creating
	 */
	abstract function get_table_name();

	function generate_table_name( $table_name ) {
		$db = $this->get_db();
		$prefix = $db->prefix;

		return $prefix . $table_name;
	}

	function get_installed_schema_version() {
		return get_option( $this->get_schema_option_name() );
	}

	function get_schema_option_name() {
		return $this->get_table_name() . '_schema_version';
	}

	function should_upgrade() {
		return version_compare(
			$this->get_schema_version(),
			$this->get_installed_schema_version(),
			'>'
		);
	}

	function upgrade( $fresh = false ) {
		if ( $this->should_upgrade() || $fresh ) {
			$sql = $this->get_schema();

			require_once( ABSPATH . '/wp-admin/includes/upgrade.php' );
			dbDelta( $sql );

			update_option(
				$this->get_schema_option_name(),
				$this->get_schema_version(),
				"no"
			);

			return true;
		} else {
			return false;
		}
	}

	public function get_db() {
		global $wpdb;

		return $wpdb;
	}


	/*
	 * Database Methods
	 */

	public function replace( $data, $format = array() ) {
		$db = $this->get_db();

		$db->replace( $this->get_table_name(), $data, $format );
	}

	public function delete( $where, $where_format = null ) {
		$db = $this->get_db();

		$db->delete( $this->get_table_name(), $where, $where_format );
	}

}

