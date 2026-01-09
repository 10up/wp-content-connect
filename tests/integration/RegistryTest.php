<?php
/**
 * Tests for the Registry class.
 *
 * @package TenUp\ContentConnect\Tests\Integration
 */

namespace TenUp\ContentConnect\Tests\Integration;

use TenUp\ContentConnect\Registry;
use TenUp\ContentConnect\Relationships\PostToPost;
use TenUp\ContentConnect\Relationships\PostToUser;

/**
 * Test cases for the Registry class.
 */
class RegistryTest extends ContentConnectTestCase {

	/**
	 * Tests that relationships don't exist before being defined.
	 *
	 * @return void
	 */
	public function test_relationship_doesnt_exist(): void {
		$registry = new Registry();

		$this->assertFalse( $registry->post_to_post_relationship_exists( 'post', 'post', 'basic' ) );
		$this->assertFalse( $registry->post_to_user_relationship_exists( 'post', 'owner' ) );
	}

	/**
	 * Tests that relationships can be added to the registry.
	 *
	 * @return void
	 */
	public function test_relationship_can_be_added(): void {
		$registry = new Registry();

		$this->assertInstanceOf( PostToPost::class, $registry->define_post_to_post( 'post', 'post', 'basic' ) );
		$this->assertInstanceOf( PostToUser::class, $registry->define_post_to_user( 'post', 'owner' ) );
	}

	/**
	 * Tests that duplicate post-to-post relationships cannot be added.
	 *
	 * @return void
	 */
	public function test_doesnt_add_duplicate_post_to_post_relationship(): void {
		$registry = new Registry();

		$this->expectException( \Exception::class );

		$registry->define_post_to_post( 'post', 'post', 'basic' );
		$registry->define_post_to_post( 'post', 'post', 'basic' );
	}

	/**
	 * Tests that duplicate post-to-user relationships cannot be added.
	 *
	 * @return void
	 */
	public function test_doesnt_add_duplicate_post_to_user_relationship(): void {
		$registry = new Registry();

		$this->expectException( \Exception::class );

		$registry->define_post_to_user( 'post', 'owner' );
		$registry->define_post_to_user( 'post', 'owner' );
	}

	/**
	 * Tests that different relationship types can be defined for the same CPTs.
	 *
	 * @return void
	 */
	public function test_can_define_different_types_for_same_cpts(): void {
		$registry = new Registry();

		$this->assertInstanceOf( PostToPost::class, $registry->define_post_to_post( 'post', 'post', 'type1' ) );
		$this->assertInstanceOf( PostToPost::class, $registry->define_post_to_post( 'post', 'post', 'type2' ) );

		$this->assertInstanceOf( PostToUser::class, $registry->define_post_to_user( 'post', 'owner' ) );
		$this->assertInstanceOf( PostToUser::class, $registry->define_post_to_user( 'post', 'contrib' ) );
	}

	/**
	 * Tests that flipped order relationships are still considered duplicates.
	 *
	 * @return void
	 */
	public function test_flipped_order_is_still_duplicate(): void {
		$registry = new Registry();

		$this->expectException( \Exception::class );

		$registry->define_post_to_post( 'post', 'car', 'basic' );
		$registry->define_post_to_post( 'car', 'post', 'basic' );
	}

	/**
	 * Tests retrieval of post-to-post relationships from the registry.
	 *
	 * @return void
	 */
	public function test_retrieval_of_post_to_post_relationships(): void {
		$registry = new Registry();

		// Add all the relationship types so we know we aren't just lucky in the return values
		$pp = $registry->define_post_to_post( 'post', 'post', 'basic' );
		$pc = $registry->define_post_to_post( 'post', 'car', 'basic' );
		$pt = $registry->define_post_to_post( 'post', 'tire', 'basic' );
		$ct = $registry->define_post_to_post( 'car', 'tire', 'basic' );
		$cc = $registry->define_post_to_post( 'car', 'car', 'basic' );
		$tt = $registry->define_post_to_post( 'tire', 'tire', 'basic' );

		$tt2 = new PostToPost( 'tire', 'tire', 'basic' );

		// Verify that two separate objects are NOT the same (sanity check)
		$this->assertNotSame( $tt, $tt2 );

		$this->assertSame( $pp, $registry->get_post_to_post_relationship( 'post', 'post', 'basic' ) );

		// Check that it doesn't matter the order of args
		$this->assertSame( $pc, $registry->get_post_to_post_relationship( 'post', 'car', 'basic' ) );
		$this->assertSame( $pc, $registry->get_post_to_post_relationship( 'car', 'post', 'basic' ) );

		// Check that calling inverse args returns the same as well (it should, based on above two tests)
		$this->assertSame( $registry->get_post_to_post_relationship( 'post', 'car', 'basic' ), $registry->get_post_to_post_relationship( 'car', 'post', 'basic' ) );
	}

	/**
	 * Tests retrieval of post-to-user relationships from the registry.
	 *
	 * @return void
	 */
	public function test_retrieval_of_post_to_user_relationships(): void {
		$registry = new Registry();

		$po = $registry->define_post_to_user( 'post', 'owner' );
		$pc = $registry->define_post_to_user( 'post', 'contrib' );

		$pc2 = new PostToUser( 'post', 'contrib' );

		// verify that two separate objects are NOT the same (sanity check)
		$this->assertNotSame( $pc, $pc2 );

		$this->assertSame( $po, $registry->get_post_to_user_relationship( 'post', 'owner' ) );
		$this->assertSame( $pc, $registry->get_post_to_user_relationship( 'post', 'contrib' ) );
	}

	/**
	 * Tests retrieval of unique relationship names on the same CPT.
	 *
	 * @return void
	 */
	public function test_retrieval_of_unique_relationship_names_on_same_cpt(): void {
		$registry = new Registry();

		$pp1 = $registry->define_post_to_post( 'post', 'post', 'type1' );
		$pp2 = $registry->define_post_to_post( 'post', 'post', 'type2' );

		$this->assertSame( $pp1, $registry->get_post_to_post_relationship( 'post', 'post', 'type1' ) );
		$this->assertSame( $pp2, $registry->get_post_to_post_relationship( 'post', 'post', 'type2' ) );
	}

	/**
	 * Tests that defining without array is the same as with array.
	 *
	 * @return void
	 */
	public function test_defining_without_array_is_same_as_with_array(): void {
		$registry = new Registry();

		$this->expectException( \Exception::class );

		$registry->define_post_to_post( 'post', 'post', 'basic' );
		$registry->define_post_to_post( 'post', array( 'post' ), 'basic' );
	}

	/**
	 * Tests that defining the same multi-to relationship is not allowed.
	 *
	 * @return void
	 */
	public function test_defining_same_multi_to_is_not_allowed(): void {
		$registry = new Registry();

		$this->expectException( \Exception::class );

		$registry->define_post_to_post( 'post', array( 'car', 'tire' ), 'basic' );
		$registry->define_post_to_post( 'post', array( 'car', 'tire' ), 'basic' );
	}

	/**
	 * Tests that defining multi-to relationships in inverse order is not allowed.
	 *
	 * @return void
	 */
	public function test_defining_multi_to_inverse_order_is_not_allowed(): void {
		$registry = new Registry();

		$this->expectException( \Exception::class );

		$registry->define_post_to_post( 'post', array( 'car', 'tire' ), 'basic' );
		$registry->define_post_to_post( 'post', array( 'tire', 'car' ), 'basic' );
	}

	/**
	 * Tests retrieval of multi post type relationships.
	 *
	 * @return void
	 */
	public function test_retrieval_of_multi_post_type_relationships(): void {
		$registry = new Registry();

		$pct = $registry->define_post_to_post( 'post', array( 'car', 'tire' ), 'basic' );

		$pct2 = new PostToPost( 'post', array( 'car', 'tire' ), 'basic' );

		// Verify that two separate objects are NOT the same (sanity check)
		$this->assertNotSame( $pct, $pct2 );

		$this->assertSame( $pct, $registry->get_post_to_post_relationship( 'post', array( 'car', 'tire' ), 'basic' ) );
		$this->assertSame( $pct, $registry->get_post_to_post_relationship( 'post', array( 'tire', 'car' ), 'basic' ) );
	}
}
