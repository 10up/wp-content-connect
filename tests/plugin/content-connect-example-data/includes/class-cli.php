<?php
/**
 * WP-CLI commands for example data management.
 *
 * @package ContentConnectExampleData
 */

namespace ContentConnectExampleData;

use TenUp\ContentConnect\Plugin;
use TenUp\ContentConnect\Registry;

/**
 * CLI commands for generating and managing example data.
 */
class CLI {

	/**
	 * Generates the university example dataset and relationships.
	 *
	 * ## EXAMPLES
	 *
	 *     wp content-connect-example generate
	 *
	 * @when after_wp_load
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 * @return void
	 */
	public function generate( array $args, array $assoc_args ): void {
		\WP_CLI::log( 'Generating example data...' );

		$this->init_plugin();

		$generator = new Data_Generator();
		$ids       = $generator->generate();

		foreach ( $ids['posts'] as $post_type => $post_ids ) {
			\WP_CLI::log( sprintf( 'Created %d %s posts.', count( $post_ids ), $post_type ) );
		}
		\WP_CLI::log( sprintf( 'Created %d users.', count( $ids['users'] ) ) );

		\WP_CLI::success( 'Example data generated successfully!' );
	}

	/**
	 * Deletes all example data.
	 *
	 * ## OPTIONS
	 *
	 * [--yes]
	 * : Skip confirmation prompt.
	 *
	 * ## EXAMPLES
	 *
	 *     wp content-connect-example delete --yes
	 *
	 * @when after_wp_load
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 * @return void
	 */
	public function delete( array $args, array $assoc_args ): void {
		if ( empty( $assoc_args['yes'] ) ) {
			\WP_CLI::confirm( 'Are you sure you want to delete all example data?' );
		}

		\WP_CLI::log( 'Deleting example data...' );

		$generator = new Data_Generator();
		$generator->delete();

		\WP_CLI::success( 'Example data deleted successfully!' );
	}

	/**
	 * Outputs example data IDs as JSON.
	 *
	 * Reads existing data from the database without creating or modifying anything.
	 *
	 * ## EXAMPLES
	 *
	 *     wp content-connect-example get_ids
	 *
	 * @when after_wp_load
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 * @return void
	 */
	public function get_ids( array $args, array $assoc_args ): void {
		$generator = new Data_Generator();
		$ids       = $generator->load_existing();

		$data = array(
			'posts'       => $ids['posts'],
			'users'       => $ids['users'],
			'post_titles' => $this->get_post_titles( $ids['posts'] ),
			'user_names'  => $this->get_user_names( $ids['users'] ),
		);

		\WP_CLI::log( wp_json_encode( $data, JSON_PRETTY_PRINT ) );
	}

	/**
	 * Initializes the Content Connect plugin, registry, and custom tables.
	 *
	 * @return void
	 */
	private function init_plugin(): void {
		$plugin = Plugin::instance();

		if ( empty( $plugin->registry ) ) {
			$plugin->registry = new Registry();
			$plugin->registry->setup();
		}

		if ( ! empty( $plugin->tables ) ) {
			foreach ( $plugin->tables as $table ) {
				$table->upgrade( true );
			}
		}
	}

	/**
	 * Gets post titles organized by type.
	 *
	 * @param array<string, array<int>> $post_ids Post IDs by type.
	 * @return array<string, array<int, string>>
	 */
	private function get_post_titles( array $post_ids ): array {
		$titles = array();

		foreach ( $post_ids as $type => $ids ) {
			$titles[ $type ] = array();
			foreach ( $ids as $id ) {
				$post = get_post( $id );
				if ( $post ) {
					$titles[ $type ][ $id ] = $post->post_title;
				}
			}
		}

		return $titles;
	}

	/**
	 * Gets user display names.
	 *
	 * @param array<int> $user_ids User IDs.
	 * @return array<int, string>
	 */
	private function get_user_names( array $user_ids ): array {
		$names = array();

		foreach ( $user_ids as $id ) {
			$user = get_user_by( 'id', $id );
			if ( $user ) {
				$names[ $id ] = $user->display_name;
			}
		}

		return $names;
	}
}
