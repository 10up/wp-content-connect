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

	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
			self::$instance->setup();
		}

		return self::$instance;
	}

	public static function get_registry() {
		$plugin = self::instance();
		return $plugin->registry;
	}

	public function setup() {
		$this->register_tables();

		$this->registry = new Registry();
		$this->registry->setup();
	}

	public function register_tables() {
		$this->tables['p2p'] = new PostToPost();
		$this->tables['p2p']->setup();
	}

}
