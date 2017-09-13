<?php

namespace TenUp\P2P;

use TenUp\P2P\Tables\PostToPost;

class Plugin {

	private static $instance;

	public $tables = array();

	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
			self::$instance->setup();
		}

		return self::$instance;
	}

	public function setup() {
		$this->register_tables();
	}

	public function register_tables() {
		$this->tables['p2p'] = new PostToPost();
		$this->tables['p2p']->setup();
	}

}
