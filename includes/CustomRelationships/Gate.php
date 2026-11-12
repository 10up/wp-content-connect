<?php

namespace TenUp\ContentConnect\CustomRelationships;

/**
 * Class Gate
 *
 * Decides whether the relationships admin UI is available.
 *
 * @package TenUp\ContentConnect\CustomRelationships
 */
class Gate {

	/**
	 * Whether the relationships admin UI is enabled.
	 *
	 * The `CONTENT_CONNECT_ADMIN_UI` constant takes precedence over the filter.
	 * Relationships already stored keep loading when the UI is disabled.
	 *
	 * @since 2.1.0
	 *
	 * @return bool
	 */
	public static function is_admin_ui_enabled() {

		if ( defined( 'CONTENT_CONNECT_ADMIN_UI' ) ) {
			return (bool) CONTENT_CONNECT_ADMIN_UI;
		}

		/**
		 * Filters whether the relationships admin UI is enabled.
		 *
		 * @since 2.1.0
		 *
		 * @param bool $enabled Whether the admin UI is enabled. Default true.
		 */
		return (bool) apply_filters( 'tenup_content_connect_enable_admin_ui', true );
	}
}
