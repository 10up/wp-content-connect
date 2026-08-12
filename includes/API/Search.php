<?php
/**
 * Backwards-compatible shim for the relocated search endpoint class.
 *
 * @package TenUp\ContentConnect
 */

namespace TenUp\ContentConnect\API;

/**
 * Deprecated alias of the relocated V1 search endpoint.
 *
 * The search endpoint class moved to TenUp\ContentConnect\API\V1\Search in 2.0.0.
 * This subclass preserves the original fully-qualified class name so integrations
 * that reference it keep resolving, while emitting a deprecation notice when
 * instantiated. It inherits all behavior from the relocated class.
 *
 * @deprecated 2.0.0 Use TenUp\ContentConnect\API\V1\Search instead.
 */
class Search extends \TenUp\ContentConnect\API\V1\Search {

	/**
	 * Emits a deprecation notice, then defers to the relocated class.
	 *
	 * @since 2.0.0
	 * @deprecated 2.0.0 Use TenUp\ContentConnect\API\V1\Search instead.
	 */
	public function __construct() {
		_deprecated_constructor( __CLASS__, '2.0.0', \TenUp\ContentConnect\API\V1\Search::class );
	}
}
