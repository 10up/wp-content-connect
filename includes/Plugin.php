<?php

namespace TenUp\ContentConnect;

use TenUp\ContentConnect\API\Search;
use TenUp\ContentConnect\QueryIntegration\UserQueryIntegration;
use TenUp\ContentConnect\QueryIntegration\WPQueryIntegration;
use TenUp\ContentConnect\Relationships\DeletedItems;
use TenUp\ContentConnect\Tables\PostToPost;
use TenUp\ContentConnect\Tables\PostToUser;
use TenUp\ContentConnect\UI\MetaBox;

class Plugin {

	private static $instance;

	/**
	 * URL to the Plugin
	 *
	 * @var string
	 */
	public $url;

	/**
	 * Current plugin version
	 *
	 * @var string
	 */
	public $version;

	/**
	 * @var array
	 */
	public $tables = array();

	/**
	 * @var Registry
	 */
	public $registry;

	/**
	 * @var WPQueryIntegration
	 */
	public $wp_query_integration;

	/**
	 * @var UserQueryIntegration
	 */
	public $user_query_integration;

	/**
	 * @var MetaBox
	 */
	public $meta_box;

	/**
	 * @var Search
	 */
	public $search;

	/**
	 * @var DeletedItems
	 */
	public $deleted_items;

	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
			self::$instance->setup();
		}

		return self::$instance;
	}

	public function __construct() {
		$this->url = plugin_dir_url( dirname( __FILE__ ) );
		$this->version = '1.0.0';
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

		$this->wp_query_integration = new WPQueryIntegration();
		$this->wp_query_integration->setup();

		$this->user_query_integration = new UserQueryIntegration();
		$this->user_query_integration->setup();

		$this->meta_box = new MetaBox();
		$this->meta_box->setup();

		$this->search = new Search();
		$this->search->setup();

		$this->deleted_items = new DeletedItems();
		$this->deleted_items->setup();

		add_action( 'init', array( $this, 'wp_init' ), 100 );
	}

	public function wp_init() {
		do_action( 'tenup-content-connect-init', $this->registry );
	}

	public function register_tables() {
		$this->tables['p2p'] = new PostToPost();
		$this->tables['p2p']->setup();

		$this->tables['p2u'] = new PostToUser();
		$this->tables['p2u']->setup();
	}

}
