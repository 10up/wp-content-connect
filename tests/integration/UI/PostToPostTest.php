<?php

namespace TenUp\ContentConnect\Tests\Integration\UI;

use TenUp\ContentConnect\Plugin;
use TenUp\ContentConnect\Registry;
use TenUp\ContentConnect\Tests\Integration\ContentConnectTestCase;

/**
 * Covers the post-to-post editor UI class: filter_data() payload building
 * (including sortable ordering) and handle_save() persistence.
 */
class PostToPostTest extends ContentConnectTestCase {

	public function setUp(): void {
		global $wpdb;

		$wpdb->query( "delete from {$wpdb->prefix}post_to_post" );

		$plugin           = Plugin::instance();
		$plugin->registry = new Registry();
		$plugin->registry->setup();

		parent::setUp();
	}

	/**
	 * @param array $args Relationship args (e.g. sortable).
	 * @return \TenUp\ContentConnect\Relationships\PostToPost
	 */
	protected function define( $args = array() ) {
		$registry = Plugin::instance()->get_registry();

		return $registry->define_post_to_post( 'post', 'car', 'basic', $args );
	}

	public function test_filter_data_ignores_non_matching_post_type() {
		$rel = $this->define();
		$ui  = $rel->from_ui;

		// from_ui renders on "post"; a car post must be ignored.
		$car    = get_post( 11 );
		$result = $ui->filter_data( array( 'existing' ), $car );

		$this->assertSame( array( 'existing' ), $result );
	}

	public function test_filter_data_appends_expected_payload() {
		$rel = $this->define();
		$ui  = $rel->from_ui;

		$rel->add_relationship( 1, 11 );
		$rel->add_relationship( 1, 12 );

		$data    = $ui->filter_data( array(), get_post( 1 ) );
		$payload = $data[0];

		$this->assertSame( 'post-to-post', $payload['reltype'] );
		$this->assertSame( 'post', $payload['object_type'] );
		$this->assertSame( 'basic', $payload['name'] );
		$this->assertSame( 1, $payload['current_post_id'] );
		$this->assertFalse( $payload['sortable'] );

		$registry = Plugin::instance()->get_registry();
		$this->assertSame( $registry->get_relationship_key( 'post', 'car', 'basic' ), $payload['relid'] );

		$selected_ids = wp_list_pluck( $payload['selected'], 'ID' );
		sort( $selected_ids );
		$this->assertEquals( array( 11, 12 ), array_map( 'intval', $selected_ids ) );
		$this->assertArrayHasKey( 'name', $payload['selected'][0] );
	}

	public function test_filter_data_sortable_orders_by_saved_sort() {
		$rel = $this->define( array( 'from' => array( 'sortable' => true ) ) );
		$ui  = $rel->from_ui;

		$this->assertTrue( $ui->sortable );

		// Save an explicit, non-natural order.
		$rel->replace_relationships( 1, array( 13, 11, 12 ) );
		$rel->save_sort_data( 1, array( 13, 11, 12 ) );

		$data         = $ui->filter_data( array(), get_post( 1 ) );
		$selected_ids = array_map( 'intval', wp_list_pluck( $data[0]['selected'], 'ID' ) );

		$this->assertEquals( array( 13, 11, 12 ), $selected_ids );
	}

	public function test_filter_data_applies_query_args_filter() {
		$rel = $this->define();
		$ui  = $rel->from_ui;

		$ran = false;
		add_filter(
			'tenup_content_connect_post_ui_query_args',
			function ( $args ) use ( &$ran ) {
				$ran = true;
				return $args;
			}
		);

		$ui->filter_data( array(), get_post( 1 ) );

		remove_all_filters( 'tenup_content_connect_post_ui_query_args' );

		$this->assertTrue( $ran );
	}

	public function test_handle_save_replaces_relationships() {
		$rel = $this->define();
		$ui  = $rel->from_ui;

		$rel->add_relationship( 1, 13 );
		$rel->add_relationship( 1, 14 );

		$ui->handle_save( array( 'add_items' => array( 11, 12 ) ), 1 );

		$related = array_map( 'intval', $rel->get_related_object_ids( 1 ) );
		sort( $related );
		$this->assertEquals( array( 11, 12 ), $related );
	}

	public function test_handle_save_empty_clears_relationships() {
		$rel = $this->define();
		$ui  = $rel->from_ui;

		$rel->add_relationship( 1, 11 );

		$ui->handle_save( array( 'add_items' => array() ), 1 );

		$this->assertEmpty( $rel->get_related_object_ids( 1 ) );
	}

	public function test_handle_save_persists_sort_order_when_sortable() {
		$rel = $this->define( array( 'from' => array( 'sortable' => true ) ) );
		$ui  = $rel->from_ui;

		$ui->handle_save( array( 'add_items' => array( 13, 11, 12 ) ), 1 );

		$data         = $ui->filter_data( array(), get_post( 1 ) );
		$selected_ids = array_map( 'intval', wp_list_pluck( $data[0]['selected'], 'ID' ) );

		$this->assertEquals( array( 13, 11, 12 ), $selected_ids );
	}

}
