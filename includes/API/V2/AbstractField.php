<?php

namespace TenUp\ContentConnect\API\V2;

/**
 * Abstract class for REST API fields.
 *
 * This class provides a common setup method for registering REST API fields.
 *
 * @package TenUp\ContentConnect\API\V2
 */
abstract class AbstractField {

	/**
	 * Setup actions and filters.
	 *
	 * @since 1.7.0
	 */
	public function setup() {
		add_action( 'rest_api_init', array( $this, 'register_fields' ) );
	}

	/**
	 * Registers the REST API fields.
	 *
	 * @since 1.7.0
	 *
	 * @return void
	 */
	abstract public function register_fields();
}
