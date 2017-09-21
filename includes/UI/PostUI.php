<?php

namespace TenUp\ContentConnect\UI;

abstract class PostUI {

	/**
	 * @var \TenUp\ContentConnect\Relationships\Relationship
	 */
	public $relationship;

	/**
	 * The post type to render this UI on
	 *
	 * @var String
	 */
	public $render_post_type;

	/**
	 * Labels for this UI
	 *
	 * @var Array
	 */
	public $labels;

	/**
	 * Whether or not this UI should allow sorting
	 *
	 * @var bool
	 */
	public $sortable;

	/**
	 * @param PostToPost $relationship
	 */
	public function __construct( $relationship, $render_post_type, $labels, $sortable = false ) {
		$this->relationship = $relationship;
		$this->render_post_type = $render_post_type;
		$this->labels = $labels;
		$this->sortable = $sortable;
	}

}
