<?php

namespace TenUp\ContentConnect\API\V2\Post\Route;

use TenUp\ContentConnect\API\V2\AbstractRoute;

/**
 * Abstract class for post REST API routes.
 *
 * This class provides a common setup method for registering post REST API routes.
 *
 * @package TenUp\ContentConnect\API\V2\Post
 */
abstract class AbstractPostRoute extends AbstractRoute {

	/**
	 * {@inheritDoc}
	 */
	protected $rest_base = 'post';
}
