<?php

namespace TenUp\P2P\Tests;

use TenUp\P2P\Plugin;
use TenUp\P2P\Registry;

class P2PTestCase extends \PHPUnit_Framework_TestCase {

	public static function setupBeforeClass() {
		self::insert_dummy_data();
		self::register_post_types();
		self::register_relationship_types();

		parent::setUpBeforeClass();
	}

	public static function insert_dummy_data() {
		global $wpdb;

		$wpdb->query( "DELETE FROM {$wpdb->posts}" );
		$wpdb->query( "INSERT INTO `{$wpdb->posts}` " . file_get_contents( __DIR__ . '/data/posts.sql' ) );
	}

	public static function register_post_types() {
		$post_types = array(
			'car',
			'tire'
		);

		foreach ( $post_types as $post_type ) {
			if ( ! post_type_exists( $post_type ) ) {
				register_post_type( $post_type );
			}
		}
	}

	public static function register_relationship_types() {
		$registry = Plugin::instance()->get_registry();

		$types = array(
			array( 'post', 'post' ),
			array( 'post', 'car' ),
			array( 'post', 'tire' ),
			array( 'car', 'tire' ),
			array( 'car', 'car' ),
			array( 'tire', 'tire' ),
		);

		foreach( $types as $type ) {
			if ( ! $registry->relationship_exists( $type[0], $type[1] ) ) {
				$registry->define_many_to_many( $type[0], $type[1] );
			}
		}
	}

	/**
	 * Adds known relationships that we can then test against
	 *
	 * Post Type to Post ID Mapping:
	 *
	 * Post Type Post: 1, 2, 3, 4
	 * Post Type Car:  5, 6, 7, 8
	 * Post Type Tire: 9, 10, 11, 12
	 */
	public function add_known_relations() {
		global $wpdb;

		$wpdb->query( "DELETE FROM {$wpdb->prefix}post_to_post" );
		$wpdb->query( "INSERT INTO `{$wpdb->prefix}post_to_post` " . file_get_contents( __DIR__ . '/data/relationships.sql' ) );
	}

	/**
	 * @return ManyToMany
	 */
	public function get_post_to_post_object() {
		$registry = Plugin::instance()->get_registry();

		$p2p = $registry->get_relationship( 'post', 'post' );
		if ( $p2p ) {
			return $p2p;
		}

		return $registry->define_many_to_many( 'post', 'post' );
	}

	public function get_relationship_object( $from, $to ) {
		$reg = Plugin::instance()->get_registry();
		return $reg->get_relationship( $from, $to );
	}

}

