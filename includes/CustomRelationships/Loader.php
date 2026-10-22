<?php

namespace TenUp\ContentConnect\CustomRelationships;

use TenUp\ContentConnect\Registry;

use function TenUp\ContentConnect\Helpers\get_registry;

/**
 * Class Loader
 *
 * Registers the custom relationships created in the admin UI.
 *
 * Published custom relationships are compiled into a single autoloaded option,
 * so loading them on each request costs no query. The option is rebuilt the
 * first time it is read after a custom relationship changes.
 *
 * @package TenUp\ContentConnect\CustomRelationships
 */
class Loader {

	/**
	 * Option holding the compiled published custom relationships.
	 *
	 * @since 2.1.0
	 *
	 * @var string
	 */
	const OPTION = 'content_connect_custom_relationships';

	/**
	 * Registration errors for the current request, keyed by post ID.
	 *
	 * @since 2.1.0
	 *
	 * @var string[]
	 */
	protected $errors = array();

	/**
	 * Post IDs of the registered custom relationships, keyed by relationship key.
	 *
	 * @since 2.1.0
	 *
	 * @var int[]
	 */
	protected $keys = array();

	/**
	 * Setup the loader module.
	 *
	 * @since 2.1.0
	 */
	public function setup() {
		add_action( 'tenup-content-connect-init', array( $this, 'register' ), 100 ); // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores
		add_action( 'wp_after_insert_post', array( $this, 'maybe_invalidate' ), 10, 2 );
		add_action( 'deleted_post', array( $this, 'maybe_invalidate' ), 10, 2 );
		add_action( 'added_post_meta', array( $this, 'maybe_invalidate_for_meta' ), 10, 2 );
		add_action( 'updated_post_meta', array( $this, 'maybe_invalidate_for_meta' ), 10, 2 );
		add_action( 'deleted_post_meta', array( $this, 'maybe_invalidate_for_meta' ), 10, 2 );
	}

	/**
	 * Registers each custom relationship with the registry.
	 *
	 * A custom relationship that fails, such as one whose post type is no longer
	 * registered or whose key is now taken by code, is skipped and its error recorded.
	 *
	 * @since 2.1.0
	 *
	 * @param  Registry $registry The relationship registry.
	 * @return void
	 */
	public function register( $registry ) {

		foreach ( $this->get_all() as $relationship ) {

			$args = array(
				'from' => $relationship['from_args'],
				'to'   => $relationship['to_args'],
			);

			try {
				if ( 'post-to-user' === $relationship['rel_type'] ) {
					$registry->define_post_to_user( $relationship['from'], $relationship['rel_name'], $args );
				} else {
					$registry->define_post_to_post( $relationship['from'], $relationship['to'], $relationship['rel_name'], $args );
				}

				$this->keys[ self::get_key( $relationship ) ] = $relationship['id'];
			} catch ( \Exception $e ) {
				$this->errors[ $relationship['id'] ] = $e->getMessage();
			}
		}
	}

	/**
	 * Clears the compiled option when a custom relationship is saved or deleted.
	 *
	 * @since 2.1.0
	 *
	 * @param  int      $post_id Post ID.
	 * @param  \WP_Post $post    Post object.
	 * @return void
	 */
	public function maybe_invalidate( $post_id, $post ) {

		if ( ! $post instanceof \WP_Post || PostType::POST_TYPE !== $post->post_type ) {
			return;
		}

		delete_option( self::OPTION );
	}

	/**
	 * Clears the compiled option when a custom relationship's meta changes outside a full save.
	 *
	 * @since 2.1.0
	 *
	 * @param  int|int[] $meta_ids  Meta ID or IDs.
	 * @param  int       $object_id Post ID.
	 * @return void
	 */
	public function maybe_invalidate_for_meta( $meta_ids, $object_id ) {
		$this->maybe_invalidate( $object_id, get_post( $object_id ) );
	}

	/**
	 * Returns the published custom relationships, compiling them when the option is missing.
	 *
	 * @since 2.1.0
	 *
	 * @return array[]
	 */
	public function get_all() {

		$relationships = get_option( self::OPTION, false );

		if ( is_array( $relationships ) ) {
			return $relationships;
		}

		return $this->compile();
	}

	/**
	 * Compiles the published custom relationships into the option.
	 *
	 * @since 2.1.0
	 *
	 * @return array[]
	 */
	public function compile() {

		$post_ids = get_posts(
			array(
				'post_type'      => PostType::POST_TYPE,
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'orderby'        => 'ID',
				'order'          => 'ASC',
				'no_found_rows'  => true,
			)
		);

		$relationships = array();

		foreach ( $post_ids as $post_id ) {

			$relationship = self::read_post( $post_id );

			if ( '' === $relationship['rel_name'] || '' === $relationship['from'] ) {
				continue;
			}

			$relationships[] = $relationship;
		}

		update_option( self::OPTION, $relationships, true );

		return $relationships;
	}

	/**
	 * Returns the ID of the post that registered a relationship key.
	 *
	 * @since 2.1.0
	 *
	 * @param  string $key Relationship key.
	 * @return int|null The post ID, or null when the key was not registered from a custom relationship.
	 */
	public function get_post_id( $key ) {
		return isset( $this->keys[ $key ] ) ? $this->keys[ $key ] : null;
	}

	/**
	 * Returns the registration errors for the current request.
	 *
	 * @since 2.1.0
	 *
	 * @return string[] Error messages keyed by post ID.
	 */
	public function get_errors() {
		return $this->errors;
	}

	/**
	 * Reads a normalized custom relationship from its post.
	 *
	 * @since 2.1.0
	 *
	 * @param  int $post_id Custom relationship post ID.
	 * @return array
	 */
	public static function read_post( $post_id ) {

		$rel_type = (string) get_post_meta( $post_id, 'rel_type', true );
		$rel_type = in_array( $rel_type, PostType::REL_TYPES, true ) ? $rel_type : 'post-to-post';
		$to       = get_post_meta( $post_id, 'rel_to', true );

		return array(
			'id'        => (int) $post_id,
			'title'     => get_the_title( $post_id ),
			'rel_type'  => $rel_type,
			'from'      => (string) get_post_meta( $post_id, 'rel_from', true ),
			'to'        => 'post-to-post' === $rel_type ? array_values( array_filter( (array) $to ) ) : array(),
			'rel_name'  => (string) get_post_meta( $post_id, 'rel_name', true ),
			'from_args' => self::normalize_side_args( get_post_meta( $post_id, 'from_args', true ), true ),
			'to_args'   => self::normalize_side_args( get_post_meta( $post_id, 'to_args', true ), false ),
		);
	}

	/**
	 * Returns the relationship key for a normalized custom relationship.
	 *
	 * @since 2.1.0
	 *
	 * @param  array $relationship Relationship with `rel_type`, `from`, `to` and `rel_name`.
	 * @return string
	 */
	public static function get_key( $relationship ) {
		$to = 'post-to-user' === $relationship['rel_type'] ? 'user' : $relationship['to'];

		return get_registry()->get_relationship_key( $relationship['from'], $to, $relationship['rel_name'] );
	}

	/**
	 * Returns the default UI settings for one side of a relationship.
	 *
	 * These mirror the defaults in `Relationship::__construct()`.
	 *
	 * @since 2.1.0
	 *
	 * @param  bool $is_from Whether these are the settings for the "from" side.
	 * @return array
	 */
	public static function get_default_side_args( $is_from ) {
		return array(
			'enable_ui' => $is_from,
			'sortable'  => false,
			'max_items' => 100,
			'labels'    => array(
				'name' => '',
			),
		);
	}

	/**
	 * Normalizes stored UI settings for one side of a relationship.
	 *
	 * An empty panel title is dropped so the relationship falls back to its name.
	 *
	 * @since 2.1.0
	 *
	 * @param  mixed $args    Stored settings.
	 * @param  bool  $is_from Whether these are the settings for the "from" side.
	 * @return array
	 */
	protected static function normalize_side_args( $args, $is_from ) {

		$defaults = self::get_default_side_args( $is_from );
		$args     = is_array( $args ) ? array_replace_recursive( $defaults, $args ) : $defaults;

		$normalized = array(
			'enable_ui' => (bool) $args['enable_ui'],
			'sortable'  => (bool) $args['sortable'],
			'max_items' => max( 1, (int) $args['max_items'] ),
		);

		$label = isset( $args['labels']['name'] ) ? trim( (string) $args['labels']['name'] ) : '';

		if ( '' !== $label ) {
			$normalized['labels'] = array( 'name' => $label );
		}

		return $normalized;
	}
}
