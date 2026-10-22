<?php

namespace TenUp\ContentConnect\CustomRelationships;

use function TenUp\ContentConnect\Helpers\get_plugin;
use function TenUp\ContentConnect\Helpers\get_registry;

/**
 * Class PostType
 *
 * Registers the private post type that stores the custom relationships created
 * from the admin UI, along with its meta, REST fields and validation.
 *
 * @package TenUp\ContentConnect\CustomRelationships
 */
class PostType {

	/**
	 * Post type slug.
	 *
	 * @since 2.1.0
	 *
	 * @var string
	 */
	const POST_TYPE = 'cc_relationship';

	/**
	 * Relationship types a custom relationship can have.
	 *
	 * @since 2.1.0
	 *
	 * @var string[]
	 */
	const REL_TYPES = array( 'post-to-post', 'post-to-user' );

	/**
	 * Meta keys that identify the stored connections and cannot change once saved.
	 *
	 * @since 2.1.0
	 *
	 * @var string[]
	 */
	const IMMUTABLE_META = array( 'rel_type', 'rel_from', 'rel_to', 'rel_name' );

	/**
	 * Setup the post type module.
	 *
	 * @since 2.1.0
	 */
	public function setup() {
		add_action( 'init', array( $this, 'register' ) );
		add_filter( 'rest_pre_insert_' . self::POST_TYPE, array( $this, 'validate_rest_insert' ), 10, 2 );
	}

	/**
	 * Registers the post type, its meta and REST fields when the admin UI is enabled.
	 *
	 * @since 2.1.0
	 *
	 * @return void
	 */
	public function register() {

		if ( ! Gate::is_admin_ui_enabled() ) {
			return;
		}

		register_post_type(
			self::POST_TYPE,
			array(
				'labels'                => array(
					'name'          => __( 'Relationships', 'wp-content-connect' ),
					'singular_name' => __( 'Relationship', 'wp-content-connect' ),
				),
				'public'                => false,
				'show_ui'               => false,
				'show_in_rest'          => true,
				'rest_namespace'        => 'content-connect/v2',
				'rest_base'             => 'custom-relationships',
				'rest_controller_class' => RestController::class,
				'supports'              => array( 'title', 'custom-fields' ),
				'rewrite'               => false,
				'query_var'             => false,
				'can_export'            => true,
				'map_meta_cap'          => false,
				'capability_type'       => self::POST_TYPE,
				'capabilities'          => $this->get_capabilities(),
			)
		);

		$this->register_meta();

		register_rest_field(
			self::POST_TYPE,
			'rel_key',
			array(
				'get_callback' => array( $this, 'get_rel_key_field' ),
				'schema'       => array(
					'description' => __( 'The relationship key.', 'wp-content-connect' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
			)
		);

		register_rest_field(
			self::POST_TYPE,
			'registration_error',
			array(
				'get_callback' => array( $this, 'get_registration_error_field' ),
				'schema'       => array(
					'description' => __( 'Why the relationship could not be registered, if it failed.', 'wp-content-connect' ),
					'type'        => array( 'string', 'null' ),
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
			)
		);
	}

	/**
	 * Returns the relationship key for a custom relationship REST response.
	 *
	 * @since 2.1.0
	 *
	 * @param  array $data Prepared post data.
	 * @return string
	 */
	public function get_rel_key_field( $data ) {
		$relationship = Loader::read_post( (int) $data['id'] );

		return Loader::get_key( $relationship );
	}

	/**
	 * Returns the registration error for a custom relationship REST response.
	 *
	 * @since 2.1.0
	 *
	 * @param  array $data Prepared post data.
	 * @return string|null
	 */
	public function get_registration_error_field( $data ) {

		$post_id = (int) $data['id'];
		$errors  = get_plugin()->get_custom_relationships()->get_errors();

		if ( isset( $errors[ $post_id ] ) ) {
			return $errors[ $post_id ];
		}

		$relationship = Loader::read_post( $post_id );
		$post_types   = array_merge( array( $relationship['from'] ), $relationship['to'] );

		foreach ( array_filter( $post_types ) as $post_type ) {
			if ( ! post_type_exists( $post_type ) ) {
				/* translators: %s: post type name */
				return sprintf( __( 'Post type "%s" is not registered.', 'wp-content-connect' ), $post_type );
			}
		}

		return null;
	}

	/**
	 * Validates a custom relationship before the REST API inserts or updates it.
	 *
	 * @since 2.1.0
	 *
	 * @param  \stdClass        $prepared_post The prepared post object.
	 * @param  \WP_REST_Request $request       Full details about the request.
	 * @return \stdClass|\WP_Error The prepared post, or WP_Error when invalid.
	 */
	public function validate_rest_insert( $prepared_post, $request ) {

		$post_id = empty( $prepared_post->ID ) ? 0 : (int) $prepared_post->ID;
		$meta    = is_array( $request['meta'] ) ? $request['meta'] : array();
		$stored  = $post_id ? Loader::read_post( $post_id ) : null;

		if ( $stored && '' !== $stored['rel_name'] ) {
			foreach ( self::IMMUTABLE_META as $meta_key ) {

				if ( ! array_key_exists( $meta_key, $meta ) ) {
					continue;
				}

				$stored_value = $stored[ $this->get_normalized_field( $meta_key ) ];
				$new_value    = $meta[ $meta_key ];

				if ( is_array( $stored_value ) ) {
					$new_value = (array) $new_value;
					sort( $stored_value );
					sort( $new_value );
				}

				if ( $stored_value !== $new_value ) {
					return $this->error(
						'rest_relationship_immutable',
						/* translators: %s: field name */
						sprintf( __( '"%s" cannot change after the relationship is saved, because stored connections depend on it.', 'wp-content-connect' ), $meta_key )
					);
				}
			}

			return $prepared_post;
		}

		$relationship = array(
			'rel_type' => isset( $meta['rel_type'] ) ? $meta['rel_type'] : '',
			'from'     => isset( $meta['rel_from'] ) ? $meta['rel_from'] : '',
			'to'       => isset( $meta['rel_to'] ) ? array_values( (array) $meta['rel_to'] ) : array(),
			'rel_name' => isset( $meta['rel_name'] ) ? $meta['rel_name'] : '',
		);

		$error = $this->validate_relationship( $relationship, $post_id );

		if ( is_wp_error( $error ) ) {
			return $error;
		}

		return $prepared_post;
	}

	/**
	 * Registers the custom relationship meta fields.
	 *
	 * @since 2.1.0
	 *
	 * @return void
	 */
	protected function register_meta() {

		$auth_callback = function () {
			return current_user_can( 'manage_options' );
		};

		register_post_meta(
			self::POST_TYPE,
			'rel_type',
			array(
				'type'          => 'string',
				'single'        => true,
				'default'       => 'post-to-post',
				'auth_callback' => $auth_callback,
				'show_in_rest'  => array(
					'schema' => array(
						'type' => 'string',
						'enum' => self::REL_TYPES,
					),
				),
			)
		);

		register_post_meta(
			self::POST_TYPE,
			'rel_from',
			array(
				'type'              => 'string',
				'single'            => true,
				'default'           => '',
				'sanitize_callback' => 'sanitize_key',
				'auth_callback'     => $auth_callback,
				'show_in_rest'      => true,
			)
		);

		register_post_meta(
			self::POST_TYPE,
			'rel_to',
			array(
				'type'          => 'array',
				'single'        => true,
				'default'       => array(),
				'auth_callback' => $auth_callback,
				'show_in_rest'  => array(
					'schema' => array(
						'type'  => 'array',
						'items' => array(
							'type' => 'string',
						),
					),
				),
			)
		);

		register_post_meta(
			self::POST_TYPE,
			'rel_name',
			array(
				'type'              => 'string',
				'single'            => true,
				'default'           => '',
				'sanitize_callback' => 'sanitize_key',
				'auth_callback'     => $auth_callback,
				'show_in_rest'      => array(
					'schema' => array(
						'type'      => 'string',
						'maxLength' => 64,
					),
				),
			)
		);

		foreach ( array( 'from_args', 'to_args' ) as $meta_key ) {
			register_post_meta(
				self::POST_TYPE,
				$meta_key,
				array(
					'type'          => 'object',
					'single'        => true,
					'default'       => Loader::get_default_side_args( 'from_args' === $meta_key ),
					'auth_callback' => $auth_callback,
					'show_in_rest'  => array(
						'schema' => $this->get_side_args_schema(),
					),
				)
			);
		}
	}

	/**
	 * Returns the REST schema for the per-side UI settings.
	 *
	 * @since 2.1.0
	 *
	 * @return array
	 */
	protected function get_side_args_schema() {
		return array(
			'type'                 => 'object',
			'additionalProperties' => false,
			'properties'           => array(
				'enable_ui' => array(
					'type' => 'boolean',
				),
				'sortable'  => array(
					'type' => 'boolean',
				),
				'max_items' => array(
					'type'    => 'integer',
					'minimum' => 1,
				),
				'labels'    => array(
					'type'                 => 'object',
					'additionalProperties' => false,
					'properties'           => array(
						'name' => array(
							'type' => 'string',
						),
					),
				),
			),
		);
	}

	/**
	 * Returns the capability map, which restricts every action to site administrators.
	 *
	 * @since 2.1.0
	 *
	 * @return array
	 */
	protected function get_capabilities() {

		$capabilities = array(
			'edit_post',
			'read_post',
			'delete_post',
			'edit_posts',
			'edit_others_posts',
			'delete_posts',
			'publish_posts',
			'read_private_posts',
			'delete_private_posts',
			'delete_published_posts',
			'delete_others_posts',
			'edit_private_posts',
			'edit_published_posts',
			'create_posts',
		);

		return array_fill_keys( $capabilities, 'manage_options' );
	}

	/**
	 * Validates the fields that identify a new custom relationship.
	 *
	 * @since 2.1.0
	 *
	 * @param  array $relationship Relationship with `rel_type`, `from`, `to` and `rel_name`.
	 * @param  int   $post_id      ID of the custom relationship being saved, 0 when new.
	 * @return true|\WP_Error
	 */
	protected function validate_relationship( $relationship, $post_id ) {

		if ( ! in_array( $relationship['rel_type'], self::REL_TYPES, true ) ) {
			return $this->error( 'rest_relationship_invalid_type', __( 'Choose a relationship type.', 'wp-content-connect' ) );
		}

		$rel_name = sanitize_key( $relationship['rel_name'] );

		if ( '' === $rel_name || $rel_name !== $relationship['rel_name'] ) {
			return $this->error( 'rest_relationship_invalid_name', __( 'The name may only contain lowercase letters, numbers, dashes and underscores.', 'wp-content-connect' ) );
		}

		$post_types = array( $relationship['from'] );

		if ( 'post-to-post' === $relationship['rel_type'] ) {

			if ( empty( $relationship['to'] ) ) {
				return $this->error( 'rest_relationship_invalid_to', __( 'Choose at least one related post type.', 'wp-content-connect' ) );
			}

			$post_types = array_merge( $post_types, $relationship['to'] );
		}

		foreach ( $post_types as $post_type ) {
			if ( self::POST_TYPE === $post_type || ! post_type_exists( $post_type ) ) {
				return $this->error(
					'rest_relationship_invalid_post_type',
					/* translators: %s: post type name */
					sprintf( __( 'Post type "%s" is not registered.', 'wp-content-connect' ), $post_type )
				);
			}
		}

		if ( $this->key_exists( $relationship, $post_id ) ) {
			return $this->error( 'rest_relationship_exists', __( 'A relationship between these types with this name already exists.', 'wp-content-connect' ) );
		}

		return true;
	}

	/**
	 * Checks whether another relationship already uses the custom relationship's key.
	 *
	 * Relationships registered from code and other custom relationships, including
	 * disabled ones, are both checked. A post-to-post relationship with a single
	 * related post type also collides with its reverse.
	 *
	 * @since 2.1.0
	 *
	 * @param  array $relationship Relationship with `rel_type`, `from`, `to` and `rel_name`.
	 * @param  int   $post_id      ID of the custom relationship being saved, 0 when new.
	 * @return bool
	 */
	protected function key_exists( $relationship, $post_id ) {

		$registry = get_registry();
		$loader   = get_plugin()->get_custom_relationships();
		$keys     = $this->get_candidate_keys( $relationship );

		foreach ( $keys as $key ) {

			$in_registry = 'post-to-post' === $relationship['rel_type']
				? $registry->get_post_to_post_relationship_by_key( $key )
				: $registry->get_post_to_user_relationship_by_key( $key );

			if ( $in_registry && $loader->get_post_id( $key ) !== $post_id ) {
				return true;
			}
		}

		$post_ids = get_posts(
			array(
				'post_type'      => self::POST_TYPE,
				'post_status'    => array( 'publish', 'draft' ),
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'post__not_in'   => array( $post_id ),
				'no_found_rows'  => true,
			)
		);

		foreach ( $post_ids as $other_id ) {

			$other = Loader::read_post( $other_id );

			if ( $other['rel_type'] !== $relationship['rel_type'] ) {
				continue;
			}

			if ( in_array( Loader::get_key( $other ), $keys, true ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Returns the keys a custom relationship would collide with.
	 *
	 * @since 2.1.0
	 *
	 * @param  array $relationship Relationship with `rel_type`, `from`, `to` and `rel_name`.
	 * @return string[]
	 */
	protected function get_candidate_keys( $relationship ) {

		$keys = array( Loader::get_key( $relationship ) );

		if ( 'post-to-post' === $relationship['rel_type'] && 1 === count( $relationship['to'] ) ) {
			$keys[] = get_registry()->get_relationship_key( $relationship['to'][0], $relationship['from'], $relationship['rel_name'] );
		}

		return $keys;
	}

	/**
	 * Maps a meta key to its field in a normalized custom relationship.
	 *
	 * @since 2.1.0
	 *
	 * @param  string $meta_key Meta key.
	 * @return string
	 */
	protected function get_normalized_field( $meta_key ) {

		switch ( $meta_key ) {
			case 'rel_from':
				return 'from';
			case 'rel_to':
				return 'to';
			default:
				return $meta_key;
		}
	}

	/**
	 * Builds a 400 REST error.
	 *
	 * @since 2.1.0
	 *
	 * @param  string $code    Error code.
	 * @param  string $message Error message.
	 * @return \WP_Error
	 */
	protected function error( $code, $message ) {
		return new \WP_Error( $code, $message, array( 'status' => 400 ) );
	}
}
