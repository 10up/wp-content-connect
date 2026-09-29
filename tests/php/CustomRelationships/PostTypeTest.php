<?php
/**
 * Tests for the custom relationships post type and its REST endpoint.
 *
 * @package TenUp\ContentConnect\Tests\CustomRelationships
 */

namespace TenUp\ContentConnect\Tests\CustomRelationships;

use TenUp\ContentConnect\CustomRelationships\Loader;
use TenUp\ContentConnect\CustomRelationships\PostType;
use TenUp\ContentConnect\Tests\ContentConnectTestCase;
use function TenUp\ContentConnect\Helpers\get_plugin;
use function TenUp\ContentConnect\Helpers\get_registry;

/**
 * Test cases for TenUp\ContentConnect\CustomRelationships\PostType.
 */
class PostTypeTest extends ContentConnectTestCase {

	use CustomRelationshipTestTrait;

	/**
	 * Sets up the test environment.
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		$this->register_custom_relationship_post_type();
		wp_set_current_user( $this->factory()->user->create( array( 'role' => 'administrator' ) ) );
	}

	/**
	 * Tests that the post type is registered and exposed in REST.
	 *
	 * @return void
	 */
	public function test_post_type_is_registered() {
		$post_type = get_post_type_object( PostType::POST_TYPE );

		$this->assertNotNull( $post_type );
		$this->assertFalse( $post_type->public );
		$this->assertTrue( $post_type->show_in_rest );
		$this->assertSame( 'content-connect/v2', $post_type->rest_namespace );
		$this->assertSame( 'custom-relationships', $post_type->rest_base );
	}

	/**
	 * Tests that an administrator can create a custom relationship.
	 *
	 * @return void
	 */
	public function test_admin_can_create_custom_relationship() {
		$response = $this->create_via_rest( array( 'rel_name' => 'rest-create' ) );
		$data     = $response->get_data();

		$this->assertSame( 201, $response->get_status() );
		$this->assertSame( 'car_tire_rest-create', $data['rel_key'] );
		$this->assertSame( array( 'tire' ), $data['meta']['rel_to'] );
		$this->assertNull( $data['registration_error'] );
	}

	/**
	 * Tests that custom relationships cannot be read without the manage_options capability.
	 *
	 * @return void
	 */
	public function test_reading_requires_manage_options() {
		$this->create_custom_relationship();

		wp_set_current_user( 0 );
		$anonymous = rest_do_request( new \WP_REST_Request( 'GET', '/content-connect/v2/custom-relationships' ) );

		wp_set_current_user( $this->factory()->user->create( array( 'role' => 'editor' ) ) );
		$editor = rest_do_request( new \WP_REST_Request( 'GET', '/content-connect/v2/custom-relationships' ) );

		$this->assertSame( 401, $anonymous->get_status() );
		$this->assertSame( 403, $editor->get_status() );
	}

	/**
	 * Tests that editors cannot create custom relationships.
	 *
	 * @return void
	 */
	public function test_editor_cannot_create_custom_relationship() {
		wp_set_current_user( $this->factory()->user->create( array( 'role' => 'editor' ) ) );

		$response = $this->create_via_rest( array( 'rel_name' => 'rest-editor' ) );

		$this->assertSame( 403, $response->get_status() );
	}

	/**
	 * Tests that a key already registered from code is rejected.
	 *
	 * @return void
	 */
	public function test_rejects_key_registered_from_code() {
		get_registry()->define_post_to_post( 'car', 'tire', 'rest-code-taken' );

		$response = $this->create_via_rest( array( 'rel_name' => 'rest-code-taken' ) );

		$this->assertSame( 400, $response->get_status() );
		$this->assertSame( 'rest_relationship_exists', $response->get_data()['code'] );
	}

	/**
	 * Tests that the reverse of an existing single-type custom relationship is rejected.
	 *
	 * @return void
	 */
	public function test_rejects_reverse_of_existing_custom_relationship() {
		$this->create_custom_relationship( array( 'rel_name' => 'rest-reverse' ), array( 'post_status' => 'draft' ) );

		$response = $this->create_via_rest(
			array(
				'rel_from' => 'tire',
				'rel_to'   => array( 'car' ),
				'rel_name' => 'rest-reverse',
			)
		);

		$this->assertSame( 400, $response->get_status() );
		$this->assertSame( 'rest_relationship_exists', $response->get_data()['code'] );
	}

	/**
	 * Tests that unregistered post types are rejected.
	 *
	 * @return void
	 */
	public function test_rejects_unregistered_post_type() {
		$response = $this->create_via_rest(
			array(
				'rel_from' => 'not_registered',
				'rel_name' => 'rest-bad-type',
			)
		);

		$this->assertSame( 400, $response->get_status() );
		$this->assertSame( 'rest_relationship_invalid_post_type', $response->get_data()['code'] );
	}

	/**
	 * Tests that names outside the allowed characters are rejected.
	 *
	 * @return void
	 */
	public function test_rejects_invalid_name() {
		$response = $this->create_via_rest( array( 'rel_name' => 'Not Valid' ) );

		$this->assertSame( 400, $response->get_status() );
		$this->assertSame( 'rest_relationship_invalid_name', $response->get_data()['code'] );
	}

	/**
	 * Tests that post-to-post custom relationships need a related post type.
	 *
	 * @return void
	 */
	public function test_rejects_post_to_post_without_related_types() {
		$response = $this->create_via_rest(
			array(
				'rel_to'   => array(),
				'rel_name' => 'rest-no-to',
			)
		);

		$this->assertSame( 400, $response->get_status() );
		$this->assertSame( 'rest_relationship_invalid_to', $response->get_data()['code'] );
	}

	/**
	 * Tests that post-to-user custom relationships need no related post types and drop any sent.
	 *
	 * @return void
	 */
	public function test_post_to_user_ignores_related_types() {
		$response = $this->create_via_rest(
			array(
				'rel_type' => 'post-to-user',
				'rel_to'   => array( 'tire' ),
				'rel_name' => 'rest-owners',
			)
		);
		$data     = $response->get_data();

		$this->assertSame( 201, $response->get_status() );
		$this->assertSame( 'car_user_rest-owners', $data['rel_key'] );
		$this->assertSame( array(), Loader::read_post( $data['id'] )['to'] );
	}

	/**
	 * Tests that a post-to-user custom relationship collides with one registered from code.
	 *
	 * @return void
	 */
	public function test_rejects_post_to_user_key_registered_from_code() {
		get_registry()->define_post_to_user( 'car', 'rest-owners-taken' );

		$response = $this->create_via_rest(
			array(
				'rel_type' => 'post-to-user',
				'rel_to'   => array(),
				'rel_name' => 'rest-owners-taken',
			)
		);

		$this->assertSame( 400, $response->get_status() );
		$this->assertSame( 'rest_relationship_exists', $response->get_data()['code'] );
	}

	/**
	 * Tests that identifying fields cannot change after saving, while settings can.
	 *
	 * @return void
	 */
	public function test_identifying_fields_are_immutable() {
		$post_id = $this->create_custom_relationship( array( 'rel_name' => 'rest-immutable' ) );

		$renamed = $this->update_via_rest( $post_id, array( 'meta' => array( 'rel_name' => 'rest-renamed' ) ) );
		$retyped = $this->update_via_rest( $post_id, array( 'meta' => array( 'rel_to' => array( 'post' ) ) ) );
		$settled = $this->update_via_rest(
			$post_id,
			array(
				'title' => 'New label',
				'meta'  => array(
					'rel_name'  => 'rest-immutable',
					'rel_to'    => array( 'tire' ),
					'from_args' => array(
						'enable_ui' => false,
						'sortable'  => false,
						'max_items' => 3,
						'labels'    => array( 'name' => 'Changed' ),
					),
				),
			)
		);

		$this->assertSame( 400, $renamed->get_status() );
		$this->assertSame( 'rest_relationship_immutable', $renamed->get_data()['code'] );
		$this->assertSame( 400, $retyped->get_status() );
		$this->assertSame( 200, $settled->get_status() );
		$this->assertSame( 3, $settled->get_data()['meta']['from_args']['max_items'] );
	}

	/**
	 * Tests that an unregistered post type is reported on the custom relationship.
	 *
	 * @return void
	 */
	public function test_registration_error_reports_missing_post_type() {
		$post_id = $this->create_custom_relationship( array( 'rel_from' => 'not_registered' ) );

		$response = rest_do_request( new \WP_REST_Request( 'GET', '/content-connect/v2/custom-relationships/' . $post_id ) );

		$this->assertStringContainsString( 'not_registered', $response->get_data()['registration_error'] );
	}

	/**
	 * Tests that a key later taken by code is reported on the custom relationship.
	 *
	 * @return void
	 */
	public function test_registration_error_reports_key_taken_by_code() {
		$post_id = $this->create_custom_relationship( array( 'rel_name' => 'rest-taken-later' ) );

		get_registry()->define_post_to_post( 'car', 'tire', 'rest-taken-later' );
		get_plugin()->get_custom_relationships()->register( get_registry() );

		$response = rest_do_request( new \WP_REST_Request( 'GET', '/content-connect/v2/custom-relationships/' . $post_id ) );

		$this->assertStringContainsString( 'already exists', $response->get_data()['registration_error'] );
	}

	/**
	 * Tests that editors can neither update nor delete custom relationships.
	 *
	 * @return void
	 */
	public function test_editor_cannot_update_or_delete_custom_relationship() {
		$post_id = $this->create_custom_relationship( array( 'rel_name' => 'rest-editor-locked' ) );

		wp_set_current_user( $this->factory()->user->create( array( 'role' => 'editor' ) ) );

		$updated = $this->update_via_rest( $post_id, array( 'title' => 'Changed by an editor' ) );

		$delete = new \WP_REST_Request( 'DELETE', '/content-connect/v2/custom-relationships/' . $post_id );
		$delete->set_param( 'force', true );
		$deleted = rest_do_request( $delete );

		$this->assertSame( 403, $updated->get_status() );
		$this->assertSame( 403, $deleted->get_status() );
		$this->assertNotNull( get_post( $post_id ) );
	}

	/**
	 * Creates a custom relationship through the REST API.
	 *
	 * @param  array $meta Meta values, merged over a post-to-post default.
	 * @return \WP_REST_Response
	 */
	private function create_via_rest( $meta ) {
		$request = new \WP_REST_Request( 'POST', '/content-connect/v2/custom-relationships' );
		$request->set_body_params(
			array(
				'title'  => 'REST relationship',
				'status' => 'publish',
				'meta'   => array_merge(
					array(
						'rel_type' => 'post-to-post',
						'rel_from' => 'car',
						'rel_to'   => array( 'tire' ),
					),
					$meta
				),
			)
		);

		return rest_do_request( $request );
	}

	/**
	 * Updates a custom relationship through the REST API.
	 *
	 * @param  int   $post_id Custom relationship post ID.
	 * @param  array $params  Body parameters.
	 * @return \WP_REST_Response
	 */
	private function update_via_rest( $post_id, $params ) {
		$request = new \WP_REST_Request( 'POST', '/content-connect/v2/custom-relationships/' . $post_id );
		$request->set_body_params( $params );

		return rest_do_request( $request );
	}
}
