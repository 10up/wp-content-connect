<?php
/**
 * Generates the university example dataset and its Content Connect relationships.
 *
 * @package ContentConnectExampleData
 */

namespace ContentConnectExampleData;

use function TenUp\ContentConnect\Helpers\get_registry;

/**
 * Creates, loads, and deletes deterministic example data.
 *
 * Titles are fixed ("University 1", "City 1", …) and relationship wiring uses
 * fixed indices so the E2E specs can assert against known content. All create
 * operations are idempotent (existing rows are reused, not duplicated).
 */
class Data_Generator {

	/**
	 * Post counts per custom post type.
	 *
	 * @var array<string, int>
	 */
	const COUNTS = array(
		'university' => 5,
		'city'       => 8,
		'person'     => 20,
		'course'     => 10,
		'campus'     => 6,
		'department' => 3,
	);

	/**
	 * Singular title prefixes per custom post type.
	 *
	 * @var array<string, string>
	 */
	const TITLE_PREFIX = array(
		'university' => 'University',
		'city'       => 'City',
		'person'     => 'Person',
		'course'     => 'Course',
		'campus'     => 'Campus',
		'department' => 'Department',
	);

	/**
	 * Deterministic editor users used for post-to-user relationships.
	 *
	 * @var array<int, array<string, string>>
	 */
	const USERS = array(
		array(
			'first_name' => 'Alex',
			'last_name'  => 'Reed',
			'email'      => 'alex.reed@example.org',
		),
		array(
			'first_name' => 'Blair',
			'last_name'  => 'Stone',
			'email'      => 'blair.stone@example.org',
		),
		array(
			'first_name' => 'Casey',
			'last_name'  => 'Long',
			'email'      => 'casey.long@example.org',
		),
		array(
			'first_name' => 'Dana',
			'last_name'  => 'Frost',
			'email'      => 'dana.frost@example.org',
		),
		array(
			'first_name' => 'Evan',
			'last_name'  => 'Pope',
			'email'      => 'evan.pope@example.org',
		),
		array(
			'first_name' => 'Farah',
			'last_name'  => 'Quinn',
			'email'      => 'farah.quinn@example.org',
		),
	);

	/**
	 * Created/loaded post IDs keyed by post type.
	 *
	 * @var array<string, array<int>>
	 */
	private $post_ids = array(
		'university' => array(),
		'city'       => array(),
		'person'     => array(),
		'course'     => array(),
		'campus'     => array(),
		'department' => array(),
	);

	/**
	 * Created/loaded user IDs.
	 *
	 * @var array<int>
	 */
	private $user_ids = array();

	/**
	 * Generates all example data and relationships.
	 *
	 * @return array{posts: array<string, array<int>>, users: array<int>}
	 */
	public function generate(): array {
		$this->create_users();
		$this->create_posts();
		$this->create_relationships();

		return $this->get_ids();
	}

	/**
	 * Loads existing example data IDs from the database without creating anything.
	 *
	 * @return array{posts: array<string, array<int>>, users: array<int>}
	 */
	public function load_existing(): array {
		$this->load_ids_from_db();

		return $this->get_ids();
	}

	/**
	 * Returns the created/loaded IDs.
	 *
	 * @return array{posts: array<string, array<int>>, users: array<int>}
	 */
	public function get_ids(): array {
		return array(
			'posts' => $this->post_ids,
			'users' => $this->user_ids,
		);
	}

	/**
	 * Deletes all example data and its relationships.
	 *
	 * @return void
	 */
	public function delete(): void {
		global $wpdb;

		$this->load_ids_from_db();

		$all_post_ids = array_merge( ...array_values( $this->post_ids ) );

		if ( ! empty( $all_post_ids ) ) {
			$placeholders = implode( ',', array_fill( 0, count( $all_post_ids ), '%d' ) );

			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$wpdb->query(
				$wpdb->prepare(
					"DELETE FROM {$wpdb->prefix}post_to_post WHERE id1 IN ($placeholders) OR id2 IN ($placeholders)",
					array_merge( $all_post_ids, $all_post_ids )
				)
			);

			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$wpdb->query(
				$wpdb->prepare(
					"DELETE FROM {$wpdb->prefix}post_to_user WHERE post_id IN ($placeholders)",
					$all_post_ids
				)
			);

			foreach ( $all_post_ids as $post_id ) {
				wp_delete_post( $post_id, true );
			}
		}

		foreach ( self::USERS as $user_data ) {
			$user = get_user_by( 'email', $user_data['email'] );
			if ( $user ) {
				wp_delete_user( $user->ID );
			}
		}

		$this->post_ids = array(
			'university' => array(),
			'city'       => array(),
			'person'     => array(),
			'course'     => array(),
			'campus'     => array(),
			'department' => array(),
		);
		$this->user_ids = array();
	}

	/**
	 * Creates the editor users (idempotent by email).
	 *
	 * @return void
	 */
	private function create_users(): void {
		add_filter( 'send_password_change_email', '__return_false' );
		add_filter( 'send_email_change_email', '__return_false' );

		foreach ( self::USERS as $user_data ) {
			$existing_user = get_user_by( 'email', $user_data['email'] );
			if ( $existing_user ) {
				$this->user_ids[] = (int) $existing_user->ID;
				continue;
			}

			$username = strtolower( $user_data['first_name'] . '.' . $user_data['last_name'] );

			$user_id = wp_insert_user(
				array(
					'user_login'   => $username,
					'user_email'   => $user_data['email'],
					'user_pass'    => wp_generate_password(),
					'first_name'   => $user_data['first_name'],
					'last_name'    => $user_data['last_name'],
					'display_name' => $user_data['first_name'] . ' ' . $user_data['last_name'],
					'role'         => 'editor',
				)
			);

			if ( ! is_wp_error( $user_id ) ) {
				$this->user_ids[] = (int) $user_id;
			}
		}

		remove_filter( 'send_password_change_email', '__return_false' );
		remove_filter( 'send_email_change_email', '__return_false' );
	}

	/**
	 * Creates the example posts for every custom post type (idempotent).
	 *
	 * @return void
	 */
	private function create_posts(): void {
		foreach ( self::COUNTS as $post_type => $count ) {
			for ( $i = 1; $i <= $count; $i++ ) {
				$title   = self::TITLE_PREFIX[ $post_type ] . ' ' . $i;
				$post_id = $this->create_or_get_post( $title, $post_type );
				if ( $post_id ) {
					$this->post_ids[ $post_type ][] = $post_id;
				}
			}
		}
	}

	/**
	 * Creates or retrieves a post by title and type.
	 *
	 * @param string $title     Post title.
	 * @param string $post_type Post type.
	 * @return int|null Post ID or null on failure.
	 */
	private function create_or_get_post( string $title, string $post_type ): ?int {
		$existing = new \WP_Query(
			array(
				'post_type'              => $post_type,
				'title'                  => $title,
				'post_status'            => 'publish',
				'posts_per_page'         => 1,
				'no_found_rows'          => true,
				'ignore_sticky_posts'    => true,
				'update_post_term_cache' => false,
				'update_post_meta_cache' => false,
			)
		);

		if ( ! empty( $existing->posts ) ) {
			return (int) $existing->posts[0]->ID;
		}

		$post_id = wp_insert_post(
			array(
				'post_title'   => $title,
				'post_content' => "Example content for {$title}.",
				'post_status'  => 'publish',
				'post_type'    => $post_type,
			)
		);

		return is_wp_error( $post_id ) ? null : (int) $post_id;
	}

	/**
	 * Loads example post and user IDs from the database.
	 *
	 * @return void
	 */
	private function load_ids_from_db(): void {
		foreach ( array_keys( self::COUNTS ) as $post_type ) {
			$this->post_ids[ $post_type ] = get_posts(
				array(
					'post_type'      => $post_type,
					'posts_per_page' => -1,
					'post_status'    => 'publish',
					'orderby'        => 'ID',
					'order'          => 'ASC',
					'fields'         => 'ids',
				)
			);
		}

		$this->user_ids = array();
		foreach ( self::USERS as $user_data ) {
			$user = get_user_by( 'email', $user_data['email'] );
			if ( $user ) {
				$this->user_ids[] = (int) $user->ID;
			}
		}
	}

	/**
	 * Creates all example relationships.
	 *
	 * @return void
	 */
	private function create_relationships(): void {
		$registry = get_registry();
		if ( ! $registry ) {
			return;
		}

		$this->create_post_to_post_relationships( $registry );
		$this->create_post_to_user_relationships( $registry );
	}

	/**
	 * Wires post-to-post relationships with fixed indices.
	 *
	 * @param \TenUp\ContentConnect\Registry $registry Registry instance.
	 * @return void
	 */
	private function create_post_to_post_relationships( $registry ): void {
		$u = $this->post_ids['university'];
		$c = $this->post_ids['city'];
		$p = $this->post_ids['person'];
		$o = $this->post_ids['course'];
		$m = $this->post_ids['campus'];
		$d = $this->post_ids['department'];

		$wiring = array(
			// from,       to,       name,          pairs (from_index => [to_indexes]).
			array( 'university', 'city', 'cities', $u, $c, array( 0 => array( 0, 1, 2 ), 1 => array( 3 ) ) ),
			array( 'university', 'campus', 'campuses', $u, $m, array( 0 => array( 0, 1 ), 1 => array( 2 ) ) ),
			array( 'university', 'course', 'courses', $u, $o, array( 0 => array( 0, 1 ), 2 => array( 2 ) ) ),
			array( 'campus', 'person', 'people', $m, $p, array( 0 => array( 0, 1, 2 ), 1 => array( 3 ) ) ),
			array( 'course', 'person', 'instructors', $o, $p, array( 0 => array( 4, 5 ), 1 => array( 6 ) ) ),
			// Department 1 pre-wired to City 1 (classic-editor "load existing" case);
			// departments 2-3 stay empty for the classic-editor "save new" case.
			array( 'department', 'city', 'cities', $d, $c, array( 0 => array( 0 ) ) ),
		);

		foreach ( $wiring as $entry ) {
			list( $from, $to, $name, $from_ids, $to_ids, $pairs ) = $entry;

			$relationship = $registry->get_post_to_post_relationship( $from, $to, $name );
			if ( ! $relationship ) {
				continue;
			}

			foreach ( $pairs as $from_index => $to_indexes ) {
				if ( ! isset( $from_ids[ $from_index ] ) ) {
					continue;
				}
				foreach ( $to_indexes as $to_index ) {
					if ( isset( $to_ids[ $to_index ] ) ) {
						$relationship->add_relationship( $from_ids[ $from_index ], $to_ids[ $to_index ] );
					}
				}
			}
		}
	}

	/**
	 * Wires post-to-user relationships with fixed indices.
	 *
	 * @param \TenUp\ContentConnect\Registry $registry Registry instance.
	 * @return void
	 */
	private function create_post_to_user_relationships( $registry ): void {
		$u     = $this->post_ids['university'];
		$o     = $this->post_ids['course'];
		$users = $this->user_ids;

		if ( empty( $users ) ) {
			return;
		}

		$wiring = array(
			// post_type,   name,             post_ids, pairs (post_index => [user_indexes]).
			array( 'university', 'administrators', $u, array( 0 => array( 0, 1 ), 1 => array( 2 ) ) ),
			array( 'course', 'instructors', $o, array( 0 => array( 0 ), 1 => array( 1 ) ) ),
		);

		foreach ( $wiring as $entry ) {
			list( $post_type, $name, $post_ids, $pairs ) = $entry;

			$relationship = $registry->get_post_to_user_relationship( $post_type, $name );
			if ( ! $relationship ) {
				continue;
			}

			foreach ( $pairs as $post_index => $user_indexes ) {
				if ( ! isset( $post_ids[ $post_index ] ) ) {
					continue;
				}
				foreach ( $user_indexes as $user_index ) {
					if ( isset( $users[ $user_index ] ) ) {
						$relationship->add_relationship( $post_ids[ $post_index ], $users[ $user_index ] );
					}
				}
			}
		}
	}
}
