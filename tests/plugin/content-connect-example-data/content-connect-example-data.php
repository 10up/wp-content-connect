<?php
/**
 * Plugin Name: Content Connect Example Data
 * Description: Seeds a realistic university dataset (universities, cities, people, courses, campuses) and Content Connect relationships for E2E testing.
 * Version: 1.0.0
 * Author: 10up
 * License: GPL-3.0-or-later
 *
 * @package ContentConnectExampleData
 */

namespace ContentConnectExampleData;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin constants.
define( 'CONTENT_CONNECT_EXAMPLE_DATA_PATH', plugin_dir_path( __FILE__ ) );

/**
 * Autoloader for plugin classes.
 *
 * @param string $class_name Class name to load.
 * @return void
 */
function autoloader( string $class_name ): void {
	$namespace = 'ContentConnectExampleData\\';

	if ( strpos( $class_name, $namespace ) !== 0 ) {
		return;
	}

	$relative_class = substr( $class_name, strlen( $namespace ) );

	// Handle post-types subdirectory.
	if ( strpos( $relative_class, 'PostTypes\\' ) === 0 ) {
		$sub_class = substr( $relative_class, strlen( 'PostTypes\\' ) );
		$file      = CONTENT_CONNECT_EXAMPLE_DATA_PATH . 'includes/post-types/class-' . strtolower( str_replace( '_', '-', $sub_class ) ) . '.php';
		if ( file_exists( $file ) ) {
			require_once $file;
		}
		return;
	}

	// Handle taxonomies subdirectory.
	if ( strpos( $relative_class, 'Taxonomies\\' ) === 0 ) {
		$sub_class = substr( $relative_class, strlen( 'Taxonomies\\' ) );
		$file      = CONTENT_CONNECT_EXAMPLE_DATA_PATH . 'includes/taxonomies/class-' . strtolower( str_replace( '_', '-', $sub_class ) ) . '.php';
		if ( file_exists( $file ) ) {
			require_once $file;
		}
		return;
	}

	$relative_path = strtolower( str_replace( array( '\\', '_' ), array( '/', '-' ), $relative_class ) );
	$file          = CONTENT_CONNECT_EXAMPLE_DATA_PATH . 'includes/class-' . $relative_path . '.php';

	if ( file_exists( $file ) ) {
		require_once $file;
	}
}
spl_autoload_register( __NAMESPACE__ . '\\autoloader' );

/**
 * Registers custom post types and taxonomies on init.
 *
 * @return void
 */
function register_content_types(): void {
	PostTypes\University::register();
	PostTypes\City::register();
	PostTypes\Person::register();
	PostTypes\Course::register();
	PostTypes\Campus::register();
	Taxonomies\Person_Role::register();
}
add_action( 'init', __NAMESPACE__ . '\\register_content_types', 5 );

/**
 * Defines example relationships after Content Connect is loaded.
 *
 * All relationships enable the block-editor UI so the E2E specs can drive them.
 * Relationship keys follow Registry::get_relationship_key() = "{from}_{to}_{name}".
 *
 * @return void
 */
function define_relationships(): void {
	$registry = \TenUp\ContentConnect\Helpers\get_registry();
	if ( ! $registry ) {
		return;
	}

	// Post-to-post relationships: array( from, to, name, label, from_sortable, to_sortable ).
	$post_to_post_relationships = array(
		array( 'university', 'city', 'cities', 'Related Cities', true, true ),
		array( 'university', 'campus', 'campuses', 'Related Campuses', true, false ),
		array( 'university', 'course', 'courses', 'Related Courses', true, false ),
		array( 'campus', 'person', 'people', 'Related People', true, true ),
		array( 'course', 'person', 'instructors', 'Course Instructors', true, true ),
	);

	foreach ( $post_to_post_relationships as $relationship ) {
		list( $from, $to, $name, $label, $from_sortable, $to_sortable ) = $relationship;

		try {
			$registry->define_post_to_post(
				$from,
				$to,
				$name,
				array(
					'from' => array(
						'enable_ui' => true,
						'sortable'  => $from_sortable,
						'labels'    => array( 'name' => $label ),
					),
					'to'   => array(
						'enable_ui' => true,
						'sortable'  => $to_sortable,
						'labels'    => array( 'name' => $label ),
					),
				)
			);
		} catch ( \Exception $e ) {
			// Relationship might already exist, that's okay.
		}
	}

	// Post-to-user relationships: array( post_type, name, label ).
	$post_to_user_relationships = array(
		array( 'university', 'administrators', 'Administrators' ),
		array( 'course', 'instructors', 'Instructors' ),
	);

	foreach ( $post_to_user_relationships as $relationship ) {
		list( $post_type, $name, $label ) = $relationship;

		try {
			$registry->define_post_to_user(
				$post_type,
				$name,
				array(
					'from' => array(
						'enable_ui' => true,
						'sortable'  => true,
						'labels'    => array( 'name' => $label ),
					),
				)
			);
		} catch ( \Exception $e ) {
			// Relationship might already exist, that's okay.
		}
	}
}
add_action( 'tenup-content-connect-init', __NAMESPACE__ . '\\define_relationships' );

/**
 * Loads CLI commands when WP-CLI is available.
 *
 * @return void
 */
function load_cli_commands(): void {
	if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
		return;
	}

	\WP_CLI::add_command( 'content-connect-example', __NAMESPACE__ . '\\CLI' );
}
add_action( 'cli_init', __NAMESPACE__ . '\\load_cli_commands' );
