<?php

namespace TenUp\ContentConnect;

use TenUp\ContentConnect\Relationships\PostToPost;
use TenUp\ContentConnect\Relationships\PostToUser;
use TenUp\ContentConnect\Relationships\Relationship;

/**
 * Creates and tracks any relationships between post types and users.
 */
class Registry {

	/**
	 * Array of all post-to-post relationships.
	 *
	 * @var array
	 */
	protected $post_post_relationships = array();

	/**
	 * Array of all post-to-user relationships.
	 *
	 * @var array
	 */
	protected $post_user_relationships = array();

	/**
	 * Setup the registry.
	 */
	public function setup() {}

	/**
	 * Gets all post-to-post relationships.
	 *
	 * @return array
	 */
	public function get_post_to_post_relationships() {
		return $this->post_post_relationships;
	}

	/**
	 * Gets a key that uniquely identifies a relationship between two entities.
	 *
	 * @param string $from Post type.
	 * @param string $to   Post type or user.
	 * @param string $name Relationship name.
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
	 * Checks if a post-to-post relationship exists.
	 *
	 * The order of CPT arguments does not matter.
	 *
	 * @param string $cpt1 First post type.
	 * @param string $cpt2 Second post type.
	 * @param string $name Relationship name.
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
	 * Returns the post-to-post relationship object by key.
	 *
	 * @param string $key Relationship key.
	 * @return bool|Relationship Returns relationship object if relationship exists, otherwise false.
	 */
	public function get_post_to_post_relationship_by_key( $key ) {
		if ( isset( $this->post_post_relationships[ $key ] ) ) {
			return $this->post_post_relationships[ $key ];
		}

		return false;
	}

	/**
	 * Returns the post-to-post relationship object for the post types provided.
	 *
	 * The order of CPT arguments does not matter.
	 *
	 * @param string $cpt1 First post type.
	 * @param string $cpt2 Second post type.
	 * @param string $name Relationship name.
	 * @return bool|Relationship Returns relationship object if relationship exists, otherwise false.
	 */
	public function get_post_to_post_relationship( $cpt1, $cpt2, $name ) {
		$key = $this->get_relationship_key( $cpt1, $cpt2, $name );

		$relationship = $this->get_post_to_post_relationship_by_key( $key );

		if ( $relationship instanceof Relationship ) {
			return $relationship;
		}

		// Try the inverse, only if "cpt2" isn't an array.
		if ( is_array( $cpt2 ) ) {
			return false;
		}

		$key = $this->get_relationship_key( $cpt2, $cpt1, $name );

		$relationship = $this->get_post_to_post_relationship_by_key( $key );

		return $relationship;
	}

	/**
	 * Defines a new many to many relationship between two post types.
	 *
	 * @param string       $from First post type in the relationship.
	 * @param string|array $to   Second post type(s) in the relationship.
	 * @param string       $name Relationship name.
	 * @param array        Array of options for the relationship.
	 *
	 * @throws \Exception
	 *
	 * @return Relationship
	 */
	public function define_post_to_post( $from, $to, $name, $args = array() ) {

		if ( $this->post_to_post_relationship_exists( $from, $to, $name ) ) {
			$to = implode( ', ', (array) $to );
			throw new \Exception( esc_html( "A relationship already exists between {$from} and {$to} with name {$name}" ) );
		}

		$key = $this->get_relationship_key( $from, $to, $name );

		$this->post_post_relationships[ $key ] = new PostToPost( $from, $to, $name, $args );
		$this->post_post_relationships[ $key ]->setup();

		$relationship = $this->post_post_relationships[ $key ];

		return $relationship;
	}

	/**
	 * Gets all post-to-user relationships.
	 *
	 * @return array
	 */
	public function get_post_to_user_relationships() {
		return $this->post_user_relationships;
	}

	/**
	 * Checks if a post-to-user relationship exists.
	 *
	 * @param string $post_type Post type.
	 * @param string $name      Relationship name.
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
	 * Returns the post-to-user relationship object by key.
	 *
	 * @param string $key Relationship key.
	 * @return bool|Relationship Returns relationship object if relationship exists, otherwise false.
	 */
	public function get_post_to_user_relationship_by_key( $key ) {
		if ( isset( $this->post_user_relationships[ $key ] ) ) {
			return $this->post_user_relationships[ $key ];
		}

		return false;
	}

	/**
	 * Returns the post-to-user relationship object for the post type provided.
	 *
	 * @param string $post_type Post type.
	 * @param string $name      Relationship name.
	 * @return @return bool|Relationship Returns relationship object if relationship exists, otherwise false.
	 */
	public function get_post_to_user_relationship( $post_type, $name ) {
		$key = $this->get_relationship_key( $post_type, 'user', $name );

		$relationship = $this->get_post_to_user_relationship_by_key( $key );

		return $relationship;
	}

	/**
	 * Defines a new many to many relationship between users and a post type.
	 *
	 * @param string $post_type The post type to be related to users.
	 * @param string $name      Relationship name.
	 * @param array  $args      Array of options for the relationship.
	 *
	 * @throws \Exception

	 * @return Relationship
	 */
	public function define_post_to_user( $post_type, $name, $args = array() ) {

		if ( $this->post_to_user_relationship_exists( $post_type, $name ) ) {
			throw new \Exception( esc_html( "A relationship already exists between users and post type {$post_type} named {$name}" ) );
		}

		$key = $this->get_relationship_key( $post_type, 'user', $name );

		$this->post_user_relationships[ $key ] = new PostToUser( $post_type, $name, $args );
		$this->post_user_relationships[ $key ]->setup();

		$relationship = $this->post_user_relationships[ $key ];

		return $relationship;
	}
}
