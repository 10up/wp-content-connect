<?php

namespace TenUp\P2P\Relationships;

abstract class Relationship {

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

	public function __construct( $type, $args = array() ) {
		$this->type = $type;

		$defaults = array(
			'enable_ui' => true,
		);

		$args = wp_parse_args( $args, $defaults );

		$this->enable_ui = $args['enable_ui'];
	}

}
