<?php

namespace TenUp\ContentConnect\Tests\Integration\UI;

use TenUp\ContentConnect\Plugin;
use TenUp\ContentConnect\Registry;
use TenUp\ContentConnect\Tests\Integration\ContentConnectTestCase;

/**
 * Covers the post-to-user editor UI class: filter_data() payload building
 * (including sortable ordering) and handle_save() persistence.
 */
class PostToUserTest extends ContentConnectTestCase {

	public function setUp(): void {
		global $wpdb;

		$wpdb->query( "delete from {$wpdb->prefix}post_to_user" );

		$plugin           = Plugin::instance();
		$plugin->registry = new Registry();
		$plugin->registry->setup();

		parent::setUp();
	}

	/**
	 * @param array $args Relationship args (e.g. sortable).
	 * @return \TenUp\ContentConnect\Relationships\PostToUser
	 */
	protected function define( $args = array() ) {
		$registry = Plugin::instance()->get_registry();

		return $registry->define_post_to_user( 'post', 'owner', $args );
	}

	public function test_filter_data_ignores_non_matching_post_type() {
		$rel = $this->define();
		$ui  = $rel->from_ui;

		// Renders on "post"; a car post must be ignored.
		$result = $ui->filter_data( array( 'existing' ), get_post( 11 ) );

		$this->assertSame( array( 'existing' ), $result );
	}

	public function test_filter_data_appends_expected_payload() {
		$rel = $this->define();
		$ui  = $rel->from_ui;

		$rel->add_relationship( 1, 2 );
		$rel->add_relationship( 1, 3 );

		$data    = $ui->filter_data( array(), get_post( 1 ) );
		$payload = $data[0];

		$this->assertSame( 'post-to-user', $payload['reltype'] );
		$this->assertSame( 'user', $payload['object_type'] );
		$this->assertSame( 'owner', $payload['name'] );
		$this->assertSame( 1, $payload['current_post_id'] );
		$this->assertFalse( $payload['sortable'] );

		$registry = Plugin::instance()->get_registry();
		$this->assertSame( $registry->get_relationship_key( 'post', 'user', 'owner' ), $payload['relid'] );

		$selected_ids = array_map( 'intval', wp_list_pluck( $payload['selected'], 'ID' ) );
		sort( $selected_ids );
		$this->assertEquals( array( 2, 3 ), $selected_ids );
		$this->assertArrayHasKey( 'name', $payload['selected'][0] );
	}

	public function test_filter_data_sortable_orders_by_saved_sort() {
		$rel = $this->define( array( 'from' => array( 'sortable' => true ) ) );
		$ui  = $rel->from_ui;

		$this->assertTrue( $ui->sortable );

		$rel->replace_post_to_user_relationships( 1, array( 4, 2, 3 ) );
		$rel->save_post_to_user_sort_data( 1, array( 4, 2, 3 ) );

		$data         = $ui->filter_data( array(), get_post( 1 ) );
		$selected_ids = array_map( 'intval', wp_list_pluck( $data[0]['selected'], 'ID' ) );

		$this->assertEquals( array( 4, 2, 3 ), $selected_ids );
	}

	public function test_filter_data_applies_query_args_filter() {
		$rel = $this->define();
		$ui  = $rel->from_ui;

		$ran = false;
		add_filter(
			'tenup_content_connect_post_ui_user_query_args',
			function ( $args ) use ( &$ran ) {
				$ran = true;
				return $args;
			}
		);

		$ui->filter_data( array(), get_post( 1 ) );

		remove_all_filters( 'tenup_content_connect_post_ui_user_query_args' );

		$this->assertTrue( $ran );
	}

	public function test_handle_save_replaces_relationships() {
		$rel = $this->define();
		$ui  = $rel->from_ui;

		$rel->add_relationship( 1, 4 );
		$rel->add_relationship( 1, 5 );

		$ui->handle_save( array( 'add_items' => array( 2, 3 ) ), 1 );

		$related = array_map( 'intval', $rel->get_related_user_ids( 1 ) );
		sort( $related );
		$this->assertEquals( array( 2, 3 ), $related );
	}

	public function test_handle_save_empty_clears_relationships() {
		$rel = $this->define();
		$ui  = $rel->from_ui;

		$rel->add_relationship( 1, 2 );

		$ui->handle_save( array( 'add_items' => array() ), 1 );

		$this->assertEmpty( $rel->get_related_user_ids( 1 ) );
	}

	public function test_handle_save_persists_sort_order_when_sortable() {
		$rel = $this->define( array( 'from' => array( 'sortable' => true ) ) );
		$ui  = $rel->from_ui;

		$ui->handle_save( array( 'add_items' => array( 4, 2, 3 ) ), 1 );

		$data         = $ui->filter_data( array(), get_post( 1 ) );
		$selected_ids = array_map( 'intval', wp_list_pluck( $data[0]['selected'], 'ID' ) );

		$this->assertEquals( array( 4, 2, 3 ), $selected_ids );
	}

}
