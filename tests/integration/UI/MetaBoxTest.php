<?php

namespace TenUp\ContentConnect\Tests\Integration\UI;

use TenUp\ContentConnect\Plugin;
use TenUp\ContentConnect\Registry;
use TenUp\ContentConnect\Tests\Integration\ContentConnectTestCase;
use TenUp\ContentConnect\UI\MetaBox;

/**
 * Covers the primary write path: MetaBox::save_post().
 *
 * Exercises the nonce/capability guards, malformed-input handling, and the
 * from_ui/to_ui save dispatch that persists relationships from the editor.
 */
class MetaBoxTest extends ContentConnectTestCase {

	protected $admin_id = 0;

	public function setUp(): void {
		global $wpdb;

		$wpdb->query( "delete from {$wpdb->prefix}post_to_post" );
		$wpdb->query( "delete from {$wpdb->prefix}post_to_user" );

		$plugin           = Plugin::instance();
		$plugin->registry = new Registry();
		$plugin->registry->setup();

		parent::setUp();
	}

	public function tearDown(): void {
		unset(
			$_POST['tenup-content-connect-save'],
			$_POST['tenup-content-connect-relationships']
		);

		if ( $this->admin_id ) {
			wp_delete_user( $this->admin_id );
			$this->admin_id = 0;
		}

		wp_set_current_user( 0 );

		parent::tearDown();
	}

	/**
	 * Defines a UI-enabled post-to-car relationship in the registry and returns its key.
	 */
	protected function define_post_to_car() {
		$registry = Plugin::instance()->get_registry();
		$registry->define_post_to_post( 'post', 'car', 'basic' );

		return $registry->get_relationship_key( 'post', 'car', 'basic' );
	}

	/**
	 * Defines a UI-enabled post-to-user relationship in the registry and returns its key.
	 */
	protected function define_post_to_user() {
		$registry = Plugin::instance()->get_registry();
		$registry->define_post_to_user( 'post', 'owner' );

		return $registry->get_relationship_key( 'post', 'user', 'owner' );
	}

	/**
	 * Logs in an administrator (created on the fly) and returns a valid save nonce.
	 */
	protected function login_admin_with_nonce() {
		$this->admin_id = wp_insert_user(
			array(
				'user_login' => 'cc_metabox_admin',
				'user_pass'  => 'password',
				'role'       => 'administrator',
			)
		);

		wp_set_current_user( $this->admin_id );

		return wp_create_nonce( 'content-connect-save' );
	}

	protected function related_car_ids( $post_id ) {
		$registry     = Plugin::instance()->get_registry();
		$relationship = $registry->get_post_to_post_relationship( 'post', 'car', 'basic' );

		return $relationship->get_related_object_ids( $post_id );
	}

	public function test_missing_nonce_returns_false_and_writes_nothing() {
		$this->define_post_to_car();

		$metabox = new MetaBox();
		$this->assertFalse( $metabox->save_post( 1 ) );
		$this->assertEmpty( $this->related_car_ids( 1 ) );
	}

	public function test_invalid_nonce_returns_false() {
		$this->define_post_to_car();

		$_POST['tenup-content-connect-save'] = 'not-a-valid-nonce';

		$metabox = new MetaBox();
		$this->assertFalse( $metabox->save_post( 1 ) );
		$this->assertEmpty( $this->related_car_ids( 1 ) );
	}

	public function test_user_without_edit_capability_returns_false() {
		$relid = $this->define_post_to_car();

		// A subscriber lacks edit_post. Nonce must be valid for this user.
		$this->admin_id = wp_insert_user(
			array(
				'user_login' => 'cc_metabox_subscriber',
				'user_pass'  => 'password',
				'role'       => 'subscriber',
			)
		);
		wp_set_current_user( $this->admin_id );
		$_POST['tenup-content-connect-save']          = wp_create_nonce( 'content-connect-save' );
		$_POST['tenup-content-connect-relationships'] = wp_slash( wp_json_encode(
			array(
				array( 'reltype' => 'post-to-post', 'relid' => $relid, 'add_items' => array( 11 ) ),
			)
		) );

		$metabox = new MetaBox();
		$this->assertFalse( $metabox->save_post( 1 ) );
		$this->assertEmpty( $this->related_car_ids( 1 ) );
	}

	public function test_missing_relationships_field_returns_false() {
		$this->define_post_to_car();

		$_POST['tenup-content-connect-save'] = $this->login_admin_with_nonce();

		$metabox = new MetaBox();
		$this->assertFalse( $metabox->save_post( 1 ) );
		$this->assertEmpty( $this->related_car_ids( 1 ) );
	}

	public function test_malformed_json_returns_false_without_warning() {
		$this->define_post_to_car();

		$_POST['tenup-content-connect-save']          = $this->login_admin_with_nonce();
		$_POST['tenup-content-connect-relationships'] = 'this is not json';

		$metabox = new MetaBox();
		$this->assertFalse( $metabox->save_post( 1 ) );
		$this->assertEmpty( $this->related_car_ids( 1 ) );
	}

	public function test_unknown_reltype_and_relid_are_skipped() {
		$relid = $this->define_post_to_car();

		$_POST['tenup-content-connect-save']          = $this->login_admin_with_nonce();
		$_POST['tenup-content-connect-relationships'] = wp_slash( wp_json_encode(
			array(
				// Unknown reltype: skipped.
				array( 'reltype' => 'bogus-type', 'relid' => $relid, 'add_items' => array( 11 ) ),
				// Known reltype but unregistered relid: skipped.
				array( 'reltype' => 'post-to-post', 'relid' => 'nope_nope_nope', 'add_items' => array( 12 ) ),
			)
		) );

		$metabox = new MetaBox();
		// No fatal, no warning, nothing written.
		$metabox->save_post( 1 );
		$this->assertEmpty( $this->related_car_ids( 1 ) );
	}

	public function test_valid_post_to_post_payload_saves_relationships() {
		$relid = $this->define_post_to_car();

		$_POST['tenup-content-connect-save']          = $this->login_admin_with_nonce();
		$_POST['tenup-content-connect-relationships'] = wp_slash( wp_json_encode(
			array(
				array( 'reltype' => 'post-to-post', 'relid' => $relid, 'add_items' => array( 11, 12 ) ),
			)
		) );

		$metabox = new MetaBox();
		$metabox->save_post( 1 );

		$related = $this->related_car_ids( 1 );
		sort( $related );
		$this->assertEquals( array( 11, 12 ), array_map( 'intval', $related ) );
	}

	public function test_valid_payload_replaces_existing_relationships() {
		$relid        = $this->define_post_to_car();
		$registry     = Plugin::instance()->get_registry();
		$relationship = $registry->get_post_to_post_relationship( 'post', 'car', 'basic' );

		$relationship->add_relationship( 1, 13 );
		$relationship->add_relationship( 1, 14 );

		$_POST['tenup-content-connect-save']          = $this->login_admin_with_nonce();
		$_POST['tenup-content-connect-relationships'] = wp_slash( wp_json_encode(
			array(
				array( 'reltype' => 'post-to-post', 'relid' => $relid, 'add_items' => array( 11 ) ),
			)
		) );

		$metabox = new MetaBox();
		$metabox->save_post( 1 );

		$this->assertEquals( array( 11 ), array_map( 'intval', $this->related_car_ids( 1 ) ) );
	}

	public function test_empty_add_items_clears_relationships() {
		$relid        = $this->define_post_to_car();
		$registry     = Plugin::instance()->get_registry();
		$relationship = $registry->get_post_to_post_relationship( 'post', 'car', 'basic' );

		$relationship->add_relationship( 1, 11 );

		$_POST['tenup-content-connect-save']          = $this->login_admin_with_nonce();
		$_POST['tenup-content-connect-relationships'] = wp_slash( wp_json_encode(
			array(
				array( 'reltype' => 'post-to-post', 'relid' => $relid, 'add_items' => array() ),
			)
		) );

		$metabox = new MetaBox();
		$metabox->save_post( 1 );

		$this->assertEmpty( $this->related_car_ids( 1 ) );
	}

	public function test_valid_post_to_user_payload_saves_relationships() {
		$relid        = $this->define_post_to_user();
		$registry     = Plugin::instance()->get_registry();
		$relationship = $registry->get_post_to_user_relationship( 'post', 'owner' );

		$_POST['tenup-content-connect-save']          = $this->login_admin_with_nonce();
		$_POST['tenup-content-connect-relationships'] = wp_slash( wp_json_encode(
			array(
				array( 'reltype' => 'post-to-user', 'relid' => $relid, 'add_items' => array( 2, 3 ) ),
			)
		) );

		$metabox = new MetaBox();
		$metabox->save_post( 1 );

		$related = $relationship->get_related_user_ids( 1 );
		sort( $related );
		$this->assertEquals( array( 2, 3 ), array_map( 'intval', $related ) );
	}

}
