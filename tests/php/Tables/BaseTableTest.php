<?php

namespace TenUp\ContentConnect\Tests\Tables;

use TenUp\ContentConnect\Tables\BaseTable;
use TenUp\ContentConnect\Tables\PostToPost;

/**
 * Covers the schema versioning / upgrade machinery in BaseTable, plus the
 * bulk-insert SQL builders. Uses an isolated throwaway table so the plugin's
 * real tables and schema-version options are never disturbed.
 */
class BaseTableTest extends \PHPUnit\Framework\TestCase {

	/**
	 * @var BaseTable
	 */
	protected $table;

	public function setUp(): void {
		$this->table = $this->make_table( '0.1.0' );

		parent::setUp();
	}

	public function tearDown(): void {
		global $wpdb;

		$table_name = $this->table->get_table_name();
		$wpdb->query( "DROP TABLE IF EXISTS `{$table_name}`" );
		delete_option( $this->table->get_schema_option_name() );

		parent::tearDown();
	}

	/**
	 * Builds a concrete BaseTable with a unique, throwaway table name.
	 *
	 * @param string $version Schema version the table reports.
	 * @return BaseTable
	 */
	protected function make_table( $version ) {
		return new class( $version ) extends BaseTable {

			public $version;

			public function __construct( $version ) {
				$this->version = $version;
			}

			function get_schema_version() {
				return $this->version;
			}

			function get_table_name() {
				return $this->generate_table_name( 'cc_basetable_test' );
			}

			function get_schema() {
				$table_name = $this->get_table_name();

				return "CREATE TABLE `{$table_name}` (
					`id` bigint(20) unsigned NOT NULL,
					PRIMARY KEY (`id`)
				);";
			}
		};
	}

	public function test_should_upgrade_false_when_versions_match() {
		update_option( $this->table->get_schema_option_name(), '0.1.0' );

		$this->assertFalse( $this->table->should_upgrade() );
	}

	public function test_should_upgrade_true_when_installed_version_older() {
		update_option( $this->table->get_schema_option_name(), '0.0.1' );

		$this->assertTrue( $this->table->should_upgrade() );
	}

	public function test_should_upgrade_true_when_no_version_installed() {
		delete_option( $this->table->get_schema_option_name() );

		$this->assertTrue( $this->table->should_upgrade() );
	}

	public function test_upgrade_fresh_creates_table_and_stores_version() {
		global $wpdb;

		$table_name = $this->table->get_table_name();
		$wpdb->query( "DROP TABLE IF EXISTS `{$table_name}`" );
		delete_option( $this->table->get_schema_option_name() );

		$this->assertTrue( $this->table->upgrade( true ) );

		$exists = $wpdb->query( "SHOW TABLES LIKE '{$table_name}'" );
		$this->assertEquals( 1, $exists );
		$this->assertEquals( '0.1.0', get_option( $this->table->get_schema_option_name() ) );
	}

	public function test_upgrade_returns_false_when_up_to_date() {
		$this->table->upgrade( true );

		// Already at 0.1.0; a non-fresh upgrade should be a no-op.
		$this->assertFalse( $this->table->upgrade() );
	}

	public function test_version_bump_triggers_upgrade_and_updates_option() {
		// Install at 0.1.0.
		$this->table->upgrade( true );
		$this->assertEquals( '0.1.0', get_option( $this->table->get_schema_option_name() ) );

		// A newer table version (same table name) should upgrade.
		$bumped = $this->make_table( '0.2.0' );
		$this->assertTrue( $bumped->should_upgrade() );
		$this->assertTrue( $bumped->upgrade() );
		$this->assertEquals( '0.2.0', get_option( $bumped->get_schema_option_name() ) );
	}

	public function test_get_column_names_query_builds_backticked_list() {
		$table   = new PostToPost();
		$columns = array( 'id1' => '%d', 'id2' => '%d', 'name' => '%s' );
		$rows    = array( array( 'id1' => 1, 'id2' => 2, 'name' => 'basic' ) );

		$this->assertSame( '( `id1`,`id2`,`name` )', $table->get_column_names_query( $columns, $rows ) );
	}

	public function test_get_column_names_query_drops_columns_absent_from_rows() {
		$table   = new PostToPost();
		$columns = array( 'id1' => '%d', 'id2' => '%d', 'order' => '%d' );
		$rows    = array( array( 'id1' => 1, 'id2' => 2 ) ); // no "order"

		$this->assertSame( '( `id1`,`id2` )', $table->get_column_names_query( $columns, $rows ) );
		// The absent column is also removed from the referenced $columns array.
		$this->assertArrayNotHasKey( 'order', $columns );
	}

	public function test_get_column_updates_query_builds_on_duplicate_clause() {
		$table   = new PostToPost();
		$columns = array( 'id1' => '%d', 'name' => '%s' );

		$this->assertSame(
			'`id1` = VALUES(`id1`),`name` = VALUES(`name`)',
			$table->get_column_updates_query( $columns )
		);
	}

	public function test_get_values_query_escapes_and_formats_rows() {
		$table   = new PostToPost();
		$columns = array( 'id1' => '%d', 'name' => '%s' );
		$rows    = array(
			array( 1, 'basic' ),
			array( 2, "o'brien" ),
		);

		$values = $table->get_values_query( $columns, $rows );

		$this->assertStringContainsString( "('1', 'basic')", $values );
		// esc_sql escapes the single quote.
		$this->assertStringContainsString( "o\\'brien", $values );
	}

}
