<?php

namespace TenUp\P2P;

use TenUp\P2P\Relationships\ManyToMany;
use TenUp\P2P\Relationships\Relationship;

/**
 * Creates and Tracks any relationships between post types
 */
class Registry {

	protected $relationships = array();

	public function setup() {}

	/**
	 * Gets a key that uniquely identifies a relationship between two CPTs
	 *
	 * @param $from
	 * @param $to
	 *
	 * @return string
	 */
	public function get_relationship_key( $from, $to ) {
		return "{$from}_{$to}";
	}

	/**
	 * Checks if a relationship exists between two post types.
	 *
	 * Order of post type doesn't matter when checking if the relationship already exists.
	 *
	 * @param string $cpt1
	 * @param string $cpt2
	 *
	 * @return bool
	 */
	public function relationship_exists( $cpt1, $cpt2 ) {
		$relationship = $this->get_relationship( $cpt1, $cpt2 );

		if ( ! $relationship ) {
			return false;
		}

		return true;
	}

	/**
	 * Returns the relationship object for the post types provided. Order of CPT args is unimportant.
	 *
	 * @param $cpt1
	 * @param $cpt2
	 *
	 * @return bool|Relationship Returns relationship object if relationship exists, otherwise false
	 */
	public function get_relationship( $cpt1, $cpt2 ) {
		$key = $this->get_relationship_key( $cpt1, $cpt2 );

		if ( isset( $this->relationships[ $key ] ) ) {
			return $this->relationships[ $key ];
		}

		// Try the inverse
		$key = $this->get_relationship_key( $cpt2, $cpt1 );

		if ( isset( $this->relationships[ $key ] ) ) {
			return $this->relationships[ $key ];
		}

		return false;
	}

	/**
	 * @param $from
	 * @param $to
	 *
	 * @throws \Exception
	 *
	 * @return Relationship
	 */
	public function add_many_to_many( $from, $to ) {
		if ( $this->relationship_exists( $from, $to ) ) {
			throw new \Exception( "A relationship already exists between {$from} and {$to}" );
		}

		$key = $this->get_relationship_key( $from, $to );

		$this->relationships[ $key ] = new ManyToMany( $from, $to );

		return $this->relationships[ $key ];
	}

}
