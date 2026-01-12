<?php
/**
 * Base test case for Content Connect integration tests.
 *
 * @package TenUp\ContentConnect\Tests
 */

namespace TenUp\ContentConnect\Tests;

use TenUp\ContentConnect\Plugin;
use TenUp\ContentConnect\Registry;
use TenUp\ContentConnect\Relationships\PostToPost;
use TenUp\ContentConnect\Relationships\PostToUser;
use function TenUp\ContentConnect\Helpers\get_registry;

/**
 * Base test case class for Content Connect integration tests.
 *
 * Provides common setup methods and test data helpers.
 */
class ContentConnectTestCase extends \WP_UnitTestCase {

	/**
	 * Sets up the test suite before any tests run.
	 *
	 * @return void
	 */
	public static function setUpBeforeClass(): void {
		self::register_post_types();
		parent::setUpBeforeClass();
	}

	/**
	 * Sets up the test environment.
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		// Ensure the plugin and registry are initialized
		$plugin = Plugin::instance();
		if ( empty( $plugin->registry ) ) {
			$plugin->registry = new Registry();
			$plugin->registry->setup();
		}

		// Ensure custom tables are created/upgraded
		if ( ! empty( $plugin->tables ) ) {
			foreach ( $plugin->tables as $table ) {
				$table->upgrade( true );
			}
		}

		self::insert_dummy_data();
	}

	/**
	 * Inserts dummy post and user data for testing.
	 *
	 * @return void
	 */
	public static function insert_dummy_data(): void {
		global $wpdb;

		$wpdb->query( "DELETE FROM {$wpdb->posts}" );
		$wpdb->query( "INSERT INTO `{$wpdb->posts}` " . file_get_contents( __DIR__ . '/data/posts.sql' ) );

		$wpdb->query( "DELETE FROM {$wpdb->users}" );
		$wpdb->query( "INSERT INTO `{$wpdb->users}` " . file_get_contents( __DIR__ . '/data/users.sql' ) );
	}

	/**
	 * Registers custom post types needed for testing.
	 *
	 * @return void
	 */
	public static function register_post_types(): void {
		$post_types = array(
			'car',
			'tire',
		);

		foreach ( $post_types as $post_type ) {
			if ( ! post_type_exists( $post_type ) ) {
				register_post_type( $post_type );
			}
		}
	}

	/**
	 * Adds known post-to-post relationships that can be used for testing.
	 *
	 * Post Type to Post ID Mapping:
	 * - Post Type Post: 1-10
	 * - Post Type Car:  11-20
	 * - Post Type Tire: 21-30
	 *
	 * @return void
	 */
	public function add_post_relations(): void {
		global $wpdb;

		$wpdb->query( "DELETE FROM {$wpdb->prefix}post_to_post;" );

		$registry = get_registry();

		// Register relationships in the registry
		try {
			$registry->define_post_to_post( 'post', 'post', 'basic' );
		} catch ( \Exception $e ) {
			// Relationship might already exist, that's okay
		}
		try {
			$registry->define_post_to_post( 'post', 'post', 'complex' );
		} catch ( \Exception $e ) {
			// Relationship might already exist, that's okay
		}
		try {
			$registry->define_post_to_post( 'post', 'car', 'basic' );
		} catch ( \Exception $e ) {
			// Relationship might already exist, that's okay
		}
		try {
			$registry->define_post_to_post( 'post', 'car', 'complex' );
		} catch ( \Exception $e ) {
			// Relationship might already exist, that's okay
		}
		try {
			$registry->define_post_to_post( 'post', 'tire', 'basic' );
		} catch ( \Exception $e ) {
			// Relationship might already exist, that's okay
		}
		try {
			$registry->define_post_to_post( 'post', 'tire', 'complex' );
		} catch ( \Exception $e ) {
			// Relationship might already exist, that's okay
		}
		try {
			$registry->define_post_to_post( 'car', 'tire', 'basic' );
		} catch ( \Exception $e ) {
			// Relationship might already exist, that's okay
		}
		try {
			$registry->define_post_to_post( 'car', 'tire', 'complex' );
		} catch ( \Exception $e ) {
			// Relationship might already exist, that's okay
		}
		try {
			$registry->define_post_to_post( 'post', 'post', 'page1' );
		} catch ( \Exception $e ) {
			// Relationship might already exist, that's okay
		}
		try {
			$registry->define_post_to_post( 'post', 'post', 'page2' );
		} catch ( \Exception $e ) {
			// Relationship might already exist, that's okay
		}

		// Get relationship objects from registry to add actual relationships
		$ppb = $registry->get_post_to_post_relationship( 'post', 'post', 'basic' );
		$ppc = $registry->get_post_to_post_relationship( 'post', 'post', 'complex' );
		$pcb = $registry->get_post_to_post_relationship( 'post', 'car', 'basic' );
		$pcc = $registry->get_post_to_post_relationship( 'post', 'car', 'complex' );
		$ptb = $registry->get_post_to_post_relationship( 'post', 'tire', 'basic' );
		$ptc = $registry->get_post_to_post_relationship( 'post', 'tire', 'complex' );
		$ctb = $registry->get_post_to_post_relationship( 'car', 'tire', 'basic' );
		$ctc = $registry->get_post_to_post_relationship( 'car', 'tire', 'complex' );

		$ppb->add_relationship( 1, 2 );
		$ppb->add_relationship( 1, 3 );
		$ppc->add_relationship( 1, 3 );
		$ppc->add_relationship( 1, 4 );
		$pcb->add_relationship( 1, 11 );
		$pcb->add_relationship( 1, 12 );
		$pcc->add_relationship( 1, 13 );
		$pcc->add_relationship( 1, 14 );
		$ptb->add_relationship( 1, 21 );
		$ptb->add_relationship( 1, 22 );
		$ptc->add_relationship( 1, 23 );
		$ptc->add_relationship( 1, 24 );
		$ctb->add_relationship( 11, 21 );
		$ctc->add_relationship( 13, 23 );

		// for pagination tests, we'll use "page1" and "page2" names to make sure we have different names
		$p1 = $registry->get_post_to_post_relationship( 'post', 'post', 'page1' );
		$p2 = $registry->get_post_to_post_relationship( 'post', 'post', 'page2' );

		for ( $i = 35; $i <= 90; $i++ ) {
			switch ( $i % 4 ) {
				case 0:
					$p1->add_relationship( 31, $i );
					break;
				case 1:
					$p1->add_relationship( 32, $i );
					break;
				case 2:
					$p2->add_relationship( 33, $i );
					break;
				case 3:
					$p2->add_relationship( 34, $i );
					break;
			}
		}
	}

	/**
	 * Adds known post-to-user relationships that can be used for testing.
	 *
	 * @return void
	 */
	public function add_user_relations(): void {
		global $wpdb;

		$wpdb->query( "DELETE FROM {$wpdb->prefix}post_to_user;" );

		$registry = get_registry();

		// Register relationships in the registry
		try {
			$registry->define_post_to_user( 'post', 'owner' );
		} catch ( \Exception $e ) {
			// Relationship might already exist, that's okay
		}
		try {
			$registry->define_post_to_user( 'post', 'contrib' );
		} catch ( \Exception $e ) {
			// Relationship might already exist, that's okay
		}
		try {
			$registry->define_post_to_user( 'car', 'owner' );
		} catch ( \Exception $e ) {
			// Relationship might already exist, that's okay
		}
		try {
			$registry->define_post_to_user( 'car', 'contrib' );
		} catch ( \Exception $e ) {
			// Relationship might already exist, that's okay
		}

		// Get relationship objects from registry to add actual relationships
		$postowner   = $registry->get_post_to_user_relationship( 'post', 'owner' );
		$postcontrib = $registry->get_post_to_user_relationship( 'post', 'contrib' );
		$carowner    = $registry->get_post_to_user_relationship( 'car', 'owner' );
		$carcontrib  = $registry->get_post_to_user_relationship( 'car', 'contrib' );

		$postowner->add_relationship( 1, 1 );
		$postowner->add_relationship( 2, 1 );
		$postowner->add_relationship( 3, 1 );
		$postowner->add_relationship( 4, 1 );
		$postowner->add_relationship( 5, 1 );
		$postowner->add_relationship( 3, 2 );
		$postowner->add_relationship( 4, 2 );
		$postowner->add_relationship( 5, 2 );
		$postowner->add_relationship( 6, 2 );
		$postowner->add_relationship( 7, 2 );
		$postowner->add_relationship( 5, 3 );
		$postowner->add_relationship( 6, 3 );
		$postowner->add_relationship( 7, 3 );
		$postowner->add_relationship( 8, 3 );
		$postowner->add_relationship( 9, 3 );

		$postcontrib->add_relationship( 2, 1 );
		$postcontrib->add_relationship( 3, 1 );
		$postcontrib->add_relationship( 4, 1 );
		$postcontrib->add_relationship( 5, 1 );
		$postcontrib->add_relationship( 6, 1 );
		$postcontrib->add_relationship( 4, 2 );
		$postcontrib->add_relationship( 5, 2 );
		$postcontrib->add_relationship( 6, 2 );
		$postcontrib->add_relationship( 7, 2 );
		$postcontrib->add_relationship( 8, 2 );
		$postcontrib->add_relationship( 6, 3 );
		$postcontrib->add_relationship( 7, 3 );
		$postcontrib->add_relationship( 8, 3 );
		$postcontrib->add_relationship( 9, 3 );
		$postcontrib->add_relationship( 10, 3 );

		$carowner->add_relationship( 16, 1 );
		$carowner->add_relationship( 17, 1 );
		$carowner->add_relationship( 18, 1 );
		$carowner->add_relationship( 19, 1 );
		$carowner->add_relationship( 20, 1 );
		$carowner->add_relationship( 14, 2 );
		$carowner->add_relationship( 15, 2 );
		$carowner->add_relationship( 16, 2 );
		$carowner->add_relationship( 17, 2 );
		$carowner->add_relationship( 18, 2 );
		$carowner->add_relationship( 12, 3 );
		$carowner->add_relationship( 13, 3 );
		$carowner->add_relationship( 14, 3 );
		$carowner->add_relationship( 15, 3 );
		$carowner->add_relationship( 16, 3 );

		$carcontrib->add_relationship( 15, 1 );
		$carcontrib->add_relationship( 16, 1 );
		$carcontrib->add_relationship( 17, 1 );
		$carcontrib->add_relationship( 18, 1 );
		$carcontrib->add_relationship( 19, 1 );
		$carcontrib->add_relationship( 13, 2 );
		$carcontrib->add_relationship( 14, 2 );
		$carcontrib->add_relationship( 15, 2 );
		$carcontrib->add_relationship( 16, 2 );
		$carcontrib->add_relationship( 17, 2 );
		$carcontrib->add_relationship( 11, 3 );
		$carcontrib->add_relationship( 12, 3 );
		$carcontrib->add_relationship( 13, 3 );
		$carcontrib->add_relationship( 14, 3 );
		$carcontrib->add_relationship( 15, 3 );
	}

	/**
	 * Cleans up after each test.
	 *
	 * Resets the registry to ensure test isolation.
	 *
	 * @return void
	 */
	public function tearDown(): void {
		$plugin           = Plugin::instance();
		$plugin->registry = new Registry();
		$plugin->registry->setup();

		parent::tearDown();
	}
}
