<?php

namespace TenUp\P2P\Relationships;

abstract class Relationship {

	/**
	 * CPT Name of the first post type in the relationship
	 *
	 * @var string
	 */
	public $from;

	/**
	 * CPT Name of the second post type in the relationship
	 *
	 * @var string
	 */
	public $to;

	/**
	 * Relationship Type. Used to enable multiple relationships between the same combinations of objects.
	 *
	 * @var string
	 */
	public $type;

	/**
	 * Unique ID string for the relationship
	 *
	 * Used for IDs in the DOM and other places we need a unique ID
	 *
	 * @var string
	 */
	public $id;

	/**
	 * Should the default UI for this relationship be enabled
	 *
	 * @var bool
	 */
	public $enable_ui;

	public function __construct( $from, $to, $type, $args = array() ) {
		if ( ! post_type_exists( $from ) ) {
			throw new \Exception( "Post Type {$from} does not exist. Post types must exist to create a relationship" );
		}

		if ( ! post_type_exists( $to ) ) {
			throw new \Exception( "Post Type {$to} does not exist. Post types must exist to create a relationship" );
		}

		$this->from = $from;
		$this->to = $to;
		$this->type = $type;
		$this->id = strtolower( get_class( $this ) ) . "-{$type}-{$from}-{$to}";

		$defaults = array(
			'enable_ui' => true,
		);

		$args = wp_parse_args( $args, $defaults );

		$this->enable_ui = $args['enable_ui'];
	}

}
