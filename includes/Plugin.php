<?php

namespace TenUp\P2P;

use TenUp\P2P\Tables\PostToPost;

class Plugin {

	private static $instance;

	public $tables = array();

	/**
	 * @var Registry
	 */
	public $registry;

	/**
	 * @var WP_Query
	 */
	public $wp_query;

	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
			self::$instance->setup();
		}

		return self::$instance;
	}

	public function get_registry() {
		return $this->registry;
	}

	public function get_table( $table ) {
		if ( isset( $this->tables[ $table ] ) ) {
			return $this->tables[ $table ];
		}

		return false;
	}

	public function setup() {
		$this->register_tables();

		$this->registry = new Registry();
		$this->registry->setup();

		$this->wp_query = new WP_Query();
		$this->wp_query->setup();
	}

	public function register_tables() {
		$this->tables['p2p'] = new PostToPost();
		$this->tables['p2p']->setup();
	}

}
