<?php

namespace TenUp\ContentConnect\Relationships;

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
	 * Should the from UI for this relationship be enabled
	 *
	 * @var bool
	 */
	public $enable_from_ui;

	/**
	 * Should the to UI for this relationship be enabled
	 *
	 * @var bool
	 */
	public $enable_to_ui;

	/**
	 * Various labels used for from UI
	 *
	 * @var Array
	 */
	public $from_labels;

	/**
	 * Various labels used for to UI
	 *
	 * @var Array
	 */
	public $to_labels;

	/**
	 * Is the "from" UI for this sortable
	 *
	 * @var bool
	 */
	public $from_sortable;

	public function __construct( $type, $args = array() ) {
		$this->type = $type;

		$defaults = array(
			'from_ui' => array(
				'enabled' => true,
				'sortable' => false,
				'labels' => array(
					'name' => $type,
				),
			),
			'to_ui' => array(
				'enabled' => false,
				'labels' => array(
					'name' => $type,
				)
			),
		);

		$args = array_replace_recursive( $defaults, $args );

		$this->enable_from_ui = $args['from_ui']['enabled'];
		$this->from_sortable = $args['from_ui']['sortable'];
		$this->from_labels = $args['from_ui']['labels'];

		$this->enable_to_ui = $args['to_ui']['enabled'];
		$this->to_labels = $args['to_ui']['labels'];
	}

	abstract function setup();

	abstract function save_sort_data( $object_id, $ordered_ids );

}
