<?php

namespace TenUp\ContentConnect;

use TenUp\ContentConnect\Relationships\PostToPost;
use TenUp\ContentConnect\Relationships\PostToUser;
use TenUp\ContentConnect\Relationships\Relationship;

/**
 * Creates and Tracks any relationships between post types
 */
class Registry {

	protected $post_post_relationships = array();

	protected $post_user_relationships = array();

	public function setup() {}

	/**
	 * Gets a key that uniquely identifies a relationship between two CPTs
	 *
	 * @param string $from
	 * @param string $to
	 * @param string $name
	 *
	 * @return string
	 */
	public function get_relationship_key( $from, $to, $name ) {
		$from = (array) $from;
		sort( $from );
		$from = implode( '.', $from );

		$to = (array) $to;
		sort( $to );
		$to = implode( '.', $to );

		return "{$from}_{$to}_{$name}";
	}

	/**
	 * Checks if a relationship exists between two post types.
	 *
	 * Order of post type doesn't matter when checking if the relationship already exists.
	 *
	 * @param string $cpt1
	 * @param string $cpt2
	 * @param string $name
	 *
	 * @return bool
	 */
	public function post_to_post_relationship_exists( $cpt1, $cpt2, $name ) {
		$relationship = $this->get_post_to_post_relationship( $cpt1, $cpt2, $name );

		if ( ! $relationship ) {
			return false;
		}

		return true;
	}

	/**
	 * Returns the post relationship object for the key provided.
	 *
	 * @param  string $key Relationship key.
	 * @return bool|Relationship Returns relationship object if relationship exists, otherwise false
	 */
	public function get_post_to_post_relationship_by_key( $key ) {
		if ( isset( $this->post_post_relationships[ $key ] ) ) {
			return $this->post_post_relationships[ $key ];
		}

		return false;
	}

	/**
	 * Returns the relationship object for the post types provided. Order of CPT args is unimportant.
	 *
	 * @param string $cpt1
	 * @param string $cpt2
	 * @param string $name
	 *
	 * @return bool|Relationship Returns relationship object if relationship exists, otherwise false
	 */
	public function get_post_to_post_relationship( $cpt1, $cpt2, $name ) {
		$key = $this->get_relationship_key( $cpt1, $cpt2, $name );

		$relationship = $this->get_post_to_post_relationship_by_key( $key );

		if ( $relationship ) {
			return $relationship;
		}

		// Try the inverse, only if "cpt2" isn't an array
		if ( is_array( $cpt2 ) ) {
			return false;
		}
		$key = $this->get_relationship_key( $cpt2, $cpt1, $name );

		$relationship = $this->get_post_to_post_relationship_by_key( $key );

		return $relationship;
	}

	/**
	 * Defines a new many to many relationship between two post types
	 *
	 * @param string $from
	 * @param string $to
	 * @param string $name
	 *
	 * @throws \Exception
	 *
	 * @return Relationship
	 */
	public function define_post_to_post( $from, $to, $name, $args = array() ) {
		if ( $this->post_to_post_relationship_exists( $from, $to, $name ) ) {
			$to = implode( ', ', (array) $to );
			throw new \Exception( "A relationship already exists between {$from} and {$to} with name {$name}" );
		}

		$key = $this->get_relationship_key( $from, $to, $name );

		$this->post_post_relationships[ $key ] = new PostToPost( $from, $to, $name, $args );
		$this->post_post_relationships[ $key ]->setup();

		return $this->post_post_relationships[ $key ];
	}

	/* POST TO USER RELATIONSHIPS */

	/**
	 * Checks if a relationship exists between a post type and users
	 *
	 * @param string $post_type
	 * @param string $name
	 *
	 * @return bool
	 */
	public function post_to_user_relationship_exists( $post_type, $name ) {
		$relationship = $this->get_post_to_user_relationship( $post_type, $name );

		if ( ! $relationship ) {
			return false;
		}

		return true;
	}

	/**
	 * Returns the user relationship object for the key provided.
	 *
	 * @param  string $key Relationship key.
	 * @return bool|Relationship Returns relationship object if relationship exists, otherwise false
	 */
	public function get_post_to_user_relationship_by_key( $key ) {
		if ( isset( $this->post_user_relationships[ $key ] ) ) {
			return $this->post_user_relationships[ $key ];
		}

		return false;
	}

	/**
	 * Returns the relationship object between users and the post type provided.
	 *
	 * @param string $post_type
	 * @param string $name
	 */
	public function get_post_to_user_relationship( $post_type, $name ) {
		$key = $this->get_relationship_key( $post_type, 'user', $name );

		return $this->get_post_to_user_relationship_by_key( $key );
	}

	/**
	 * Defines a new many to many relationship between users and a post type
	 *
	 * @param string $post_type
	 * @param string $name
	 * @param array $args
	 *
	 * @return mixed
	 * @throws \Exception
	 */
	public function define_post_to_user( $post_type, $name, $args = array() ) {
		if ( $this->post_to_user_relationship_exists( $post_type, $name ) ) {
			throw new \Exception( "A relationship already exists between users and post type {$post_type} named {$name}" );
		}

		$key = $this->get_relationship_key( $post_type, 'user', $name );

		$this->post_user_relationships[ $key ] = new PostToUser( $post_type, $name, $args );
		$this->post_user_relationships[ $key ]->setup();

		return $this->post_user_relationships[ $key ];
	}
}
